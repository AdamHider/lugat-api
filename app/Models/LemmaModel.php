<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class LemmaModel extends Model
{
    protected $table      = 'lgt_lemmas';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'id',
        'lemma', 
        'language_id'
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem($lemma, $languageId)
    {
        $lemma = $this->where("lemma = ".$this->escape($lemma)." AND language_id = ".$languageId)->get()->getRowArray();

        if(empty($lemma)){
            return false;
        }
        return $lemma;
    }
    public function getList ($data) 
    {
        $this->select('lgt_lemmas.*');
        
        if(!empty($data['word_id'])){
            $this->join('lgt_word_forms wf', 'wf.lemma_id = lgt_lemmas.id', 'left');
            $this->where('wf.word_id', $data['word_id']);
        }

        $lemmas = $this->get()->getResultArray();
        
        if(empty($lemmas)){
            return [];
        }
        return $lemmas;
    }
    
    public function createItem ($data)
    {
        $this->validationRules = [];
        $lemma_id = $this->insert($data, true);
        return $lemma_id;        
    }
    public function updateItem ($data)
    {
        $this->update(['id'=>$data['id']], $data);
        return $data['id'];        
    }
    public function autocomplete ($data) 
    {
        $this->select('lgt_lemmas.*');
        
        if(!empty($data['filter']->lemma)){
            $this->like('lgt_lemmas.lemma', $data['filter']->lemma);
        }
        if(isset($data['limit']) && isset($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        } else {
            $this->limit(0, 0);
        }

        $lemmas = $this->orderBy('lemma')->get()->getResultArray();
        
        if(empty($lemmas)){
            return [];
        }
        return $lemmas;
    }
    public function predictList ($data)
    {
        $FormModel = model('FormModel');
        helper('Token');

        $result = [];

        $exactLemma = $this->getItem($data['word'], $data['language_id']);
        if(!empty($exactLemma)) $result[] = $this->createEmptyItem($exactLemma['id'], $exactLemma['lemma'], $exactLemma['language_id'], 0);
        
        $this->select('*, LEVENSTEIN(lemma, '.$this->escape($data['word']).') AS distance')
        ->where('language_id ='.$data['language_id']);
        if(!empty($exactLemma)){
            $this->where('lemma !='.$this->escape($exactLemma['lemma']));
        }
        $nearLemmas = $this->having('distance = 0')->get()->getResultArray();
        $result = array_merge($result, $nearLemmas);

        if(empty( $result)){
            $forms = $FormModel->predictList($data);
            foreach($forms as $form){
                $lemmaCalculated = unlemmatize($form, $data['word'], $data['language_id']);
                if(empty($lemmaCalculated)){
                    continue;
                }
                $lemma = $this->getItem($lemmaCalculated, $data['language_id']);
                if(empty($lemma)){
                    $lemma = $this->createEmptyItem(null, $lemmaCalculated, $data['language_id'], 1);   
                } else {
                    $lemma['distance'] = 0;
                }
                $result[] = $lemma;
            }
        }
        if(empty($result)){
            $result[] = $this->createEmptyItem(null, $data['word'], $data['language_id'], 2);
        }
        usort($result, function($a, $b) {return strcmp($a['distance'], $b['distance']);});
        return $result;
    }

    private function createEmptyItem($id, $lemma, $language_id, $distance)
    {
        return [
            'id' => $id,
            'lemma' => $lemma,
            'language_id' => $language_id,
            'distance' => $distance
        ];    
    }
}