<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Thalamus;
class Translator extends BaseController
{
    use ResponseTrait;

    public function analyze()
    {
        $Thalamus = new Thalamus;

        $data = $this->request->getJSON(true);
        
        $result = $Thalamus->analyze($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

    public function train()
    {
        $Thalamus = new Thalamus;

        $data = $this->request->getJSON(true);
        
        $result = $Thalamus->remember($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

    public function predict()
    {
        $Thalamus = new Thalamus;
        $SentenceModel = model('SentenceModel');

        $data = $this->request->getJSON(true);
        
        $sentences = $SentenceModel->searchList($data);

        $result = $Thalamus->markupSentences($data['token'], $sentences, $data['source_language_id'], $data['target_language_id']);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function getTranslations()
    {
        $Thalamus = new Thalamus;

        $data = $this->request->getJSON(true);

        $result = $Thalamus->getTranslations($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function feed()
    {
        $Thalamus = new Thalamus;

        $data = $this->request->getJSON(true);
        
        $result = $Thalamus->feed();
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    
}
