<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Sentence extends BaseController
{
    use ResponseTrait;
    
    public function getPair()
    {
        $SentenceModel = model('SentenceModel');

        $source_language_id = $this->request->getVar('source_language_id');
        $target_language_id = $this->request->getVar('target_language_id');
        $data = [
            'source_language_id' => $source_language_id,
            'target_language_id' => $target_language_id
        ];

        $result = $SentenceModel->getPair($data);

        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($result);
    }

    public function setTrained()
    {
        $SentenceModel = model('SentenceModel');

        $id = $this->request->getVar('id');

        $result = $SentenceModel->updateItem(['id' => $id, 'is_trained' => 1]);

        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($result);
    }

}
