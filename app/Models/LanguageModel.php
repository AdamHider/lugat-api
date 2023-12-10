<?php

namespace App\Models;

use CodeIgniter\Model;

class LanguageModel extends Model
{
    protected $table      = 'lgt_languages';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'title',
        'code', 
        'flag'
    ];
    
    protected $useTimestamps = false;

    public function getItem ($language_id) 
    {
        
        $language = $this->where('id', $language_id)->get()->getRowArray();
        
        $language['flag'] = base_url('images/' . $language['flag']);
        return $language;
    }
    public function getItemId ($title) 
    {
        
        $language = $this->where('title', $title)->get()->getRowArray();
        if(empty($language)){
            return 0;
        }
        return $language['id'];
    }
    public function getList () 
    {
        $languages = $this->get()->getResultArray();
        foreach($languages as &$language){
            $language['flag'] = base_url('images/' . $language['flag']);
        }
        return $languages;
    }

}