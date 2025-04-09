<?php
namespace TourCMS\OnBoarding\Helper;

use PhpParser\Error;
use TourCMS\Utils\TourCMS;
use TourCMS\OnBoarding\Controller\mustacheController;
use TourCMS\OnBoarding\Helper\redisWrapper;
use TourCMS\OnBoarding\Config\env;


class tourCMSService extends TourCMS
{

    protected $redis;
    public $channel_id;
    protected $base_url;

    public function __construct($type)
    {
        $api_key = env::getEnvVariable("API_KEY");
        $type == 'o' ? $marketplace_id = env::getEnvVariable("MARKETPLACE_ID_OPERATOR") : $marketplace_id = env::getEnvVariable("MARKETPLACE_ID_AGENT");
        parent::__construct($marketplace_id, $api_key, 'simplexml', 0);
        $this->base_url = 'http://api.tourcms.local';
        $this->redis = new redisWrapper();
        $this->channel_id = env::getEnvVariable("CHANNEL_ID");
    }

    #IN PROGRESS
    public function getTourCMSData($typeOfData, $channel = 0, $params = '', $tour = 0)
    {
        $results = [];
        if ($this->redis->existKey($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour)) {
            switch ($typeOfData) {
                case 'tours':
                    $encodedTours = $this->redis->getItemFromRedis($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour, 'string');
                    $encodedChannels = $this->redis->getItemFromRedis($_COOKIE['PHPSESSID'].'channels00', 'string');
                    $results = ['tours'=>json_decode($encodedTours, true),'channels'=>json_decode($encodedChannels)];
                    break;
                
                default:
                    $encodedResults = $this->redis->getItemFromRedis($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour, 'string');
                    $results = json_decode($encodedResults, true);
                    break;
            }
        } else {
            $results = $this->callTourCMSFunction($typeOfData, $channel, $params, $tour);
            match ($typeOfData) {
                'tours' => $this->redis->redisDataInsertion('json', array($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour => $results["tours"])),
                'booking' => '',
                default => $this->redis->redisDataInsertion('json', array($_COOKIE['PHPSESSID'] . $typeOfData . $channel . $tour => $results)),
            };
        }
        if (isset($results->error) && $results->error == 'NO MATCHING DATA') {
            throw new Error('No matching data');
        }
        return $results;
    }

    public function callTourCMSFunction($typeOfData, $channel = 0, $params = '', $tour = 0)
    {
        $results = [];
        match ($typeOfData) {
            'channels' => $results = $this->list_channels($params),
            'tours' => $results = $this->channelTours($channel, $params),
            'booking' => $results = $this->getTourCMSData('tour', $channel , $params = '', $tour ),
            'tour' => $results = $this->show_tour($tour, $channel),
            'availability' => $results = $this->check_availability($params, $tour, $channel ),
            'customers' => $results
        };
        return $results;
    }

    public function channelTours($channel = 0, $params = '')
    {
        $tours = $this->list_tours($channel);
        $channels = null;
        if ($this->redis->existKey($_COOKIE['PHPSESSID'].'channels00')) {
            $encodedResults = $this->redis->getItemFromRedis($_COOKIE['PHPSESSID'].'channels00', 'string');
            $channels = json_decode($encodedResults, true);
        } else {
            $channels = $this->callTourCMSFunction('channels');
            $this->redis->redisDataInsertion('json', array($_COOKIE['PHPSESSID'].'channels00'=> $channels));
        }
        $results = ["tours" => $tours, "channels" => $channels];
        return $results;
    }

    public function check_availability($params, $tour, $channel){
        $qs = '';
        foreach ($params as $param) {
            $qs .= $param;
            if($params[sizeof($params)-1]!=$param){
                $qs .= '&';
            }
        }

        $results = $this->check_tour_availability($qs,$tour,$channel);
        return $results;
    }
}

