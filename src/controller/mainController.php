<?php

use TourCMS\OnBoarding\Controller\connexionController;
require(__DIR__.'/vendor/autoload.php');
use TourCMS\OnBoarding\Controller\loginController;
use TourCMS\OnBoarding\Controller\mustacheController;
use TourCMS\OnBoarding\Controller\tourCMSFactoryController;
use TourCMS\OnBoarding\Config\env;


$serverURI = [];
$serverName = env::getEnvVariable('SERVER_NAME');

$_SERVER['REQUEST_URI'] == '/' ? $serverURI[1] = $_SERVER['REQUEST_URI'] : $serverURI = explode('/', $_SERVER['REQUEST_URI']);
;



if ($serverURI[1] == 'error') {
    $mustacheController = new mustacheController('error', [], 'template');
    $mustacheController->mustacheRenderer();
    exit;
}

if (isset($_COOKIE['SESSION'])) {
    #IN PROGRESS
    $checkConnexion = new connexionController();
    $checkConnexion->redisUserLogged();
    $tourCMSFactory = new tourCMSFactoryController();

    if (isset($_POST['selectedChannel'])) {
        header('Location: '.$serverName.'/tours/' . $_POST['selectedChannel']);
    }
    if (isset($_POST['selectedTour'])) {
        header('Location: '.$serverName. '/tour/' . $_POST['selectedTourChannel'] . '/' . $_POST['selectedTour']);
    }
    switch ($serverURI[1]) {
        case 'channels':
            $tourCMSFactory->getTourCMSData('channels', 'channels');
            break;

        case 'tours':
            $tourCMSFactory->getTourCMSData('tours', 'tours', $serverURI[2]);
            break;

        case 'tour':
            $tourCMSFactory->getTourCMSData('tour', 'tour', $serverURI[2], '', $serverURI[3]);
            break;

        case '/':
            $tourCMSFactory->getTourCMSData('channels', 'channels');
            header('Location: '.$serverName.'/channels');
            break;
    }


} else {
    if ($serverURI[1] != '/') {
        header('Location: '.$serverName);
    }
    $mustacheController = new mustacheController('login', [], 'template');
    $mustacheController->mustacheRenderer();
    if (isset($_POST['uname']) && isset($_POST['psw'])) {
        $login = new loginController([$_POST['uname'] => $_POST['psw']]);
        $login->login();
    }
}



