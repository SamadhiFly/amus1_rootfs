<?php
namespace App\Http\Api\Upgrade\Configs;

use App\Http\Api\GatewayAbstract;

/**
 * Class Export
 * @package App\Http\Api\Upgrade\Configs
 */
class Export extends GatewayAbstract
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