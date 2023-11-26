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
    protected $allowedFields = [
        'chapter_id', 
        'language_id', 
        'source', 
        'text',
        'is_built'
    ];
    
    protected $useTimestamps = false;
        
    public function getList ($data) 
    {
        
        $this->join('lgt_languages', 'lgt_texts.language_id = lgt_languages.id', 'left')
        ->join('lgt_book_chapters', 'lgt_texts.chapter_id = lgt_book_chapters.id');

        $this->select('lgt_texts.*, lgt_languages.code as language_code, lgt_languages.title as language');

        if(isset($data['book_id'])){
            $this->where(['lgt_book_chapters.book_id' => $data['book_id']]);
        }
        if(isset($data['chapter_id'])){
            $this->where(['lgt_texts.chapter_id' => $data['chapter_id']]);
        }

        $texts = $this->get()->getResultArray();
        if(empty($texts)){
            return [];
        }
        return $texts;
    }
    public function getItem ($data) 
    {
        
        $text = $this->select('*')->where(['chapter_id' => $data['chapter_id'], 'language_id' => $data['language_id']])->get()->getRowArray();

        if(empty($text)){
            return false;
        }
        $text['is_built'] = (bool) $text['is_built'];
        return $text;
    }
    public function createItem ($data)
    {
        $data = [
            'chapter_id' => $data['chapter_id'], 
            'language_id' => $data['language_id'], 
            'text' => ($data['text']) ? $data['text'] : NULL, 
            'is_built' => $data['is_built'], 
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
    public function deleteItem ($data)
    {
        return $this->delete($data);
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