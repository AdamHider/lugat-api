<?php

namespace App\Libraries\Cerebrum;

use App\Libraries\Cerebrum\Neuron;
use App\Libraries\Cerebrum\Cortex\Visio;
class Thalamus{

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
    public function remember($data)
    {
        $Neuron = new Neuron;
        $CortexVisio = new Visio;
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
    public function analyze($data)
    {
        $CortexVisio = new Visio;
        $result = [
            'tokens' => [],
            'matches' => [],
            'languages' => [
                'source' => $data['source']['language_id'],
                'target' => $data['target']['language_id']
            ]
        ];
        $result['tokens'][$data['source']['language_id']] = $CortexVisio->tokenize($data['source']['text']);
        $result['tokens'][$data['target']['language_id']] = $CortexVisio->tokenize($data['target']['text']);
        $result['matches'] = $this->getMatches($result);
        return $result;
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


