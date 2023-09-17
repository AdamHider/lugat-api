<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;
use App\Libraries\Cerebrum\Hippocampus;

class WordModel extends Model
{
    protected $table      = 'lgt_wordform_list';
    protected $primaryKey = 'wordform_id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'wordform_id', 
        'wordform', 
        'is_disabled', 
        'word_id', 
        'set_configuration_id'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';



    
}