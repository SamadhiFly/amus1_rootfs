<?php
namespace App\Http\Api\Monitor;

use App\Http\Api\GatewayAbstract;

/**
 * read monitor log
 *
 * version 1
 *
 * Class Read
 * @package App\Http\Api\Monitor
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

        //pmt model
        $pmtModel = $this->connection('status')->table('status')
            ->where('module','PMT')
            ->orderBy('id','desc')
            ->first();
        if($pmtModel) {
            $data = json_decode($pmtModel['json_status'],true);
            if($data) {
                $result['cpu_used_percent']         = isset($data['cpu_used_percent'])?$data['cpu_used_percent']:'';
                $result['ram_used_percent']         = isset($data['ram_used_percent'])?$data['ram_used_percent']:'';
                $result['flash_used_percent']       = isset($data['flash_used_percent'])?$data['flash_used_percent']:'';
            }
        }
        //500ms
        usleep(1000000);
        //acu model
        $acuModel = $this->connection('status')->table('status')
            ->where('module','ACU')
            ->orderBy('id','desc')
            ->first();
        if($acuModel) {
            $data = json_decode($acuModel['json_status'],true);
            if($data) {
                $result['cpu_temperature_degc']     = isset($data['cpu_temperature_degc'])?$data['cpu_temperature_degc']:'';
                $result['gps_locked']               = isset($data['gps_locked'])?$data['gps_locked']:'';
                $result['gps_utc_s']                = isset($data['gps_utc_s'])?$data['gps_utc_s']:'';
                $gps_longitude_degs = $data['gps_longitude_degs'] .','. $data['gps_latitude_degs'];
                $result['gps_longitude_degs']       = $gps_longitude_degs;

                $result['gps_lon']       = $data['gps_longitude_degs'];
                $result['gps_lat']       = $data['gps_latitude_degs'];

                $result['srv_link_state']           = isset($data['srv_link_state'])?$data['srv_link_state']:'';
                $result['data_link_state']          = isset($data['data_link_state'])?$data['data_link_state']:'';

                $sensor_fault = isset($data['sensor_fault'])?$data['sensor_fault']:false;
                if($sensor_fault == true)
                {
                    $result['sensor_fault']             ='false';
                }
                else{
                    $result['sensor_fault']             ='true';
                }
                $buc_fault = isset($data['buc_fault'])?$data['buc_fault']:false;
                if($buc_fault == true)
                {
                    $result['buc_fault']             ='false';
                }
                else{
                    $result['buc_fault']             ='true';
                }
                $lnb_fault = isset($data['lnb_fault'])?$data['lnb_fault']:false;
                if($lnb_fault == true)
                {
                    $result['lnb_fault']             ='false';
                }
                else{
                    $result['lnb_fault']             ='true';
                }
                $motor_fault = isset($data['motor_fault'])?$data['motor_fault']:false;
                if($motor_fault == true)
                {
                    $result['motor_fault']             ='false';
                }
                else{
                    $result['motor_fault']             ='true';
                }
                //$result['buc_fault']                = isset($data['buc_fault'])?$data['buc_fault']:'';
                //$result['lnb_fault']                = isset($data['lnb_fault'])?$data['lnb_fault']:'';
                //$result['sensor_fault']             = isset($data['sensor_fault'])?$data['sensor_fault']:'';
                //$result['motor_fault']              = isset($data['motor_fault'])?$data['motor_fault']:'';
                $result['amu_dc_v']                 = isset($data['amu_dc_v'])?$data['amu_dc_v']:'';
                $result['amu_dc_a']                 = isset($data['amu_dc_a'])?$data['amu_dc_a']:'';
                $result['lnb_dc_v']                 = isset($data['lnb_dc_v'])?$data['lnb_dc_v']:'';
                $result['lnb_dc_a']                 = isset($data['lnb_dc_a'])?$data['lnb_dc_a']:'';
                $result['buc_dc_v']                 = isset($data['buc_dc_v'])?$data['buc_dc_v']:'';
                $result['buc_dc_a']                 = isset($data['buc_dc_a'])?$data['buc_dc_a']:'';
                $result['el_pointing_error_degs']   = isset($data['el_current_position_degs'])?$data['el_current_position_degs']:'';
                $result['az_pointing_error_degs']   = isset($data['az_current_position_degs'])?$data['az_current_position_degs']:'';
                $result['rl_pointing_error_degs']   = isset($data['rl_current_position_degs'])?$data['rl_current_position_degs']:'';
                $result['sk_current_position_degs']   = isset($data['sk_current_position_degs'])?$data['sk_current_position_degs']:'';
                $result['cpn_db_avg']   = isset($data['cpn_db_avg'])?$data['cpn_db_avg']:'';
                $result['tracking_stable']   = isset($data['tracking_stable'])?$data['tracking_stable']:'';
                $result['demod_con_db_avg']   = isset($data['demod_con_db_avg'])?$data['demod_con_db_avg']:'';
                $result['current_state']   = isset($data['current_state'])?$data['current_state']:'';
                $result['current_substate']   = isset($data['current_substate'])?$data['current_substate']:'';
                $result['ant_current_state']   = isset($data['ant_current_state'])?$data['ant_current_state']:'';
                $result['ant_current_substate']   = isset($data['ant_current_substate'])?$data['ant_current_substate']:'';
            }
        }

        /*
        //ant model
        $antModel = $this->connection('status')->table('status')
            ->where('module','ANT')
            ->orderBy('id','desc')
            ->first();
        if($antModel) {
            $data = json_decode($antModel['json_status'],true);
            if($data) {
                $result['el_pointing_error_degs']   = isset($data['el_current_position_degs'])?$data['el_current_position_degs']:'';
                $result['az_pointing_error_degs']   = isset($data['az_current_position_degs'])?$data['az_current_position_degs']:'';
                $result['rl_pointing_error_degs']   = isset($data['rl_current_position_degs'])?$data['rl_current_position_degs']:'';
                $result['el_control_dac']           = isset($data['el_control_dac'])?$data['el_control_dac']:'';
                $result['az_control_dac']           = isset($data['az_control_dac'])?$data['az_control_dac']:'';
                $result['control_dac']              = isset($data['control_dac'])?$data['control_dac']:'';
            }
        }
        */
        return $this->response($result);
    }

}