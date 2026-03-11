<?php
namespace App\Http\Api\Network;

use App\Http\Api\GatewayAbstract;

/**
 * Class Write
 * @package App\Http\Api\Network
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
        /*
        return $this->_validate($this->_params,[
            'format_version'            => 'required|integer',
            'srv_port_interface'        => 'required',
            'srv_port_address'          => 'required',
            'srv_port_mode'             => 'required',
            'data_port_interface'       => 'required',
            'data_port_address_modem'   => 'required',
            'data_port_mode_modem'      => 'required',
            'data_port_address_wan'     => 'required',
            'data_port_mode_wan'        => 'required',
            'data_port_wan_vlan'        => 'required|integer',
            'data_port_wan_vlanid'      => 'required',
            'openamip_tcp_port'         => 'required|integer',
        ]);
        */
        return $this->_validate($this->_params,[
            'openamip_tcp_port'         => 'required|integer',
        ],[
            'ret'   => 'ret is required',
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {
        /*
        $value['format_version']           = $this->_params['format_version'];
        $value['srv_port_interface']       = $this->_params['srv_port_interface'];
        $value['srv_port_address']         = $this->_params['srv_port_address'];
        $value['srv_port_mode']            = $this->_params['srv_port_mode'];
        $value['data_port_interface']      = $this->_params['data_port_interface'];
        $value['data_port_address_modem']  = $this->_params['data_port_address_modem'];
        $value['data_port_mode_modem']     = $this->_params['data_port_mode_modem'];
        $value['data_port_address_wan']    = $this->_params['data_port_address_wan'];
        $value['data_port_mode_wan']       = $this->_params['data_port_mode_wan'];
        $value['data_port_wan_vlan']       = $this->_params['data_port_wan_vlan'];
        $value['data_port_wan_vlanid']     = $this->_params['data_port_wan_vlanid'];
        $value['openamip_tcp_port']        = $this->_params['openamip_tcp_port'];
        */
        $commands = [];


        $value           = $this->_params['ret'];
        $value['service_port']['mode'] = strtolower($value['service_port']['mode']);
        $value['service_port']['virtual_interfaces']['openamip']['mode'] = strtolower($value['service_port']['virtual_interfaces']['openamip']['mode']);
        $value['service_port']['virtual_interfaces']['wan']['mode'] = strtolower($value['service_port']['virtual_interfaces']['wan']['mode']);
        $value['service_port']['virtual_interfaces']['wan']['mode'] = strtolower($value['service_port']['virtual_interfaces']['wan']['mode']);

        if($value['service_port']['mode'] == 'dhcp')
        {
            $value['service_port']['address'] = '';
        }
        if($value['service_port']['virtual_interfaces']['openamip']['mode'] == 'dhcp')
        {
            $value['service_port']['virtual_interfaces']['openamip']['address'] = '';
        }
        if($value['service_port']['virtual_interfaces']['wan']['mode'] == 'dhcp')
        {
            $value['service_port']['virtual_interfaces']['wan']['address'] = '';
        }
        if($value['spare_port']['mode'] == 'dhcp')
        {
            $value['spare_port']['address'] = '';
        }


        array_push($commands, [
            //'write_cmd'     => 'sudo net_config '.'\''.json_encode($value,true).'\''.' > '. $this->output . ' &',
            'write_cmd'     => 'sudo net_config '.'\''.json_encode($value,true).'\'',

        ]);

        if($value['format_version'] === '3.0')
        {
            $value['format_version'] = '2.0';
        }
        unset($value['format_version']);
        //auc openamip_tcp_port
        $acuConfig = '/home/bird/configs/acu_all_cfg.json';
        if(isset($this->_params['openamip_tcp_port']) && !empty($this->_params['openamip_tcp_port']))
        {
            $value['openamip_tcp_port'] = intval($this->_params['openamip_tcp_port']);
        }

        array_push($commands, [
            'file' => $acuConfig,
            'read_cmd' => 'sudo mod_cli acu ' . $this->output . ' sc_acu_all ' . $acuConfig,
            'write_cmd' => 'sudo mod_cli acu ' . $this->output . ' ljc_acu_all ',

        ]);
        
        array_push($commands, [
            'read_cmd' => 'sudo save_config ',

        ]);
        $this->syncCallCli($commands,$value);
        return $this->response();
    }

}