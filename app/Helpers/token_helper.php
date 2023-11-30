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
function lemmatize($lemma, $word, $language_id)
{
    $splittedLemma = mb_str_split($lemma);
    $splittedWord = mb_str_split($word);
    $form = [];
    $diff = [];
    $lemma = [];
    $totalSymbols = count($splittedWord);
    if(count($splittedLemma) > count($splittedWord)){
        $totalSymbols = count($splittedLemma);
    }
    for($i = 0; $i < $totalSymbols; $i++){
        if(!isset($splittedWord[$i]) && isset($splittedLemma[$i])){
            $diff[] = $splittedLemma[$i];
            continue;
        };
        $wordChar = $splittedWord[$i];
        if(!isset($splittedLemma[$i])){
            $form[] = $wordChar;
            continue;
        };
        if($splittedLemma[$i] !== $wordChar){
            $form[] = $wordChar;
            $lemma[] = $splittedLemma[$i];
            $diff[] = $splittedLemma[$i];
            continue;
        };
        $lemma[] = $wordChar;
    }
    if(count($form) == count($splittedWord)){
        return false;
    }
    return [
        'template' => '',
        'form' => implode('', $form),
        'replace' => implode('', $diff),
        'language_id' => $language_id
    ];      
}
function unlemmatize($form, $word, $language_id)
{
    $splittedWord = mb_str_split($word);
    $splittedForm = mb_str_split($form['form']);
    $reversedWord = array_reverse($splittedWord);
    $reversedForm = array_reverse($splittedForm);
    $lemma = [];
    foreach($reversedWord as $index => $wordChar){
        if(!isset($reversedForm[$index])){
            $lemma[] = $wordChar;
            continue;
        };
        if($reversedForm[$index] !== $wordChar){
            break;
        };
    }
    if(empty($lemma)){
        return '';
    }
    $reversedLemma = array_reverse($lemma);
    return implode('', $reversedLemma).$form['replace'];
}


