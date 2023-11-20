<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class TokenRelationsModel extends Model
{
    protected $table      = 'lgt_token_relations';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'group_id', 
        'token_id', 
        'is_compound'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    
    public function getGroupId($sourceTokenList, $targetTokenList, $source_language, $target_language)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT  p.axon_id 
            FROM lgt_words d
            JOIN lgt_token_relations p ON d.id = p.token_id  
            JOIN lgt_token_relations p1 ON p.axon_id = p1.axon_id 
            JOIN lgt_words d1 ON d1.id = p1.token_id AND d1.language_id = ".$target_language."
            WHERE d.language_id = ".$source_language."
            AND   d.token IN ('".implode("','", $sourceTokenList)."')
            AND   d1.token IN ('".implode("','", $targetTokenList)."')
            GROUP BY p.axon_id 
        ";
        $result = $db->query($sql)->getRow();
        if(!empty($result->axon_id)){
            return $result->axon_id;
        }
        return null;
    }

    public function getList ($data) 
    {
        helper("Token");

        $tokenRelations = $this->join('lgt_tokens t', 'lgt_token_relations.token_id = t.id')
        ->join('lgt_words w', 't.word_id = w.id')
        ->select("lgt_token_relations.*, w.word")
        ->where("t.sentence_id IN (".$data['source']['id'].", ".$data['target']['id'].")")
        ->get()->getResultArray();
        
        if(empty($tokenRelations)){
            return false;
        }
        $result = groupBy($tokenRelations, 'group_id');
        return $result;
    }

    public function getListByGroup($axonId)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT * 
            FROM lgt_words d
            JOIN lgt_token_relations p ON d.id = p.token_id  
            WHERE p.axon_id = $axonId
        ";
        return $db->query($sql)->getResultArray();
    }
    public function saveItem($neuron)
    {
        $db = \Config\Database::connect();
         $sql = "
            INSERT INTO
                lgt_token_relations
            SET
                id              = NULL, 
                axon_id         = ".(int) $neuron['axon_id'].", 
                token_id        = ".(int) $neuron['token_id'].", 
                position        = ".(float) $neuron['position'].", 
                is_compound     = ".((!$neuron['is_compound']) ? 'NULL' : 1).", 
                frequency       = ".(int) $neuron['frequency']."
            ON DUPLICATE KEY UPDATE
                position        = ".(float) $neuron['position'].", 
                is_compound     = ".((!$neuron['is_compound']) ? 'NULL' : 1).", 
                frequency       = ".(int) $neuron['frequency']."
        ";
        return $db->query($sql);
    }
    public function getLastGroupId()
    {
        $db = \Config\Database::connect();
        $sql = " SELECT MAX(axon_id)+1 as lastId FROM lgt_token_relations";
        return $db->query($sql)->getRow()->lastId;
    }
    
    public function createEmpty($axon_id, $token_id, $position)
    {
        return [
            'axon_id'   => $axon_id,
            'token_id'  => $token_id,
            'position'  => $position,
            'is_compound' => null,
            'frequency' => 0
        ];
    }
}