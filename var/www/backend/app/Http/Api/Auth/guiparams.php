<?php
namespace App\Http\Api\Auth;

use App\Http\Api\GatewayAbstract;
use App\Utils\Token\AuthHelper;

/**
 * Class Login
 * @package App\Http\Api\Auth
 */
class guiparams extends GatewayAbstract
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
        ],[
            'account.required'  => 'account is required',
            'password.required' => 'password is required'
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {

        //$ret_cmd = $this->reAuth();
        $account = $this->_params['account'];

        //get user info
        $user = $this->table('user')->where('account',$account)->first();
        if(!$user) {
            return $this->error('User is not found, please register.');
        }

        $role       = $user['role'];
        $password   = $user['password'];
        $createTime = $user['created_at'];

        //check password
        $buildPassword = AuthHelper::buildPasswd($this->_params['password'],$createTime,$user['salting']);
        if($buildPassword != $password) {
            return $this->error('The account password is incorrect. Please try again.');
        }

        //build token and ticket
        $token = AuthHelper::build($user['id'],$account,$password,$createTime);
        $ticket = AuthHelper::ticket($user['id'],$password);
        $userLogin = $this->table('user')->where('account',$account)->update([
            'token'     => $token,
            'ticket'    => $ticket,
            'updated_at'=> time(),
        ]);
        if(!$userLogin) {
            return $this->error('login fail, please try again');
        }
        $showlogo = '1';
        $params = $this->table('params')->where('items','showlogo')->first();
        if($params) {
            $showlogo = $params['param_value'];
        }

        $result = [
            'account'   => $account,
            'token'     => $token,
            'ticket'    => $ticket,
            'role'      => $role,
            'showlogo'  => $showlogo,
        ];
        return $this->response($result);
    }

}