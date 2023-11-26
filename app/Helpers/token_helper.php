<?php


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

function clearSentence($sentence) 
{
    $sentence = preg_replace('/^[#%]/', '', $sentence);
    $sentence = str_replace(' - ', ' — ', $sentence);
    $sentence = preg_replace("/([.,—])(\w)/ui", "\1 \2", $sentence);
    return trim($sentence);
}
function tokenize($sentence)
{
    $sentence = mb_strtolower(trim($sentence));
    preg_match_all('/\w(?<!\d)[\w\'-]*/ui', $sentence, $tokens, PREG_OFFSET_CAPTURE);
    if(!empty($tokens)){
        return $tokens[0];
    }
    return [];
    
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
