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
        'image'
    ];
    
    protected $useTimestamps = false;
        

    public function getList ($data) 
    {

        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        
        $this->table('lgta_books')->select('lgta_books.*');
        
        if(!empty($data['filter'])){
            $this->like('title', $data['filter']);
            $this->orLike('author', $data['filter']);   
            $this->orLike('year', $data['filter']); 
        }
        if(!empty($data['limit']) && !empty($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        }

        $books = $this->orderBy('title')->get()->getResultArray();
        
        if(empty($books)){
            return false;
        }
        /*
        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }*/
        return $books;
    }
    public function getItem ($book_id) 
    {

        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        $book = $this->join('lgt_word_list', 'lgt_word_list.word_id = lgta_books.word_id')
        ->join('lgt_book_sets', 'lgt_book_sets.set_configuration_id = lgta_books.set_configuration_id')
        ->select('lgta_books.*, lgt_book_sets.template, lgt_word_list.word')
        ->where('book_id', $book_id)->get()->getRowArray();
        

        if(empty($book)){
            return false;
        }
        $omonymsFilter = (object) array('book' => $book['book']);
        $book['omonyms'] = $this->getList(['fields' => $omonymsFilter]);
        /*
        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }*/
        return $book;
    }
}