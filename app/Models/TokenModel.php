<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class TokenModel extends Model
{
    protected $table      = 'lgt_tokens';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'word_id', 
        'sentence_id', 
        'index'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';


    public function getList ($data) 
    {
        $this->join('lgt_words w', 'lgt_tokens.word_id = w.id')
        ->select("lgt_tokens.*, lgt_tokens.id as token_id, w.word, w.language_id");
        
        if(isset($data['source']) && isset($data['target'])){
            $this->where("lgt_tokens.sentence_id IN (".$data['source']['id'].", ".$data['target']['id'].")");
        }

        if(isset($data['ids'])){
            $this->where("lgt_tokens.id IN (".$data['ids'].")");
        }

        $tokens = $this->orderBy('sentence_id, `index` ASC')->get()->getResultArray();
        
        if(empty($tokens)){
            return false;
        }
        return $tokens;
    }
    public function predictList ($data) 
    {
        $groups = $this->join('lgt_tokens t1 ', 'lgt_tokens.word_id = t1.word_id')
        ->join('lgt_token_relations tr', 'tr.token_id = t1.id')
        ->join('lgt_sentences s', 's.id = t1.sentence_id')
        ->select("DISTINCT GROUP_CONCAT(lgt_tokens.id) as wset, COUNT(DISTINCT s.language_id) as ct")
        ->where("lgt_tokens.sentence_id IN (".$data['source']['id'].", ".$data['target']['id'].")")
        ->groupBy('tr.group_id')
        ->having('ct = 2')
        ->get()->getResultArray();
        
        if(empty($groups)){
            return false;
        }
        return $groups;
    }

    
    
    public function createItem ($data)
    {
        $this->validationRules = [];
        $this->transBegin();
        $word_id = $this->insert($data, true);

        $this->transCommit();

        return $word_id;        
    }


}