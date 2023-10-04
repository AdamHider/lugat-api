<?php

namespace App\Libraries\Cerebrum;

use App\Libraries\Cerebrum\Neuron;
use App\Libraries\Cerebrum\Cerebellum;
use App\Libraries\Cerebrum\Cortex\Visio;
ini_set('max_execution_time', 0); 
class Thalamus{

    public function predict($data)
    {
        $CortexVisio = new Visio;
        $Neuron = new Neuron;
        $tokenList = $CortexVisio->tokenize($data['source']['text']);
        $predictions = [];
        foreach($tokenList as $index => $token){
            $token['language_id'] = $data['source']['language_id'];
            $context = $CortexVisio->getSurroundingTokens($index, $tokenList);
            $neurons = $Neuron->find($token, $data['target']['language_id'], $context, 1);
            $predictions = array_merge($predictions, $neurons);
        }
        $result = ["text" => $CortexVisio->normalizeOutput($predictions)];
        return $result;
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

        list($sourceTokenList, $targetTokenList) = $this->prepareDict($sentencePair); 
        
        foreach($sourceTokenList as &$sourceToken){
            foreach($targetTokenList as &$targetToken){
                $axonId = $Neuron->getAxonId($sourceToken, $targetToken);
                if(empty($axonId)){
                    $axonId = $Neuron->getLastAxonId();
                } else {
                    $Neuron->decreaseAxonFrequency($sourceToken['id'], $axonId);  
                    $Neuron->decreaseAxonFrequency($targetToken['id'], $axonId);  
                }
                $sourceToken['axon_id'] = $axonId;
                $targetToken['axon_id'] = $axonId;
                $Neuron->save($sourceToken);
                $Neuron->save($targetToken);
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


