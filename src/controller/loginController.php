<?php
namespace TourCMS\OnBoarding\Controller;

use TourCMS\OnBoarding\Controller\connexionController;
use Error;


class loginController
{
    public $data;
    public function __construct($data) {
        $this->data = $data;
    }

    public function login() {
        $redisConnection = new connexionController($this->data);
        $redisConnection->redisUserLogged();
        header('Location: http://www.onboardingbelen.local/');
    }
}
