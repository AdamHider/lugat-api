<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class FormModel extends Model
{
    protected $table      = 'lgt_forms';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $allowedFields = [
        'form', 
        'template', 
        'replace', 
        'language_id'
    ];
    
    protected $useTimestamps = true;
        
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

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
    public function getItem ($data) 
    {
        
        if(isset($data['form'])){
            $this->where('lgt_forms.form', $data['form']);
        }
        
        if(isset($data['replace'])){
            $this->where('lgt_forms.replace', $data['replace']);
        }
        if(isset($data['language_id'])){
            $this->where('lgt_forms.language_id', $data['language_id']);
        }
        
        $form = $this->get()->getRowArray();
        if(empty($form)){
            return false;
        }
        
        return $form;
    }
    public function createItem ($data)
    {
        $book_id = $this->insert($data, true);
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