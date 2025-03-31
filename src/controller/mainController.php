<?php
require('/var/www/html/onboardingBelen/vendor/autoload.php');
use TourCMS\OnBoarding\Controller\loginController;
use TourCMS\OnBoarding\Controller\mustacheController;


if($_SERVER['REQUEST_URI'] =='/error'){
    $mustacheController=new mustacheController('error',[],'template');
    $mustacheController->mustacheRenderer();
    exit;
}

if(isset($_COOKIE['SESSION'])){
    $mustacheController=new mustacheController('channels',[],'template');
    $mustacheController->mustacheRenderer();
}else{
    $mustacheController=new mustacheController('login',[],'template');
    $mustacheController->mustacheRenderer();
    
    if(isset($_POST['uname']) && isset($_POST['psw'])){
        $login = new loginController([$_POST['uname']=>$_POST['psw']]);
        $login->login();
    
    }
}


    
