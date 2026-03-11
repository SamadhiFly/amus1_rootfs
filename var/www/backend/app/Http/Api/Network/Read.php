<?php
namespace App\Http\Api\Network;

use App\Http\Api\GatewayAbstract;

/**
 * Class Read
 * @package App\Http\Api\Network
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
        $model = $this->connection('config')->table('config')
            ->whereIn('group_name',['ACU','NET'])
            ->where('active',1)
            ->get();
        if($model) {
            foreach ($model as $key => $val) {
                $data = json_decode($val['json_config'],true);
                switch ($val['group_name']) {
                    case 'NET':
                        /*
                        $result['format_version']           = isset($data['format_version'])?$data['format_version']:'';
                        $result['srv_port_interface']       = isset($data['srv_port_interface'])?$data['srv_port_interface']:'';
                        $result['srv_port_address']         = isset($data['srv_port_address'])?$data['srv_port_address']:'';
                        $result['srv_port_mode']            = isset($data['srv_port_mode'])?$data['srv_port_mode']:'';
                        $result['data_port_interface']      = isset($data['data_port_interface'])?$data['data_port_interface']:'';
                        $result['data_port_address_modem']  = isset($data['data_port_address_modem'])?$data['data_port_address_modem']:'';
                        $result['data_port_mode_modem']     = isset($data['data_port_mode_modem'])?$data['data_port_mode_modem']:'';
                        $result['data_port_address_wan']    = isset($data['data_port_address_wan'])?$data['data_port_address_wan']:'';
                        $result['data_port_mode_wan']       = isset($data['spare_port']['address'])?$data['spare_port']['address']:'';
                        $result['data_port_wan_vlan']       = isset($data['format_version'])?$data['format_version']:'';
                        $result['data_port_wan_vlanid']     = isset($data['data_port_wan_vlanid'])?$data['data_port_wan_vlanid']:'';
                        */
                        $result['ret']           = isset($data)?$data:'';
                        break;
                    case 'ACU':
                        $result['openamip_tcp_port'] = isset($data['openamip_tcp_port'])?$data['openamip_tcp_port']:'';
                        break;
                    default;
                }
            }
        }
        return $this->response($result);
    }

}