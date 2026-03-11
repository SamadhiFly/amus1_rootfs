<?php
namespace App\Http\Api\Upgrade\Software;

use App\Http\Api\GatewayAbstract;

/**
 * Class Install
 * @package App\Http\Api\Upgrade\Software
 */
class Install extends GatewayAbstract
{

    /**
     * @var bool
     */
    protected $foreLogin = false;


    /**
     * @return mixed
     */
    public function validateParams()
    {
        return $this->_validate($this->_params,[
            'name'              => 'required',
        ],[
            'name.required'     => 'required',
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $package = BASE_PATH . 'uploads/software/'.$this->_params['name'];
        if(!file_exists($package)) {
            return $this->error('软件包不存在。');
        }

        $extension = substr(strrchr($package, '.'), 1);
        if(!in_array($extension,['bas','deb'])) {
            return $this->error('文件类型不正确，仅支持[.bas | .deb]');
        }

        $commands = [];

        if($extension == 'bas') {
            array_push($commands,[
                'write_cmd'     => 'sudo install_sw -f '.$package.' -i gui_upgrade_'. date('Ymd_His') .' -c gui -g '.$this->output.' &'
            ]);
        }

        if($extension == 'deb') {
            array_push($commands,[
                'write_cmd'     => 'sudo opkg install '.$package.' > '. $this->output . ' &'
            ]);
        }
        $this->syncCallCli($commands,[]);
        return $this->error('',6001);//异步返回
    }
}