<?php
namespace App\Utils\Traits;

use App\Utils\Widgets\App;

/**
 * Trait IO
 * @package App\Utils\Traits
 */
trait IO
{

    /**
     * @var string
     */
    protected $allowHeaders = "Content-Type,GUI-USER-TOKEN,GUI-USER-TICKET,GUI-USER-USPS";

    /**
     * @var array
     */
    private $errors = [
        400     => 'unknown mistake.',
        500     => 'Internal server error.',

        //other
        4000    => 'You not logged in. Please log in first.',
        4001    => 'The account is abnormal. Please log in again.',

        //nsync
        6001    => '您的请求已经提交至后台异步执行，请稍等片刻。',
    ];


    /**
     * @param array $result
     * @param array $cookies
     * @return mixed
     */
    protected function response(array $result = [],array $cookies = [])
    {
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        $origin = '*';
        if(isset($this->_headers['HTTP_ORIGIN'][0])) {
            $origin = $this->_headers['HTTP_ORIGIN'][0];
        }
        $response = $this->_container->get('response');
        $response = $response->withHeader('Access-Control-Allow-Credentials','true');
        $response = $response->withHeader('Access-Control-Allow-Origin',$origin);
        $response = $response->withHeader('Access-Control-Allow-Headers',$this->allowHeaders);
        $response = $response->withHeader('Access-Control-Allow-Methods', 'GET,OPTIONS, POST, PUT, DELETE, PATCH');
        $response = $response->withHeader('Access-Control-Allow-Options','*');
        return $response->withJson([
            'state' => 0,
            'msg'   => '',
            'data'  => $result
        ]);
    }


    /**
     * @param string $msg
     * @param int $state
     * @return mixed
     */
    protected function error($msg = "", $state = 400)
    {
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        $origin = '*';
        if(isset($this->_headers['HTTP_ORIGIN'][0])) {
            $origin = $this->_headers['HTTP_ORIGIN'][0];
        }
        $response = $this->_container->get('response');
        $response = $response->withHeader('Access-Control-Allow-Credentials','true');
        $response = $response->withHeader('Access-Control-Allow-Origin',$origin);
        $response = $response->withHeader('Access-Control-Allow-Headers',$this->allowHeaders);
        $response = $response->withHeader('Access-Control-Allow-Methods', 'GET,OPTIONS, POST, PUT, DELETE, PATCH');
        $response = $response->withHeader('Access-Control-Allow-Options','*');
        if(empty($msg)) {
            $msg = $this->errors[$state];
        }
        $response = $response->withJson([
            'state' => $state,
            'msg'   => $msg,
        ]);
        App::getInstance()->respond($response);exit;
    }


}