<?php

namespace App\Models;

use CodeIgniter\Model;

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
        
        $wordforms = $this->table('lgt_wordform_list')->select('lgt_wordform_list.*, 
            (SELECT word FROM lgt_word_list WHERE lgt_word_list.word_id = lgt_wordform_list.word_id LIMIT 1) as word,
            (SELECT template FROM lgt_wordform_sets WHERE lgt_wordform_sets.set_configuration_id = lgt_wordform_list.set_configuration_id LIMIT 1) as template'
        )->where('lgt_wordform_list.language_id', $data['language_id'])->limit($data['limit'], $data['offset'])->get()->getResultArray();
        
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