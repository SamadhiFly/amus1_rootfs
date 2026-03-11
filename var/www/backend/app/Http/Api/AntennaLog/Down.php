<?php
namespace App\Http\Api\AntennaLog;

use App\Http\Api\GatewayAbstract;

/**
 * Class All
 * @package App\Http\Api\Auth
 */
class Down extends GatewayAbstract
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
        $LogFileName = '';
        if ($LogType == 'ACU') {
            $LogFileName = 'acu.log';
        } else if ($LogType == 'ANT') {
            $LogFileName = 'ant.log';
        } else if ($LogType == 'BMC') {
            $LogFileName = 'bmc.log';
        } else if ($LogType == 'PMT') {
            $LogFileName = 'pmt.log';
        }
        $file_dir = "/home/bird/data/logs/";        //下载文件存放目录
        if($DateType==2)
        {
            $LogFile = isset($this->_params['LogFile'])?$this->_params['LogFile']:'';
            $file_dir = '/home/bird/data/logs/backup/';
            $LogFileName = $LogFile;

        }

        //检查文件是否存在
        if (! file_exists ( $file_dir . $LogFileName )) {
            header('HTTP/1.1 404 NOT FOUND');
        } else {
            //以只读和二进制模式打开文件
            $file = fopen ( $file_dir . $LogFileName, "r" );

            Header ( "Access-Control-Allow-Origin: *" );
            Header ( "Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE" );
            //Header ("Access-Control-Allow-Origin" .$this->_request.getHeader("origin"));
            //告诉浏览器这是一个文件流格式的文件
            Header ( "Content-type: application/octet-stream" );
            //请求范围的度量单位
            Header ( "Accept-Ranges: bytes" );
            //Content-Length是指定包含于请求或响应中数据的字节长度
            Header ( "Accept-Length: " . filesize ( $file_dir . $LogFileName ) );
            //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$file_name该变量的值。
            Header ( "Content-Disposition: attachment; filename=" . $LogFileName );
            //读取文件内容并直接输出到浏览器
            //$this->error(filesize ( $file_dir . $LogFileName ) );
            echo fread ( $file, filesize ( $file_dir . $LogFileName ) );

            //echo file_get_contents($file_dir . $LogFileName,false,null,0,20*1024*1024);
            //echo file_get_contents($file_dir . $LogFileName,false,null,20*1024*1024,20*1024*1024);
            //echo file_get_contents($file_dir . $LogFileName,false,null,40*1024*1024,20*1024*1024);


            fclose ( $file );
            exit ();
        }
    }

}