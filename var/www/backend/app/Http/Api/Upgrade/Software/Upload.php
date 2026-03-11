<?php
namespace App\Http\Api\Upgrade\Software;

use App\Http\Api\GatewayAbstract;
use App\Utils\Widgets\Random;

/**
 * Class Upload
 * @package App\Http\Api\Upgrade\Software
 */
class Upload extends GatewayAbstract
{

    /**
     * @var bool
     */
    protected $foreLogin = false;


    /**
     * @return bool|mixed
     */
    public function validateParams()
    {
        return true;
    }


    /**
     * @return mixed
     */
    public function run()
    {
        try {
            $files = $this->_request->getUploadedFiles();
            if(!isset($files['file'])) {
                $this->error('No files were checked.');
            }
            $file = $files['file'];
            $fileName = $file->getClientFilename();
            if ($file->getError() === UPLOAD_ERR_OK) {
                $directory = BASE_PATH . 'uploads/software';
                if(!is_dir($directory)) {
                    mkdir($directory,0777,true);
                }
                $extension  = pathinfo($fileName, PATHINFO_EXTENSION);
                $basename   = base64_encode($fileName).'_'.time().'_'.Random::randVar(12,6);
                $filename   = sprintf('%s.%0.8s', $basename, $extension);

                $file->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
            }
        }catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $this->response([
            'name' => $filename
        ]);
    }
}