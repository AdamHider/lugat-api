<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Thalamus;
class TokenRelation extends BaseController
{
    use ResponseTrait;

    public function getList()
    {
        $TokenRelationModel = model('TokenRelationModel');

        $data = $this->request->getJSON(true);
        
        $result = $TokenRelationModel->getList($data);
        
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
