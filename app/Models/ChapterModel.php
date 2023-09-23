<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class ChapterModel extends Model
{
    protected $table      = 'lgta_chapters';
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
        
        $this->table('lgta_chapters')->select('lgta_chapters.*, 
            (SELECT word FROM lgt_word_list WHERE lgt_word_list.word_id = lgta_chapters.word_id LIMIT 1) as word,
            (SELECT template FROM lgt_chapter_sets WHERE lgt_chapter_sets.set_configuration_id = lgta_chapters.set_configuration_id LIMIT 1) as template'
        );
        
        if(!empty($data['fields']->language_id)){
            $this->where('lgta_chapters.language_id', $data['fields']->language_id);
        }
        if(!empty($data['fields']->chapter)){
            $this->like('chapter', $data['fields']->chapter, 'after');
        }
        if(!empty($data['fields']->word)){
            $this->whereIn('word_id', static function (BaseBuilder $builder) use ($data) {
                return $builder->select('word_id')->from('lgt_word_list')->like('word', $data['fields']->word, 'after');
            });
        }
        if(!empty($data['fields']->template)){
            $this->whereIn('set_configuration_id', static function (BaseBuilder $builder) use ($data) {
                return $builder->select('set_configuration_id')->from('lgt_chapter_sets')->like('template', $data['fields']->template, 'both');
            });
        }

        if(!empty($data['limit']) && !empty($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        }

        $chapters = $this->orderBy('chapter')->get()->getResultArray();
        
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
        $chapter = $this->join('lgt_word_list', 'lgt_word_list.word_id = lgta_chapters.word_id')
        ->join('lgt_chapter_sets', 'lgt_chapter_sets.set_configuration_id = lgta_chapters.set_configuration_id')
        ->select('lgta_chapters.*, lgt_chapter_sets.template, lgt_word_list.word')
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
}