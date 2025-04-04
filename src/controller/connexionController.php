<?php
namespace TourCMS\OnBoarding\Controller;

use Error;
use TourCMS\OnBoarding\Model\connexion;

class connexionController
{
    protected $connexion;

    public function __construct($data = null)
    {
        $this->connexion = new connexion($data);
    }

    function redisDataInsertion($dataType, $data = null)
    {
        if (isset($data)) {
            $this->connexion->dataSetter($data);
        }

        try {
            foreach ($this->connexion->data as $key => $value) {
                switch ($dataType) {
                    case 'json':
                        $this->connexion->redis->storeItemInRedis($key, json_encode($value), 'string');
                        break;
                    case 'array':
                        $this->connexion->redis->storeItemInRedis($key, $value, 'array');
                        break;
                    case 'string':
                        $this->connexion->redis->storeItemInRedis($key, $value, 'string');
                        break;
                    case 'set':
                        $this->connexion->redis->storeItemInRedis($key, $value, 'set');
                        break;
                }
            }
        } catch (Error $e) {
            echo $e->getMessage();
            header('Location:http://' . $_SERVER['SERVER_NAME'] . '/error');
            exit();
        }
    }
    function redisUserLogged()
    {
        if (!isset($_COOKIE['SESSION'])) {
            session_start();
            foreach ($this->connexion->data as $key => $value) {
                $_SESSION['user'] = $key;
            }
            $this->connexion->redis->storeItemInRedis(session_id(), $_SESSION['user'], 'string');
            $this->connexion->redis->expireAt(session_id(), $this->connexion->expirationTime);
            $cookieExpiration = time() + 86400;
            setcookie("SESSION", session_id(), $cookieExpiration, "/");
        } elseif (isset($_COOKIE['SESSION'])) {
            if ($this->connexion->redis->existKey($_COOKIE['SESSION']) == 0) {
                session_destroy();
                unset($_COOKIE['SESSION']);
                setcookie("SESSION", "", time() - 3600);
                if (isset($_COOKIE["PHPSESSID"])) {
                    setcookie("PHPSESSID", "", time() - 3600);
                }
                header('Location:http://' . $_SERVER['SERVER_NAME'] . '/');
                exit();
            }
            if (!isset($_COOKIE['PHPSESSID'])) {
                session_id($_COOKIE['SESSION']);
                session_start();
            }
        }

    }
    function redisDataExists($data)
    {
        if ($this->connexion->redis->existKey($data) == 0) {
            return false;
        } else {
            return true;
        }
    }

    function redisGetData($data, $type)
    {
        return $this->connexion->redis->getItemFromRedis($data, $type);
    }

}


