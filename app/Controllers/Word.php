<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Thalamus;
class Word extends BaseController
{
    use ResponseTrait;

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
