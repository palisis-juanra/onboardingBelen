<?php

namespace TourCMS\OnBoarding\Model;

class enrouter
{
    static function enroute($routes)
    {
        if(in_array($_SERVER['REQUEST_URI'], $routes,true) == false ){
            header('Location:http://'.$_SERVER['SERVER_NAME'].'/error') ;
            exit();
        }else{
            foreach ($routes as $key => $value) {
                $_SERVER['REQUEST_URI'] == $value ? include_once('src/controller/mainController.php') : '';
            }
        }
        
    }

}