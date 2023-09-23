<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Chapter extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $ChapterModel = model('ChapterModel');

        $chapter_id = $this->request->getVar('chapter_id');

        $chapter = $ChapterModel->getItem($chapter_id);

        if ($chapter == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($chapter);
    }
    public function getList()
    {
        $ChapterModel = model('ChapterModel');

        $fields = $this->request->getVar('fields');
        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');
        $data = [
            'fields' => $fields,
            'limit' => $limit,
            'offset' => $offset
        ];
        $result = $ChapterModel->getList($data);
        if(!$result){
            return $this->failNotFound('not_found');
        }
        return $this->respond($result, 200);
    }

}
