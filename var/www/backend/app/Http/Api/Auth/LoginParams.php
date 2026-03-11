<?php
namespace App\Http\Api\Auth;

use App\Http\Api\GatewayAbstract;
use App\Utils\Token\AuthHelper;

/**
 * Class Login
 * @package App\Http\Api\Auth
 */
class LoginParams extends GatewayAbstract
{

    /**
     * @var bool
     */
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
        $showlogo = '1';
        $params = $this->table('params')->where('items','showlogo')->first();
        if($params) {
            $showlogo = $params['param_value'];
        }

        $result = [
            'showlogo'  => $showlogo,
        ];
        return $this->response($result);
    }

}