<?php
namespace App\Http\Api\Configs;

use App\Http\Api\GatewayAbstract;

/**
 * Class Enum
 * @package App\Http\Api\Configs
 */
class Enum extends GatewayAbstract
{

    protected $foreLogin = false;


    /**
     * @return bool|mixed
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
        $result = [
           'antenna_mode'   => [
               ['label' => 'VS80B','value' => 'VS80B'],
               ['label' => 'VS80A','value' => 'VS80A'],
               ['label' => 'VS80A2','value' => 'VS80A2'],
               ['label' => 'VS60D','value' => 'VS60D'],
               ['label' => 'VS80H','value' => 'VS80H'],
               ['label' => 'VS60H','value' => 'VS60H'],

           ],
        ];
        return $this->response($result);
    }

}