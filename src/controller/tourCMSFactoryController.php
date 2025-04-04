<?php
namespace TourCMS\OnBoarding\Controller;

use TourCMS\OnBoarding\Model\tourCMSFactory;
use TourCMS\OnBoarding\Controller\mustacheController;
use TourCMS\OnBoarding\Controller\connexionController;

class tourCMSFactoryController
{
    public $tourCMS;
    public $redisConnexion;

    public function __construct()
    {
        $this->tourCMS = new tourCMSFactory();
        $this->redisConnexion = new connexionController();
    }

    #IN PROGRESS
    public function getTourCMSData($template, $typeOfData, $channel = 0, $params = '', $tour = 0)
    {
        $results = [];
        if ($this->redisConnexion->redisDataExists($_COOKIE['SESSION'] . $typeOfData . $channel . $tour)) {
            switch ($typeOfData) {
                case 'tours':
                    $encodedTours = $this->redisConnexion->redisGetData($_COOKIE['SESSION'] . $typeOfData . $channel . $tour, 'string');
                    $encodedChannels = $this->redisConnexion->redisGetData($_COOKIE['SESSION'].'channels00', 'string');
                    $results = ['tours'=>json_decode($encodedTours, true),'channels'=>json_decode($encodedChannels)];
                    break;
                
                default:
                    $encodedResults = $this->redisConnexion->redisGetData($_COOKIE['SESSION'] . $typeOfData . $channel . $tour, 'string');
                    $results = json_decode($encodedResults, true);
                    break;
            }
        } else {
            $results = $this->callTourCMSFunction($typeOfData, $channel, $params, $tour);
            match ($typeOfData) {
                
                'tours' => $this->redisConnexion->redisDataInsertion('json', array($_COOKIE['SESSION'] . $typeOfData . $channel . $tour => $results["tours"])),
                default => $this->redisConnexion->redisDataInsertion('json', array($_COOKIE['SESSION'] . $typeOfData . $channel . $tour => $results)),
            };
        }
        if (isset($results->error) && $results->error == 'NO MATCHING DATA') {
            header('Location: http://' . $_SERVER['SERVER_NAME'] . '/error');
            exit();
        }
        $mustacheController = new mustacheController($template, $results, 'template');
        $mustacheController->mustacheRenderer();
    }

    public function callTourCMSFunction($typeOfData, $channel = 0, $params = '', $tour = 0)
    {
        $results = [];
        match ($typeOfData) {
            'channels' => $results = $this->tourCMS->list_channels($params),
            'tours' => $results = $this->channelTours($channel, $params),
            'bookings' => $results = $this->tourCMS->list_bookings($params, $channel),
            'tour' => $results = $this->tourCMS->show_tour($tour, $channel),
            'customers' => $results
        };
        return $results;
    }

    public function channelTours($channel = 0, $params = '')
    {
        $tours = $this->tourCMS->search_tours($params, $channel);
        $channels = null;
        if ($this->redisConnexion->redisDataExists($_COOKIE['SESSION'].'channels00')) {
            $encodedResults = $this->redisConnexion->redisGetData($_COOKIE['SESSION'].'channels00', 'string');
            $channels = json_decode($encodedResults, true);
        } else {
            $channels = $this->callTourCMSFunction('channels');
            $this->redisConnexion->redisDataInsertion('json', array($_COOKIE['SESSION'].'channels00'=> $channels));
        }
        $results = ["tours" => $tours, "channels" => $channels];
        return $results;
    }

}
