<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class BookModel extends Model
{
    protected $table      = 'lgta_books';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'relation_id', 
        'author', 
        'title', 
        'year', 
        'language_id', 
        'status'
    ];
    
    protected $useTimestamps = false;
        

    public function getList ($data) 
    {

        $ChapterModel = model('ChapterModel');
        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        
        $this->table('lgta_books')
        ->join('lgta_book_chapters', 'lgta_book_chapters.book_id = lgta_books.id', 'left')
        ->select('lgta_books.*, COUNT(lgta_book_chapters.id) as chapters');
        
        if(!empty($data['filter']->search)){
            $this->like('lgta_books.title', $data['filter']->search);
            $this->orLike('lgta_books.author', $data['filter']->search);   
            $this->orLike('lgta_books.year', $data['filter']->search); 
        }
        if(!empty($data['limit']) && !empty($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        } else {
            $this->limit(0, 0);
        }

        $books = $this->groupBy('lgta_books.id')->orderBy('title')->get()->getResultArray();
        
        if(empty($books)){
            return false;
        }
        foreach($books as &$book){
            $book['chapters'] = $ChapterModel->getList(['book_id' => $book['id']]);
        }
        return $books;
    }
    public function getItem ($data) 
    {
        
        if(!empty($data['book_id'])){
            $this->where('lgta_books.id', $data['book_id'])
            ->select('lgta_books.*, lgt_book_sets.template, lgt_word_list.word'); 
        }
        if(!empty($data['filter']->chapter_id)){
            $this->join('lgta_book_chapters', 'lgta_book_chapters.book_id = lgta_books.id')->where('lgta_book_chapters.id', $data['filter']->chapter_id)
            ->select('lgta_books.*, lgta_book_chapters.number as chapter, lgta_book_chapters.is_exported as chapter_exported'); 
        }
        $book = $this->get()->getRowArray();
        if(empty($book)){
            return false;
        }
        return $book;
    }
    
    public function createItem ($data)
    {
        $this->validationRules = [];
        $data = [
            'relation_id' => null,
            'title' => $data['title'], 
            'author' => $data['author'], 
            'year' => $data['year'], 
            'language_id' => null,
            'status' => 0
        ];
        $this->transBegin();
        $book_id = $this->insert($data, true);

        $this->transCommit();

        return $book_id;        
    }

}