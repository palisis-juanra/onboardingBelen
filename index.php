<?php
require(__DIR__.'/vendor/autoload.php');

use TourCMS\OnBoarding\Config\env;
$routes = ['/', 'login', 'error', 'channels', 'tours', 'tour'];
$serverURI = ['', ''];
$serverName = env::getEnvVariable('SERVER_NAME');


$_SERVER['REQUEST_URI'] == '/' ? $serverURI[1] = $_SERVER['REQUEST_URI'] : $serverURI = explode('/', $_SERVER['REQUEST_URI']);
;

if (in_array($serverURI[1], $routes, true) == false) {
    header('Location: '.$serverName. '/error');
    exit();
} else {
    include_once('src/controller/mainController.php');
}

    



