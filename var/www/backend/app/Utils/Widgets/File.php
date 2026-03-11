<?php
namespace App\Utils\Widgets;

/**
 * Class File
 * @package App\Utils\Widgets
 */
final class File
{

    /**
     * environment : /tmp/root/*
     * production : /home/root/*
     *
     * @param $filePath
     * @return array|false|string
     */
    public static function read($filePath)
    {
        $checkFile = file_exists($filePath);
        if(!$checkFile) {
            return [
                'code'  => 400,
                'msg'   => 'file is no found.',
            ];
        }
        $read   = file_get_contents($filePath);
        return [
            'code'  => 0,
            'msg'   => '',
            'data'  => $read
        ];
    }


    /**
     * environment : /tmp/root/*
     * production : /home/root/*
     *
     * @param $filePath
     * @param $data
     * @return array|false|string
     */
    public static function write($filePath,$data)
    {
        $checkFile = file_exists($filePath);
        if(!$checkFile) {
            return false;
        }
        $lockFile = fopen($filePath,'w+');
        if(!flock($lockFile,LOCK_EX|LOCK_NB)) {
            return false;
        }
        fwrite($lockFile,$data);
        flock($lockFile,LOCK_UN);//unlock
        fclose($lockFile);
        return true;
    }

}
