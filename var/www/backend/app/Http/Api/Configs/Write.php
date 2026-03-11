<?php
namespace App\Http\Api\Configs;

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
        $signs = explode(',',$this->_params['sign']);
        foreach ($signs as $sign) {
            switch ($sign) {
                case 'acu':
                    $acuConfig = '/home/bird/configs/acu_all_cfg.json';
                    //array_push($commands,[
                    //    'file'          => $acuConfig,
                    ///    'read_cmd'      => 'mod_cli acu '.$this->output.' wjc_acu_all' .$acuConfig,
                    //     'write_cmd'     => 'mod_cli acu '.$this->output.' mjc_acu_all ' ,
                    //]);
                    if(isset($data['carriers']['mf_enabled']))
                    {
                        $data['mf_enabled'] = $data['carriers']['mf_enabled'];
                        if($data['carriers']['mf_enabled'] == false)
                        {
                            $data['openamip_rx_rolloff_percent'] = $data['openamip_rx_rolloff_percent'];
                        }
                    }


                    array_push($commands, [
                        'file' => $acuConfig,
                        'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' sc_acu_all ' . $acuConfig,
                        'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_acu_all ',

                    ]);

                    array_push($commands, [
                        'read_cmd' => 'sudo save_config ',

                    ]);

                        /*
                        array_push($commands,[
                            'file'          => $acuConfig,
                            'write_cmd'     => 'mod_cli acu '.$this->output.' lnbdc ' . 18,
                            'read_cmd'      => 'sudo mod_cli acu '.$this->output.' sc_acu_all ' .$acuConfig,
                            //'write_cmd'     => 'mod_cli acu '.$this->output.' lnbdc ' . $data['lnb_dc_manual_v'],

                        ]);
                        */


                    break;
                case 'hi':
                    $hiConfig = '/home/bird/configs/hi_all_cfg.json';
                    array_push($commands, [
                        'file' => $hiConfig,
                        'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' sc_hi_all ' . $hiConfig,
                        'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_hi_all ',

                    ]);

                    array_push($commands, [
                        'read_cmd' => 'sudo save_config ',

                    ]);
//                    $hiConfig  = '/home/bird/configs/hi_all_cfg.json';
//                    $result = exec('mod_cli acu '.$this->output.' sc_hi_all',$output,$return_code);
//                    if($return_code != 0) {
//                      $this->error($result);
//                    }
//                    $result = File::read($hiConfig);
//                    if($result['code'] != 0) {
//                        $this->error($result['msg']);
//                    }
//                    $resultData = json_decode($result['data'],true);
//                    if(empty($resultData)) {
//                        $this->error('文件空。');
//                    }
//                    foreach ($data as $key => $val) {
//                        $resultData[$key] = $val;
//                    }
//                    $write = File::write($hiConfig,json_encode($resultData,true));
//                    if(!$write) {
//                        $this->error('写入文件失败。');
//                    }
//                    $result = exec('mod_cli acu '.$this->output.' ljc_hi_all '.$hiConfig,$output,$return_code);
//                    if($return_code != 0) {
//                        $this->error($result);
//                    }
//                    if(isset($data['lnb_dc_manual_v']) && !empty($data['lnb_dc_manual_v']))
//                    {
//                        $result = exec('mod_cli acu '.$this->output.' lnbdc '.$data['lnb_dc_manual_v'],$output,$return_code);
//                        if($return_code != 0) {
//                            $this->error($result);
//                        }
//                    }
//                    $result = exec('sudo save_config',$output,$return_code);
//                    if($return_code != 0) {
//                        $this->error($result);
//                    }
                    break;
                case 'mf':
                    if(isset($data['carriers']['mf_enabled']))
                    {
                        $data['mf_enabled'] = $data['carriers']['mf_enabled'];
                        if($data['carriers']['mf_enabled'] == false)
                        {
                            break;
                        }
                    }
                    if (isset($data['carriers']['rolloff_percent']) && !empty($data['carriers']['rolloff_percent']))
                    {
                        $data['carriers']['rolloff_percent'] = intval($data['carriers']['rolloff_percent']);
                    }

                    if (isset($data['carriers']['rf_centre_frequency_khz']) && !empty($data['carriers']['rf_centre_frequency_khz']))
                    {
                        $data['carriers']['rf_centre_frequency_khz'] = intval($data['carriers']['rf_centre_frequency_khz']);
                    }

                    if (isset($data['carriers']['symbol_rate_ksps']) && !empty($data['carriers']['symbol_rate_ksps']))
                    {
                        $data['carriers']['symbol_rate_ksps'] = intval($data['carriers']['symbol_rate_ksps']);
                    }

                    if (isset($data['carriers']['sat_skew_offset']) && !empty($data['carriers']['sat_skew_offset']))
                    {
                        $data['carriers']['sat_skew_offset'] = intval($data['carriers']['sat_skew_offset']);
                    }

                    if (isset($data['carriers']['bandwidth_khz']) && !empty($data['carriers']['bandwidth_khz']))
                    {
                        $data['carriers']['bandwidth_khz'] = intval($data['carriers']['bandwidth_khz']);
                    }

                    if (isset($data['carriers']['sat_longitude']) && !empty($data['carriers']['sat_longitude']))
                    {
                        $data['carriers']['sat_longitude'] = floatval($data['carriers']['sat_longitude']);
                    }
                    $mfConfig  = '/home/bird/configs/mf_all_cfg.json';
                    array_push($commands,[
                        'file'          => $mfConfig,
                        'read_cmd'      => 'sudo mod_cli acu '.$this->output.' sc_mf_all ' .$mfConfig,
                        'write_cmd'     => 'sudo mod_cli acu '.$this->output.' ljc_mf_all ' ,

                    ]);

                    array_push($commands,[
                        'read_cmd'      => 'sudo save_config ',

                    ]);
                    break;
                default;
            }
        }
        if(!empty($commands)) {
            $this->syncCallCli($commands,$data);
        }
        return $this->response();
    }

}
