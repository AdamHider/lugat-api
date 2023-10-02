<?php

namespace App\Libraries\Cerebrum;

class Neuron{


    public function find($neuron, $target_language, $context = [], $onlyFirstAxon = false)
    {
        $db = \Config\Database::connect();
        $contextQuery = "1";
        if(isset($context['previousTokens'])) $contextQuery .= ", (SELECT COUNT(*) FROM crbrm_neurons t2 WHERE t1.axon_id = t2.axon_id and t1.core != t2.core AND t2.language_id = ".$neuron['language_id']." and t2.core IN ('".implode("','", $context['previousTokens'])."') AND t2.position < ".$neuron['position'].")";
        if(isset($context['nextTokens']))     $contextQuery .= ", (SELECT COUNT(*) FROM crbrm_neurons t2 WHERE t1.axon_id = t2.axon_id and t1.core != t2.core AND t2.language_id = ".$neuron['language_id']." and t2.core IN ('".implode("','", $context['nextTokens'])."') AND t2.position > ".$neuron['position'].")";
        $sql = "
            SELECT 
                t.axon_id, t1.core, t1.position, 
                ABS(t.position - ".$neuron['position'].") as `rank`, 
                CONCAT($contextQuery) AS `context_rank`
            FROM 
                crbrm_neurons t JOIN crbrm_neurons t1 ON t.axon_id = t1.axon_id and t.core != t1.core   AND t1.language_id = $target_language
            WHERE 
                t.core = '".$neuron['core']."' AND t.language_id = ".$neuron['language_id']."
            GROUP BY 
                t1.core, t1.position, `rank`, t.axon_id, context_rank
            ORDER BY 
                context_rank DESC, `rank`
        ";
        if($onlyFirstAxon){
            $sql = "SELECT * FROM crbrm_neurons WHERE axon_id = (SELECT axon_id FROM ($sql)a GROUP BY axon_id LIMIT 1) AND language_id = $target_language";
        }
        return $db->query($sql)->getResultArray();
    }
    
    public function getAxonId($sourceTokenList, $targetTokenList)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT  n1.axon_id 
            FROM crbrm_neurons n JOIN crbrm_neurons n1 ON n.axon_id = n1.axon_id and n.core != n1.core
            WHERE n.core IN ('".implode("','", $sourceTokenList)."') AND n1.core IN ('".implode("','", $targetTokenList)."')
            GROUP BY n.axon_id 
        ";
        $result = $db->query($sql)->getRow();
        if(!empty($result->axon_id)){
            return $result->axon_id;
        }
        return null;
    }
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
    public function save($neuron)
    {
        $db = \Config\Database::connect();
        $sql = "
            INSERT INTO
                crbrm_neurons
            SET
                id              = NULL, 
                axon_id         = ".(int) $neuron['axon_id'].", 
                core            = ".$db->escape($neuron['core']).", 
                coords          = ".(float) $neuron['coords'].", 
                position        = ".(float) $neuron['position'].", 
                is_compound     = ".((!$neuron['is_compound']) ? 'NULL' : 1).", 
                frequency       = ".(int) $neuron['frequency'].",
                axon_strength   = ".(int) $neuron['axon_strength'].",
                language_id     = ".(int) $neuron['language_id']."
            ON DUPLICATE KEY UPDATE
                coords          = ".(float) $neuron['coords'].", 
                position        = ".(float) $neuron['position'].", 
                is_compound     = ".((!$neuron['is_compound']) ? 'NULL' : 1).", 
                frequency       = ".(int) $neuron['frequency'].",
                axon_strength   = ".(int) $neuron['axon_strength'].",
                language_id     = ".(int) $neuron['language_id']."
        ";
        return $db->query($sql);
    }
    public function getLastAxonId()
    {
        $db = \Config\Database::connect();
        $sql = " SELECT MAX(axon_id)+1 as lastId FROM crbrm_neurons";
        return $db->query($sql)->getRow()->lastId;
    }
    public function createEmpty($axon_id, $core, $position, $languageId)
    {
        return [
            'axon_id'   => $axon_id,
            'core'      => $core,
            'coords'    => 0,
            'position'  => $position,
            'is_compound' => null,
            'frequency' => 0,
            'axon_strength' => 1,
            'language_id' => $languageId
        ];
    }
    public function recalculatePosition($newPosition, $oldPosition, $frequency)
    {
        if($oldPosition === null || $frequency == 0){
            return $newPosition;
        }
        $quantifier = 1 - (($frequency - 1) / $frequency );
        $value = ($newPosition - $oldPosition) * $quantifier;
        return round($oldPosition + $value, 4);
    }


    public function find1($neuron, $target_language, $context = [], $onlyFirstAxon = false)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT p1.axon_id, d1.token AS core, p1.position, ABS(p.position - ".$neuron['position'].") as `rank`
            FROM crbrm_neurons_dict d
            JOIN crbrm_neurons_position p ON d.id = p.token_id  
            JOIN crbrm_neurons_position p1 ON p.axon_id = p1.axon_id 
            JOIN crbrm_neurons_dict d1 ON d1.id = p1.token_id AND d1.language_id = $target_language
            WHERE d.token = '".$neuron['core']."' AND d.language_id = ".$neuron['language_id']."
            ORDER BY p1.frequency DESC, `rank` LIMIT 1
        ";
        /*
        if($onlyFirstAxon){
            $sql = "SELECT 
            d.id, axon_id, d.token as core, p.position
            FROM crbrm_neurons_dict d JOIN crbrm_neurons_position p ON d.id = p.token_id WHERE p.axon_id = (SELECT axon_id FROM ($sql)a GROUP BY axon_id LIMIT 1) AND d.language_id = $target_language";
        }*/
        return $db->query($sql)->getResultArray();
    }

    
    public function getDictItem($token, $languageId)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT * FROM crbrm_neurons_dict WHERE token = $token AND language_id = $languageId
        ";
        return $db->query($sql)->getResultArray();
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

}