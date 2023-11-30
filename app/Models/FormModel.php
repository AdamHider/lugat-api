<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class FormModel extends Model
{
    protected $table      = 'lgt_forms';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $allowedFields = [
        'form', 
        'template', 
        'replace', 
        'language_id'
    ];
    
    protected $useTimestamps = true;
        
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function predictList ($data) 
    {

        $this->select('template, form, replace, LEVENSTEIN(form, '.$this->escape($data['word']).') AS distance');
        
        $forms = $this->where('language_id', $data['language_id'])->having('distance = 0')->get()->getResultArray();
        
        return $forms;
    }
    public function getItem ($data) 
    {
        
        if(isset($data['form'])){
            $this->where('lgt_forms.form', $data['form']);
        }
        
        if(isset($data['replace'])){
            $this->where('lgt_forms.replace', $data['replace']);
        }
        if(isset($data['language_id'])){
            $this->where('lgt_forms.language_id', $data['language_id']);
        }
        
        $form = $this->get()->getRowArray();
        if(empty($form)){
            return false;
        }
        
        return $form;
    }
    public function createItem ($data)
    {
        $book_id = $this->insert($data, true);
        return $book_id;        
    }
    public function updateItem ($data)
    {
        $this->transBegin();
        
        $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return $data['id'];        
    }
    public function deleteItem ($data)
    {
        return $this->delete($data);
    }
    
    public function createItemFromLemma($data)
    {
        $WordModel = model('WordModel');
        helper('Token');
        $word = $WordModel->getItem(['word_id' => $data['word_id']]);

        $data = [
            'lemma' => $data['lemma'],
            'word' => $word['word'],
            'language_id' => $word['language_id']
        ];
        $result = false;
        if( (int) $data['language_id'] === 1){
            $formData = lemmatize($data['lemma'], $data['word'], $data['language_id']);
            $form = $this->getItem($formData);
            if(!empty($form)){
                $result = $form['id'];
            } else {
                $result = $this->createItem($formData); 
            }
        }
        return $result;
    }
}