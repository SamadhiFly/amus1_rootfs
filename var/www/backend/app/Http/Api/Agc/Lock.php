<?php
namespace App\Http\Api\Agc;

use App\Http\Api\GatewayAbstract;

/**
 * Class Lock
 * @package App\Http\Api\Agc
 */
class Lock extends GatewayAbstract
{

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
        return $this->response();
    }

}