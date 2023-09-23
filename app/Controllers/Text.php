<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Text extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $TextModel = model('TextModel');

        $text_id = $this->request->getVar('text_id');

        $text = $TextModel->getItem($text_id);

        if ($text == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($text);
    }
    public function getList()
    {
        $TextModel = model('TextModel');

        $fields = $this->request->getVar('fields');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $data = [
            'fields' => $fields,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $TextModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
