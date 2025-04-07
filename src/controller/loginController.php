<?php
namespace TourCMS\OnBoarding\Controller;

use TourCMS\OnBoarding\Helper\redisWrapper;

class loginController
{
    public $redis;
    private $cookieExpiration;
    private $unsetCookieExpiration;
    public function __construct()
    {
        $cookieSumValue = 86400;
        $unsetValue = 3600;
        $this->redis = new redisWrapper();
        $this->cookieExpiration = time() + $cookieSumValue;
        $this->unsetCookieExpiration = time() - $unsetValue;
    }

    function userLogged($data = null)
    {
        if (!isset($_COOKIE['SESSION'])) {
            session_start();
            foreach ($data as $key => $value) {
                $_SESSION['user'] = $key;
            }
            $this->redis->redisDataInsertion('string', [$_SESSION['user'] => session_id()]);
            $this->redis->expireAt($_SESSION['user'], $this->redis->expirationTime);
            setcookie("SESSION", $_SESSION['user'], $this->cookieExpiration, "/");
        } elseif (isset($_COOKIE['SESSION'])) {
            if ($this->redis->existKey($_COOKIE['SESSION']) == 0) {
                session_destroy();
                unset($_COOKIE['SESSION']);
                setcookie("SESSION", "", $this->unsetCookieExpiration);
                if (isset($_COOKIE["PHPSESSID"])) {
                    setcookie("PHPSESSID", "", $this->unsetCookieExpiration);
                }
                return false;
            }
            if (!isset($_COOKIE['PHPSESSID'])) {
                $session = $this->redis->getItemFromRedis($_COOKIE['SESSION'],'string');
                foreach ($session as $key => $value) {
                    session_id($value);
                }
                session_start();
            }
            return true;
        }

    }
}
