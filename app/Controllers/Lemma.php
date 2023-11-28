<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Thalamus;
class Lemma extends BaseController
{
    use ResponseTrait;

    public function getTranslations()
    {
        $LemmaModel = model('LemmaModel');
        $result = false;
        $data = $this->request->getJSON(true);
        
        $result = $LemmaModel->predictList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function saveItem()
    {
        $LemmaModel = model('LemmaModel');
        $data = $this->request->getJSON(true);
        if(isset($data['id'])){
            $result = $LemmaModel->updateItem($data);
        } else {
            $result = $LemmaModel->createItem($data);
        }
        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($LemmaModel->errors()){
            return $this->failValidationErrors($LemmaModel->errors());
        }
        return $this->respond($result);
    }
    public function autocomplete()
    {
        $LemmaModel = model('LemmaModel');

        $filter = $this->request->getVar('filter');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $data = [
            'filter' => $filter,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $LemmaModel->autocomplete($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function lemmatize()
    {
        $LemmaModel = model('LemmaModel');
        $FormModel = model('FormModel');

        $lemma = $this->request->getVar('lemma');
        $word = $this->request->getVar('word');
        $language_id = $this->request->getVar('language_id');
        $data = [
            'lemma' => $lemma,
            'word' => $word,
            'language_id' => $language_id
        ];
        if( (int) $language_id === 1){
            $form = $LemmaModel->lemmatize($data);
            if($form){
                $result = $FormModel->createItem($form);
            }
        }
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

    
}
