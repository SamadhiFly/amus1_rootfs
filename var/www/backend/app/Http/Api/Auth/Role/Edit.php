<?php
namespace App\Http\Api\Auth\Role;

use App\Http\Api\GatewayAbstract;

/**
 * Class Edit
 * @package App\Http\Api\Auth\Role
 */
class Edit extends GatewayAbstract
{

    /**
     * validate params
     *
     * @return mixed|void
     */
    public function validateParams()
    {
        return $this->_validate($this->_params,[
            'id'            => 'required',
            'items'         => 'required',
        ],[
            'id.required'   => 'role id is required',
            'items.required'=> 'role route path is required',
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $role = $this->table('role')->where('id',$this->_params['id'])->first();
        if(!$role) {
            $this->error('role is not found.');
        }

        $upRole = $this->table('role')->where('id',$this->_params['id'])->update([
            'items'         => $this->_params['items'],
            'updated_at'    => time(),
        ]);
        if(!$upRole) {
            $this->error('edit role info failed.');
        }
        return $this->response();
    }

}