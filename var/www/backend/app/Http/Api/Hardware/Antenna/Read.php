<?php
namespace App\Http\Api\Hardware\Antenna;

use App\Http\Api\GatewayAbstract;
use App\Utils\Widgets\File;

/**
 * antenna info interface
 *
 * version v1
 *
 * Class Info
 * @package App\Http\Api\Hardware\Antenna
 */
class Read extends GatewayAbstract
{

    /**
     * no need login
     *
     * @var bool
     */
    protected $foreLogin = false;

    /**
     * @return bool|mixed
     */
    public function validateParams()
    {
        return false;
    }


    /**
     * @return mixed
     */
    public function run()
    {
        $commands = [
            "read_cmd" => 'mod_cli acu '.$this->output.' rc_hi_all ',
        ];
        $this->syncCallCli($commands,[]);

        file_put_contents('/tmp/log.txt',);
        return $this->response();

        //hardware info
//        $path = '/tmp/hardware.json';
//        $result = File::read($path);
//        if($result['code'] != 0) {
//            return $this->error($result['msg']);
//        }
//
//        $data = json_decode($result['data'],true);
//        if(json_last_error() > 0) {
//            $this->error('File content parsing error.');
//        }
//
//        return $this->response($data);
    }


}
