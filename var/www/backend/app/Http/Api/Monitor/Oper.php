<?php
namespace App\Http\Api\Monitor;

use App\Http\Api\GatewayAbstract;
use App\Utils\Widgets\File;

/**
 * Class Write
 * @package App\Http\Api\Configs
 */
class Oper extends GatewayAbstract
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

    }

    /**
     * @return mixed
     */
    public function run()
    {
        $data = "";
        $commands = [];
        array_push($commands, [
            'read_cmd' => 'sudo reboot ',

        ]);

        if(!empty($commands)) {
            $this->syncCallCli($commands,$data);
        }

        return $this->response();
    }

}
