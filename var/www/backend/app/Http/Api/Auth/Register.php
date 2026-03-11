<?php
namespace App\Http\Api\Auth;

use App\Http\Api\GatewayAbstract;
use App\Utils\Token\AuthHelper;
use App\Utils\Widgets\Random;

/**
 * register api
 *
 * Class Register
 * @package App\Http\Api\Auth
 */
class Register extends GatewayAbstract
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
        return $this->_validate($this->_params,[
            'account'           => 'required',
            'password'          => 'required',
            'role'              => 'required'
        ],[
            'account.required'  => 'account is required',
            'password.required' => 'password is required',
            'role.required'     => 'role is required'
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $role       = $this->_params['role'];
        $createTime = time();
        $account    = $this->_params['account'];
        $salting    = Random::randVar(20,6);
        $password   = AuthHelper::buildPasswd($this->_params['password'],$createTime,$salting);

        //check if user is exists.
        $user = $this->table('user')->where('account',$account)->exists();
        if($user) {
            $this->error('The user already exists, please do not register again.');
        }

        //check if role is normal
        $roleInfo = $this->table('role')->where('id',$role)->first();
        if(!$roleInfo) {
            $this->error('role is not normal..');
        }

        $values = [
            'account'   => $account,
            'password'  => $password,
            'salting'   => $salting,
            'role'      => $role,
            'created_at'=> $createTime,
            'updated_at'=> $createTime,
        ];
        $user = $this->table('user')->insertGetId($values);
        if(!$user) {
            $this->error('register user fail, Please try again。');
        }

        $result = [
            'account'   => $account,
            'role'      => $role,
        ];
        return $this->response($result);
    }

}