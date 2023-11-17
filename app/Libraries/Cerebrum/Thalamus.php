<?php
namespace App\Libraries\Cerebrum;

use App\Libraries\Cerebrum\Neuron;
use App\Libraries\Cerebrum\Cerebellum;
use App\Libraries\Cerebrum\Cortex\Visio;

CONST SKIP_START_END_POINTERS = 1;

class Thalamus{

    public function predict($data)
    {
        $CortexVisio = new Visio;
        $Neuron = new Neuron;
        $tokenList = $CortexVisio->tokenize($data['source']['text']);
        $predictions = [];
        $tokensFound = [];
        foreach($tokenList as $index => $token){
            $neurons = [];
            $token['language_id'] = $data['source']['language_id'];
            $context = $CortexVisio->getSurroundingTokens($index, $tokenList);
            //$neurons = $Neuron->find($token, $data['target']['language_id'], $context, 1);
            $object = $Neuron->findToken($token, $data['target']['language_id']);
            $object['source'] = $token['token'];
            $tokensFound[] = $object;
            //$predictions = array_merge($predictions, $neurons);
        }
        
        print_r( $tokensFound);
        die;

        $result = [
            "text" => implode(' ', array_map(fn($item) => $item['token'], $cleanResult))
        ];
       

        //$result = ["text" => $CortexVisio->normalizeOutput($predictions)];
        return $result;
    }
    public function markupSentences($token, $sentences, $source_language, $target_language)
    {
        $CortexVisio = new Visio;
        $Neuron = new Neuron;
        $result = [];

        foreach($sentences as &$sentence) {
            $tokenizedSource = $CortexVisio->tokenize($sentence['source_sentence']);
            $tokenizedTarget = $CortexVisio->tokenize($sentence['target_sentence']);
            if(empty(array_column($tokenizedSource, null, 'token')[$token])) continue;
            $tokenObject =  array_column($tokenizedSource, null, 'token')[$token];
            $most_probable = $Neuron->getMostProbable($tokenObject, $tokenizedTarget, $source_language, $target_language);
            $sentence['source_result'] = preg_replace("/$token/i", '<b>$0</b>', $sentence['source_sentence']);
            $sentence['target_result'] = '';
            if(!empty($most_probable)){
                $sentence['target_result'] = preg_replace("/".$most_probable['token']."/i", '<b>$0</b>', $sentence['target_sentence']);
            }
            
        }
        return ['sentences' => $sentences];
    }

    
    public function remember($data)
    {
        $Neuron = new Neuron;
        $Cerebellum = new Cerebellum;
        $CortexVisio = new Visio;
        if($data['source_id'] != 0 && $data['target_id'] != 0){
            $Cerebellum->rememberCahceCreate('visio', implode(',', [$data['source_id'], $data['target_id']]), $data);
        }
        foreach($data['matches'] as $neuronGroup){
            $sourceTokenList = $CortexVisio->exctractTokens($neuronGroup[$data['languages']['source']]);
            $targetTokenList = $CortexVisio->exctractTokens($neuronGroup[$data['languages']['target']]);
            $axonId = $Neuron->getAxonId($sourceTokenList, $targetTokenList);
            if(empty($axonId)){
                $axonId = $Neuron->getLastAxonId();
            }
            foreach($neuronGroup as $languageId => $neuronList){
                foreach($neuronList['neurons'] as $index => $neuron){
                    if($neuronList['isFixedPosition']){
                        $position = $CortexVisio->calculatePosition(count($data['tokens'][$languageId]), $neuron['index'], $neuronList['neurons']);
                    } else {
                        $position = $CortexVisio->calculatePosition(count($data['tokens'][$languageId]), $neuron['index']);
                    }
                    if(empty($neuron['axon_id'])){
                        $neuron = $Neuron->createEmpty($axonId, $neuron['core'], $position, $languageId);
                    }
                    $neuron['position'] = $Neuron->recalculatePosition($position, $neuron['position'], $neuron['frequency']);
                    $neuron['frequency']++;
                    if(!$Neuron->save($neuron)){
                        return false;
                    };
                }
            }
        }
        return true;
    }
    public function train($sentencePair)
    {
        $Neuron = new Neuron;
        $CortexVisio = new Visio;

        list($sourceTokenList, $targetTokenList) = $this->prepareDict($sentencePair); 
        /*
        $sourceCombinations = $CortexVisio->getSentenceTokenCombinations($sourceTokenList);
        $targetCombinations = $CortexVisio->getSentenceTokenCombinations($targetTokenList);
        */
        $mergedCombinations = $CortexVisio->multisortCombinations1($sourceTokenList, $targetTokenList);
        
        foreach($mergedCombinations as &$combinationObject){
            $is_new = false;
            $ids = array_column($combinationObject, 'id');
            $axonId = $Neuron->getGroupAxonId($ids);
            if(empty($axonId)){
                $is_new = true;
                $axonId = $Neuron->getLastAxonId();
            } else {
                //if($is_new) $Neuron->decreaseAxonFrequency($ids, $token['axon_id']); 
                /*
                $Neuron->getGroupAxonIdTest($combinationObject);
                print_r($combinationObject);
                die;*/
            }
            $Neuron->decreaseAxonFrequency($ids, $axonId); 

            foreach($combinationObject as &$token){
                $token['axon_id'] = $axonId;
                $Neuron->save($token);
                //if($is_new) $Neuron->decreaseAxonFrequency([$token['id']], $token['axon_id']); 
            }
        }
        return true;
    }
    private function prepareDict($sentencePair)
    {
        $Neuron = new Neuron;
        $CortexVisio = new Visio;

        $sourceTokenList = $CortexVisio->tokenize($sentencePair['source']['text']);
        $targetTokenList = $CortexVisio->tokenize($sentencePair['target']['text']);

        foreach($sourceTokenList as &$sourceToken){
            $tokenId = $Neuron->getDictItemId($sourceToken['token'], $sentencePair['source']['language_id']); 
            if(empty($tokenId)){
                $tokenId = $Neuron->createDictItem($sourceToken['token'], $sentencePair['source']['language_id']); 
            }
            $sourceToken['id'] = $tokenId;
            $sourceToken['language_id'] = $sentencePair['source']['language_id'];
        }
        foreach($targetTokenList as &$targetToken){
            $tokenId = $Neuron->getDictItemId($targetToken['token'], $sentencePair['target']['language_id']); 
            if(empty($tokenId)){
                $tokenId = $Neuron->createDictItem($targetToken['token'], $sentencePair['target']['language_id']); 
            }
            $targetToken['id'] = $tokenId;
            $targetToken['language_id'] = $sentencePair['target']['language_id'];
        }
        return [$sourceTokenList, $targetTokenList];
    }

