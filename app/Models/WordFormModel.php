<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class WordFormModel extends Model
{
    protected $table      = 'lgt_word_forms';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $allowedFields = [
        'word_id',
        'form_id', 
        'lemma_id'
    ];
    
    protected $useTimestamps = false;

    public function getItem($data)
    {
        $wordForm = $this->where("word_id = ".$data['word_id']." AND lemma_id = ".$data['lemma_id'])->get()->getRowArray();
        if(empty($wordForm)){
            return false;
        }
        return $wordForm;
    }
    public function createItem ($data)
    {
        $this->transBegin();
        $word_form_id = $this->insert($data, true);
        $this->transCommit();

        return $word_form_id;        
    }
    public function deleteItem ($data)
    {
        return $this->delete($data);
    }
}