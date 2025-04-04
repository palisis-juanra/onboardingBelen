<?php

namespace TourCMS\OnBoarding\Model;

class enrouter
{
    public $routes;
    public $serverURI;

    public function __construct()
    {
        $this->routes = ['/', 'login', 'error', 'channels', 'tours', 'tour'];
        $this->serverURI = ['', ''];

    }
    public function enroute()
    {
        $_SERVER['REQUEST_URI'] == '/' ? $serverURI[1] = $_SERVER['REQUEST_URI'] : $serverURI = explode('/', $_SERVER['REQUEST_URI']);
        ;

        if (in_array($serverURI[1], $this->routes, true) == false) {
            header('Location:http://' . $_SERVER['SERVER_NAME'] . '/error');
            exit();
        } else {
            include_once('src/controller/mainController.php');
        }

    }

}