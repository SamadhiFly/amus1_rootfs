<?php
namespace App\Http\Api\Agc;

use App\Http\Api\GatewayAbstract;

/**
 * Class Write
 * @package App\Http\Api\Agc
 */
class Write extends GatewayAbstract
{

    /**
     * @return bool|mixed
     */
    public function validateParams()
    {
        return $this->_validate($this->_params,[
            'default_modem'             => 'required',
            'manual_agc_enable'         => 'required',
            'agc_threshold_value'       => 'required',
            'agc_threshold_loopback'    => 'required',
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $agcConfig  = '/tmp/agc_config.json';
        $commands = [
            [
                'file'          => $agcConfig,
                'read_cmd'      => 'mod_cli acu '.$this->output.' wjc_acu_all '. $agcConfig,
                'write_cmd'     => 'mod_cli acu '.$this->output.' mjc_acu_all ',
            ],[
                'acu_cmd'      => 'mod_cli acu '.$this->output.' start',
            ]
        ];
        $attributes = [
            'default_modem'             => $this->_params['default_modem'],
            'manual_agc_enable'         => ($this->_params['manual_agc_enable']=='false')?false:true,
            'agc_threshold_value'       => intval($this->_params['agc_threshold_value']),
            'agc_threshold_loopback'    => intval($this->_params['agc_threshold_loopback']),
        ];
        $this->syncCallCli($commands,$attributes);
        return $this->response();
    }

}
