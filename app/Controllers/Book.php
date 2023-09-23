<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Book extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $BookModel = model('BookModel');

        $book_id = $this->request->getVar('Book_id');

        $book = $BookModel->getItem($book_id);

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
}
