<?php
namespace App\Utils\Token;

use App\Utils\Widgets\Mid;

/**
 * auth helper
 *
 * Class AuthHelper
 * @package App\Utils\Token
 */
class AuthHelper
{
    /**
     * user secret key
     *
     * token
     */
    const SECRET_KEY = '[1g910u2*.i.]';

    /**
     * GUI USER TOKEN
     */
    const GUI_USER_TOKEN = 'GUI-USER-TOKEN';

    /**
     * BUILD TOKEN
     *
     * @param $userId
     * @param $account
     * @param $password
     * @param $createdAt
     * @return string
     */
    public static function build($userId, $account, $password, $createdAt)
    {
        $userIdStr = Mid::id2URL($userId);
        return strtoupper(md5(md5(self::SECRET_KEY . $userId . $account) . $password . $createdAt)).$userIdStr;
    }

    /**
     * check token
     *
     * @param string $checkToken token
     * @param int $userid
     * @param string $account
     * @param string $password
     * @param int $createtime
     * @return bool| true | false
     */
    public static function check($checkToken, $userid, $account, $password, $createtime)
    {
        $token = self::build($userid, $account, $password, $createtime);
        if ($token == $checkToken) {
            return true;
        } else {
            $token = self::build($userid, $account, $password, $createtime,self::SECRET_KEY);
            if ($token == $checkToken) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $token
     * @return int
     */
    public static function stripUserid($token)
    {
        $useridStr = mb_substr($token, 32);
        $userid = Mid::url2ID($useridStr);
        return $userid;
    }


    /**
     * ticket
     *
     * @param int $userid
     * @param string $password
     * @return string
     */
    public static function ticket($userid, $password)
    {
        return str_shuffle(strtoupper(md5(md5($userid) . $password)) . (time() * mt_rand(0,999)));
    }

    /**
     * build user password
     *
     * @param string $password
     * @param int $createtime
     * @param string $salting
     * @return string
     */
    public static function buildPasswd($password, $createtime, $salting)
    {
        if (empty($salting)) {
            return md5($password);
        }
        return md5(md5($salting) . $password . $createtime);
    }


}