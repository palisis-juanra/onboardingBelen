<?php
namespace TourCMS\OnBoarding\Controller;

use TourCMS\OnBoarding\Model\tourCMSFactory;
use TourCMS\OnBoarding\Controller\mustacheController;
use TourCMS\OnBoarding\Controller\connexionController;

class tourCMSFactoryController
{
    public $tourCMS;
    public $redisConnexion;

    public function __construct() {
        $this->tourCMS = new tourCMSFactory();
        $this->redisConnexion = new connexionController();
    }

    #IN PROGRESS
    public function getTourCMSData($template,$typeOfData,$channel = 0, $params = ''){
        $results=[];
        if($this->redisConnexion->redisDataExists($_COOKIE['SESSION'].$typeOfData.$channel )){
            echo 'aqui';
            $encodedResults=$this->redisConnexion->redisGetData($_COOKIE['SESSION'].$typeOfData.$channel,'string');
            $results=json_decode($encodedResults,true);
        }else{
            $results = $this->callTourCMSFunction($typeOfData,$channel,$params);
            $this->redisConnexion->redisDataInsertion('json',array($_COOKIE['SESSION'].$typeOfData.$channel => $results));
        }
        $mustacheController=new mustacheController($template,$results,'template');
        $mustacheController->mustacheRenderer();
    }

    public function callTourCMSFunction($typeOfData,$channel = 0, $params = ''){
        $results=[];
        match ($typeOfData) {
            'channels' => $results = $this->tourCMS->list_channels($params),
            'tours' => $results = $this->tourCMS->search_tours($params,$channel),
            'bookings' => $results = $this->tourCMS->list_bookings($params,$channel),
            'customers' => $results 
        };
        return $results;
    }

}
