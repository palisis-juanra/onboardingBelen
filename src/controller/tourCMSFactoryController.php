<?php
namespace TourCMS\OnBoarding\Controller;

use TourCMS\OnBoarding\Model\tourCMSFactory;
use TourCMS\Utils\TourCMS as TourCMS;

final class tourCMSFactoryController
{
    public $tourCMS;

    public function __construct() {
        $this->tourCMS = new tourCMSFactory();
    }

    public function getChannels(){
        $this->tourCMS->list_channels();

    {
    }
    }
}
