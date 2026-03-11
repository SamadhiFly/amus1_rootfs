<?php
namespace App\Http\Api\Version;

use App\Http\Api\GatewayAbstract;

/**
 * read hardware interface.
 *
 * Class Read
 * @package App\Http\Api\Version
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
                $result['fs_version']       = isset($data['fs_version'])?$data['fs_version']:'';
            }
        }
        return $this->response($result);
    }

}