<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Text extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        $TextModel = model('TextModel');

        $chapter_id = $this->request->getVar('chapter_id');
        $language_id = $this->request->getVar('language_id');
        $data = [
            'chapter_id' => $chapter_id,
            'language_id' => $language_id
        ];
        $result = $TextModel->getItem($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function getList()
    {
        $TextModel = model('TextModel');

        $chapter_id = $this->request->getVar('chapter_id');
        $language_id = $this->request->getVar('language_id');
        $data = [
            'chapter_id' => $chapter_id,
            'language_id' => $language_id
        ];
        $result = $TextModel->getItem($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }
    public function saveItem()
    {
        $TextModel = model('TextModel');
        $data = $this->request->getJSON(true);

        $result = $TextModel->updateItem($data);

        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($TextModel->errors()){
            return $this->failValidationErrors($TextModel->errors());
        }
        return $this->respond($result);
    }

    public function exportItem()
    {
        $TextModel = model('TextModel');
        $data = $this->request->getJSON(true);

        $result = $TextModel->textToSentences($data);
        
        print_r($result);
        die;
        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($TextModel->errors()){
            return $this->failValidationErrors($TextModel->errors());
        }
        return $this->respond($result);
    }
    

}
