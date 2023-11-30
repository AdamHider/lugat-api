<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Libraries\Cerebrum\Thalamus;
class Form extends BaseController
{
    use ResponseTrait;
    
    public function createItemFromLemma()
    {
        $FormModel = model('FormModel');
        $WordModel = model('WordModel');
        
        $lemma = $this->request->getVar('lemma');
        $word_id = $this->request->getVar('word_id');
        $word = $WordModel->getItem(['word_id' => $word_id]);

        $data = [
            'lemma' => $lemma,
            'word' => $word['word'],
            'language_id' => $word['language_id']
        ];
        $form_id = $FormModel->createItemFromLemma($data);
        if ($form_id === 'forbidden') {
            return $this->failForbidden();
        }
        if($FormModel->errors()){
            return $this->failValidationErrors($FormModel->errors());
        }
        return $this->respond(['form_id' => $form_id], 200);
    }
}
