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
        'form_id', 
        'word_id'
    ];
    
    protected $useTimestamps = false;

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