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

    public function getList ($data) 
    {
        helper("Token");
        $this->join('lgt_tokens t', 'lgt_token_relations.token_id = t.id')
        ->join('lgt_words w', 't.word_id = w.id')
        ->select("lgt_token_relations.*, w.word, w.language_id");
        if(isset($data['source']) && isset($data['target'])){
            $this->where("t.sentence_id IN (".$data['source']['id'].", ".$data['target']['id'].")");
        }
        $tokenRelations = $this->get()->getResultArray();
        
        if(empty($tokenRelations)){
            return false;
        }
        return $tokenRelations;
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
    public function saveGroup($group)
    {
        if($this->checkIfExists($group)) return true;
        
        $groupId = $this->getLastGroupId();
        foreach($group as $relation){
            $this->createItem([
                'group_id' => $groupId, 
                'token_id' => $relation['token_id'], 
                'is_compound' => null
            ]);
        }
        return true;
    }
    public function createItem ($data)
    {
        $this->validationRules = [];
        $this->transBegin();
        $book_id = $this->insert($data, true);

        $this->transCommit();

        return $book_id;        
    }
    public function checkIfExists ($data)
    {
        $ids = array_column($data, 'token_id');
        $group = $this->select("group_id, GROUP_CONCAT(token_id) AS gset")
        ->where("token_id IN (".implode(',', $ids).")")
        ->groupBy("group_id")
        ->having("gset = '".implode(',', $ids)."'")
        ->get()->getRowArray();
        
        return !empty($group);
    }
    public function getLastGroupId()
    {
        $result = $this->select("MAX(group_id)+1 as lastId")->get()->getRowArray();
        if(!$result['lastId']){
            return 0;
        }
        return $result['lastId'];
    }
}