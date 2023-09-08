<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class WordModel extends Model
{
    protected $table      = 'lgt_wordform_list';
    protected $primaryKey = 'wordform_id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'wordform_id', 
        'wordform', 
        'is_disabled', 
        'word_id', 
        'set_configuration_id'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';


    public function getList ($data) 
    {

        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        
        $this->table('lgt_wordform_list')->select('wordform_id, lgt_wordform_list.is_disabled, wordform, word, template, lgt_wordform_list.set_configuration_id, lgt_wordform_list.word_id')
        ->join('lgt_word_list', 'lgt_word_list.word_id = lgt_wordform_list.word_id')
        ->join('lgt_wordform_sets', 'lgt_wordform_sets.set_configuration_id = lgt_wordform_list.set_configuration_id');
        
        if(!empty($data['fields']->language_id)){
            $this->where('lgt_wordform_list.language_id', $data['fields']->language_id);
        }
        if(!empty($data['fields']->wordform)){
            $this->where('lgt_wordform_list.wordform', $data['fields']->wordform);
        }
        if(!empty($data['fields']->word)){
            $this->where('lgt_word_list.word', $data['fields']->word);
        }

        if(!empty($data['fields']->template)){
            $this->where('(SELECT template FROM lgt_wordform_sets WHERE lgt_wordform_sets.set_configuration_id = lgt_wordform_list.set_configuration_id LIMIT 1)', $data['fields']->template);
        }
        $wordforms = $this->limit($data['limit'], $data['offset'])->groupBy('wordform_id, lgt_wordform_list.is_disabled, wordform, word, template')->get()->getResultArray();
        
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
    public function calculateProgress($data)
    {
        if ($data['code'] == 'total_lessons') {
            $current_progress = session()->get('user_data')['dashboard']['total_exercises'];
        } else
        if ($data['code'] == 'total_points') {
            $current_progress = session()->get('user_data')['dashboard']['total_points'];
        } else 
        if ($data['code'] == 'total_classrooms') {
            $current_progress = session()->get('user_data')['dashboard']['total_classrooms'];
        } else {
            $current_progress = 0;
        }
        return [
            'current' => $current_progress,
            'target' => $data['value'],
            'percentage' => ceil($current_progress * 100 / $data['value']),
            'is_done' => $current_progress >=  $data['value']
        ];
        
    }

    public function getTotalRows () 
    {

        $totalRows = $this->countAll();
        
        if(empty($totalRows)){
            return false;
        }

        return $totalRows;
    }
    
}