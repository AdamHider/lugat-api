<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Language extends BaseController
{
    use ResponseTrait;

    public function getList()
    {
        $LanguageModel = model('LanguageModel');

        $data = $this->request->getJSON(true);
        $result = $LanguageModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    
}
