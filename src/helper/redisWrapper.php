<?php
namespace TourCMS\OnBoarding\Helper;

use TourCMS\Core\RedisService as Redis;
use TourCMS\OnBoarding\Config\env;

class redisWrapper extends Redis
{
    public $expirationTime;


    public function __construct()
    {
        parent::__construct(env::getEnvVariable("REDIS_HOST"), env::getEnvVariable('REDIS_PORT'), env::getEnvVariable('REDIS_PASSWORD'));
        $this->expirationTime = strtotime(date("Y-m-d H:i:s", strtotime("+1 day")));
    }

    function redisDataInsertion($dataType, $data)
    {
        foreach ($data as $key => $value) {
            switch ($dataType) {
                case 'json':
                    $this->storeItemInRedis($key, json_encode($value), 'string');
                    break;
                case 'array':
                    $this->storeItemInRedis($key, $value, 'array');
                    break;
                case 'string':
                    $this->storeItemInRedis($key, $value, 'string');
                    break;
                case 'set':
                    $this->storeItemInRedis($key, $value, 'set');
                    break;
            }
        }
    }
}




