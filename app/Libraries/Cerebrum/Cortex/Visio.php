<?php

namespace App\Libraries\Cerebrum\Cortex;

class Visio{
    protected $skip = ['«', '»', '!', '?', '.', ',', ';', ':', '…', '“'];
   
    public function normalizeOutput($predictionList)
    {
        $result = [];
        usort($predictionList, function($a, $b){ return $a['position'] <=> $b['position']; });
        $predictionNormalized = $this->groupBy($predictionList, 'core');
        foreach($predictionNormalized as $token){
            $result[] = $token['core'];
        }
        return implode(' ', $result);
    }
    public function calculatePosition($tokenTotal, $index, $context = [])
    {
        $modifier = $index / 100;
        $indexCount = 0;
        if(!empty($context)){
            foreach($context as $item){
                $indexCount += $item['index'];
            }
            $index = $indexCount / count($context) + $modifier;
        }
        return round(($index + 1) / $tokenTotal, 4);
    }
    public function groupBy($arr, $key)
    {   
        $result = [];
        $keys = [];
        foreach($arr as $a){
            if(in_array($a[$key], $keys)){
                continue;
            }
            $result[] = $a;
            $keys[] = $a[$key];
        }
        return $result;
    }
    public function utilizeToken($str)
    {
        $str = str_replace('ё', 'е', $str);
        return $str;
    }
    public function exctractTokens($neuronList)
    {
        $result = [];
        
        foreach($neuronList['neurons'] as $neuron){
            $result[] = $neuron['core'];
        }
        return $result;
    }
    public function tokenize($sentence)
    {
        $sentence = mb_strtolower($sentence);
        $sentence = str_replace(array("\n", "\r"), '', $sentence);
        foreach($this->skip as $item){
            if((int) $item || strpos($sentence, $item) > -1){
                $sentence = str_replace($item, '', $sentence);
                continue;
            }
        }
        $sentence = str_replace('  ',  ' ',$sentence);
        $sentence = str_replace('ё', 'е', $sentence);
        return explode(' ', $sentence);
    }
    public function getSurroundingTokens($index, $tokenList)
    {
        $result = [];
        if(isset($tokenList[$index-1])) $result['previousToken'] = $tokenList[$index-1];
        if(isset($tokenList[$index+1])) $result['nextToken'] = $tokenList[$index+1];
        return $result;
    }
    
}


