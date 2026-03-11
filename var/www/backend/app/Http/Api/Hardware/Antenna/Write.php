<?php
namespace App\Http\Api\Hardware\Antenna;

use App\Http\Api\GatewayAbstract;

/**
 * write info
 *
 * version v1
 *
 * Class Info
 * @package App\Http\Api\Hardware\Antenna
 */
class Write extends GatewayAbstract
{
    /**
     * no need login
     *
     * @var bool
     */
    protected $foreLogin = false;


    /**
     * open write lock
     *
     * @var bool
     */
    protected $writeLock = true;


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

        return $this->response();
    }


}