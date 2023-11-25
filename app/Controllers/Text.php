<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

use App\Libraries\Transliterator\Transliterator;
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
    public function deleteItem()
    {
        $TextModel = model('TextModel');
        $id = $this->request->getVar('id');

        $result = $TextModel->deleteItem(['id'=>$id]);

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
        $Transliterator = new Transliterator;

        $TextModel = model('TextModel');
        $SentenceModel = model('SentenceModel');
        $ChapterModel = model('ChapterModel');
        $data = $this->request->getJSON(true);

        $text = $TextModel->getItem($data);

        if(empty($text) || empty(trim($data['text'])) || $text['is_exported']){
            return $this->failForbidden();
        }
        if($data['language_id'] == 1 && $Transliterator->checkAlphabet($data['text'], 'crh-cyrl')){

            $data['text'] = $Transliterator->translate($data['text'], 'crh-latn');
        }
        $sentences = $TextModel->textToSentences($data);

        foreach($sentences as $sentence){
            $result = $SentenceModel->createItem($sentence);
        }
        $ChapterModel->updateItem(['id' => $data['chapter_id'], 'is_exported' => 1]);

        $data['is_exported'] = true;
        $TextModel->updateItem($data);

        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($TextModel->errors()){
            return $this->failValidationErrors($TextModel->errors());
        }
        return $this->respond($result);
    }
    

}
