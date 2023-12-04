<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class BookModel extends Model
{
    protected $table      = 'lgt_books';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'author', 
        'title', 
        'year', 
        'language_id', 
        'is_built'
    ];
    
    protected $useTimestamps = true;
        
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getList ($data) 
    {
        $this->table('lgt_books')
        ->join('lgt_book_chapters', 'lgt_book_chapters.book_id = lgt_books.id', 'left')
        ->select('lgt_books.*, COUNT(lgt_book_chapters.id) as chapters');
        
        if(!empty($data['filter']->search)){
            $this->like('lgt_books.title', $data['filter']->search);
            $this->orLike('lgt_books.author', $data['filter']->search);   
            $this->orLike('lgt_books.year', $data['filter']->search); 
        }
        if(isset($data['limit']) && isset($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        } else {
            $this->limit(0, 0);
        }

        $books = $this->groupBy('lgt_books.id')->orderBy('title')->get()->getResultArray();
        
        if(empty($books)){
            return [];
        }
        return $books;
    }
    public function getItem ($data) 
    {
        
        $ChapterModel = model('ChapterModel');
        if(!empty($data['book_id'])){
            $this->where('lgt_books.id', $data['book_id'])
            ->select('lgt_books.*'); 
        }
        if(!empty($data['filter']['chapter_id'])){
            $this->join('lgt_book_chapters', 'lgt_book_chapters.book_id = lgt_books.id')->where('lgt_book_chapters.id', $data['filter']['chapter_id'])
            ->select('lgt_books.*, lgt_book_chapters.number as chapter'); 
        }
        $book = $this->get()->getRowArray();
        if(empty($book)){
            return false;
        }
        $book['is_built'] = (bool) $book['is_built'];
        $book['chapters'] = $ChapterModel->getList(['book_id' => $book['id']]);
        return $book;
    }
    
    public function createItem ($data)
    {
        $this->validationRules = [];
        $data = [
            'title' => $data['title'], 
            'author' => $data['author'], 
            'year' => $data['year'], 
            'language_id' => null,
            'is_built' => false
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