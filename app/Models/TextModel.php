<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class TextModel extends Model
{
    protected $table      = 'lgt_texts';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'chapter_id', 
        'language_id', 
        'source', 
        'text',
        'is_done',
        'is_exported'
    ];
    
    protected $useTimestamps = false;
        

    public function getList ($data) 
    {
        
        $texts = $this->join('lgt_languages', 'lgt_texts.language_id = lgt_languages.id', 'left')->
        select('lgt_texts.*, lgt_languages.code as language_code')->where(['chapter_id' => $data['chapter_id']])->get()->getResultArray();

        if(empty($texts)){
            return false;
        }
        return $texts;
    }
    public function getItem ($data) 
    {
        $text = $this->select('*')->where(['chapter_id' => $data['chapter_id'], 'language_id' => $data['language_id']])->get()->getRowArray();

        if(empty($text)){
            return false;
        }
        $text['is_exported'] = (bool) $text['is_exported'];
        return $text;
    }
    public function createItem ($data)
    {
        $data = [
            'chapter_id' => $data['chapter_id'], 
            'language_id' => $data['language_id'], 
            'text' => ($data['text']) ? $data['text'] : NULL, 
            'is_done' => $data['is_done'], 
        ];
        $this->transBegin();
        $text_id = $this->insert($data, true);

        $this->transCommit();

        return $text_id;        
    }
    public function updateItem ($data)
    {
        $item = $this->getItem($data);
        if(empty($item['id'])){
            $data['id'] = $this->createItem($data);
        } else {
            $data['id'] = $item['id'];
        }
        $this->transBegin();
        
        $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return $data['id'];        
    }
    public function textToSentences ($data)
    {
        $result = [];
        if($data['is_done']){
            return $data;
        }
        $sentences = explode("\n", $data['text']);
        foreach($sentences as $index => $sentence){
            if(trim($sentence) !== ''){
                $result[] = [
                    'chapter_id'    => $data['chapter_id'],
                    'text'          => trim($sentence),
                    'index'         => $index,
                    'language_id'   => $data['language_id'],
                    'is_trained'    => false
                ];
                
            }
        }
        return $result;
    }

    
}