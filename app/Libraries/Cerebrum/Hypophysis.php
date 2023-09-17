<?php

namespace App\Libraries\Cerebrum;

class Hypophysis{


    protected $skip = ['«', '»', '!', '?', '.', ',', ';', ':', '…', '“'];
   
    public function predictionNormalize($predictionList)
    {
        $result = [];
        usort($predictionList, function($a, $b){
            return $a['position'] <=> $b['position'];
        });
        $predictionNormalized = $this->groupBy($predictionList, 'core');


        foreach($predictionNormalized as $token){
            $result[] = $token['core'];
        }
        return implode(' ', $result);
    }
    public function calculateTokenPosition($tokenTotal, $index)
    {
        return round(($index + 1) / $tokenTotal, 2);
    }
    public function groupBy($arr, $key)
    {   
        $result = [];
        $keys = [];
        foreach($arr as $a){
            if(in_array($a[$key], $keys)){
                continue;
            }
            $result[] = $a;
            $keys[] = $a[$key];
        }
        return $result;
    }
    public function tokenizeSentence($sentence)
    {
        $sentence = mb_strtolower($sentence);
        $sentence = str_replace(array("\n", "\r"), '', $sentence);
        foreach($this->skip as $item){
            if((int) $item || strpos($sentence, $item) > -1){
                $sentence = str_replace($item, '', $sentence);
                continue;
            }
        }
        $sentence = str_replace('  ',  ' ',$sentence);
        $sentence = str_replace('ё', 'е', $sentence);
        return explode(' ', $sentence);
    }
    public function utilizeToken($str)
    {
        $str = str_replace('ё', 'е', $str);
        return $str;
    }
    
    public function unmap($data)
    {
        $result = [];
        foreach($data['map'] as $sourceIndex => $matches){
            $axonGroup = [];
            $axonGroup[] = [
                'language_id'=> $data['languageMap']['source'],
                'token'     => $data['tokens']['source'][$sourceIndex],
                'position'  => $this->calculateTokenPosition(count($data['tokens']['source']), $sourceIndex)
            ];
            foreach($matches as $targetIndex){
                $axonGroup[] = [ 
                    'language_id'=> $data['languageMap']['target'],
                    'token'     => $data['tokens']['target'][$targetIndex],
                    'position'  => $this->calculateTokenPosition(count($data['tokens']['target']), $targetIndex)
                ];
            }
            $result[] = $axonGroup;
        }
        return $result;
    }

}
