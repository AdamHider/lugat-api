<?php
namespace App\Libraries\Cerebrum;

use App\Libraries\Cerebrum\Neuron;
use App\Libraries\Cerebrum\Cerebellum;
use App\Libraries\Cerebrum\Cortex\Visio;

CONST SKIP_START_END_POINTERS = 1;

class Thalamus{
    
    public function getTranslations($data)
    {
        $CortexVisio = new Visio;
        $Neuron = new Neuron;
        $translations = $Neuron->getTokens($data['token'], $data['source_language_id'], $data['target_language_id']);
        return $translations;
    }
    public function getSentences($data)
    {
        $CortexVisio = new Visio;
        $Neuron = new Neuron;
        $translations = $Neuron->getTokens($data['token'], $data['source_language_id'], $data['target_language_id']);
        return $translations;
    }
    
    public function predict($data)
    {
        $CortexVisio = new Visio;
        $Neuron = new Neuron;
        $tokenList = $CortexVisio->tokenize($data['source']['text']);
        $predictions = [];
        foreach($tokenList as $index => $token){
            $position = $CortexVisio->calculatePosition(count($tokenList), $index);
            $neuronPrototype = $Neuron->createEmpty(null, $token, $position, $data['source']['language_id']);
            $context = $CortexVisio->getSurroundingTokens($index, $tokenList);
            $neurons = $Neuron->find($neuronPrototype, $data['target']['language_id'], $context, 1);
            $predictions = array_merge($predictions, $neurons);
        }
        $result = ["text" => $CortexVisio->normalizeOutput($predictions)];

        return $result;
    }

    public function markupSentences($token, $sentences, $source_language, $target_language)
    {
        $CortexVisio = new Visio;
        $Neuron = new Neuron;
        $result = [];
        
        return $this->predict([
            'source' => [
                'text' => $token,
                'language_id' => $source_language
            ],
            'target' => [
                'text' => '',
                'language_id' => $target_language
            ]
        ]);


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
    public function train($sentencePair)
    {
        $Neuron = new Neuron;
        $CortexVisio = new Visio;

        list($sourceTokenList, $targetTokenList) = $this->prepareDict($sentencePair); 
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
    
    

    public function remember($data)
    {
        $Neuron = new Neuron;
        $Cerebellum = new Cerebellum;
        $CortexVisio = new Visio;
        if($data['source_id'] != 0 && $data['target_id'] != 0){
            //$Cerebellum->rememberCahceCreate('visio', implode(',', [$data['source_id'], $data['target_id']]), $data);
        }
        foreach($data['matches'] as $neuronGroup){
            $sourceTokenList = $CortexVisio->exctractTokens($neuronGroup[$data['languages']['source']]);
            $targetTokenList = $CortexVisio->exctractTokens($neuronGroup[$data['languages']['target']]);
            $axonId = $Neuron->getAxonId($sourceTokenList, $targetTokenList, $data['languages']['source'], $data['languages']['target']);
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
                        $neuron = $Neuron->createEmpty($axonId, $neuron['id'], $position);
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
    
    
}


