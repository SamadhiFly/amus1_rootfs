<?php
namespace App\Http\Api\Upgrade\Configs;

use App\Http\Api\GatewayAbstract;

/**
 * Class Verification
 * @package App\Http\Api\Upgrade\Configs
 */
class Verification extends GatewayAbstract
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
        return $this->_validate($this->_params,[
            'name'          => 'required',
        ],[
            'name.required' => 'file name is required',
        ]);
    }


    /**
     * @return mixed
     */
    public function run()
    {

        return $this->response();
    }
}