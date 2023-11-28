<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Thalamus;
class Word extends BaseController
{
    use ResponseTrait;

    public function getList()
    {
        $WordModel = model('WordModel');

        $filter = $this->request->getVar('filter');
        $lemmaless = $this->request->getVar('lemmaless');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $data = [
            'filter' => $filter,
            'lemmaless' => $lemmaless,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $WordModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function saveItem()
    {
        $WordModel = model('WordModel');
        $data = $this->request->getJSON(true);

        $result = $WordModel->updateItem($data);

        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($WordModel->errors()){
            return $this->failValidationErrors($WordModel->errors());
        }
        return $this->respond($result);
    }
    public function getTranslations()
    {
        $WordModel = model('WordModel');
        $result = false;
        $data = $this->request->getJSON(true);
        
        $result = $WordModel->predictList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
