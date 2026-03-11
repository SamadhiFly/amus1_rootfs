<?php
namespace App\Http\Api\FunctionConfig;

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
        $jsonfile = '/home/bird/configs/acu_all_cfg.json';
        $commands = 'sudo mod_cli acu ' . $this->output . ' sc_acu_all ' . $jsonfile;
        $ret_cmd = $this->readCli_byCmd($commands,$jsonfile);
        if(!empty($ret_cmd))
        {
            //`$result['log']  = $ret_cmd;
            //理论仰角"el_nominal_degs"
            $amuModel = $this->connection('status')->table('status')
                ->where('module','ACU')
                ->orderBy('id','desc')
                ->first();
            if($amuModel) {
                $data = json_decode($amuModel['json_status'],true);
                if($data) {
                    $result['el_nominal_degs']         = isset($data['el_nominal_degs'])?$data['el_nominal_degs']:'';
                }
            }
            //锁星仰角
            //当前仰角偏差
            //1.buc
            $ret_cmd = json_decode($ret_cmd, true);
            //BUC电压开关
            $result['buc_dc_auto']        = isset($ret_cmd['buc_dc_auto'])?$ret_cmd['buc_dc_auto']:'';
            //BUC DC电压
            $result['buc_dc_manual_v']       = isset($ret_cmd['buc_dc_manual_v'])?$ret_cmd['buc_dc_manual_v']:'';
            //Tx Mute
            $result['mute_uses_buc_dc']          = isset($ret_cmd['mute_uses_buc_dc'])?$ret_cmd['mute_uses_buc_dc']:'';
            //2.lnb
            //LNB DC开关
            $result['lnb_dc_auto']          = isset($ret_cmd['lnb_dc_auto'])?$ret_cmd['lnb_dc_auto']:'';
            //LNB DC电压
            $result['lnb_dc_manual_v']            = isset($ret_cmd['lnb_dc_manual_v'])?$ret_cmd['lnb_dc_manual_v']:'';
            //3.仰角偏差

            //极化角偏移量
            //$result['sk_cal_offset_degs']            = isset($ret_cmd['sk_cal_offset_degs'])?$ret_cmd['sk_cal_offset_degs']:'';
            $result['sk_hori_pol_cal_offest_degs']            = isset($ret_cmd['sk_hori_pol_cal_offest_degs'])?$ret_cmd['sk_hori_pol_cal_offest_degs']:'';
            $result['sk_ver_pol_cal_offest_degs']            = isset($ret_cmd['sk_ver_pol_cal_offest_degs'])?$ret_cmd['sk_ver_pol_cal_offest_degs']:'';

            //4.门限设置
            ///home/bird/data/acu_tracking_config.json
            $jsonfile = '/home/bird/data/acu_tracking_config.json';
            $ret_json = $this->readfile($jsonfile);
            if(!empty($ret_json))
            {
                $ret_json = json_decode($ret_json, true);
                //锁星门限
                $result['Immediate_Track_Threshold_dB']           = isset($ret_json['Immediate_Track_Threshold_dB'])?$ret_json['Immediate_Track_Threshold_dB']:'';
                //旁瓣门限
                $result['Sidelobe_Check_Threshold_dB']            = isset($ret_json['Sidelobe_Check_Threshold_dB'])?$ret_json['Sidelobe_Check_Threshold_dB']:'';
                //关闭发射门限
                $result['Leave_Track_Threshold_dB']           = isset($ret_json['Leave_Track_Threshold_dB'])?$ret_json['Leave_Track_Threshold_dB']:'';
                //多滚降功能设置FSA_Multi_Rolloff_Enabled
                $result['FSA_Multi_Rolloff_Enabled']           = isset($ret_json['FSA_Multi_Rolloff_Enabled'])?$ret_json['FSA_Multi_Rolloff_Enabled']:'';
                $result['FSA_Multi_Rolloff_Percent_Table']           = isset($ret_json['FSA_Multi_Rolloff_Percent_Table'])?$ret_json['FSA_Multi_Rolloff_Percent_Table']:'';
                $result['mute_by_buc_dc_enabled']           = isset($ret_json['mute_by_buc_dc_enabled'])?$ret_json['mute_by_buc_dc_enabled']:'';
                $result['mute_by_L10_switch_enabled']           = isset($ret_json['mute_by_L10_switch_enabled'])?$ret_json['mute_by_L10_switch_enabled']:'';
                $result['mute_by_openamip_enabled']           = isset($ret_json['mute_by_openamip_enabled'])?$ret_json['mute_by_openamip_enabled']:'';

            }
            $jsonfile = '/home/bird/data/acu_22k_buc_config.json';
            $commands = 'sudo mod_cli acu ' . $this->output . ' rc_acu_buc ' . $jsonfile;
            $ret_json = $this->readCli_byCmd_noRet($commands,$jsonfile);
            if(!empty($ret_json))
            {
                $ret_json = json_decode($ret_json, true);
                $result['Switch_22k']  = isset($ret_json['Switch_22k'])?$ret_json['Switch_22k']:'';
                $result['BUC_Tx_Switch']  = isset($ret_json['BUC_Tx_Switch'])?$ret_json['BUC_Tx_Switch']:'';
                $result['BUC_Tx_Power']  = isset($ret_json['BUC_Tx_Power'])?$ret_json['BUC_Tx_Power']:'';
                $result['BUC_Power']  = isset($ret_json['BUC_Power'])?$ret_json['BUC_Power']:'';
                $result['BUC_Temperature']  = isset($ret_json['BUC_Temperature'])?$ret_json['BUC_Temperature']:'';
                $result['BUC_Temp_Out_Of_Range']  = isset($ret_json['BUC_Temp_Out_Of_Range'])?$ret_json['BUC_Temp_Out_Of_Range']:'';
                $result['BUC_PLL_Out_Of_Lock']  = isset($ret_json['BUC_PLL_Out_Of_Lock'])?$ret_json['BUC_PLL_Out_Of_Lock']:'';
                $result['BUC_Checksum_Err']  = isset($ret_json['BUC_Checksum_Err'])?$ret_json['BUC_Checksum_Err']:'';
                $result['BUC_Attenuator']  = isset($ret_json['BUC_Attenuator'])?$ret_json['BUC_Attenuator']:'';
            }
            else{
                $result['Switch_22k']  = '';
                $result['BUC_Tx_Switch']  = '';
                $result['BUC_Tx_Power']  = '';
                $result['BUC_Power']  = '';
                $result['BUC_Temperature']  = '';
                $result['BUC_Temp_Out_Of_Range']  = '';
                $result['BUC_PLL_Out_Of_Lock']  = '';
                $result['BUC_Checksum_Err']  = '';
                $result['BUC_Attenuator']  = '';
            }


        }
        return $this->response($result);
    }

}