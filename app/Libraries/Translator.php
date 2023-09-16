<?php

namespace App\Libraries;
class Translator{


    protected $skip = ['«', '»', '!', '?', '.', ',', ';', ':', '…', '“'];
    public function tokenize($sentence)
    {
        $result = [];
        $tokenList = $this->sentenceSplit($sentence);
        
        foreach($tokenList as $index => $token){
            $tokenPosition = $this->calculateTokenPosition($tokenList, $index);
            $result[] = [
                'value' => $token,
                'position' => $tokenPosition
            ];
        }
        return $result;
    }

    private function predict($tokenList)
    {
        $result = [];
        $predictionList = [];
        foreach($tokenList as $token){
            $prediction = $this->dbPredictToken($token['value'], $token['position']);
            $predictionList = array_merge($predictionList, $prediction);
        }
        $predictionNormalized = $this->predictionNormalize($predictionList);


        return $predictionNormalized;
    }
    public function remember($data)
    {
        $result = [];
        foreach($data['tokenData'] as $sourceIndex => $token){
            foreach($token['matches'] as $match){
                $result[] = [
                    'relation_id'   => 1, 
                    'token'         => $token['text'], 
                    'coords'        => 1, 
                    'position'      => $this->calculateTokenPosition($data['sentences']['source'], $sourceIndex), 
                    'is_compound'   
                ];
                $result[] = [
                    'relation_id'   => 1, 
                    'token'         => $data['sentences']['target'][$match], 
                    'coords'        => 1, 
                    'position'      => $this->calculateTokenPosition($data['sentences']['target'], $match), 
                    'is_compound'   
                ];
            }
        }
        print_r($result);
        die;
        
        return $result;
    }
    public function learn($data)
    {
        $result = [];

        $sourceTokenList = explode(' ', $this->utilizeSentence($data['source']));
        $targetTokenList = explode(' ', $this->utilizeSentence($data['target']));

        foreach($sourceTokenList as $index => &$sourceToken){
            if(in_array($sourceToken, $this->skip)){
                continue;
            }
            $tokenObject                = [];
            $tokenObject['text']        = strtolower($sourceToken);
            $tokenObject['position']    = $index;
            $tokenObject['matches']     = $this->getMatchesFromExperience($tokenObject, $targetTokenList);

            $result['tokenData'][] = $tokenObject;
        }
        $result['sentences']['source'] = $sourceTokenList;
        $result['sentences']['target'] = $targetTokenList;
        return $result;
    }

    public function getMatchesFromExperience($sourceTokenObject, $targetTokenList)
    {

        $stockPredictions = $this->getTokenPredictions($sourceTokenObject['text'], $sourceTokenObject['position']);
        $result = [];
        foreach($targetTokenList as $index => $targetToken){
            foreach($stockPredictions as $prediction){
                $prediction['token'] = $this->utilizeToken($prediction['token']);
                if($prediction['token'] === $targetToken){
                    $result[] = $index;
                }
            }
        }
        return $result;
    }
    
    public function findMatches($sourceToken, $targetTokenList)
    {
        $translations = $this->getTokenTranslations($sourceToken);
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


    private function predictionNormalize($predictionList)
    {
        usort($predictionList, 'array_sort');
        $result = $this->groupBy($predictionList, 'value');
        return $result;
    }
    private function sentenceSplit($sentence)
    {
        $result = explode(' ', $sentence);
        return $result;
    }
    public function calculateTokenPosition($tokenList, $index)
    {
        $sentenceLength = count($tokenList);
        return round(($index+1) * 100 / $sentenceLength,2);
    }
    private function getTokenPredictions($token, $position)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT t1.token, t1.position
            FROM lugat_db.lgt_example_relation_test t JOIN lugat_db.lgt_example_relation_test t1 ON t.denotation_id = t1.denotation_id and t.token != t1.token 
            WHERE t.token = '$token'
            GROUP BY t1.coords ORDER BY abs(t.position - $position)
        ";
        return $db->query($sql)->getResultArray();
    }
    public function getTokenTranslations($token)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT DISTINCT
                wl.word as `source_word`,
                wl.denotation_id as `denotation_id`,
                wfl.word_id as `source_word_id`,
                wfl.wordform as `source_wordform`,
                wl1.word as `target_word`,
                wfl1.word_id as `target_word_id`,
                wfl1.wordform as `target_wordform`
            FROM
                lugat_db.lgt_wordform_list wfl
                JOIN
                lugat_db.lgt_word_list wl ON wfl.word_id = wl.word_id
                JOIN
                lugat_db.lgt_word_list wl1 ON wl.denotation_id = wl1.denotation_id AND wl.language_id != wl1.language_id
                JOIN
                lugat_db.lgt_wordform_list wfl1 ON wfl1.word_id = wl1.word_id
            WHERE
                wfl.wordform = '$token'
        ";
        return $db->query($sql)->getResultArray();
    }
    private function array_sort($a, $b)
    {
        return $a['position'] <=> $b['position'];
    }
    private function groupBy($arr, $key)
    {   
        $result = [];
        $keys = [];
        foreach($arr as $a){
            if(!in_array($key, $keys)){
                $result[] = $a;
                continue;
            }
            $keys[] = $a[$key];
        }
        return $result;
    }
    public function utilizeSentence($sentence)
    {
        $sentence = mb_strtolower($sentence);
        $sentence = str_replace(array("\n", "\r"), '', $sentence);
        foreach($this->skip as $item){
            if((int)$item || strpos($sentence, $item)>-1){
                $sentence = str_replace($item, '', $sentence);
                continue;
            }
        }
        $sentence = str_replace('  ',  ' ',$sentence);
        $sentence = str_replace('ё', 'е', $sentence);
        return $sentence;
    }
    private function utilizeToken($str)
    {
        $str = str_replace('ё', 'е', $str);
        return $str;
    }

}