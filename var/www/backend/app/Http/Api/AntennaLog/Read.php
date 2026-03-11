<?php
namespace App\Http\Api\AntennaLog;

use App\Http\Api\GatewayAbstract;

/**
 * Class All
 * @package App\Http\Api\Auth
 */
class Read extends GatewayAbstract
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
        $DateType = isset($this->_params['DateType'])?$this->_params['DateType']:'';
        if($DateType == 1)
        {
            $LogFileName = '';
            if ($LogType == 'ACU') {
                $LogFileName = '/home/bird/data/logs/acu.log';
            } else if ($LogType == 'ANT') {
                $LogFileName = '/home/bird/data/logs/ant.log';
            } else if ($LogType == 'BMC') {
                $LogFileName = '/home/bird/data/logs/bmc.log';
            } else if ($LogType == 'PMT') {
                $LogFileName = '/home/bird/data/logs/pmt.log';
            }
            //$content = file_get_contents($LogFileName,TRUE);
            $content = "";
            $fp = fopen($LogFileName, "r");
            $line = 200;
            $pos = -2;
            $t = " ";
            $data = "";
            while ($line > 0) {
                while ($t != "\n") {
                    fseek($fp, $pos, SEEK_END);
                    $t = fgetc($fp);
                    $pos --;
                }
                $t = " ";
                $content .= fgets($fp);
                $line --;
            }
            fclose ($fp);


            if(isset($content))
            {
                return $this->response([
                    'logContent' => $content
                ]);
            }
            else{
                return $this->response([
                    'logContent' => ""
                ]);
            }

        }
        else{
            $LogDate =isset($this->_params['LogDate'])?$this->_params['LogDate']:'';
            $LogFile =isset($this->_params['LogFile'])?$this->_params['LogFile']:'';
            $logPath = '/home/bird/data/logs/backup/';
            $LogFileName = $logPath . $LogFile;
            $content = file_get_contents($LogFileName,true);
            if(isset($content))
            {
                return $this->response([
                    'logContent' => $content
                ]);
            }
            else{
                return $this->response([
                    'logContent' => ""
                ]);
            }
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