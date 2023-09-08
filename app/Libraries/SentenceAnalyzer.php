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
            $sourceData = $this->getTranslations($word);
            $sourceData['index'] = $sourceIndex;

            $translations = $this->findWord($word, $targetWords);
            print_r($sourceData);
            print_r($translations);
            die;
            /*
            $chunks = getChunks(trim($word));
            $translated_word = findWord(implode('', $chunks));
            if(!$translated_word){
                $translated_word = morphologyNormalize($chunks);
                if($translated_word){
                    $translated[] = $translated_word[0];
                    //$translated[][$word] = '( '.implode('|',$translated_word).' )';
                } else {
                    //$translated[][$word] = $word;
                    $translated[] = $word;
                }
            } else {
                $translated[] = $translated_word[0];
                //$translated[][$word] = '( '.implode('|',$translated_word).' )';
            }
            */
            $sourceResult[] = $word;
        }
        print_r($sourceResult);
        die;

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
        return $sentence;
    }

    public function findWord($word, $targetWords)
    {
        $translations = $this->getTranslations($word);
        foreach($targetWords as $sentenceIndex => $word){
            foreach($translations as $translation){
                if($translation['wordform'] === $word){
                    $translation['index'] = $sentenceIndex;
                    return $translation;
                }
            }
        }
        return false;
    }

    public function getTranslations($word)
    {
        $db = \Config\Database::connect();
        $sql = "
            SELECT 
                wfl1.*
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
    
}