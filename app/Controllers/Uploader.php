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
            $filename = $file->getRandomName();
            $file->move(ROOTPATH . 'public/files', $filename);
            return $this->respond(['file' => base_url('files/' . $filename)]);
        }

        $data = ['errors' => 'The file has already been moved.'];

        return $this->fail($data);
    }
    
    private function fileSaveImage( $image_holder_id, $file, $image_holder='store' ){
        $image_holder=($image_holder=='store_avatar'?'store_avatar':'store');
        $image_data=[
            'image_holder'=>$image_holder,
            'image_holder_id'=>$image_holder_id
        ];
        $StoreModel=model('StoreModel');
        $image_hash=$StoreModel->imageCreate($image_data);
        if( !$image_hash ){
            return $this->failForbidden('forbidden');
        }
        if( $image_hash === 'limit_exeeded' ){
            return $this->fail('limit_exeeded');
        }
        $file->move(WRITEPATH.'images/', $image_hash.'.webp');
        
        try{
            return \Config\Services::image()
            ->withFile(WRITEPATH.'images/'.$image_hash.'.webp')
            ->resize(1600, 1600, true, 'height')
            ->convert(IMAGETYPE_WEBP)
            ->save();
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

}
