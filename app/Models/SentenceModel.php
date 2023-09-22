<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;
use App\Libraries\Cerebrum\Hippocampus;

class SentenceModel extends Model
{
    protected $table      = 'lgta_sentences';
    protected $primaryKey = 'id';

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



    public function getPair () 
    {

        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        $sentencePair = $this->join('lgta_books b1', 'lgta_sentences.book_id = b1.id')
        ->join('lgta_books b2', 'b1.relation_id = b2.relation_id AND b1.language_id != b2.language_id')
        ->join('lgta_sentences s2', 's2.book_id = b2.id AND s2.`index` = lgta_sentences.`index`')
        ->select('lgta_sentences.text as source_text, s2.text as target_text')
        ->where('lgta_sentences.is_trained', 0)->limit(1)->get()->getRowArray();
        

        return $sentencePair;
    }
    
}