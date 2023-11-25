<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class ChapterModel extends Model
{
    protected $table      = 'lgt_book_chapters';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $allowedFields = [
        'book_id', 
        'number', 
        'title',
        'is_built'
    ];
    
    protected $useTimestamps = false;
        

    public function getList ($data) 
    {

        $TextModel = model('TextModel');
        $this->join('lgt_texts', 'lgt_book_chapters.id = lgt_texts.chapter_id', 'left')
        ->join('lgt_languages', 'lgt_texts.language_id = lgt_languages.id', 'left')
        ->select('lgt_book_chapters.*, COUNT(lgt_texts.id) as total_texts, GROUP_CONCAT(lgt_languages.code) as languages')
        ->where('lgt_book_chapters.book_id', $data['book_id']);
        
        $chapters = $this->groupBy('lgt_book_chapters.id')->get()->getResultArray();
        
        if(empty($chapters)){
            return false;
        }
        foreach($chapters as &$chapter){
            if(!empty($chapter['languages'])) $chapter['languages'] = explode(',', $chapter['languages']);
            $chapter['texts'] = $TextModel->getList(["chapter_id" => $chapter['id']]);
        }
        return $chapters;
    }
    public function getItem ($id) 
    {
        $chapter = $this->where('id', $id)->get()->getRowArray();
        
        if(empty($chapter)){
            return false;
        }
        
        return $chapter;
    }
    public function createItem ($data)
    {
        $this->validationRules = [];
        $data = [
            'book_id' => $data['book_id'], 
            'number' => $data['number'],
            'title' => null,
            'is_built' => 0
        ];
        $this->transBegin();
        $book_id = $this->insert($data, true);

        $this->transCommit();

        return $book_id;        
    }
    public function updateItem ($data)
    {
        $this->transBegin();
        
        $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return $data['id'];        
    }
    public function deleteItem ($data)
    {
        return $this->delete($data);
    }
}