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
        $predictions = [];
        foreach($data['tokenData'] as $sourceIndex => $token){
            foreach($token['matches'] as $targetIndex){
                $sourceTokenTotal = count($data['sentences']['source']);
                $targetTokenTotal = count($data['sentences']['target']);

                $groupId = $this->getLastGroupId();
                $sourceToken = $token['text'];
                $sourcePosition = $this->calculateTokenPosition($sourceTokenTotal, $sourceIndex);
                $oldSourcePosition = 0;
                $sourceFrequency = 0;

                $targetToken = $data['sentences']['target'][$targetIndex];
                $targetPosition = $this->calculateTokenPosition($targetTokenTotal, $sourceIndex);
                $oldTargetPosition = 0;
                $targetFrequency = 0;

                $existingPredictionPair = $this->getPredictionPair($sourceToken, $targetToken);
                if(!empty($existingPredictionPair)){
                    $groupId = $existingPredictionPair['denotation_id'];
                    $oldSourcePosition = $existingPredictionPair['source_position'];
                    $oldTargetPosition = $existingPredictionPair['target_position'];
                    $sourceFrequency = $existingPredictionPair['source_frequency']+1;
                    $targetFrequency = $existingPredictionPair['target_frequency']+1;
                }
                $predictions[] = [
                    'denotation_id' => $groupId, 
                    'token'         => $sourceToken, 
                    'coords'        => 1, 
                    'position'      => $this->updateTokenPosition($sourcePosition, $oldSourcePosition, $sourceFrequency), 
                    'is_compound'   => false,
                    'frequency'     => $sourceFrequency
                ];
                $predictions[] = [
                    'denotation_id' => $groupId, 
                    'token'         => $targetToken, 
                    'coords'        => 1, 
                    'position'      => $this->updateTokenPosition($targetPosition, $oldTargetPosition, $targetFrequency), 
                    'is_compound'   => false,
                    'frequency'     => $targetFrequency
                ];
            }
        }
        return $this->putInMemory($predictions);
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
    /*
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
    */

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
    public function calculateTokenPosition($tokenTotal, $index)
    {
        return round(($index + 1) / $tokenTotal, 2);
    }
    public function updateTokenPosition($newPosition, $oldPosition, $frequency)
    {
        $quantifier = 1 - (($frequency - 1) / $frequency );
        $value = ($newPosition - $oldPosition) * $quantifier;
        return round($oldPosition + $value, 2);
    }
    private function getTokenPredictions($token, $position)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT t1.token, t1.position, ABS(t.position - 0) as `rank`
            FROM lugat_db.lgt_example_relation_test t JOIN lugat_db.lgt_example_relation_test t1 ON t.denotation_id = t1.denotation_id and t.token != t1.token 
            WHERE t.token = '$token'
            GROUP BY t1.token, t1.position, `rank`
            ORDER BY `rank`
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
    private function getPredictionPair($sourceToken, $targetToken)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT 
                t.denotation_id,
                t.token as `source_token`,
                t.position as `source_position`,
                t.frequency as `source_frequency`,
                t1.token as `target_token`, 
                t1.position as `target_position`,
                t1.frequency as `target_frequency`
            FROM
                lugat_db.lgt_example_relation_test t
                    JOIN
                lugat_db.lgt_example_relation_test t1 ON t.denotation_id = t1.denotation_id
                    AND t.token != t1.token
            WHERE
                t.token = '$sourceToken' AND t1.token = '$targetToken'
        ";
        return $db->query($sql)->getRowArray();
    }
    private function putInMemory($predictions)
    {
        $db = \Config\Database::connect();
        foreach($predictions as $prediction){
            $sql = "
                INSERT INTO
                    lugat_db.lgt_example_relation_test
                SET
                    id = NULL, 
                    denotation_id = '".$prediction['denotation_id']."', 
                    token = '".$prediction['token']."', 
                    coords = '".$prediction['coords']."', 
                    position = '".$prediction['position']."', 
                    is_compound = '".$prediction['is_compound']."', 
                    frequency = '".$prediction['frequency']."'
                ON DUPLICATE KEY UPDATE
                    coords = '".$prediction['coords']."', 
                    position = '".$prediction['position']."', 
                    is_compound = '".$prediction['is_compound']."', 
                    frequency = '".$prediction['frequency']."'
            ";
            $db->query($sql);
        }
        return true;
    }
    
    private function getLastGroupId()
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT 
                MAX(denotation_id)+1 as lastId
            FROM
                lugat_db.lgt_example_relation_test
        ";
        return $db->query($sql)->getRow()->lastId;
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