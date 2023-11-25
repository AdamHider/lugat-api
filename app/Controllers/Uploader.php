<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Files\File;
class Uploader extends BaseController
{
    use ResponseTrait;
    public function uploadItem()
    {
        $validationRule = [
            'file' => [
                'label' => 'Text File',
                'rules' => [
                    'uploaded[file]',
                    'mime_in[file,text/plain]',
                    'max_size[file,2000]',
                ],
            ],
        ];
        
        if (! $this->validate($validationRule)) {
            $data = ['errors' => $this->validator->getErrors()];
            return $this->fail($data);
        }

        $file = $this->request->getFile('file');
        
        if (!$file->hasMoved()) {
            $filename = md5($file->getBasename()).'.txt';
            $file->move(ROOTPATH . 'public/files', $filename);
            return $this->respond(['file' => base_url('files/' . $filename)]);
        }

        $data = ['errors' => 'The file has already been moved.'];

        return $this->fail($data);
    }
    
}
