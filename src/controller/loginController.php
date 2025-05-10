<?php
namespace TourCMS\OnBoarding\Controller;

use TourCMS\OnBoarding\Helper\redisWrapper;

class loginController
{
    public $redis;
    private $cookieExpiration;
    private $unsetCookieExpiration;

    # This value sets and unsets the session cookie life time (86400 = 1 day in seconds)
    private const COOKIE_LIFE_TIME = 86400;

    public function __construct()
    {
        $this->redis = new redisWrapper();
        $this->cookieExpiration = time() + self::COOKIE_LIFE_TIME;
        $this->unsetCookieExpiration = time() - self::COOKIE_LIFE_TIME;
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
