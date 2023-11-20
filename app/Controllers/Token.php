<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Thalamus;
class Token extends BaseController
{
    use ResponseTrait;

    public function getList()
    {
        $TokenModel = model('TokenModel');

        $data = $this->request->getJSON(true);
        
        $result = $TokenModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
