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
        $result = [
            "source" => [],
            "target" => []
        ];
        $tokens = $this->join('lgt_words w', 'lgt_tokens.word_id = w.id')
        ->select("lgt_tokens.*, w.word")
        ->where("lgt_tokens.sentence_id IN (".$data['source']['id'].", ".$data['target']['id'].")")
        ->orderBy('sentence_id, `index` ASC')->get()->getResultArray();
        
        if(empty($tokens)){
            return false;
        }
        foreach($tokens as $token){
            if($token['sentence_id'] == $data['source']['id']) {
                $result["source"][] = $token;
            } else {
                $result["target"][] = $token;
            }
        }
        return $result;
    }
    public function createItem($token_id, $sentence_id, $index)
    {
        $db = \Config\Database::connect();
        $sql = "
            INSERT IGNORE INTO
                lgt_words
            SET
                token_id    = ".(int) $token_id.",
                sentence_id = ".(int) $sentence_id.",
                `index`       = ".(int) $index."
        ";
        $db->query($sql);
        return $db->insertID();
    }


}