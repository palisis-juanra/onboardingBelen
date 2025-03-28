<?php
require('/var/www/html/onboardingBelen/vendor/autoload.php');
use TourCMS\OnBoarding\Controller\connexionController;
use TourCMS\OnBoarding\Controller\mustacheController;

if(isset($_POST['uname']) && isset($_POST['psw'])){
    $redisConnection = new connexionController([$_POST['uname']=>$_POST['psw']]);
    $redisConnection->redisDataInsertion();

}
if(isset($_GET['error'])){
    $mustacheController=new mustacheController('error',[],'template');
    $mustacheController->mustacheRenderer();
}
else{
    $mustacheController=new mustacheController('login',[],'template');
    $mustacheController->mustacheRenderer();
}
    
