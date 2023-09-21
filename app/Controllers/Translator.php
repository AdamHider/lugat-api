<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Thalamus;
class Translator extends BaseController
{
    use ResponseTrait;

    public function analyze()
    {
        $Thalamus = new Thalamus;

        $data = $this->request->getJSON(true);
        
        $result = $Thalamus->analyze($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

    public function train()
    {
        $Thalamus = new Thalamus;

        $data = $this->request->getJSON(true);
        
        $result = $Thalamus->remember($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

    public function predict()
    {
        $Thalamus = new Thalamus;

        $data = $this->request->getJSON(true);
        
        $result = $Thalamus->predict($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
