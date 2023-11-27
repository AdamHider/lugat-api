<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class LemmaModel extends Model
{
    protected $table      = 'lgt_lemmas';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'id',
        'lemma', 
        'language_id'
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem($word, $languageId)
    {
        $word = $this->where("word = ".$this->escape($word)." AND language_id = ".$languageId)->get()->getRowArray();

        if(empty($word)){
            return false;
        }
        return $word;
    }
    
    public function createItem ($data)
    {
        $this->validationRules = [];
        $this->transBegin();
        $word_id = $this->insert($data, true);

        $this->transCommit();

        return $word_id;        
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
        return $result;        
    }

}