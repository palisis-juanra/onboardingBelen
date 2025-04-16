<?php
namespace TourCMS\OnBoarding\Controller;

use TourCMS\OnBoarding\Helper\redisWrapper;

class loginController
{
    public $redis;
    private $cookieExpiration;
    private $unsetCookieExpiration;

    # This value sets and unsettles the session cookie life time (86400 = 1 day in seconds)
    private const COOKIE_LIFE_TIME = 86400;

    public function __construct()
    {
        // Constructs the login controller and settles the session cookie expiration time
        $this->redis = new redisWrapper();
        $this->cookieExpiration = time() + self::COOKIE_LIFE_TIME;
        $this->unsetCookieExpiration = time() - self::COOKIE_LIFE_TIME;
    }

    function userLogged($data = null)
    {
        // If there isn't a session cookie settled in the browser a new session is created
        // The new user session will be stored in Redis and a session cookie will be saved in the browser
        if (!isset($_COOKIE['SESSION'])) {
            session_start();
            foreach ($data as $key => $value) {
                $_SESSION['user'] = $key;
            }
            $this->redis->redisDataInsertion('string', [$_SESSION['user'] => session_id()]);
            $this->redis->expireAt($_SESSION['user'], $this->redis->expirationTime);
            setcookie("SESSION", $_SESSION['user'], $this->cookieExpiration, "/");
        } elseif (isset($_COOKIE['SESSION'])) {
            // If the session cookie is active but there is no matching data in Redis for that session
            // the cookie and a any existing php session will be deleted 
            // It will return false
            if ($this->redis->existKey($_COOKIE['SESSION']) == 0) {
                session_status() == PHP_SESSION_ACTIVE ? session_destroy() : null;
                unset($_COOKIE['SESSION']);
                setcookie("SESSION", "", $this->unsetCookieExpiration);
                if (isset($_COOKIE["PHPSESSID"])) {
                    setcookie("PHPSESSID", "", $this->unsetCookieExpiration);
                }
                return false;
            }
            // If the php session isn't started but the session user cookie is a new session with the previous ID will start
            // It will return true
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
