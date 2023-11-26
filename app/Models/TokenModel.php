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
        'index',
        'char_index'
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
        $groups = $this
        ->join('lgt_token_relations tr', 'tr.token_id = lgt_tokens.id')
        ->join('lgt_tokens t1 ', 'lgt_tokens.word_id = t1.word_id AND t1.sentence_id IN ('.$data['source']['id'].", ".$data['target']['id'].')', 'left')
        ->join('lgt_sentences s', 's.id = t1.sentence_id', 'left')
        ->select("DISTINCT GROUP_CONCAT(t1.id) as wset, COUNT(DISTINCT s.language_id) as ct, COUNT(DISTINCT lgt_tokens.id) as tcount, COUNT(DISTINCT t1.id) as t1count ")
        ->groupBy('tr.group_id')
        ->having('ct = 2  AND tcount = t1count')
        ->get()->getResultArray();
        
        if(empty($groups)){
            return false;
        }
        return $groups;
    }

    
    
    public function createItem ($data)
    {
        $this->validationRules = [];
        $word_id = $this->insert($data, true);


        return $word_id;        
    }


}