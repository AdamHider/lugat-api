<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class TextModel extends Model
{
    protected $table      = 'lgta_texts';
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
        
        $this->table('lgta_texts')->select('lgta_texts.*, 
            (SELECT word FROM lgt_word_list WHERE lgt_word_list.word_id = lgta_texts.word_id LIMIT 1) as word,
            (SELECT template FROM lgt_text_sets WHERE lgt_text_sets.set_configuration_id = lgta_texts.set_configuration_id LIMIT 1) as template'
        );
        
        if(!empty($data['fields']->language_id)){
            $this->where('lgta_texts.language_id', $data['fields']->language_id);
        }
        if(!empty($data['fields']->text)){
            $this->like('text', $data['fields']->text, 'after');
        }
        if(!empty($data['fields']->word)){
            $this->whereIn('word_id', static function (BaseBuilder $builder) use ($data) {
                return $builder->select('word_id')->from('lgt_word_list')->like('word', $data['fields']->word, 'after');
            });
        }
        if(!empty($data['fields']->template)){
            $this->whereIn('set_configuration_id', static function (BaseBuilder $builder) use ($data) {
                return $builder->select('set_configuration_id')->from('lgt_text_sets')->like('template', $data['fields']->template, 'both');
            });
        }

        if(!empty($data['limit']) && !empty($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        }

        $texts = $this->orderBy('text')->get()->getResultArray();
        
        if(empty($texts)){
            return false;
        }
        /*
        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }*/
        return $texts;
    }
    public function getItem ($text_id) 
    {

        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        $text = $this->join('lgt_word_list', 'lgt_word_list.word_id = lgta_texts.word_id')
        ->join('lgt_text_sets', 'lgt_text_sets.set_configuration_id = lgta_texts.set_configuration_id')
        ->select('lgta_texts.*, lgt_text_sets.template, lgt_word_list.word')
        ->where('text_id', $text_id)->get()->getRowArray();
        

        if(empty($text)){
            return false;
        }
        $omonymsFilter = (object) array('text' => $text['text']);
        $text['omonyms'] = $this->getList(['fields' => $omonymsFilter]);
        /*
        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }*/
        return $text;
    }
}