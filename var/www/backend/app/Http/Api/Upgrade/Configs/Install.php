<?php
namespace App\Http\Api\Upgrade\Configs;

use App\Http\Api\GatewayAbstract;

/**
 * Class Install
 * @package App\Http\Api\Upgrade\Configs
 */
class Install extends GatewayAbstract
{

    protected $foreLogin = false;

    /**
     * @return mixed
     */
    public function validateParams()
    {
        return $this->_validate($this->_params,[
            'name'          => 'required',
        ],[
            'name.required' => 'file name is required',
        ]);
    }

    /**
     * @return mixed
     */
    public function run()
    {

        $directory = BASE_PATH . 'uploads';

        $file = $directory . '/' . $this->_params['name'];

        // call product cli verification file .
        $result = exec('install_sw -f ' . $file . ' -g /tmp/gui_output',$output,$return_code);

        var_dump($return_code);

        echo "<br />";

        print_r($output);

        echo "<br />";

        dd($result);

        return $this->response();
    }
}