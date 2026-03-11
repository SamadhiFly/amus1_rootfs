<?php
namespace App\Http\Api\Auth\Role;

use App\Http\Api\GatewayAbstract;

/**
 * Class Register
 * @package App\Http\Api\Auth\Role
 */
class Register extends GatewayAbstract
{

    /**
     * validate params
     *
     * @return mixed|void
     */
    public function validateParams()
    {
        return $this->_validate($this->_params,[
            'name'              => 'required',
            'items'             => 'required',
        ],[
            'name.required'     => 'role name is required',
            'items.required'    => 'role route path is required',
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $time   = time();
        $name   = $this->_params['name'];
        $items  = $this->_params['items'];

        //check role is exists
        $checkRole = $this->table('role')->where('name',$name)->exists();
        if($checkRole) {
           $this->error("Don't repeat create the role .");
        }

        $addRole = $this->table('role')->where('name','!=',$name)->insert([
            'name'          => $name,
            'items'         => $items,
            'created_at'    => $time,
            'updated_at'    => $time,
        ]);
        if(!$addRole) {
            $this->error('register role failed.');
        }
        return $this->response();
    }

}