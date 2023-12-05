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
        $BookModel = model('BookModel');
        $data = $this->request->getJSON(true);
        if($data['id']){
            $result = $BookModel->updateItem($data);
        } else {
            $result = $BookModel->createItem($data);
        }
        

        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($BookModel->errors()){
            return $this->failValidationErrors($BookModel->errors());
        }
        return $this->respond($result);
    }
    
    public function buildItemStart()
    {

        $BookModel = model('BookModel');
        $TextModel = model('TextModel');
        $book_id = $this->request->getVar('id');

        $texts = $TextModel->getList(['book_id' => $book_id, 'is_built' => false]);
        
        $result = false;

        if(!empty($texts)){
            $result = count($texts);
        }
        if (!$result) {
            return $this->fail($result);
        }
        if($BookModel->errors()){
            return $this->failValidationErrors($BookModel->errors());
        }
        return $this->respond($result);
    }
    public function buildItemProcess()
    {

        $BookModel = model('BookModel');
        $TextModel = model('TextModel');
        $book_id = $this->request->getVar('id');

        $text = $TextModel->getItem(['book_id' => $book_id, 'is_built' => false]);
        
        $result = false;
        
        if(!empty($text)){
            if($text['source']){
                if($this->buildSource($text, $book_id)) $result = $TextModel->updateItem(['id' => $text['id'], 'is_built' => true]);
            }
        } else {
            return $this->failNotFound($result);
        }
        if (!$result) {
            return $this->fail($result);
        }
        if($BookModel->errors()){
            return $this->failValidationErrors($BookModel->errors());
        }
        return $this->respond($result);
    }
    public function buildItemFinish()
    {

        $BookModel = model('BookModel');
        $TextModel = model('TextModel');
        $book_id = $this->request->getVar('id');

        $texts = $TextModel->getList(['book_id' => $book_id, 'is_built' => false]);
        $result = false;
        if(empty($texts)){
           $result = $BookModel->updateItem(['id' => $book_id, 'is_built' => true]);
        }
        if (!$result) {
            return $this->fail($result);
        }
        if($BookModel->errors()){
            return $this->failValidationErrors($BookModel->errors());
        }
        return $this->respond($result);
    }
    public function buildSource($data, $book_id)
    {
        set_time_limit(9000000000);
        ini_set('memory_limit', '1500M'); 
        $SentenceModel = model('SentenceModel');
        helper('Token');
        foreach(file($data['source']) as $lineIndex => $sentence) {
            if(empty(trim($sentence))){
                continue;
            }
            $sentence = clearSentence($sentence);  
            $sentenceId = $SentenceModel->createItem([
                'book_id' => $book_id,
                'chapter_id' => $data['chapter_id'],
                'index' => $lineIndex,
                'sentence' => $sentence,
                'language_id' => $data['language_id'], 
                'is_trained' => false,
                'is_skipped' => false
            ]);
            $this->prepareDict($sentence, $data['language_id'], $sentenceId);
        }
        return true;
    }
    private function prepareDict($sentence, $language_id, $sentenceId)
    {
        helper('Token');
        $WordModel = model('WordModel');
        $TokenModel = model('TokenModel');
        $tokenList = tokenize($sentence);
        foreach($tokenList as $index => &$token){
            $tokenWord = $token[0];
            $tokenCharIndex = $token[1]*1;
            $word = $WordModel->getItem(['filter' => ['word' => $tokenWord, 'language_id' => $language_id]]); 
            $wordId = $word['id'] ?? $WordModel->createItem(['word' => $tokenWord, 'language_id' => $language_id]); 
            $TokenModel->createItem([
                'word_id' => $wordId, 
                'sentence_id' => $sentenceId, 
                'index' => $index,
                'char_index' => $tokenCharIndex]);
        }
        return $tokenList;
    }
}
