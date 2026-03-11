<?php
namespace App\Http\Api\Auth;

use App\Http\Api\GatewayAbstract;

/**
 * Class All
 * @package App\Http\Api\Auth
 */
class All extends GatewayAbstract
{

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
        $user = $this->table('user')->leftJoin('role','role.id','=','user.role')->orderBy('user.id','desc')->get([
            'user.id','user.account','user.role','role.name as roleName'
        ]);
        return $this->response($user);
    }

}