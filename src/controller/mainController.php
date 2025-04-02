<?php
require('/var/www/html/onboardingBelen/vendor/autoload.php');
use TourCMS\OnBoarding\Controller\loginController;
use TourCMS\OnBoarding\Controller\mustacheController;
use TourCMS\OnBoarding\Controller\tourCMSFactoryController;


if($_SERVER['REQUEST_URI'] =='/error'){
    $mustacheController=new mustacheController('error',[],'template');
    $mustacheController->mustacheRenderer();
    exit;
}

if(isset($_COOKIE['SESSION'])){
    #IN PROGRESS
    $tourCMSFactory= new tourCMSFactoryController();
    if(isset($_POST['selectedChannel'])){
        $tourCMSFactory->getTourCMSData('tours','tours',$_POST['selectedChannel']);
        exit();
    }
    $tourCMSFactory->getTourCMSData('channels','channels');
    
}else{
    
    if(isset($_POST['uname']) && isset($_POST['psw'])){
        $login = new loginController([$_POST['uname']=>$_POST['psw']]);
        $login->login();
    }
    $mustacheController=new mustacheController('login',[],'template');
    $mustacheController->mustacheRenderer();
    
    
}


    
