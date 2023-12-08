<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;
use App\Libraries\Cerebrum\Hippocampus;

class SentenceModel extends Model
{
    protected $table      = 'lgt_sentences';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $allowedFields = [
        'language_id', 
        'book_id', 
        'chapter_id',
        'index',
        'sentence', 
        'is_trained',
        'is_skipped'
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getList ($data) 
    {
        if(!empty($data['word'])){
            $this->join('lgt_tokens t', 't.sentence_id = lgt_sentences.id', 'left')
            ->join('lgt_words w', 'w.id = t.word_id', 'left')->select('GROUP_CONCAT(t.char_index) char_idxs, GROUP_CONCAT(w.word) words')
            ->where('w.word', $data['word']);
        }
        if(!empty($data['language_id'])){
            $this->like('lgt_sentences.language_id', $data['language_id']);
        }
        if(isset($data['limit']) && isset($data['offset'])){
            $this->limit($data['limit'], $data['offset']);
        } else {
            $this->limit(0, 0);
        }

        $sentences = $this->select('lgt_sentences.*')->groupBy('lgt_sentences.id')->get()->getResultArray();
        
        if(empty($sentences)){
            return [];
        }
        if(!empty($data['word'])){
            foreach($sentences as &$sentence){
                $sentence['sentence'] = $this->markupTokens($sentence['sentence'], $sentence['char_idxs'], $sentence['words']);
            }
        }
        
        return $sentences;
    }

    public function getPair ($data) 
    {
        $sentencePair = $this->join('lgt_sentences s2', 's2.chapter_id = lgt_sentences.chapter_id AND s2.`index` = lgt_sentences.`index`')
        ->select('lgt_sentences.id as source_id, lgt_sentences.sentence as source_text, s2.id as target_id, s2.sentence as target_text')
        ->where(' lgt_sentences.is_trained = 0 AND lgt_sentences.is_skipped = 0 AND lgt_sentences.language_id = '.$data['source_language_id'].' AND s2.language_id = '.$data['target_language_id'])
        ->orderBy('LENGTH(lgt_sentences.sentence)')
        ->limit(1)->get()->getRowArray();
        
        return $sentencePair;
    }
    public function getPairList ($data) 
    {
        $tokenList = explode(' ', $data['token']);
        $sentenceGroups = $this->join('lgt_tokens t', 'lgt_sentences.id = t.sentence_id')
        ->join('lgt_words w', 'w.id = t.word_id AND w.word IN ("'.implode('","',$tokenList).'")')
        ->join('lgt_token_relations tr', 't.id = tr.token_id')
        ->join('lgt_token_relations tr1', 'tr.group_id = tr1.group_id')
        ->join('lgt_tokens t1', 't1.id = tr1.token_id')
        ->join('lgt_sentences s1', 's1.id = t1.sentence_id  AND s1.language_id = '.$data['target_language_id'])
        ->join('lgt_words w1', 'w1.id = t1.word_id AND w1.language_id = '.$data['target_language_id'])
        ->select("
            lgt_sentences.sentence source_sentence, GROUP_CONCAT(t.char_index) source_char_idxs, GROUP_CONCAT(w.word) source_words, lgt_sentences.language_id source_language,
            s1.sentence target_sentence, GROUP_CONCAT(t1.char_index) target_char_idxs, GROUP_CONCAT(w1.word) target_words, s1.language_id target_language
        ")
        ->like('lgt_sentences.sentence', $data['token'])
        ->where("lgt_sentences.language_id = ".$data['source_language_id'])
        ->groupBy('lgt_sentences.id')->get()->getResultArray();

        $result = [];
        if(!empty($sentenceGroups)){
            $result = $this->markupList($sentenceGroups);
        }

        return $result;
    }
    private function markupList($sentenceGroups)
    {
        $result = [];
        foreach($sentenceGroups as $group){
            $result[] = [
                'source_sentence' => $this->markupTokens($group['source_sentence'], $group['source_char_idxs'], $group['source_words']),
                'target_sentence' => $this->markupTokens($group['target_sentence'], $group['target_char_idxs'], $group['target_words'])
            ];
        }
        return $result;
    }

    private function markupTokens($sentence, $char_idxs, $words)
    {
        $wrapTags = [
            'start' => '<::><s>',
            'end' => '<::>'
        ];
        $sentenceDiff = 0;
        $indexes = array_combine(explode(',', $char_idxs), explode(',', $words));
        ksort($indexes);
        foreach($indexes as $index => $word){
            $sentence = substr_replace($sentence, $wrapTags['start'], $index+$sentenceDiff, 0);
            $sentenceDiff += strlen($wrapTags['start']);
            $sentence = substr_replace($sentence, $wrapTags['end'], ($index + strlen($word))+$sentenceDiff, 0);
            $sentenceDiff += strlen($wrapTags['end']);
        }
        return $sentence;
    }

    
    public function createItem ($data)
    {
        $this->transBegin();
        $sentence_id = $this->insert($data, true);
        $this->transCommit();

        return $sentence_id;        
    }
    public function updateItem ($data)
    {
        $this->transBegin();
        
        $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return $data['id'];        
    }
    public function forgetAll()
    {
        $db = \Config\Database::connect();
        $db->query("TRUNCATE lgt_words");
        $db->query("TRUNCATE lgt_token_relations");
        $db->query("TRUNCATE lgt_sentences");
        $db->query("TRUNCATE lgt_tokens");
        return;
    }

    public function getSentenceTokens($sentence_id)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT * 
            FROM lgt_words d
            JOIN lgt_tokens p ON d.id = p.token_id  
            WHERE p.sentence_id = $sentence_id
            ORDER BY `index` ASC
        ";
        return $db->query($sql)->getResultArray();
    }
}