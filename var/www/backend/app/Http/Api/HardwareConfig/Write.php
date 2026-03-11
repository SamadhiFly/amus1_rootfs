<?php
namespace App\Http\Api\HardwareConfig;

use App\Http\Api\GatewayAbstract;
use App\Utils\Widgets\File;

/**
 * Class Write
 * @package App\Http\Api\Configs
 */
class Write extends GatewayAbstract
{

    /**
     * @var bool
     */
    protected $foreLogin = false;

    /**
     * @return bool|mixed
     */
    public function validateParams()
    {
        return $this->_validate($this->_params,[
            'remark'            => 'required',
            'sign'              => 'required',
            'data'              => 'required',
        ],[
            'remark.required'   => 'remark is required',
            'sign.required'     => 'sign is required',
            'data.required'     => 'data is required',
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $data   = json_decode($this->_params['data'],true);
        if(empty($data)) {
            $this->error('无效的data字段结构');
        }

        $commands = [];
        $signs = $this->_params['sign'];
        //$signs = explode(',',$this->_params['sign']);
        if($signs=='acu')
        {
            $acuConfig = '/home/bird/configs/acu_all_cfg.json';
            array_push($commands, [
                'file' => $acuConfig,
                'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' sc_acu_all ' . $acuConfig,
                'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_acu_all ',

            ]);

            $bucConfig = '/home/bird/data/acu_22k_buc_config.json';
            array_push($commands, [
                'file' => $bucConfig,
                'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' rc_acu_buc ' . $bucConfig,
                'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_acu_buc ',

            ]);

            array_push($commands, [
                'read_cmd' => 'sudo save_config ',

            ]);

            if(!empty($commands)) {
                $this->syncCallCli($commands,$data);
            }
        }
        else if($signs=='hi')
        {
            $hiConfig = '/home/bird/configs/hi_all_cfg.json';
            array_push($commands, [
                'file' => $hiConfig,
                'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' sc_hi_all ' . $hiConfig,
                'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_hi_all ',

            ]);

            array_push($commands, [
                'read_cmd' => 'sudo save_config ',

            ]);

            if(!empty($commands)) {
                $this->syncCallCli($commands,$data);
            }
        }
        else if($signs=='buc')
        {
            $bucConfig = '/home/bird/data/acu_22k_buc_config.json';
            array_push($commands, [
                'file' => $bucConfig,
                'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' rc_acu_buc ' . $bucConfig,
                'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_acu_buc ',
            ]);
            /*
            array_push($commands, [
                'read_cmd' => 'sudo save_config ',

            ]);
            */
            if(!empty($commands)) {
                $this->syncCallCli($commands,$data);
            }
        }
        else if($signs=='tracking')
        {
            $hiConfig = '/home/bird/data/acu_tracking_config.json';
            $commands = 'acu_tracking_config';
            $this->writeJson($commands,$data,$hiConfig);
        }
        else if($signs=='rolloff')
        {
            $hiConfig = '/home/bird/data/acu_tracking_config.json';
            $commands = 'acu_tracking_config';
            $this->writeJson2($commands,$data,$hiConfig);
        }
        else if($signs=='mute')
        {
            $hiConfig = '/home/bird/data/acu_tracking_config.json';
            $commands = 'acu_tracking_config';
            $this->writeJson2($commands,$data,$hiConfig);
        }
        else if($signs=='skew')
        {
            $skewConfig = '/home/bird/configs/acu_all_cfg.json';
            //if (isset($data['sk_cal_offset_degs']) && !empty($data['sk_cal_offset_degs']))
            //{

                //$sk_cal_offset_degs = floatval($data['sk_cal_offset_degs']);
            //$data['sk_cal_offset_degs'] = $sk_cal_offset_degs;
            $sk_hori_pol_cal_offest_degs = floatval($data['sk_hori_pol_cal_offest_degs']);
            $sk_ver_pol_cal_offest_degs = floatval($data['sk_ver_pol_cal_offest_degs']);
            $data['sk_hori_pol_cal_offest_degs'] = $sk_hori_pol_cal_offest_degs;
            $data['sk_ver_pol_cal_offest_degs'] = $sk_ver_pol_cal_offest_degs;
            //}
            array_push($commands, [
                'file' => $skewConfig,
                'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' sc_acu_all ' . $skewConfig,
                'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_acu_all ',

            ]);

            array_push($commands, [
                'read_cmd' => 'sudo save_config ',

            ]);

            if(!empty($commands)) {
                $this->syncCallCli($commands,$data);
            }
        }

        return $this->response();
    }

}
