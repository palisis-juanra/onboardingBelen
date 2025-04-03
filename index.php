<?php
require('/var/www/html/onboardingBelen/vendor/autoload.php');

use TourCMS\OnBoarding\Model\enrouter;
use TourCMS\OnBoarding\Model\tourCMSFactory;

$enrouter = new enrouter();
$enrouter->enroute();

