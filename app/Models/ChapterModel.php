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
        'title'
    ];
    
    protected $useTimestamps = false;
        

    public function getList ($data) 
    {

        $this->table('lgta_book_chapters')->select('lgta_book_chapters.*')->where('lgta_book_chapters.book_id', $data['book_id']);
        
        $chapters = $this->get()->getResultArray();
        
        if(empty($chapters)){
            return false;
        }
        /*
        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }*/
        return $chapters;
    }
    public function getItem ($chapter_id) 
    {

        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        $chapter = $this->join('lgt_word_list', 'lgt_word_list.word_id = lgta_book_chapters.word_id')
        ->join('lgt_chapter_sets', 'lgt_chapter_sets.set_configuration_id = lgta_book_chapters.set_configuration_id')
        ->select('lgta_book_chapters.*, lgt_chapter_sets.template, lgt_word_list.word')
        ->where('chapter_id', $chapter_id)->get()->getRowArray();
        

        if(empty($chapter)){
            return false;
        }
        $omonymsFilter = (object) array('chapter' => $chapter['chapter']);
        $chapter['omonyms'] = $this->getList(['fields' => $omonymsFilter]);
        /*
        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }*/
        return $chapter;
    }
    public function createItem ($data)
    {
        $this->validationRules = [];
        $data = [
            'book_id' => $data['book_id'], 
            'number' => $data['number'],
            'title' => null
        ];
        $this->transBegin();
        $book_id = $this->insert($data, true);

        $this->transCommit();

        return $book_id;        
    }
}