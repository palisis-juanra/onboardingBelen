<?php
namespace TourCMS\OnBoarding\Controller;

use Error;
use Exception;
use TourCMS\Core\RedisService as Redis;
use TourCMS\OnBoarding\Config\env;

class connexionController{
    public $redis;
    public $data;

    public function __construct($data){
        try{

            $this->redis = new Redis(env::getEnvVariable("REDIS_HOST"),env::getEnvVariable('REDIS_PORT'),env::getEnvVariable('REDIS_PASSWORD'));
            $this->data = $data;
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

                $time = strtotime(date("Y-m-d H:i:s", strtotime ("+30 minutes")) );
                $this->redis->expireAt($key,$time);
                
                //echo $this->redis->getItemFromRedis('holi','string');
            }
            
            // ini_set('session.gc_maxlifetime', 30);
            // session_start();
    
        } catch (Error $e) {
            header('http://www.onboardingbelen.local/mainController.php?error=error');
            exit();
        }
    }
    function redisUserLogged(){
        $this->redis->existKey('');
    }

}
    
    
