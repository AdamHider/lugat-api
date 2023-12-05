<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class WordModel extends Model
{
    protected $table      = 'lgt_words';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $allowedFields = [
        'lemma_id',
        'form_id',
        'word', 
        'language_id'
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem($data)
    {
        
        $this->join('lgt_languages', 'lgt_words.language_id = lgt_languages.id', 'left');

        if(!empty($data['word_id'])){
            $this->where('lgt_words.id', $data['word_id']); 
        }
        if(!empty($data['filter']['word'])){
            $this->where('lgt_words.word', $this->escape($data['filter']['word']));
        }
        if(!empty($data['filter']['language_id'])){
            $this->where('lgt_words.language_id', $data['filter']['language_id']);
        }

        $word = $this->select('lgt_words.*, lgt_languages.title as language')->get()->getRowArray();

        if(empty($word)){
            return false;
        }
        return $word;
    }
    public function getList ($data) 
    {
        $this->select('lgt_words.*');
        
        if(!empty($data['word'])){
            $this->where('lgt_words.word', $this->escape($data['word']));
        }
        if(isset($data['filter']['word'])){
            $this->like('lgt_words.word', $data['filter']['word']);
        }
        if(!empty($data['language_id'])){
            $this->where('lgt_words.language_id', $this->escape($data['language_id']));
        }
        if(isset($data['lemmaless'])){
            $this->join('lgt_word_forms wf', 'wf.word_id = lgt_words.id', 'left');
            $this->where('wf.lemma_id IS NULL');
        }
        if(isset($data['limit']) && isset($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        } else {
            $this->limit(0, 0);
        }

        $words = $this->orderBy('word')->get()->getResultArray();
        
        if(empty($words)){
            return [];
        }
        return $words;
    }
    
    public function createItem ($data)
    {
        $this->validationRules = [];
        $this->transBegin();
        $word_id = $this->insert($data, true);

        $this->transCommit();

        return $word_id;        
    }
    public function updateItem ($data)
    {
        $this->transBegin();
        
        $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return $data['id'];        
    }
    public function predictList ($data)
    {
        $db = db_connect();
        $tokenList = explode(' ', $data['token']);
        $subquery = $db->table('lgt_sentences s')
        ->join('lgt_tokens t', 's.id = t.sentence_id')
        ->join('lgt_words w', 'w.id = t.word_id AND w.word IN ("'.implode('","',$tokenList).'")')
        ->join('lgt_token_relations tr', 't.id = tr.token_id')
        ->join('lgt_token_relations tr1', 'tr.group_id = tr1.group_id')
        ->join('lgt_tokens t1', 't1.id = tr1.token_id')
        ->join('lgt_words w1', 'w1.id = t1.word_id AND w1.language_id = '.$data['target_language_id'])
        ->select("GROUP_CONCAT(DISTINCT w1.word  ORDER BY t1.`index` SEPARATOR ' ') AS word, COUNT(t1.id) AS freq")
        ->like('s.sentence', $data['token'])
        ->where("s.language_id = ".$data['source_language_id'])
        ->groupBy('tr.id');
        $builder = $db->newQuery()->fromSubquery($subquery, 'q');
        $result = $builder
        ->select("q.word, SUM(q.freq) as freq")
        ->groupBy('q.word')
        ->orderBy('freq DESC')->get()->getResultArray();
        print_r($this->getLastQuery());
        die;
        return $result;        
    }
    public function linkLemmas ($data)
    {
        $WordFormModel = model('WordFormModel');
        $FormModel = model('FormModel');
        if(empty($data['word_id']) || empty($data['lemmas'])){
            return false;
        }
        foreach($data['lemmas'] as $lemma){
            $wordForm = $WordFormModel->getItem(['word_id' => $data['word_id'], 'lemma_id' => $lemma['id']]);
            if(!empty($wordForm)){
                continue;
            }
            $form_id = $FormModel->createItemFromLemma(['word_id' => $data['word_id'], 'lemma' => $lemma['lemma']]);
            
            $WordFormModel->createItem([
                'word_id' => $data['word_id'], 
                'lemma_id' => $lemma['id'],
                'form_id' => $form_id
            ]);
        }
        return true;
    }
}