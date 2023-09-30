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
        'book_id', 
        'chapter_id', 
        'text', 
        'index', 
        'language_id', 
        'is_trained'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';



    public function getPair ($data) 
    {

        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        $sentencePair = $this->join('lgta_sentences s2', 'lgta_sentences.chapter_id = s2.chapter_id AND s2.`index` = lgta_sentences.`index`')
        ->select('lgta_sentences.id as source_id, lgta_sentences.text as source_text, s2.id as target_id, s2.text as target_text')
        ->where([
            'lgta_sentences.is_trained'  => 0, 
            'lgta_sentences.language_id' => $data['source_language_id'],
            's2.language_id' => $data['target_language_id']
        ])->limit(1)->get()->getRowArray();
        

        return $sentencePair;
    }
    public function createItem ($data)
    {
        $data = [
            'chapter_id' => $data['chapter_id'], 
            'text' => ($data['text']) ? $data['text'] : NULL, 
            'index' => $data['index'], 
            'language_id' => $data['language_id'], 
            'is_trained' => $data['is_trained']
        ];
        $this->transBegin();
        $text_id = $this->insert($data, true);

        $this->transCommit();

        return $text_id;        
    }
    public function updateItem ($data)
    {
        $this->transBegin();
        
        $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return $data['id'];        
    }
    
}