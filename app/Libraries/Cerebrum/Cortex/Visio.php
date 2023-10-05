<?php

namespace App\Libraries\Cerebrum\Cortex;

class Visio{
    protected $skip = ['«', '»', '!', '?', '.', ',', ';', ':', '…', '“', '—'];
   
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
        $result = [];
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
        $tokenList = explode(' ', trim($sentence));
        return $this->assetPossitions($tokenList);
    }
    public function assetPossitions($tokenList)
    {
        $result = [];
        foreach($tokenList as $index => $token){
            $result[] = [
                'token' => $token,
                'position' => $this->calculatePosition(count($tokenList), $index)
            ];
        }
        return $result;
    }
    public function getSurroundingTokens($index, $tokenList)
    {
        $result = [];
        foreach($tokenList as $tokenIndex => $token){
            if($tokenIndex < $index) $result['previousTokens'][] = $token;
            if($tokenIndex > $index) $result['nextTokens'][] = $token;
        }
        
        return $result;
    }
    
    public function getSentenceTokenCombinations($tokenList)
    {
        $results = array(array( ));

        foreach ($tokenList as $index => $values)
            foreach ($results as $combination)
                    array_push($results, array_merge($combination, array($values))); 
        array_shift($results);
        return $results;
    }
    public function multisortCombinations($arrays, $i = 0)
    {
        if ( !isset($arrays[$i]) ) return array();
        if ( $i == count($arrays) - 1) return $arrays[$i];
        $tmp = $this->multisortCombinations($arrays, $i + 1);
        $result = array();
        foreach ($arrays[$i] as $v) 
            foreach ($tmp as $t) 
                $result[] = is_array($t) ? 
                    array_merge($v, $t) :
                    array($v, $t);
            
        
        return $result;
    }

}


