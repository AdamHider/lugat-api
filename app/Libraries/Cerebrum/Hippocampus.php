<?php

namespace App\Libraries\Cerebrum;

use App\Libraries\Cerebrum\Neuron;
use App\Libraries\Cerebrum\Hypophysis;
class Hippocampus{
    protected $skip = ['«', '»', '!', '?', '.', ',', ';', ':', '…', '“'];

    public function predict($data)
    {
        $Hypophysis = new Hypophysis;
        $Neuron = new Neuron;
        $tokenList = $this->tokenize($data['source']['text']);
        $predictionList = [];
        foreach($tokenList as $index => $token){
            $tokenObject = [
                'text' => $token['value'],
                'position' => $token['position'],
                'language_id' => $data['source']['language_id']
            ];
            if(isset($tokenList[$index-1])){
                $tokenObject['previousToken'] = $tokenList[$index-1]['value'];
            }
            if(isset($tokenList[$index+1])){
                $tokenObject['nextToken'] = $tokenList[$index+1]['value'];
            }
            $predictionRaw = $Neuron->getList($tokenObject, $data['target']['language_id']);
            $predictions = [];
            if(!empty($predictionRaw)){
                $axon_id = $predictionRaw[0]['axon_id'];
                foreach($predictionRaw as $prediction){
                    if($axon_id != $prediction['axon_id']){
                        break;
                    }
                    $predictions[] = $prediction;
                }
            }
            $predictionList = array_merge($predictionList, $predictions);
        }
        $result = ["text" => $Hypophysis->predictionNormalize($predictionList)];

        return $result;
    }
    public function tokenize($text)
    {
        $Hypophysis = new Hypophysis;
        $result = [];
        $tokenList = $Hypophysis->tokenizeSentence($text);
        
        foreach($tokenList as $index => $token){
            $tokenPosition = $Hypophysis->calculateTokenPosition(count($tokenList), $index);
            $result[] = [
                'value' => $token,
                'position' => $tokenPosition
            ];
        }
        return $result;
    }
    public function remember($data)
    {
        $Neuron = new Neuron;
        $Hypophysis = new Hypophysis;
        $tokenPairs = $Hypophysis->unmap($data);
        foreach($tokenPairs as $tokenPair){
            $neuronPair = $Neuron->getPair($tokenPair[0]['token'], $tokenPair[1]['token']);
            foreach($neuronPair as $index => &$neuron){
                $neuron['position'] = $Neuron->calculatePosition($tokenPair[$index]['position'], $neuron['position'], $neuron['frequency']);
                $neuron['frequency']++;
                if(!$Neuron->save($neuron)){
                    return false;
                };
            }
        }
        return true;
    }

    public function analyze($data)
    {
        $Hypophysis = new Hypophysis;
        $result = [
            'tokens' => [
                'source' => $Hypophysis->tokenizeSentence($data['source']['text']),
                'target' => $Hypophysis->tokenizeSentence($data['target']['text'])
            ],
            'languageMap' => [
                'source' => $data['source']['language_id'],
                'target' => $data['target']['language_id']
            ],
            'map' => []
        ];
        foreach($result['tokens']['source'] as $index => &$sourceToken){
            $token = strtolower($sourceToken);
            $result['map'][$index] = $this->getMatchesFromExperience($index, $result);
        }
        return $result;
    }
    public function getMatchesFromExperience($index, $data)
    {
        $Neuron = new Neuron;
        $Hypophysis = new Hypophysis;
        
        $tokenObject = [
            'text' => $data['tokens']['source'][$index],
            'position' => $Hypophysis->calculateTokenPosition(count($data['tokens']['source']), $index),
            'language_id' => $data['languageMap']['source']
        ];
        if(isset($data['tokens']['source'][$index-1])){
            $tokenObject['previousToken'] = $data['tokens']['source'][$index-1];
        }
        if(isset($data['tokens']['source'][$index+1])){
            $tokenObject['nextToken'] = $data['tokens']['source'][$index+1];
        }
        $neurons = $Neuron->getList($tokenObject, $data['languageMap']['target']);
        $result = [];
        foreach($data['tokens']['target'] as $index => $targetToken){
            foreach($neurons as $neuron){
                $neuron['core'] = $Hypophysis->utilizeToken($neuron['core']);
                if($neuron['core'] === $targetToken){
                    $result[] = $index;
                }
            }
        }
        return $result;
    }
    /*
    public function findMatches($sourceToken, $targetTokenList)
    {
        $Neuron = new Neuron;
        $translations = $Neuron->getTokenTranslations($sourceToken);
        $result = [];
        foreach($targetTokenList as $index => $targetToken){
            $targetToken = strtolower($targetToken);
            foreach($translations as $translation){
                $translation['target_wordform'] = $this->utilizeToken($translation['target_wordform']);
                if($translation['target_wordform'] === $targetToken){
                    $translation['positionRaw'] = $index;
                    $tokenPosition = $this->calculateTokenPosition($targetTokenList, $index);
                    $translation['position'] = $tokenPosition;
                    $result[] = $translation;
                }
            }
        }
        return $result;
    }
    */
    
}