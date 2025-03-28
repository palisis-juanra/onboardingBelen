<?php
namespace TourCMS\OnBoarding\Controller;

use Error;
use Exception;
use TourCMS\Core\RedisService as Redis;
use TourCMS\OnBoarding\Config\env;

class connexionController{
    public $redis;
    public $expirationTime;

    public $data;

    public function __construct($data){
        try{

            $this->redis = new Redis(env::getEnvVariable("REDIS_HOST"),env::getEnvVariable('REDIS_PORT'),env::getEnvVariable('REDIS_PASSWORD'));
            $this->data = $data;
            $this->expirationTime = strtotime(date("Y-m-d H:i:s", strtotime ("+30 minutes")));
        }catch(\Throwable $th){
            header('Location: http://www.onboardingbelen.local/src/controller/mainController.php?error=error');
            exit();
        }
    }
    function redisDataInsertion(){  
        try {
            foreach ($this->data as $key => $value) {
                echo $key;
                if(gettype($this->data) == 'string'){
                    $this->redis->storeItemInRedis($key,$value,'string');
                }elseif(gettype($this->data) == 'array'){
                    $this->redis->storeItemInRedis($key,$value,'array');
                }
                else{ 
                    $this->redis->storeItemInRedis($key,$value,'set');
                }

                $this->redis->expireAt($key,$this->expirationTime);
            }
    
        } catch (Error $e) {
            header('http://www.onboardingbelen.local/mainController.php?error=error');
            exit();
        }
    }
    function redisUserLogged(){
        if(!isset($_SESSION['SESSION']) && !isset($_COOKIE[password_hash(session_id(),PASSWORD_DEFAULT)]) ){
            session_start();
            foreach ($this->data as $key => $value) {
                $_SESSION['user']=$key;
            }
            $session_key = password_hash(session_id(),PASSWORD_DEFAULT);
            $this->redis->storeItemInRedis(session_id(),$_SESSION['user'],'string');
            $this->redis->expireAt(session_id(),$this->expirationTime);
            $cookieExpiration = time() + (86400 * 30);
            setcookie("SESSION", $session_key, $cookieExpiration, "/");
        }else{
            if($this->redis->existKey($_COOKIE['SESSION']) == 0 ){
                session_destroy();
                header('http://www.onboardingbelen.local/mainController.php');
                exit();
            }
        }

    }

}
    
    
