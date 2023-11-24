<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Book extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $BookModel = model('BookModel');

        $book_id = $this->request->getVar('book_id');
        $filter = $this->request->getVar('filter');
        $data = [
            'filter' => $filter,
            'book_id' => $book_id
        ];

        $book = $BookModel->getItem($data);

        if ($book == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($book);
    }
    public function getList()
    {
        $BookModel = model('BookModel');

        $filter = $this->request->getVar('filter');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $data = [
            'filter' => $filter,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $BookModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function saveItem()
    {
        $TextModel = model('TextModel');
        $data = $this->request->getJSON(true);
        if($data['id']){
            $result = $TextModel->updateItem($data);
        } else {
            $result = $TextModel->createItem($data);
        }
        

        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($TextModel->errors()){
            return $this->failValidationErrors($TextModel->errors());
        }
        return $this->respond($result);
    }
}
