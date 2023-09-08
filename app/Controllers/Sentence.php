<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Sentence extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $SentenceModel = model('SentenceModel');

        $Sentence_id = $this->request->getVar('Sentence_id');

        $Sentence = $SentenceModel->getItem($Sentence_id);

        if ($Sentence == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($Sentence);
    }
    public function getList()
    {
        $SentenceModel = model('SentenceModel');

        $fields = $this->request->getVar('fields');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $data = [
            'fields' => $fields,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $SentenceModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function analyze()
    {
        $SentenceModel = model('SentenceModel');

        $source = $this->request->getVar('source');
        $target = $this->request->getVar('target');
        
        $data = [
            'source' => $source,
            'target' => $target
        ];
        
        $result = $SentenceModel->analyze($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
