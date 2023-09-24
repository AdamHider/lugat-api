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
        'chapter_id', 
        'language_id', 
        'text'
    ];
    
    protected $useTimestamps = false;
        

    public function getList ($data) 
    {
        $this->table('lgta_texts')->select('lgta_texts.*');
        
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
    public function getItem ($data) 
    {
        $text = $this->select('*')->where(['chapter_id' => $data['chapter_id'], 'language_id' => $data['language_id']])->get()->getRowArray();
        

        if(empty($text)){
            return false;
        }
        return $text;
    }
    public function createItem ($data)
    {
        $data = [
            'chapter_id' => $data['chapter_id'], 
            'language_id' => $data['language_id'], 
            'text' => ($data['text']) ? $data['text'] : NULL, 
        ];
        $this->transBegin();
        $text_id = $this->insert($data, true);

        $this->transCommit();

        return $text_id;        
    }
    public function updateItem ($data)
    {
        $item = $this->getItem($data);
        if(empty($item['id'])){
            $data['id'] = $this->createItem($data);
        } else {
            $data['id'] = $item['id'];
        }
        $this->transBegin();
        
        $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return $data['id'];        
    }
}