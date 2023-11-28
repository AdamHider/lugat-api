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
        if($data['id']){
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

}
