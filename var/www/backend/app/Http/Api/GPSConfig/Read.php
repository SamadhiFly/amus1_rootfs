<?php
namespace App\Http\Api\GPSConfig;

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
        if(!empty($ret_cmd)) {
            $ret_cmd = json_decode($ret_cmd, true);
            //$result['ret_cmd']          = $ret_cmd;
            //卫星参数获取方式"mf_enabled"
            $result['mf_enabled']       = isset($ret_cmd['mf_enabled'])?$ret_cmd['mf_enabled']:'';
            //false  = openamip
            $acuModel = $this->connection('status')->table('status')
                ->where('module','ACU')
                ->orderBy('id','desc')
                ->first();
            $data = json_decode($acuModel['json_status'],true);
            if($data) {
                $Openamipdata = [];
                if($result['mf_enabled']==false)
                {
                    //ID编号
                    //"carrier_id"
                    $result['carrier_id']          = isset($data['mf_active_carrier_id'])?$data['mf_active_carrier_id']:'';
                    //当前生效ID编号"mf_carrier_id"
                    $result['mf_carrier_id']          = isset($data['mf_active_carrier_id'])?$data['mf_active_carrier_id']:'';
                    //卫星名称
                    //"sat_name"
                    $result['sat_name']            = isset($data['sat_name'])?$data['sat_name']:'';
                    //卫星经度
                    //"sat_longitude"
                    $result['sat_longitude']           = isset($data['openamip_sat_longitude_degs'])?$data['openamip_sat_longitude_degs']:'';
                    //载波中心频率
                    //"rf_centre_frequency_khz"
                    $result['rf_centre_frequency_khz']            = isset($data['openamip_rx_rf_freq_khz'])?$data['openamip_rx_rf_freq_khz']:'';
                    //符号率
                    //"symbol_rate_ksps"
                    $result['symbol_rate_ksps']           = isset($data['openamip_rx_symbol_rate_ksps'])?$data['openamip_rx_symbol_rate_ksps']:'';
                    //载波带宽
                    //"bandwidth_khz"
                    $result['bandwidth_khz']            = isset($data['openamip_rx_bandwidth_khz'])?$data['openamip_rx_bandwidth_khz']:'';
                    //RX 极化方式
                    //"transponder_polarisation"
                    $result['transponder_polarisation']           = isset($data['openamip_rx_polarisation'])?$data['openamip_rx_polarisation']:'';
                    //滚降系数
                    //"rolloff_percent"
                    $result['rolloff_percent']           = isset($data['openamip_rx_rolloff'])?$data['openamip_rx_rolloff']:'';
                    //极化偏移量
                    //"sat_skew_offset"
                    $result['sat_skew_offset']           = isset($data['openamip_sat_skew_offset_degs'])?$data['openamip_sat_skew_offset_degs']:'';

                }
                $Openamipdata['carrier_id']  = isset($data['mf_active_carrier_id'])?$data['mf_active_carrier_id']:'';
                $Openamipdata['mf_carrier_id']  = isset($data['mf_active_carrier_id'])?$data['mf_active_carrier_id']:'';
                $Openamipdata['sat_name']  = isset($data['sat_name'])?$data['sat_name']:'';
                $Openamipdata['sat_longitude']  = isset($data['openamip_sat_longitude_degs'])?$data['openamip_sat_longitude_degs']:'';
                $Openamipdata['rf_centre_frequency_khz']  = isset($data['openamip_rx_rf_freq_khz'])?$data['openamip_rx_rf_freq_khz']:'';
                $Openamipdata['symbol_rate_ksps']  = isset($data['openamip_rx_symbol_rate_ksps'])?$data['openamip_rx_symbol_rate_ksps']:'';
                $Openamipdata['bandwidth_khz']  = isset($data['openamip_rx_bandwidth_khz'])?$data['openamip_rx_bandwidth_khz']:'';
                $Openamipdata['transponder_polarisation']  = isset($data['openamip_rx_polarisation'])?$data['openamip_rx_polarisation']:'';
                $Openamipdata['rolloff_percent']  = isset($data['openamip_rx_rolloff'])?$data['openamip_rx_rolloff']:'';
                $Openamipdata['sat_skew_offset']  = isset($data['openamip_sat_skew_offset_degs'])?$data['openamip_sat_skew_offset_degs']:'';
                $result['openamip']  = $Openamipdata;
            }


            $acuModel = $this->connection('status')->table('status')
                    ->where('module','ACU')
                    ->orderBy('id','desc')
                    ->first();
            $data = json_decode($acuModel['json_status'],true);
            if($data) {
                    //ID编号
                    //"carrier_id"
                    $result['carrier_id'] = isset($data['mf_active_carrier_id']) ? $data['mf_active_carrier_id'] : '';
                    //当前生效ID编号"mf_carrier_id"
                    $result['mf_carrier_id'] = isset($data['mf_active_carrier_id']) ? $data['mf_active_carrier_id'] : '';

                    $MFModel1 = $this->connection('config')->table('config')
                        ->where('active',1)
                        ->where('group_name','MF')
                        ->orderBy('id','desc')
                        ->get();
                    if($MFModel1) {
                        $carrier_id = $result['carrier_id'] ;
                        $MFdata1 = json_decode($MFModel1[0]['json_config'],true);
                        $MFdata2 = $MFdata1['carriers'];
                        $carrier_id_Index = 0;
                        foreach ($MFdata2 as $MFdataSingle) {
                            if(intval($MFdataSingle['carrier_id']) == intval($carrier_id))
                            {
                                break;
                            }
                            $carrier_id_Index = $carrier_id_Index + 1;
                        }
                        if($result['mf_enabled']==true)
                        {
                            $carrier_id = $carrier_id_Index;
                            $MFdata1 = json_decode($MFModel1[0]['json_config'], true);
                            $result['sat_name'] = isset($MFdata1['carriers'][$carrier_id]['sat_name']) ? $MFdata1['carriers'][$carrier_id]['sat_name'] : '';

                            //卫星经度
                            //"sat_longitude"
                            $result['sat_longitude'] = isset($MFdata1['carriers'][$carrier_id]['sat_longitude']) ? $MFdata1['carriers'][$carrier_id]['sat_longitude'] : '';
                            //载波中心频率
                            //"rf_centre_frequency_khz"
                            $result['rf_centre_frequency_khz'] = isset($MFdata1['carriers'][$carrier_id]['rf_centre_frequency_khz']) ? $MFdata1['carriers'][$carrier_id]['rf_centre_frequency_khz'] : '';
                            //符号率
                            //"symbol_rate_ksps"
                            $result['symbol_rate_ksps'] = isset($MFdata1['carriers'][$carrier_id]['symbol_rate_ksps']) ? $MFdata1['carriers'][$carrier_id]['symbol_rate_ksps'] : '';
                            //载波带宽
                            //"bandwidth_khz"
                            $result['bandwidth_khz'] = isset($MFdata1['carriers'][$carrier_id]['bandwidth_khz']) ? $MFdata1['carriers'][$carrier_id]['bandwidth_khz'] : '';
                            //RX 极化方式
                            //"transponder_polarisation"
                            $result['transponder_polarisation'] = isset($MFdata1['carriers'][$carrier_id]['transponder_polarisation']) ? $MFdata1['carriers'][$carrier_id]['transponder_polarisation'] : '';
                            //滚降系数
                            //"rolloff_percent"
                            $result['rolloff_percent'] = isset($MFdata1['carriers'][$carrier_id]['rolloff_percent']) ? $MFdata1['carriers'][$carrier_id]['rolloff_percent'] : '';
                            //极化偏移量
                            //"sat_skew_offset"
                            $result['sat_skew_offset'] = isset($MFdata1['carriers'][$carrier_id]['sat_skew_offset']) ? $MFdata1['carriers'][$carrier_id]['sat_skew_offset'] : '';

                        }
                    /*
                    $jsonfile = '/home/bird/configs/mf_all_cfg.json';
                    $commands = 'sudo mod_cli acu ' . $this->output . ' sc_mf_all ' . $jsonfile;
                    $ret_cmd = $this->readCli_byCmd($commands,$jsonfile);
                    if(!empty($ret_cmd))
                    {
                        $ret_cmd = json_decode($ret_cmd, true);
                        $carrier_id = $result['carrier_id'] ;
                        $carrier_id = $carrier_id - 1;
                        $result['sat_name']            = isset($ret_cmd['carriers'][$carrier_id]['sat_name'])?$ret_cmd['carriers'][$carrier_id]['sat_name']:'';
                        //卫星经度
                        //"sat_longitude"
                        $result['sat_longitude']           = isset($ret_cmd['carriers'][$carrier_id]['sat_longitude'])?$ret_cmd['carriers'][$carrier_id]['sat_longitude']:'';
                        //载波中心频率
                        //"rf_centre_frequency_khz"
                        $result['rf_centre_frequency_khz']            = isset($ret_cmd['carriers'][$carrier_id]['rf_centre_frequency_khz'])?$ret_cmd['carriers'][$carrier_id]['rf_centre_frequency_khz']:'';
                        //符号率
                        //"symbol_rate_ksps"
                        $result['symbol_rate_ksps']           = isset($ret_cmd['carriers'][$carrier_id]['symbol_rate_ksps'])?$ret_cmd['carriers'][$carrier_id]['symbol_rate_ksps']:'';
                        //载波带宽
                        //"bandwidth_khz"
                        $result['bandwidth_khz']            = isset($ret_cmd['carriers'][$carrier_id]['bandwidth_khz'])?$ret_cmd['carriers'][$carrier_id]['bandwidth_khz']:'';
                        //RX 极化方式
                        //"transponder_polarisation"
                        $result['transponder_polarisation']           = isset($ret_cmd['carriers'][$carrier_id]['transponder_polarisation'])?$ret_cmd['carriers'][$carrier_id]['transponder_polarisation']:'';
                        //滚降系数
                        //"rolloff_percent"
                        $result['rolloff_percent']           = isset($ret_cmd['carriers'][$carrier_id]['rolloff_percent'])?$ret_cmd['carriers'][$carrier_id]['rolloff_percent']:'';
                        //极化偏移量
                        //"sat_skew_offset"
                        $result['sat_skew_offset']           = isset($ret_cmd['carriers'][$carrier_id]['sat_skew_offset'])?$ret_cmd['carriers'][$carrier_id]['sat_skew_offset']:'';


                    }
                    */
                }

            }

            $MFModel2= $this->connection('config')->table('config')
                ->where('active',1)
                ->whereIN('group_name',['MF'])
                ->orderBy('id','desc')
                ->get();
            if($MFModel2) {

                $MFdata2 = json_decode($MFModel2[0]['json_config'],true);
                $result['sc_mf_all']  = $MFdata2;
            }
            /*
            $jsonfile = '/home/bird/configs/mf_all_cfg.json';
            $commands = 'sudo mod_cli acu ' . $this->output . ' sc_mf_all ' . $jsonfile;
            $ret_cmd = $this->readCli_byCmd($commands,$jsonfile);
            if(!empty($ret_cmd)) {
                $ret_cmd = json_decode($ret_cmd, true);
                $result['sc_mf_all']  = $ret_cmd;
            }
            */
        }
        
        return $this->response($result);
    }

}