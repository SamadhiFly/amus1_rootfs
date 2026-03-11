<?php
namespace App\Http\Api\Auth;

use App\Http\Api\GatewayAbstract;
use App\Utils\Token\AuthHelper;
use App\Utils\Widgets\Random;

/**
 * reset password api
 *
 * Class Resets
 * @package App\Http\Api\Auth
 */
class Resets extends GatewayAbstract
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
        return $this->_validate($this->_params,[
            'oldPassword'           => 'required',
            'password'              => 'required',
        ],[
            'oldPassword.required'  => 'old password is required',
            'password.required'     => 'password is required',
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {
        if(!$this->loginUserInfo) {
            return $this->error('',4000);
        }

        $createdTime= $this->loginUserInfo['created_at'];
        $account    = $this->loginUserInfo['account'];
        $salting    = Random::randVar(20,6);
        $password   = AuthHelper::buildPasswd($this->_params['password'],$createdTime,$salting);

        //check if user is exists.
        $user = $this->table('user')->where('account',$account)->exists();
        if(!$user) {
            $this->error('The user is not exists.');
        }

        //diff old and new password
        $diffPws = AuthHelper::buildPasswd($this->_params['oldPassword'],$createdTime,$this->loginUserInfo['salting']);
        if($diffPws != $this->loginUserInfo['password']) {
            return $this->error('old password Incorrect');
        }

        $values = [
            'password'  => $password,
            'salting'   => $salting,
            'updated_at'=> time(),
        ];
        $user = $this->table('user')->where('account',$account)->update($values);
        if(!$user) {
            $this->error('reset user password fail, Please try again。');
        }
        return $this->response();
    }

}