    public function analyze($data)
    {
        return $this->train($data);
    }
    public function feed()
    {
        $Neuron = new Neuron;
        $Neuron->forgetAll();

        $source = 'fra1.txt';
        $fp = fopen(base_url().$source, 'r');

        while ( !feof($fp) )
        {
            $line = fgets($fp, 2048);

            $data_txt = str_getcsv($line, "\t");
            if(!isset($data_txt[0]) || !isset($data_txt[1])){
                continue;
            }
            $data = [
                'source' => [
                    'text' => $data_txt[0],
                    'language_id' => 4
                ],
                'target' => [
                    'text' => $data_txt[1],
                    'language_id' => 5
                ]
            ];
            //Get First Line of Data over here
            $this->train($data);
            $Neuron->createSentencePair($data_txt[0], $data_txt[1], 4, 5);
        }                              

        fclose($fp);
    }
    public function getMatches($data)
    {
        $Neuron = new Neuron;
        $result = [];
        $axonList = $Neuron->getAxonList($data['tokens'][$data['languages']['source']], $data['tokens'][$data['languages']['target']]);
        foreach($axonList as $axon){
            $neuronGroup = [];
            $group = $Neuron->getListByAxon($axon['axon_id']);
            if(!empty($group)){
                foreach($group as $neuron){
                    $neuron['index'] = array_search($neuron['core'], $data['tokens'][$neuron['language_id']]);
                    $neuronGroup[$neuron['language_id']]['isFixedPosition'] = false;
                    $neuronGroup[$neuron['language_id']]['neurons'][] = $neuron;
                }
            }
            $result[] = $neuronGroup;
        }
        return $result;
    }
    
}


