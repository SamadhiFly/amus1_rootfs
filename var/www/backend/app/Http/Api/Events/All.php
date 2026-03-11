<?php
namespace App\Http\Api\Events;

use App\Http\Api\GatewayAbstract;

/**
 * Class All
 * @package App\Http\Api\Auth
 */
class All extends GatewayAbstract
{
    protected $foreLogin = false;
    /**
     * validate params
     *
     * @return mixed|void
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
        $Lastest = isset($this->_params['Lastest'])?$this->_params['Lastest']:'false';
        $eventId = isset($this->_params['eventId'])?$this->_params['eventId']:'';
        $DateBg = isset($this->_params['DateBg'])?$this->_params['DateBg']:'';
        $DateEnd =isset($this->_params['DateEnd'])?$this->_params['DateEnd']:'';
        $eventsModel = $this->connection('events')->table('events')
            ->orderBy('id','desc');

        if($Lastest == 'true')
        {
            $eventsModel->take(10);
        }
        else{
            $eventsModel->take(50000);
        }
        if(isset($eventId) && !empty($eventId))
        {
            $eventsModel->where('event_id',isset($eventId)?$eventId:"");
        }
        if(isset($DateBg) && !empty($DateBg))
        {
            $eventsModel->where('utc_time_us','>=',$DateBg);
        }
        if(isset($DateEnd) && !empty($DateEnd))
        {
            $eventsModel->where('utc_time_us','<=',$DateEnd);
        }
        $eventsModel = $eventsModel->get();
        return $this->response([
            'items' => $eventsModel
        ]);
    }

}