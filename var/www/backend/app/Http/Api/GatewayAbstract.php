<?php
namespace App\Http\Api;

use App\Utils\Traits\Call;
use App\Utils\Traits\Capsule;
use App\Utils\Traits\CheckLogin;
use App\Utils\Traits\IO;
use App\Utils\Traits\ReadWriteLock;
use App\Utils\Widgets\App;
use Slim\Container;
use App\Utils\Traits\ValidatorTools;

/**
 * Class GatewayAbstract
 * @package App\Http\Api
 */
abstract class GatewayAbstract
{

    use IO,ValidatorTools,Capsule,CheckLogin,ReadWriteLock,Call;


    /**
     * @var array
     */
    protected $_container = [];


    /**
     * @var array
     */
    protected $_request = [];


    /**
     * @var array
     */
    protected $_params = [];


    /**
     * @var array
     */
    protected $_headers = [];


    /**
     * @var array
     */
    protected $_cookies = [];


    /**
     * GatewayAbstract constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->_container = $container;

        $this->_request = $this->_container->get('request');

        $this->_params  = $this->_request->getParams();

        $this->_headers = $this->_request->getHeaders();

        $this->_cookies = $this->_request->getCookieParams();

        //filter request method
        if($this->_request->getMethod() == 'OPTIONS') {
            $response = $this->response(['method' => 'option.']);
            App::getInstance()->respond($response);exit;
        }

        //check login
        $this->checkLogin();

        //check params
        $this->validateParams();

        //Read-write lock at the network management layer
        //$this->RwLock();

    }


    /**
     * @return mixed
     */
    abstract function validateParams();


    /**
     * auto run
     *
     * @return mixed
     */
    abstract function run();
}