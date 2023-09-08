<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class WordformModel extends Model
{
    protected $table      = 'lgt_wordform_list';
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
        
        $this->table('lgt_wordform_list')->select('lgt_wordform_list.*, 
            (SELECT word FROM lgt_word_list WHERE lgt_word_list.word_id = lgt_wordform_list.word_id LIMIT 1) as word,
            (SELECT template FROM lgt_wordform_sets WHERE lgt_wordform_sets.set_configuration_id = lgt_wordform_list.set_configuration_id LIMIT 1) as template'
        );
        
        if(!empty($data['fields']->language_id)){
            $this->where('lgt_wordform_list.language_id', $data['fields']->language_id);
        }
        if(!empty($data['fields']->wordform)){
            $this->like('wordform', $data['fields']->wordform, 'after');
        }
        if(!empty($data['fields']->word)){
            $this->whereIn('word_id', static function (BaseBuilder $builder) use ($data) {
                return $builder->select('word_id')->from('lgt_word_list')->like('word', $data['fields']->word, 'after');
            });
        }
        if(!empty($data['fields']->template)){
            $this->whereIn('set_configuration_id', static function (BaseBuilder $builder) use ($data) {
                return $builder->select('set_configuration_id')->from('lgt_wordform_sets')->like('template', $data['fields']->template, 'both');
            });
        }

        

        $wordforms = $this->limit($data['limit'], $data['offset'])->orderBy('wordform')->get()->getResultArray();
        
        if(empty($wordforms)){
            return false;
        }
        /*
        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }*/
        return $wordforms;
    }
    public function getItem ($wordform_id) 
    {

        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        

        

        $wordform = $this->join('lgt_word_list', 'lgt_word_list.word_id = lgt_wordform_list.word_id')
        ->join('lgt_wordform_sets', 'lgt_wordform_sets.set_configuration_id = lgt_wordform_list.set_configuration_id')
        ->select('lgt_wordform_list.*, lgt_wordform_sets.template, lgt_word_list.word')
        ->where('wordform_id', $wordform_id)->get()->getRowArray();
        
        if(empty($wordform)){
            return false;
        }
        /*
        foreach($achievements as &$achievement){
            $achievement = array_merge($achievement, $DescriptionModel->getItem('achievement', $achievement['id']));
            $achievement['image'] = base_url('image/' . $achievement['image']);
            $achievement['progress'] = $this->calculateProgress($achievement);
        }*/
        return $wordform;
    }


    public function getTotalRows ($data) 
    {

        if(!empty($data['fields']->language_id)){
            $this->where('lgt_wordform_list.language_id', $data['fields']->language_id);
        }
        if(!empty($data['fields']->wordform)){
            $this->like('wordform', $data['fields']->wordform, 'after');
        }
        if(!empty($data['fields']->word)){
            $this->whereIn('word_id', static function (BaseBuilder $builder) use ($data) {
                return $builder->select('word_id')->from('lgt_word_list')->like('word', $data['fields']->word, 'after');
            });
        }
        if(!empty($data['fields']->template)){
            $this->whereIn('set_configuration_id', static function (BaseBuilder $builder) use ($data) {
                return $builder->select('set_configuration_id')->from('lgt_wordform_sets')->like('template', $data['fields']->template, 'both');
            });
        }

        $totalRows = $this->select('COUNT(*) as total')->get()->getRowArray();

        if(empty($totalRows)){
            return false;
        }

        return $totalRows;
    }
    
}