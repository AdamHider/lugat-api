<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Sentence extends BaseController
{
    use ResponseTrait;
    
    public function getPair()
    {
        $SentenceModel = model('SentenceModel');

        $source_language_id = $this->request->getVar('source_language_id');
        $target_language_id = $this->request->getVar('target_language_id');
        $data = [
            'source_language_id' => $source_language_id,
            'target_language_id' => $target_language_id
        ];

        $result = $SentenceModel->getPair($data);

        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($result);
    }
    public function getPairList()
    {
        $SentenceModel = model('SentenceModel');

        $data = $this->request->getJSON(true);
        
        $result = $SentenceModel->getPairList($data);

        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($result);
    }

    public function setTrained()
    {
        $SentenceModel = model('SentenceModel');

        $id = $this->request->getVar('id');

        $result = $SentenceModel->updateItem(['id' => $id, 'is_trained' => 1]);

        if ($result == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($result);
    }
    
    public function feed()
    {
        set_time_limit(9000000000);
        ini_set('memory_limit', '1500M'); 
        $SentenceModel = model('SentenceModel');
        $SentenceModel->forgetAll();
        $source = 'fra1.txt';
        $sourceLanguageId = 1;
        $targetLanguageId = 2;
        foreach(file($source) as $line) {
            $data_txt = str_getcsv($line, "\t");
            if(!isset($data_txt[0]) || !isset($data_txt[1])){
                continue;
            }
            $group_id = $SentenceModel->getLastSentenceGroupId();
            $sourceSentenceId = $SentenceModel->createSentence($data_txt[0], $sourceLanguageId, $group_id);
            $targetSentenceId = $SentenceModel->createSentence($data_txt[1], $targetLanguageId, $group_id);
            $data = [
                'source' => [
                    'text' => $data_txt[0],
                    'language_id' => $sourceLanguageId,
                    'sentence_id' => $sourceSentenceId
                ],
                'target' => [
                    'text' => $data_txt[1],
                    'language_id' => $targetLanguageId,
                    'sentence_id' => $targetSentenceId
                ]
            ]; 
            //Get First Line of Data over here
            $this->prepareDict($data);
        }
    }
    private function prepareDict($sentencePair)
    {
        helper('Token');
        $WordModel = model('WordModel');
        $TokenModel = model('TokenModel');
        $sourceTokenList = tokenize($sentencePair['source']['text']);
        $targetTokenList = tokenize($sentencePair['target']['text']);
        foreach($sourceTokenList as $sourceIndex => &$sourceToken){
            $word = $WordModel->getItem($sourceToken, $sentencePair['source']['language_id']); 
            if(empty($word['id'])){
                $word = [];
                $word['id'] = $WordModel->createItem(['word' => $sourceToken, 'language_id' => $sentencePair['source']['language_id']]); 
            }
            $TokenModel->createItem([
                'word_id' => $word['id'], 
                'sentence_id' =>  $sentencePair['source']['sentence_id'], 
                'index' => $sourceIndex]);
        }
        foreach($targetTokenList as $targetIndex => &$targetToken){ 
            $word = $WordModel->getItem($targetToken, $sentencePair['target']['language_id']); 
            if(empty($word['id'])){
                $word = [];
                $word['id'] = $WordModel->createItem(['word' => $targetToken, 'language_id' => $sentencePair['target']['language_id']]); 
            } 
            $TokenModel->createItem([
                'word_id' => $word['id'], 
                'sentence_id' =>  $sentencePair['target']['sentence_id'], 
                'index' => $targetIndex]);
        }
        return [$sourceTokenList, $targetTokenList];
    }

}
