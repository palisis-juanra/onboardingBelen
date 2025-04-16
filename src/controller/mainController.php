<?php


require(getenv('VENDOR'));
use TourCMS\OnBoarding\Controller\loginController;
use TourCMS\OnBoarding\Helper\mustacheService;
use TourCMS\OnBoarding\Helper\tourCMSService;
use TourCMS\OnBoarding\Config\env;

// Creates an array to store the request URI
$serverURI = [];

// Stores the server base name path
$serverName = env::getEnvVariable('SERVER_BASE_NAME_PATH');

// Splits the request URI in different parameters that will be used later on to retrieve the desired TourCMS
// data an to make the proper redirects
// If the request URI is just / the URL won't be splitted
$_SERVER['REQUEST_URI'] == '/' ? $serverURI[1] = $_SERVER['REQUEST_URI'] : $serverURI = explode('/', $_SERVER['REQUEST_URI']);

// If an error is thrown there will be a redirection here
if ($serverURI[1] == 'error') {
    $mustacheService = new mustacheService('error', [], 'template');
    $mustacheService->mustacheRenderer();
    exit;
}

// If the session cookie is settled and the Redis user session is still active 
// we will start making the proper redirects
// Otherwise the user will be redirected to the login 
if (isset($_COOKIE['SESSION'])) {
    $tourCMSServiceOperator = new tourCMSService('o');
    $tourCMSServiceAgent = new tourCMSService('a');

    // Try to check if the user session is still active
    try {
        $userLogged = new loginController();
        if ($userLogged->userLogged() == false) {
            header('Location: ' . $serverName);
        }
    } catch (\Throwable $th) {
        header('Location: ' . $serverName . '/error');
        exit();
    }

    // Redirects to the correct URLs in base of the received data from Post
    if (isset($_POST['selectedChannel'])) {
        header('Location: ' . $serverName . '/tours/' . $_POST['selectedChannel']);
        exit();
    }
    if (isset($_POST['selectedTour'])) {
        header('Location: ' . $serverName . '/tour/' . $_POST['selectedTourChannel'] . '/' . $_POST['selectedTour']);
        exit();
    }
    if (isset($_POST['selectedBookingTour'])) {
        header('Location: ' . $serverName . '/booking/' . $_POST['selectedBookingTourChannel'] . '/' . $_POST['selectedBookingTour']);
        exit();
    }

    // Now we will get the TourCMS necessary data and render the correct template in base of the URL
    // If there are any errors the user will be redirected to /error
    try {
        $results = [];
        $template = '';
        switch ($serverURI[1]) {
            case 'channels':
            case 'bookings':
                $results = $tourCMSServiceAgent->getTourCMSData($serverURI[1]);
                break;
            case 'tours':
                $results = $tourCMSServiceAgent->getTourCMSData('tours', $serverURI[2]);
                break;
            case 'tour':
            case 'booking':
                $results = $tourCMSServiceAgent->getTourCMSData($serverURI[1], $serverURI[2], '', $serverURI[3]);
                break;

            case 'availability':
                $params = explode('?', $serverURI[3]);
                $results = $tourCMSServiceAgent->getTourCMSData('availability', $serverURI[2], $params[1], $params[0]);
                break;
            case 'customerUpdate':
            case 'bookingForm':
                $results = ['data' => $_POST];
                break;
            case 'bookingScreen':
                $results = $tourCMSServiceAgent->getTourCMSData($serverURI[1], $_POST['channel_id'], $_POST, $_POST['tour_id']);
                break;
            case 'customerUpdateResult':
                $results = $tourCMSServiceAgent->getTourCMSData($serverURI[1], $_POST['channel_id'], $_POST, $_POST['customer_id']);
                break;
            case 'customerBooking':
            case 'bookingErase':
                $results = $tourCMSServiceAgent->getTourCMSData($serverURI[1], $serverURI[2], $serverURI[3]);
                break;
            case 'customers':
                $results = ['channels' => $tourCMSServiceAgent->getTourCMSData('channels')];
                isset($_POST['customerID']) ? $results['customers'] = $tourCMSServiceAgent->getTourCMSData($serverURI[1], $_POST['customerChannel'], $_POST['customerID']) : null;
                break;
            case '/':
                header('Location: ' . $serverName . '/channels');
                exit();

        }
        $template = $serverURI[1];
        $mustacheService = new mustacheService($template, $results, 'template');
        $mustacheService->mustacheRenderer();

    } catch (\Throwable $th) {
        header('Location: ' . $serverName . '/error');
        exit();
    }

} else {
    // If the user isn't properly logged it'll be redirected here
    $mustacheService = new mustacheService('login', [], 'template');
    $mustacheService->mustacheRenderer();
    if (isset($_POST['uname']) && isset($_POST['psw'])) {

        // If the login returns correct the user will be redirected to /channels
        try {
            $login = new loginController();
            $login->userLogged([$_POST['uname'] => $_POST['psw']]);
            header('Location: ' . $serverName . '/channels');
            exit();
        } catch (\Throwable $th) {
            header('Location: ' . $serverName . '/error');
            exit();
        }
    } else {
        // If the user session ended while it was in the web it'll be redirected to /
        if ($serverURI[1] != '/') {
            header('Location: ' . $serverName);
            exit();
        }
    }

}