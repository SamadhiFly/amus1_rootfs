<?php
namespace App\Utils\Traits;

use App\Utils\Token\AuthHelper;
use App\Utils\Widgets\Constants;

/**
 * check login
 *
 * Trait CheckLogin
 * @package App\Utils\Traits
 */
trait CheckLogin
{

    /**
     * @var bool
     */
    protected $foreLogin = true;


    /**
     * @var array
     */
    protected $loginUserInfo = [];


    /**
     * @var bool
     */
    protected $forePermission = true;


    /**
     * check function
     */
    public function checkLogin()
    {
        if ($this->foreLogin == true) {

            //header and cookie
            if(isset($this->_headers['HTTP_GUI_USER_TOKEN'][0])) {
                $token = $this->_headers['HTTP_GUI_USER_TOKEN'][0];
            } else {
                if(isset($this->_cookies[Constants::GUI_USER_TOKEN])) {
                    $token = $this->_cookies[Constants::GUI_USER_TOKEN];
                } else {
                    $token = null;
                }
            }

            if(empty($token)) {
                return $this->error('',4000);
            }

            $userId = AuthHelper::stripUserid($token);
            if($userId < 0) {
                return $this->error('',4001);
            }

            $userInfo = $this->table('user')->where('id',$userId)->first();
            if(!$userInfo) {
                return $this->error('',4000);
            }

            //check user ticket
            $ticket = null;
            if(isset($this->_headers['HTTP_GUI_USER_TICKET'][0])) {
                $ticket = $this->_headers['HTTP_GUI_USER_TICKET'][0];
            }

            if(!$ticket) {
                return $this->error('The login ticket is abnormal. Please log in again.',4001);
            }

            if($ticket != $userInfo['ticket']) {
                return $this->error('Your account is logged in on another device. Please log in again.',4001);
            }

            //check Authority
            if($this->forePermission) {
                $usps = null;//The user of frontend Route path.
                if(isset($this->_headers['HTTP_GUI_USER_USPS'][0])) {
                    $usps = $this->_headers['HTTP_GUI_USER_USPS'][0];
                }
                if (!$usps) {
                    return $this->error('Permission exception. Please log in again.',4001);
                }

                $userRole = $this->table('role')->where('id',$userInfo['role'])->first();
                if(!$userRole) {
                    $this->error('User Role exception, Please login in again',4001);
                }

                $userRoleItems = json_decode($userRole['items'],true);
                if(!$userRoleItems) {
                    $this->error('User Role exception, Please login in again',4001);
                }

                if(!in_array($usps,$userRoleItems)) {
                    $this->error('No operation rights, Please login in again',4001);
                }
            }

            $this->loginUserInfo = $userInfo;
        }
    }

}
