<?php
namespace App\Http\Api\Configs;

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
        //pmt model
        $pmtModel = $this->connection('status')->table('status')
            ->where('module','PMT')
            ->orderBy('id','desc')
            ->first();
        if($pmtModel) {
            $data = json_decode($pmtModel['json_status'],true);
            if($data) {
                $result['sw_version']         = isset($data['sw_version'])?$data['sw_version']:'';
                $result['os_version']         = isset($data['os_version'])?$data['os_version']:'';
                $result['cfg_version']       = isset($data['cfg_version'])?$data['cfg_version']:'';
            }
        }

        //read config data in RT database
        $data = $this->connection('config')->table('config')
            ->where('active',1)
            ->whereIN('group_name',['HI','PMT','ACU','MF'])
            ->orderBy('id','desc')
            ->get();
        if($data) {
            foreach ($data as $key => $val) {
                $jsonData = json_decode($val['json_config']);
                switch ($val['group_name']) {
                    case 'HI':
                        $result['antenna_model']        = isset($jsonData->antenna_model)?$jsonData->antenna_model:'';//antenna_model（config Hi）
                        $result['antenna_serial']       = isset($jsonData->antenna_serial)?$jsonData->antenna_serial:'';//antenna_serial（config Hi）
                        $result['modem_model']          = isset($jsonData->modem_model)?$jsonData->modem_model:'';//modem_model（config Hi）
                        $result['BMB_model']            = isset($jsonData->BMB_model)?$jsonData->BMB_model:'';//BMB_model（config Hi）
                        $result['amu_model']            = isset($jsonData->amu_model)?$jsonData->amu_model:'';//amu_model（config Hi）
                        $result['amu_serial']           = isset($jsonData->amu_serial)?$jsonData->amu_serial:'';//amu_serial（config Hi）
                        $result['buc_model']            = isset($jsonData->buc_model)?$jsonData->buc_model:'';//buc_model（config Hi）
                        $result['buc_serial']           = isset($jsonData->buc_serial)?$jsonData->buc_serial:'';//buc_serial（config Hi）
                        $result['lnb_model']            = isset($jsonData->lnb_model)?$jsonData->lnb_model:'';//lnb_model（config Hi）
                        $result['lnb_serial']           = isset($jsonData->lnb_serial)?$jsonData->lnb_serial:'';//lnb_serial（config Hi）
                        break;
                    case 'ACU':
                        //$result['sk_cal_offset_degs']   = isset($jsonData->sk_cal_offset_degs)?$jsonData->sk_cal_offset_degs:'';//sk_cal_offset_degs（config acu）
                        $result['sk_hori_pol_cal_offest_degs']            = isset($jsonData->sk_hori_pol_cal_offest_degs)?$jsonData->sk_hori_pol_cal_offest_degs:'';
                        $result['sk_ver_pol_cal_offest_degs']            = isset($jsonData->sk_ver_pol_cal_offest_degs)?$jsonData->sk_ver_pol_cal_offest_degs:'';
                        $result['mute_uses_buc_dc']     = isset($jsonData->mute_uses_buc_dc)?$jsonData->mute_uses_buc_dc:'';//mute_uses_buc_dc（config acu）
                        $result['manual_mute']          = isset($jsonData->manual_mute)?$jsonData->manual_mute:'';//manual_mute（config acu）
                        $result['buc_dc_manual_v']      = isset($jsonData->buc_dc_manual_v)?$jsonData->buc_dc_manual_v:'';//buc_dc_manual_v（config acu）
                        $result['buc_dc_auto']          = isset($jsonData->buc_dc_auto)?$jsonData->buc_dc_auto:'';//buc_dc_auto（config acu）
                        $result['lnb_dc_manual_v']      = isset($jsonData->lnb_dc_manual_v)?$jsonData->lnb_dc_manual_v:'';//lnb_dc_manual_v（config acu）
                        $result['lnb_dc_auto']          = isset($jsonData->lnb_dc_auto)?$jsonData->lnb_dc_auto:'';//lnb_dc_auto（config acu）
                        $result['lnb_10m_manual']       = isset($jsonData->lnb_10m_manual)?$jsonData->lnb_10m_manual:'';//lnb_10m_manual+ lnb_10m_auto（config acu）
                        $result['lnb_10m_auto']         = isset($jsonData->lnb_10m_auto)?$jsonData->lnb_10m_auto:'';//lnb_10m_manual+ lnb_10m_auto（config acu）
                        $result['mf_enabled']           = isset($jsonData->mf_enabled)?$jsonData->mf_enabled:false;//mf_enabled
                        $result['openamip_rx_rolloff_percent']           = isset($jsonData->openamip_rx_rolloff_percent)?$jsonData->openamip_rx_rolloff_percent:'';//mf_enabled
                        if($result['mf_enabled']==false)
                        {
                            $result['rx_rolloff_percent']           = $result['openamip_rx_rolloff_percent'] ;
                        }
                        else{
                            if(isset($result['carriers']['rolloff_percent']))
                            {
                                $result['rx_rolloff_percent']           = $result['carriers']['rolloff_percent'];
                            }
                        }

                        break;
                        /*
                    case 'PMT':
                        $result['sw_version']           = isset($jsonData->sw_version)?$jsonData->sw_version:'';//sw_version（status PMT）
                        $result['os_version']           = isset($jsonData->os_version)?$jsonData->os_version:'';//os_version（status PMT）
                        $result['cfg_version']          = isset($jsonData->cfg_version)?$jsonData->cfg_version:'';//cfg_version（status PMT）
                        break;
                        */
                    case 'MF':
                        $result['carriers']             = isset($jsonData->carriers[0])?$jsonData->carriers[0]:[];
                        break;
                    default;
                }
            }
            return $this->response($result);
        }
        return $this->response($result);
    }

}