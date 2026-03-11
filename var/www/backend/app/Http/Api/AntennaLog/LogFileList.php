<?php
namespace App\Http\Api\AntennaLog;

use App\Http\Api\GatewayAbstract;

/**
 * Class All
 * @package App\Http\Api\Auth
 */
class LogFileList extends GatewayAbstract
{
    protected $foreLogin = false;

    /**
     * validate params
     *
     * @return mixed|void
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
        $LogType = isset($this->_params['LogType']) ? $this->_params['LogType'] : '';
        $LogDate =isset($this->_params['LogDate'])?$this->_params['LogDate']:'';
        $logPath = '/home/bird/data/logs/backup/';
        $LogFileName = '';
        if ($LogType == 'ACU') {
            $LogFileName = 'acu-'.$LogDate;
        } else if ($LogType == 'ANT') {
            $LogFileName = 'ant-'.$LogDate;
        } else if ($LogType == 'BMC') {
            $LogFileName = 'bmc-'.$LogDate;
        }
        else if ($LogType == 'PMT') {
            $LogFileName = 'pmt-'.$LogDate;
        }
        $fileList = $this->getDirContent($logPath);
        $fileListReturn = array();
        if(isset($fileList))
        {
            foreach ($fileList as $fileName)
            {
                if(strpos($fileName,$LogFileName) !== false)
                {
                    array_push($fileListReturn,$fileName);
                    //$fileListReturn = array($fileName);
                }
            }
            if(isset($fileListReturn))
            {
                return $this->response([
                    'fileList' => $fileListReturn
                ]);
            }
            else{
                return $this->response([
                    'fileList' => ""
                ]);
            }

        }
        else{
                return $this->response([
                    'fileList' => ""
                ]);
        }




    }
    protected function getDirContent($path){
        if(!is_dir($path)){
            return false;
        }
        //scandir方法
        $arr = array();
        $data = scandir($path);
        foreach ($data as $value){
            if($value != '.' && $value != '..'){
                $arr[] = $value;
            }
        }
        return $arr;
    }

}