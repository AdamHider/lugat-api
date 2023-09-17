<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Hippocampus;
class Translator extends BaseController
{
    use ResponseTrait;

    public function analyze()
    {
        $Hippocampus = new Hippocampus;

        $data = $this->request->getJSON(true);
        
        $result = $Hippocampus->analyze($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

    public function train()
    {
        $Hippocampus = new Hippocampus;

        $data = $this->request->getJSON(true);
        
        $result = $Hippocampus->remember($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

    public function predict()
    {
        $Hippocampus = new Hippocampus;

        $data = $this->request->getJSON(true);
        
        $result = $Hippocampus->predict($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
