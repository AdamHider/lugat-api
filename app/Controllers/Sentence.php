<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Sentence extends BaseController
{
    use ResponseTrait;

    

    public function getPair()
    {

        
        $SentenceModel = model('SentenceModel');

        $result = $SentenceModel->getPair();

        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($result);
    }

}
