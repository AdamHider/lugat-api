<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class ChapterModel extends Model
{
    protected $table      = 'lgta_book_chapters';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'book_id', 
        'number', 
        'title',
        'is_exported'
    ];
    
    protected $useTimestamps = false;
        

    public function getList ($data) 
    {

        $this->join('lgta_texts', 'lgta_book_chapters.id = lgta_texts.chapter_id', 'left')
        ->select('lgta_book_chapters.*, COUNT(lgta_texts.id) as total_texts')->where('lgta_book_chapters.book_id', $data['book_id']);
        
        $chapters = $this->groupBy('lgta_book_chapters.id')->get()->getResultArray();
        
        if(empty($chapters)){
            return false;
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
            'is_exported' => 0
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
}