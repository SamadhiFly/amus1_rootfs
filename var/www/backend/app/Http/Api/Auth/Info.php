<?php
namespace App\Http\Api\Auth;

use App\Http\Api\GatewayAbstract;

/**
 * Class Info
 * @package App\Http\Api\Auth
 */
class Info extends GatewayAbstract
{

    /**
     * @var bool
     */
    protected $forePermission = false;

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
        if(!$this->loginUserInfo) {
            $this->error('',4000);
        }
        $result = [
            'account'   => $this->loginUserInfo['account'],
            'role'      => $this->loginUserInfo['role'],
            'roleIs'    => $this->loginUserInfo['role'],
            'token'     => $this->loginUserInfo['token'],
            'ticket'    => $this->loginUserInfo['ticket'],
            'roleItems' => [],
        ];
        $role = $this->table('role')->where('id',$this->loginUserInfo['role'])->first();
        if($role&&!empty($role['items'])) {
            $result['roleIs']    = $role['is'];
            $result['roleItems'] = $role['items'];
        }
        return $this->response($result);
    }

}