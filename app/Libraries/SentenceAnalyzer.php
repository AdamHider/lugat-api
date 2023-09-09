<?php
namespace App\Libraries;
class SentenceAnalyzer{
    
    
    protected $skip = ['«', '»', '!', '?', '.', ',', ';', ':', '…', '“'];

    public function analyze($data)
    {
        $result = [];
        $source = $this->utilize($data['source']);
        $target = $this->utilize($data['target']);

        $sourceWords = explode(' ', $source);
        $targetWords = explode(' ', $target);

        $sourceResult = [];
        $targetResult = [];
        foreach($sourceWords as $sourceIndex => &$word){
            if(in_array($word, $this->skip)){
                continue;
            }
            $word = strtolower($word);
            $result[] = [
                'matches'       => $this->findMatches($word, $targetWords),
                'word'          => $word,
                'sourceIndex'   => $sourceIndex
            ];
        }
        return $result;

    }

    private function utilize($sentence)
    {
        $sentence = mb_strtolower($sentence);
        $sentence = str_replace(array("\n", "\r"), '', $sentence);
        foreach($this->skip as $item){
            if((int)$item || strpos($sentence, $item)>-1){
                $sentence = str_replace($item, '', $sentence);
                continue;
            }
        }
        $sentence = str_replace('  ',  ' ',$sentence);
        $sentence = str_replace('ё', 'е', $sentence);
        return $sentence;
    }

    public function findMatches($sourceWord, $targetWords)
    {
        $translations = $this->getTranslations($sourceWord);
        $result = [];
        foreach($targetWords as $sentenceIndex => $word){
            $word = strtolower($word);
            foreach($translations as $translation){
                $translation['target_wordform'] = $this->utilizeWord($translation['target_wordform']);
                if($translation['target_wordform'] === $word){
                    $translation['target_index'] = $sentenceIndex;
                    $result[] = $translation;
                }
            }
        }
        return $result;
    }

    public function getTranslations($word)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT DISTINCT
                wl.word as `source_word`,
                wl.relation_id as `source_relation_id`,
                wfl.word_id as `source_word_id`,
                wfl.wordform as `source_wordform`,
                wl1.word as `target_word`,
                wl1.relation_id as `target_relation_id`,
                wfl1.word_id as `target_word_id`,
                wfl1.wordform as `target_wordform`
            FROM
                lugat_db.lgt_wordform_list wfl
                JOIN
                lugat_db.lgt_word_list wl ON wfl.word_id = wl.word_id
                JOIN
                lugat_db.lgt_word_list wl1 ON wl.denotation_id = wl1.denotation_id AND wl.language_id != wl1.language_id
                JOIN
                lugat_db.lgt_wordform_list wfl1 ON wfl1.word_id = wl1.word_id
            WHERE
                wfl.wordform = '$word'
        ";
        return $db->query($sql)->getResultArray();
    }

    private function utilizeWord($str)
    {
        $str = str_replace('ё', 'е', $str);
        return $str;
    }
}