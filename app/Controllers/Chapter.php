<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Chapter extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $ChapterModel = model('ChapterModel');

        $chapter_id = $this->request->getVar('chapter_id');

        $chapter = $ChapterModel->getItem($chapter_id);

        if ($chapter == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($chapter);
    }
    public function getList()
    {
        $ChapterModel = model('ChapterModel');

        $book_id = $this->request->getVar('book_id');
        $data = [
            'book_id' => $book_id
        ];
        $result = $ChapterModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

    public function createItem()
    {
        $ChapterModel = model('ChapterModel');

        $book_id = $this->request->getVar('book_id');
        $number = $this->request->getVar('number');
        $data = [
            'book_id' => $book_id,
            'number' => $number
        ];
        $chapter_id = $ChapterModel->createItem($data);

        if ($book_id === 'forbidden') {
            return $this->failForbidden();
        }

        if($ChapterModel->errors()){
            return $this->failValidationErrors($ChapterModel->errors());
        }

        return $this->respond($chapter_id);
    }
    public function deleteItem()
    {
        $ChapterModel = model('ChapterModel');
        $TextModel = model('TextModel');
        $id = $this->request->getVar('id');

        $result = $ChapterModel->deleteItem(['id'=>$id]);
        if($result){
            $TextModel->deleteItem(['chapter_id' => $id]);
        }

        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($ChapterModel->errors()){
            return $this->failValidationErrors($ChapterModel->errors());
        }
        return $this->respond($result);
    }
}
