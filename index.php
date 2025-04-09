<?php
putenv("VENDOR=/var/www/html/onboardingBelen/vendor/autoload.php");
require(getenv('VENDOR'));

use TourCMS\OnBoarding\Config\env;
$routes = ['/', 'login', 'error', 'channels', 'tours', 'tour','booking','availability'];
$serverURI = ['', ''];
$serverName = env::getEnvVariable('SERVER_BASE_NAME_PATH');



$_SERVER['REQUEST_URI'] == '/' ? $serverURI[1] = $_SERVER['REQUEST_URI'] : $serverURI = explode('/', $_SERVER['REQUEST_URI']);
;

if (in_array($serverURI[1], $routes, true) == false) {
    header('Location: '.$serverName. '/error');
    exit();
} else {
    include_once('src/controller/mainController.php');
}

    



