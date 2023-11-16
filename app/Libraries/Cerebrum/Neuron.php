<?php

namespace App\Libraries\Cerebrum;

class Neuron{

    public function getAxonList($sourceTokenList, $targetTokenList)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT  n1.axon_id 
            FROM crbrm_neurons n JOIN crbrm_neurons n1 ON n.axon_id = n1.axon_id and n.core != n1.core
            WHERE n.core IN ('".implode("','", $sourceTokenList)."') AND n1.core IN ('".implode("','", $targetTokenList)."')
            GROUP BY n.axon_id 
        ";
        return $db->query($sql)->getResultArray();
    }
    public function getListByAxon($axonId)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT * FROM crbrm_neurons WHERE axon_id = $axonId
        ";
        return $db->query($sql)->getResultArray();
    }
    public function save($token)
    {
        $db = \Config\Database::connect();
        $sql = "
            INSERT INTO
                crbrm_neurons_position
            SET
                id              = NULL, 
                axon_id         = ".(int) $token['axon_id'].", 
                token_id        = ".(int) $token['id'].",
                position        = ".(float) $token['position'].", 
                frequency       = 1
            ON DUPLICATE KEY UPDATE
                frequency       = frequency + 1
        ";
        return $db->query($sql);
    }
    public function getAxonId($sourceToken, $targetToken)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT  p.axon_id 
            FROM crbrm_neurons_dict d
            JOIN crbrm_neurons_position p ON d.id = p.token_id  
            JOIN crbrm_neurons_position p1 ON p.axon_id = p1.axon_id 
            JOIN crbrm_neurons_dict d1 ON d1.id = p1.token_id AND d1.language_id = ".$targetToken['language_id']."
            WHERE d.language_id = ".$sourceToken['language_id']."
            AND   d.token = ".$db->escape($sourceToken['token'])." AND  p.position LIKE ".$sourceToken['position']."
            AND   d1.token = ".$db->escape($targetToken['token'])."  AND  p1.position LIKE ".$targetToken['position']."
            GROUP BY p.axon_id 
        ";
        $result = $db->query($sql)->getRow();
        if(!empty($result->axon_id)){
            return $result->axon_id;
        }
        return null;
    }
    public function getGroupAxonId($tokenSet)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT 
                p.axon_id, (SELECT GROUP_CONCAT(DISTINCT p1.token_id) FROM crbrm_neurons_position p1 WHERE p.axon_id = p1.axon_id) tokens
            FROM
                crbrm_neurons_position p
            WHERE
                p.token_id IN(".implode(',',$tokenSet).")  
            GROUP BY axon_id    
            HAVING tokens = '".implode(',',$tokenSet)."'    
        ";
        $result = $db->query($sql)->getRow();
        if(isset($result->axon_id)){
            return (int) $result->axon_id;
        }
        return null;
    }
    public function getLastAxonId()
    {
        $db = \Config\Database::connect();
        $sql = " SELECT MAX(axon_id)+1 as lastId FROM crbrm_neurons_position";
        return $db->query($sql)->getRow()->lastId;
    }
    public function createEmpty($tokenObject)
    {
        return [
            'axon_id'   => $tokenObject['axon_id'],
            'token_id'  => $tokenObject['token_id'],
            'position'  => $tokenObject['position'],
            'frequency' => 0
        ];
    }
    public function find($neuron, $target_language, $context = [], $onlyFirstAxon = false)
    {
        $db = \Config\Database::connect();
        //if(isset($context['previousTokens'])) $contextQuery .= ", (SELECT COUNT(*) FROM crbrm_neurons t2 WHERE t1.axon_id = t2.axon_id and t1.core != t2.core AND t2.language_id = ".$neuron['language_id']." and t2.core IN ('".implode("','", $context['previousTokens'])."') AND t2.position < ".$neuron['position'].")";
        //if(isset($context['nextTokens']))     $contextQuery .= ", (SELECT COUNT(*) FROM crbrm_neurons t2 WHERE t1.axon_id = t2.axon_id and t1.core != t2.core AND t2.language_id = ".$neuron['language_id']." and t2.core IN ('".implode("','", $context['nextTokens'])."') AND t2.position > ".$neuron['position'].")";
        
        $sql = "
            SELECT 
                p1.axon_id, d1.token AS core, AVG(p1.position) AS position, 
                ABS(p1.frequency - (SELECT MAX(p2.frequency) FROM crbrm_neurons_position p2 WHERE p2.token_id = p1.token_id)) as freq_rank,
                ABS(p.position - ".$neuron['position'].") as `rank`,
                IF(ABS(p.position - ".$neuron['position'].") = 0, p1.frequency * 10, p1.frequency) as `rank1`
            FROM crbrm_neurons_dict d
            JOIN crbrm_neurons_position p ON d.id = p.token_id  
            JOIN crbrm_neurons_position p1 ON p.axon_id = p1.axon_id 
            JOIN crbrm_neurons_dict d1 ON d1.id = p1.token_id AND d1.language_id = $target_language
            WHERE d.token =  ".$db->escape(addslashes($neuron['token']))." AND d.language_id = ".$neuron['language_id']."
            GROUP BY p1.id    
            ORDER BY `rank1` DESC, freq_rank,  `rank`
        ";
        return $db->query($sql)->getResultArray();
    }



    public function findToken($neuron, $target_language)
    {
        $db = \Config\Database::connect();
        echo $sql = "
            SELECT d1.token, SUM(p1.frequency) as frequency, p1.position, d.token as source
            FROM crbrm_neurons_dict d
            JOIN crbrm_neurons_position p ON d.id = p.token_id  
            JOIN crbrm_neurons_position p1 ON p.axon_id = p1.axon_id 
            JOIN crbrm_neurons_dict d1 ON d1.id = p1.token_id AND d1.language_id = $target_language
            WHERE d.token =  ".$db->escape(addslashes($neuron['token']))." AND d.language_id = ".$neuron['language_id']." 
            GROUP BY p1.token_id
            HAVING frequency > 1
            ORDER BY frequency DESC
            LIMIT 1
        ";
        die;
        return $db->query($sql)->getRowArray();
    }


    public function getDictItemId($token, $languageId)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT id FROM crbrm_neurons_dict WHERE token = ".$db->escape($token)." AND language_id = $languageId
        ";
        $result = $db->query($sql)->getRow();
        if(!empty($result->id)){
            return $result->id;
        }
        return null;
    }
    public function createDictItem($token, $languageId)
    {
        $db = \Config\Database::connect();
        $sql = "
            INSERT INTO
            crbrm_neurons_dict
            SET
                id          = NULL, 
                token       = ".$db->escape($token).", 
                language_id = ".(int) $languageId."
        ";
        $db->query($sql);
        return $db->insertID();
    }
    public function decreaseAxonFrequency($tokenSet, $axonId)
    {
        $db = \Config\Database::connect();
        $sql = "
            UPDATE IGNORE crbrm_neurons_position
            SET frequency = frequency - frequency/10
            WHERE token_id IN (".implode(',',$tokenSet).") AND axon_id != ".(int) $axonId."
        ";
        return $db->query($sql);
    }
    public function forgetAll()
    {
        $db = \Config\Database::connect();
        $db->query("TRUNCATE crbrm_neurons_dict");
        $db->query("TRUNCATE crbrm_neurons_position");
        return;
    }

    public function clearMemory()
    {
        $db = \Config\Database::connect();
        $db->query("TRUNCATE crbrm_neurons_dict");
        $db->query("TRUNCATE crbrm_neurons_position");
    }

    public function getSentences($token, $languageId)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT id FROM crbrm_neurons_dict WHERE token = ".$db->escape($token)." AND language_id = $languageId
        ";
        $result = $db->query($sql)->getRow();
        if(!empty($result->id)){
            return $result->id;
        }
        return null;
    }



}