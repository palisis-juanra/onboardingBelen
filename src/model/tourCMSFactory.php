<?php
namespace TourCMS\OnBoarding\Model;

use TourCMS\OnBoarding\Config\env;
use TourCMS\Utils\TourCMS;

class tourCMSFactory extends TourCMS
{
    public $channel_id;
    public function __construct()
    {
        $api_key = env::getEnvVariable("API_KEY");
        $marketplace_id = env::getEnvVariable("MARKETPLACE_ID");
        $this->channel_id = env::getEnvVariable("CHANNEL_ID");
        parent::__construct($marketplace_id, $api_key, 'simplexml', 0);
    }

}
