<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

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

    public function getItemId($token, $languageId)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT id FROM lgt_words WHERE token = ".$db->escape($token)." AND language_id = $languageId
        ";
        $result = $db->query($sql)->getRow();
        if(!empty($result->id)){
            return $result->id;
        }
        return null;
    }
    
    public function createItem($token, $languageId)
    {
        $db = \Config\Database::connect();
        $sql = "
            INSERT INTO
            lgt_words
            SET
                id          = NULL, 
                token       = ".$db->escape($token).", 
                language_id = ".(int) $languageId."
        ";
        $db->query($sql);
        return $db->insertID();
    }
}