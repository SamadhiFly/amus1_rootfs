<?php
namespace App\Http\Api\Auth;

use App\Http\Api\GatewayAbstract;

/**
 * Class Edit
 * @package App\Http\Api\Auth
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
            'role'          => 'required',
        ],[
            'id.required'   => 'user id is required',
            'role.required' => 'role is required',
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {

        $role = $this->table('role')->where('id',$this->_params['role'])->first();
        if(!$role) {
            $this->error('role is not found.');
        }

        $user = $this->table('user')->where('id',$this->_params['id'])->update([
            'role'          => $this->_params['role'],
            'updated_at'    => time(),
        ]);
        if(!$user) {
            $this->error('edit user info failed.');
        }

        return $this->response();
    }

}