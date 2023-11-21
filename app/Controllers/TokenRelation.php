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
    public function getGroupList()
    {
        $TokenModel = model('TokenModel');
        $TokenRelationModel = model('TokenRelationModel');
        $result = false;
        $data = $this->request->getJSON(true);
        
        $groups = $TokenModel->getGroupList($data);
        if($groups){
            $result = $TokenRelationModel->getList(['groups' => array_column($groups, 'group_id')]);
        }
        
        
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    
    public function saveList()
    {
        $TokenRelationModel = model('TokenRelationModel');

        $data = $this->request->getJSON(true);
        
        foreach($data as &$group){
            $group = array_merge(...$group);
            $TokenRelationModel->saveGroup($group);
        }
        
        return $this->respond(true, 200);
    }

}
