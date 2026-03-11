<?php
namespace App\Http\Api\Auth\Role;

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
        $role = $this->table('role')->get();
        return $this->response([
            'items' => $role
        ]);
    }

}