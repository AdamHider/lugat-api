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

}
