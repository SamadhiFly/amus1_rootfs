<?php
namespace App\Http\Api\AntennaControl;

use App\Http\Api\GatewayAbstract;

/**
 * read hardware interface.
 *
 * Class Read
 * @package App\Http\Api\Configs
 */
class Read extends GatewayAbstract
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
        $result = [];

        $amuModel = $this->connection('status')->table('status')
            ->where('module','ACU')
            ->orderBy('id','desc')
            ->first();
        if($amuModel) {
            $data = json_decode($amuModel['json_status'],true);
            if($data) {
                //方位角陀螺仪偏移值"az_gyro_centre_offset_dps"
                $result['az_gyro_centre_offset_dps']         = isset($data['az_gyro_centre_offset_dps'])?$data['az_gyro_centre_offset_dps']:'';
                //俯仰角陀螺仪偏移值"el_gyro_centre_offset_dps"
                $result['el_gyro_centre_offset_dps']         = isset($data['el_gyro_centre_offset_dps'])?$data['el_gyro_centre_offset_dps']:'';
                //横滚角陀螺仪偏移值"rl_gyro_centre_offset_dps"
                $result['rl_gyro_centre_offset_dps']         = isset($data['rl_gyro_centre_offset_dps'])?$data['rl_gyro_centre_offset_dps']:'';
                //方位角当前角度值"az_current_position_degs"
                $result['az_current_position_degs']         = isset($data['az_current_position_degs'])?$data['az_current_position_degs']:'';
                //俯仰角当前角度值"el_current_position_degs"
                $result['el_current_position_degs']         = isset($data['el_current_position_degs'])?$data['el_current_position_degs']:'';
                //横滚角当前角度值"rl_current_position_degs"
                $result['rl_current_position_degs']         = isset($data['rl_current_position_degs'])?$data['rl_current_position_degs']:'';
                //极化角当前角度值"sk_current_position_degs"
                $result['sk_current_position_degs']         = isset($data['sk_current_position_degs'])?$data['sk_current_position_degs']:'';
            }
        }

        return $this->response($result);
    }

}