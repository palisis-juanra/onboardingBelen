<?php
namespace TourCMS\OnBoarding\Controller;

use Error;
use TourCMS\OnBoarding\Model\connexion;

class connexionController
{
    protected $connexion;

    public function __construct($data)
    {
        $this->connexion = new connexion($data);
    }
    function redisDataInsertion($dataType)
    {
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
            header('Location:http://'.$_SERVER['SERVER_NAME'].'/error');
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
        } elseif (isset($_COOKIE['SESSION']) && !isset($_COOKIE['PHPSESSID'])) {
            if ($this->connexion->redis->existKey($_COOKIE['SESSION']) == 0) {
                session_destroy();
                header('Location:http://'.$_SERVER['SERVER_NAME'].'/error');
                exit();
            } else {
                session_id($_COOKIE['SESSION']);
                session_start();
            }
        }

    }

}


