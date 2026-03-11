<?php
namespace App\Http\Api\GPSConfig;

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
            'sign'              => 'required',
            'data'              => 'required',
        ],[
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
        $dataacu = [];
        if(empty($data)) {
            $this->error('无效的data字段结构');
        }
        $carrier_id = 0;
        $commands = [];
        $commands_mf = "";
        $signs = $this->_params['sign'];
        if($signs == 'acu')
        {
            $acuConfig = '/home/bird/configs/acu_all_cfg.json';
            if(isset($data['mf_enabled']))
            {

                $data['mf_enabled'] = $data['mf_enabled'];

                if($data['mf_enabled'] == false)
                {
                    $dataacu['openamip_rx_rolloff_percent'] = $data['rolloff_percent'];
                    $dataacu['mf_enabled'] = $data['mf_enabled'];
                }
                else{
                    $dataacu['mf_enabled'] = $data['mf_enabled'];
                    $dataacu['mf_carrier_id'] = $data['mf_carrier_id'];
                }

                array_push($commands, [
                    'file' => $acuConfig,
                    'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' sc_acu_all ' . $acuConfig,
                    'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_acu_all ',

                ]);

                array_push($commands, [
                    'read_cmd' => 'sudo save_config ',

                ]);

                if(!empty($commands)) {
                    $this->syncCallCli($commands,$dataacu);
                }
            }



            if(isset($data['mf_enabled']))
            {
                if($data['mf_enabled'] == true)
                {
                    $carrier_id = 0;

                    if($carrier_id>=0)
                    {

                        $jsonfile_mf_all_cfg = '/home/bird/configs/mf_all_cfg.json';
                        $output = '/tmp/gui_command_output.txt';
                        $commands_mf =  'sudo mod_cli acu ' . $output . ' sc_mf_all ' . $jsonfile_mf_all_cfg;
                        $ret_cmd = $this->readCli_byCmd($commands_mf,$jsonfile_mf_all_cfg);

                        if(!empty($ret_cmd)) {
                            $ret_cmd = preg_replace('/[\x00-\x1F]/','', $ret_cmd);

                            $ret_cmd = json_decode($ret_cmd, true);
                            $MFdata2 = $ret_cmd['carriers'];
                            foreach ($MFdata2 as $MFdataSingle) {
                                if(intval($MFdataSingle['carrier_id']) == intval($data['mf_carrier_id']))
                                {
                                    break;
                                }
                                $carrier_id = $carrier_id + 1;
                            }

                            if(isset($ret_cmd['carriers']))
                            {

                                if (isset($data['sat_name']) && !empty($data['sat_name']))
                                {
                                    $ret_cmd['carriers'][$carrier_id]['sat_name'] = $data['sat_name'];
                                }
                                else{
                                    $ret_cmd['carriers'][$carrier_id]['sat_name'] = '';
                                }
                                if (isset($data['sat_longitude']) && !empty($data['sat_longitude']))
                                {
                                    $ret_cmd['carriers'][$carrier_id]['sat_longitude'] = floatval($data['sat_longitude']);
                                }
                                if (isset($data['symbol_rate_ksps']) && !empty($data['symbol_rate_ksps']))
                                {
                                    $ret_cmd['carriers'][$carrier_id]['symbol_rate_ksps'] = intval($data['symbol_rate_ksps']);
                                }
                                if (isset($data['bandwidth_khz']) && !empty($data['bandwidth_khz']))
                                {
                                    $ret_cmd['carriers'][$carrier_id]['bandwidth_khz'] = intval($data['bandwidth_khz']);
                                }
                                if (isset($data['transponder_polarisation']) && !empty($data['transponder_polarisation']))
                                {
                                    $ret_cmd['carriers'][$carrier_id]['transponder_polarisation'] = $data['transponder_polarisation'];
                                }
                                if (isset($data['rolloff_percent']))
                                {
                                    $ret_cmd['carriers'][$carrier_id]['rolloff_percent'] = intval($data['rolloff_percent']);
                                }
                                if (isset($data['sat_skew_offset']))
                                {
                                    //$ret_cmd['carriers'][$carrier_id]['sat_skew_offset'] = intval($data['sat_skew_offset']);
                                    $ret_cmd['carriers'][$carrier_id]['sat_skew_offset'] = floatval($data['sat_skew_offset']);
                                }
                                if (isset($data['rf_centre_frequency_khz']) && !empty($data['rf_centre_frequency_khz']))
                                {
                                    $ret_cmd['carriers'][$carrier_id]['rf_centre_frequency_khz'] = intval($data['rf_centre_frequency_khz']);
                                }

                                $commands = [];

                                array_push($commands,[
                                    'file'          => $jsonfile_mf_all_cfg,
                                    'read_cmd'      => 'sudo mod_cli acu '.$output.' sc_mf_all ' .$jsonfile_mf_all_cfg,
                                    'write_cmd'     => 'sudo mod_cli acu '.$output.' ljc_mf_all ' ,

                                ]);
                                array_push($commands, [
                                    'read_cmd' => 'sudo save_config ',

                                ]);

                                if(!empty($commands)) {
                                    $this->syncCallCli($commands,$ret_cmd);
                                }

                                //$mfConfig  = '/home/bird/configs/mf_all_cfg.json';
                                //$commands = 'mf_all_cfg';
                                //$this->writeJsonFile($ret_cmd,$mfConfig);


                            }

                        }
                    }

                }
            }
        }
        else if($signs == 'active')
        {
            //将acu的mf_carrier_id设置为传回参数
            $acuConfig = '/home/bird/configs/acu_all_cfg.json';
            if(isset($data['mf_enabled'])) {
                array_push($commands, [
                    'file' => $acuConfig,
                    'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' sc_acu_all ' . $acuConfig,
                    'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_acu_all ',

                ]);

                array_push($commands, [
                    'read_cmd' => 'sudo save_config ',

                ]);

                if (!empty($commands)) {
                    $this->syncCallCli($commands, $data);
                }
            }
        }
        else if($signs == 'add')
        {
            $acuConfig = '/home/bird/configs/acu_all_cfg.json';
            $dataacu['mf_enabled'] = $data['mf_enabled'];
            array_push($commands, [
                'file' => $acuConfig,
                'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' sc_acu_all ' . $acuConfig,
                'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_acu_all ',

            ]);

            array_push($commands, [
                'read_cmd' => 'sudo save_config ',

            ]);

            if(!empty($commands)) {
                $this->syncCallCli($commands,$dataacu);
            }

            $MFModel2= $this->connection('config')->table('config')
                ->where('active',1)
                ->whereIN('group_name',['MF'])
                ->orderBy('id','desc')
                ->get();
            if($MFModel2) {

                $MFdata1 = json_decode($MFModel2[0]['json_config'],true);
                $MFdata2 = $MFdata1['carriers'];
                $MFdataSingle_id = 0;
                foreach ($MFdata2 as $MFdataSingle) {
                    if(intval($MFdataSingle['carrier_id'])>$MFdataSingle_id)
                    {
                        $MFdataSingle_id = intval($MFdataSingle['carrier_id']) ;
                    }
                }
                $carrier_Maxid = sizeof($MFdata2)-1;
                $carrier_Newid = $MFdataSingle_id+1;

                $carrier_New['carrier_id'] = $carrier_Newid;
                $carrier_New['sat_name'] = '';
                if (isset($data['sat_name']) && !empty($data['sat_name']))
                {
                    $carrier_New['sat_name'] = $data['sat_name'];
                }
                $carrier_New['sat_longitude'] = 0;
                if (isset($data['sat_longitude']) && !empty($data['sat_longitude']))
                {
                    $carrier_New['sat_longitude'] = floatval($data['sat_longitude']);
                }
                if (isset($data['symbol_rate_ksps']) && !empty($data['symbol_rate_ksps']))
                {
                    $carrier_New['symbol_rate_ksps'] = intval($data['symbol_rate_ksps']);
                }
                if (isset($data['bandwidth_khz']) && !empty($data['bandwidth_khz']))
                {
                    $carrier_New['bandwidth_khz'] = intval($data['bandwidth_khz']);
                }
                if (isset($data['transponder_polarisation']) && !empty($data['transponder_polarisation']))
                {
                    $carrier_New['transponder_polarisation'] = $data['transponder_polarisation'];
                }
                $carrier_New['rolloff_percent'] = 0;
                if (isset($data['rolloff_percent']) && !empty($data['rolloff_percent']))
                {
                    $carrier_New['rolloff_percent'] = intval($data['rolloff_percent']);
                }
                $carrier_New['sat_skew_offset'] = 0;
                if (isset($data['sat_skew_offset']) && !empty($data['sat_skew_offset']))
                {
                    //$carrier_New['sat_skew_offset'] = intval($data['sat_skew_offset']);
                    $carrier_New['sat_skew_offset'] = floatval($data['sat_skew_offset']);
                }
                if (isset($data['rf_centre_frequency_khz']) && !empty($data['rf_centre_frequency_khz']))
                {
                    $carrier_New['rf_centre_frequency_khz'] = intval($data['rf_centre_frequency_khz']);
                }

                $carrier_New['carrier_name'] = $MFdata2[$carrier_Maxid]['carrier_name'];
                $carrier_New['type'] = $MFdata2[$carrier_Maxid]['type'];
                $carrier_New['sat_latitude'] = $MFdata2[$carrier_Maxid]['sat_latitude'];
                $carrier_New['transponder_name'] = $MFdata2[$carrier_Maxid]['transponder_name'];

                array_push($MFdata2,$carrier_New);
                $MFdata1['carriers'] = $MFdata2;
                $MFdata1['num_carriers'] = sizeof($MFdata2);

                $jsonfile_mf_all_cfg = '/home/bird/configs/mf_all_cfg.json';
                $output = '/tmp/gui_command_output.txt';
                $commands_mf =  'sudo mod_cli acu ' . $output . ' sc_mf_all ' . $jsonfile_mf_all_cfg;
                $ret_cmd = $this->readCli_byCmd($commands_mf,$jsonfile_mf_all_cfg);


                $commands = [];

                array_push($commands,[
                            'file'          => $jsonfile_mf_all_cfg,
                            'read_cmd'      => 'sudo mod_cli acu '.$output.' sc_mf_all ' .$jsonfile_mf_all_cfg,
                            'write_cmd'     => 'sudo mod_cli acu '.$output.' ljc_mf_all ' ,

                ]);
                array_push($commands, [
                            'read_cmd' => 'sudo save_config ',

                ]);

                if(!empty($commands)) {
                    $this->syncCallCli($commands,$MFdata1);
                }

            }
        }
        else if($signs == 'del')
        {
            if(1 == intval($data['mf_carrier_id']))
            {
                $this->error('can not delete this carrier id');
            }
            $acuModel = $this->connection('status')->table('status')
                ->where('module','ACU')
                ->orderBy('id','desc')
                ->first();
            $acudata = json_decode($acuModel['json_status'],true);
            if($acudata) {
                //ID编号
                //"carrier_id"
                $active_carrier_id = isset($acudata['mf_active_carrier_id']) ? $acudata['mf_active_carrier_id'] : '0';
                if(intval($active_carrier_id) == intval($data['mf_carrier_id']))
                {
                    $this->error('can not delete active carrier id');
                }
            }
            $MFModel2= $this->connection('config')->table('config')
                ->where('active',1)
                ->whereIN('group_name',['MF'])
                ->orderBy('id','desc')
                ->get();
            if($MFModel2) {

                $MFdata1 = json_decode($MFModel2[0]['json_config'],true);
                $MFdata2 = $MFdata1['carriers'];
                $carrier_Delid = 0;

                foreach ($MFdata2 as $MFdataSingle) {
                    if(intval($MFdataSingle['carrier_id']) == intval($data['mf_carrier_id']))
                    {
                        break;
                    }
                    $carrier_Delid = $carrier_Delid + 1;
                }


                array_splice($MFdata2,$carrier_Delid,1);
                $MFdata1['carriers'] = $MFdata2;
                $MFdata1['num_carriers'] = sizeof($MFdata2);

                $jsonfile_mf_all_cfg = '/home/bird/configs/mf_all_cfg.json';
                $output = '/tmp/gui_command_output.txt';
                $commands_mf =  'sudo mod_cli acu ' . $output . ' sc_mf_all ' . $jsonfile_mf_all_cfg;
                $ret_cmd = $this->readCli_byCmd($commands_mf,$jsonfile_mf_all_cfg);


                $commands = [];

                array_push($commands,[
                    'file'          => $jsonfile_mf_all_cfg,
                    'read_cmd'      => 'sudo mod_cli acu '.$output.' sc_mf_all ' .$jsonfile_mf_all_cfg,
                    'write_cmd'     => 'sudo mod_cli acu '.$output.' ljc_mf_all ' ,

                ]);
                array_push($commands, [
                    'read_cmd' => 'sudo save_config ',

                ]);

                if(!empty($commands)) {
                    $this->syncCallCli($commands,$MFdata1);
                }

            }
        }



        return $this->response();
    }

}
