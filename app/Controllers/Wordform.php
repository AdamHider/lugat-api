<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Wordform extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $UserModel = model('UserModel');

        $user_id = $this->request->getVar('user_id');

        if( !$user_id ){
            $user_id = session()->get('user_id');
        }

        $user = $UserModel->getItem($user_id);

        if ($user == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($user);
    }
    public function getList()
    {
        $WordformModel = model('WordformModel');

        $language_id = $this->request->getVar('language_id');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [
            'language_id' => $language_id,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $WordformModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function getTotalRows()
    {
        $WordformModel = model('WordformModel');

        $result = $WordformModel->getTotalRows();
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
