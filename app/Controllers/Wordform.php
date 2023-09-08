<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Wordform extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $WordformModel = model('WordformModel');

        $wordform_id = $this->request->getVar('wordform_id');

        $wordform = $WordformModel->getItem($wordform_id);

        if ($wordform == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($wordform);
    }
    public function getList()
    {
        $WordformModel = model('WordformModel');

        $fields = $this->request->getVar('fields');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $data = [
            'fields' => $fields,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $WordformModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function getTotalRows()
    {
        $WordformModel = model('WordformModel');

        $fields = $this->request->getVar('fields');
        
        $data = [
            'fields' => $fields
        ];
        $result = $WordformModel->getTotalRows($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
