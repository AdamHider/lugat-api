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
        'is_trained'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';



    public function getPair ($data) 
    {

        /*
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_usermap', 'achievements_usermap.item_id = achievements.id')
            ->where('achievements_usermap.user_id', $data['user_id']);
        }*/
        $sentencePair = $this->join('lgt_sentences s2', 's2.chapter_id = lgt_sentences.chapter_id AND s2.`index` = lgt_sentences.`index`')
        ->select('lgt_sentences.id as source_id, lgt_sentences.sentence as source_text, s2.id as target_id, s2.sentence as target_text')
        ->where(' lgt_sentences.is_trained = 0 AND lgt_sentences.language_id = '.$data['source_language_id'].' AND s2.language_id = '.$data['target_language_id'])
        ->limit(1)->get()->getRowArray();
        

        return $sentencePair;
    }
    public function getPairList ($data) 
    {
        
        $sentenceGroups = $this->join('lgt_tokens t', 'lgt_sentences.id = t.sentence_id')
        ->join('lgt_words w', 'w.id = t.word_id')
        ->join('lgt_token_relations tr', 't.id = tr.token_id')
        ->join('lgt_token_relations tr1', 'tr.group_id = tr1.group_id')
        ->join('lgt_tokens t1', 't1.id = tr1.token_id')
        ->join('lgt_sentences s1', 's1.id = t1.sentence_id  AND s1.language_id = '.$data['target_language_id'])
        ->join('lgt_words w1', 'w1.id = t1.word_id AND w1.language_id = '.$data['target_language_id'])
        ->select("
            lgt_sentences.sentence source_sentence, GROUP_CONCAT(DISTINCT t.`index`) source_idxs, GROUP_CONCAT(DISTINCT w.word) source_words, lgt_sentences.language_id source_language,
            s1.sentence target_sentence, GROUP_CONCAT(DISTINCT t1.`index`) target_idxs, GROUP_CONCAT(DISTINCT w1.word) target_words, s1.language_id target_language
        ")
        ->where("w.word = ".$this->escape($data['token'])." AND w.language_id = ".$data['source_language_id'])
        ->groupBy('tr.group_id')->get()->getResultArray();

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
            $sourceIndexes = explode(',', $group['source_idxs']);
            $sourceWords = explode(',', $group['source_words']);
            $sourceTokenized = explode(' ', $group['source_sentence']);
            foreach($sourceIndexes as $sourceIndex){
                $sourceTokenized[$sourceIndex] = '<b>'.$sourceTokenized[$sourceIndex].'</b>';
            }

            $targetIndexes = explode(',', $group['target_idxs']);
            $targetWords = explode(',', $group['target_words']);
            $targetTokenized = explode(' ', $group['target_sentence']);
            foreach($targetIndexes as $targetIndex){
                $targetTokenized[$targetIndex] = '<b>'.$targetTokenized[$targetIndex].'</b>';
            }
            
            $result[] = [
                'source_sentence' => implode(' ', $sourceTokenized),
                'target_sentence' => implode(' ', $targetTokenized)
            ];
            
        }
        return $result;
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