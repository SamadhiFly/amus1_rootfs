<?php
namespace App\Http\Api\Agc;

use App\Http\Api\GatewayAbstract;

/**
 * Class Read
 * @package App\Http\Api\Agc
 */
class Read extends GatewayAbstract
{

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
        $result = [];
        $model = $this->connection('config')->table('config')->where('group_name','ACU')->where('active',1)->first();
        if($model) {
            $data = json_decode($model['json_config'],true);
            $result['default_modem']            = isset($data['default_modem'])?$data['default_modem']:'AMU';
            $result['manual_agc_enable']        = isset($data['manual_agc_enable'])?$data['manual_agc_enable']:'';
            $result['agc_threshold_value']      = isset($data['agc_threshold_value'])?$data['agc_threshold_value']:'';
            $result['agc_threshold_loopback']   = isset($data['agc_threshold_loopback'])?$data['agc_threshold_loopback']:'';
        }
        return $this->response($result);
    }

}