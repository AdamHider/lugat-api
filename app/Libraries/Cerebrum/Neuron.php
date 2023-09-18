<?php

namespace App\Libraries\Cerebrum;
class Neuron{


    public function getList($sourceToken, $target_language)
    {
        $db = \Config\Database::connect();
        $contextRankQuery = "1";
        if(isset($sourceToken['previousToken'])){
            $contextRankQuery .= ", (SELECT COUNT(*) FROM crbrm_neurons t2 WHERE t1.axon_id = t2.axon_id and t1.core != t2.core AND t2.language_id = ".$sourceToken['language_id']." and t2.core IN ('".$sourceToken['previousToken']."') AND t2.position < ".$sourceToken['position'].")";
        }
        if(isset($sourceToken['nextToken'])){
            $contextRankQuery .= ", (SELECT COUNT(*) FROM crbrm_neurons t2 WHERE t1.axon_id = t2.axon_id and t1.core != t2.core AND t2.language_id = ".$sourceToken['language_id']." and t2.core IN ('".$sourceToken['nextToken']."') AND t2.position > ".$sourceToken['position'].")";
        }
        $sql = "
            SELECT t1.core, t1.position, ABS(t.position - ".$sourceToken['position'].") as `rank`, t.axon_id, CONCAT($contextRankQuery) AS `context_rank`
            FROM crbrm_neurons t JOIN crbrm_neurons t1 ON t.axon_id = t1.axon_id and t.core != t1.core   AND t1.language_id = $target_language
            WHERE t.core = '".$sourceToken['text']."'  AND t.language_id = ".$sourceToken['language_id']."
            GROUP BY t1.core, t1.position, `rank`, t.axon_id, context_rank
            ORDER BY context_rank DESC, t.axon_strength DESC, `rank`
        ";
        return $db->query($sql)->getResultArray();
    }
    public function getTokenTranslations($core)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT DISTINCT
                wl.axon_id as `axon_id`,
                wl.word as `source_word`, wfl.word_id as `source_word_id`, wfl.wordform as `source_wordform`,
                wl1.word as `target_word`, wfl1.word_id as `target_word_id`, wfl1.wordform as `target_wordform`
            FROM
                lugat_db.lgt_wordform_list wfl
                    JOIN
                lugat_db.lgt_word_list wl ON wfl.word_id = wl.word_id
                    JOIN
                lugat_db.lgt_word_list wl1 ON wl.axon_id = wl1.axon_id AND wl.language_id != wl1.language_id
                    JOIN
                lugat_db.lgt_wordform_list wfl1 ON wfl1.word_id = wl1.word_id
            WHERE
                wfl.wordform = '$core'
        ";
        return $db->query($sql)->getResultArray();
    }
    public function getPair($sourceCore, $targetCore)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT t.*
            FROM crbrm_neurons t JOIN crbrm_neurons t1 ON t.axon_id = t1.axon_id AND t.core != t1.core
            WHERE (t.core = '$sourceCore' AND t1.core = '$targetCore') OR (t.core = '$targetCore' AND t1.core = '$sourceCore')
        ";
        $result = $db->query($sql)->getResultArray();
        if(empty($result)){
            return $this->createPair($sourceCore, $targetCore);
        }
        return $result;
    }
    public function save($neuron)
    {
        $db = \Config\Database::connect();
            $sql = "
                INSERT INTO
                    crbrm_neurons
                SET
                    id          = NULL, 
                    axon_id     = ".$neuron['axon_id'].", 
                    core        = ".$db->escape($neuron['core']).", 
                    coords      = ".$neuron['coords'].", 
                    position    = ".(float) $neuron['position'].", 
                    is_compound = ".((!$neuron['is_compound']) ? 'NULL' : 1).", 
                    frequency    = ".(int) $neuron['frequency'].",
                    axon_strength => ".(int) $neuron['axon_strength'].",
                    language_id => ".(int) $neuron['language_id']."
                ON DUPLICATE KEY UPDATE
                    coords      = ".$neuron['coords'].", 
                    position    = ".(float) $neuron['position'].", 
                    is_compound = ".((!$neuron['is_compound']) ? 'NULL' : 1).", 
                    frequency   = ".(int) $neuron['frequency'].",
                    axon_strength => ".(int) $neuron['axon_strength'].",
                    language_id => ".(int) $neuron['language_id']."
            ";
        return $db->query($sql);
    }
    public function getAxonId()
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT 
                MAX(axon_id)+1 as lastId
            FROM
                lugat_db.crbrm_neurons
        ";
        return $db->query($sql)->getRow()->lastId;
    }
    public function createPair($sourceCore, $targetCore)
    {
        $axon_id = $this->getAxonId();
        return [
            [
                'axon_id'   => $axon_id,
                'core'      => $sourceCore,
                'coords'    => 0,
                'position'  => false,
                'is_compound' => null,
                'frequency' => 0,
                'axon_strength' => 1,
                'language_id' => 1
            ],
            [
                'axon_id'   => $axon_id,
                'core'      => $targetCore,
                'coords'    => 0,
                'position'  => false,
                'is_compound' => null,
                'frequency' => 0,
                'axon_strength' => 1,
                'language_id' => 2
            ]
        ];
    }
    public function calculatePosition($newPosition, $oldPosition, $frequency)
    {
        if($oldPosition === null || $frequency == 0){
            return $newPosition;
        }
        $quantifier = 1 - (($frequency - 1) / $frequency );
        $value = ($newPosition - $oldPosition) * $quantifier;
        return round($oldPosition + $value, 2);
    }

}