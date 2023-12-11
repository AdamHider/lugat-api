<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Thalamus;
class Word extends BaseController
{
    use ResponseTrait;

    public function getItem()
    {
        $WordModel = model('WordModel');

        $word_id = $this->request->getVar('word_id');
        $filter = $this->request->getVar('filter');
        $data = [
            'filter' => $filter,
            'word_id' => $word_id
        ];

        $book = $WordModel->getItem($data);

        if ($book == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($book);
    }
    public function getList()
    {
        $WordModel = model('WordModel');

        $data = $this->request->getJSON(true);
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

    public function linkLemmas ()
    {
        $WordModel = model('WordModel');
        $result = false;
        $data = $this->request->getJSON(true);

        $result = $WordModel->linkLemmas($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function unlinkLemma ()
    {
        $WordFormModel = model('WordFormModel');
        $result = false;

        $word_id = $this->request->getVar('word_id');
        $lemma_id = $this->request->getVar('lemma_id');
        $data = [
            'word_id' => $word_id,
            'lemma_id' => $lemma_id
        ];

        $result = $WordFormModel->deleteItem($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function autocomplete()
    {
        $WordModel = model('WordModel');

        $filter = $this->request->getVar('filter');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $data = [
            'filter' => $filter,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $WordModel->autocomplete($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
