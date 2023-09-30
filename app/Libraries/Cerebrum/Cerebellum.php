<?php

namespace App\Libraries\Cerebrum;

class Cerebellum{

    public function rememberCahceCreate($cortex, $hash_data, $data)
    {
        $db = \Config\Database::connect();
        $sql = "
            INSERT INTO
                crbrm_cerebellum
            SET
                id      = NULL, 
                hash    = '".md5($hash_data)."',
                cortex  = ".$db->escape($cortex).", 
                data    = ".$db->escape(json_encode($data, JSON_UNESCAPED_UNICODE))."
            ON DUPLICATE KEY UPDATE
                data    = ".$db->escape(json_encode($data, JSON_UNESCAPED_UNICODE))."
        ";
        return $db->query($sql);
    }
    
}