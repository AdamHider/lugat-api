<?php

$skip = ['«', '»', '!', '?', '.', ',', ';', ':', '…', '“', '—'];

function normalizeOutput($predictionList)
{
    $result = [];
    usort($predictionList, function($a, $b){ return $a['position'] <=> $b['position']; });
    $predictionNormalized = groupBy($predictionList, 'core');
    foreach($predictionNormalized as $token){
        $result[] = $token['core'];
    }
    return implode(' ', $result);
}
function calculatePosition($tokenTotal, $index, $context = [])
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
function groupBy($arr, $key)
{   
    $result = [];
    $keys = [];
    foreach($arr as $i => $a){
        if(!in_array($a[$key], $keys)){
            $keys[] = $a[$key];
        }
        $i = array_search($a[$key], $keys);
        if(empty($result[$i])) $result[$i] = [];
        $result[$i][] = $a;
    }
    return $result;
}
function utilizeToken($str)
{
    $str = str_replace('ё', 'е', $str);
    return $str;
}
function exctractTokens($neuronList)
{
    $result = [];
    
    foreach($neuronList['neurons'] as $neuron){
        $result[] = $neuron['token'];
    }
    return $result;
}
function tokenize($sentence)
{
    global $skip;
    $sentence = mb_strtolower($sentence);
    $sentence = str_replace(array("\n", "\r"), '', $sentence);
    foreach($skip as $item){
        if((int) $item || strpos($sentence, $item) > -1){
            $sentence = str_replace($item, '', $sentence);
            continue;
        }
    }
    $sentence = str_replace('  ',  ' ',$sentence);
    $sentence = str_replace('ё', 'е', $sentence);
    return explode(' ', trim($sentence));
}
function getSurroundingTokens($index, $tokenList)
{
    $result = [];
    foreach($tokenList as $tokenIndex => $token){
        if($tokenIndex < $index) $result['previousTokens'][] = $token;
        if($tokenIndex > $index) $result['nextTokens'][] = $token;
    }
    
    return $result;
}
