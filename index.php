<?php
require(__DIR__.'/vendor/autoload.php');


$routes = ['/', 'login', 'error', 'channels', 'tours', 'tour'];
$serverURI = ['', ''];


$_SERVER['REQUEST_URI'] == '/' ? $serverURI[1] = $_SERVER['REQUEST_URI'] : $serverURI = explode('/', $_SERVER['REQUEST_URI']);
;

if (in_array($serverURI[1], $this->routes, true) == false) {
    header('Location:http://' . $_SERVER['SERVER_NAME'] . '/error');
    exit();
} else {
    include_once('src/controller/mainController.php');
}

    



