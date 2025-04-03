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
    public function getTourCMSData($template,$typeOfData,$channel = 0, $params = '',$tour=0){
        $results=[];
        if($this->redisConnexion->redisDataExists($_COOKIE['SESSION'].$typeOfData.$channel.$tour )){
            $encodedResults=$this->redisConnexion->redisGetData($_COOKIE['SESSION'].$typeOfData.$channel.$tour,'string');
            $results=json_decode($encodedResults,true);
        }else{
            $results = $this->callTourCMSFunction($typeOfData,$channel,$params,$tour);
            $this->redisConnexion->redisDataInsertion('json',array($_COOKIE['SESSION'].$typeOfData.$channel.$tour => $results));
        }
        if(isset($results->error)){
            header('Location: http://'.$_SERVER['SERVER_NAME'].'/error');
            exit();
        }
        $mustacheController=new mustacheController($template,$results,'template');
        $mustacheController->mustacheRenderer();
    }

    public function callTourCMSFunction($typeOfData,$channel = 0, $params = '',$tour=0){
        $results=[];
        match ($typeOfData) {
            'channels' => $results = $this->tourCMS->list_channels($params),
            'tours' => $results = $this->channelTours($channel,$params),
            'bookings' => $results = $this->tourCMS->list_bookings($params,$channel),
            'tour' => $results = $this->tourCMS->show_tour($tour,$channel),
            'customers' => $results 
        };
        return $results;
    }

    public function channelTours($channel = 0, $params = '') {
        $tours = $this->tourCMS->search_tours($params,$channel);
        $channels = $this->tourCMS->list_channels($params);
        $results = ["tours"=>$tours,"channels"=>$channels];
        return $results;
    }

}
