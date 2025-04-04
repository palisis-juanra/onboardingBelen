<?php

namespace TourCMS\OnBoarding\Model;

use TourCMS\Core\RedisService as Redis;
use TourCMS\OnBoarding\Config\env;
class connexion
{
    public $redis;
    public $expirationTime;
    public $data;

    public function __construct($data)
    {
        try {
            $this->redis = new Redis(env::getEnvVariable("REDIS_HOST"), env::getEnvVariable('REDIS_PORT'), env::getEnvVariable('REDIS_PASSWORD'));
            $this->data = $data;
            $this->expirationTime = strtotime(date("Y-m-d H:i:s", strtotime("+1 day")));

        } catch (\Throwable $th) {
            header('Location:http://'.$_SERVER['SERVER_NAME'].'/error');
            exit();
        }
    }
}
