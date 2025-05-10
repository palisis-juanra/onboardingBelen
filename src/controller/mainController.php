<?php


require(getenv('VENDOR'));
use TourCMS\OnBoarding\Controller\loginController;
use TourCMS\OnBoarding\Helper\mustacheService;
use TourCMS\OnBoarding\Helper\tourCMSService;
use TourCMS\OnBoarding\Config\env;


$serverURI = [];
$serverName = env::getEnvVariable('SERVER_BASE_NAME_PATH');

$_SERVER['REQUEST_URI'] == '/' ? $serverURI[1] = $_SERVER['REQUEST_URI'] : $serverURI = explode('/', $_SERVER['REQUEST_URI']);


if ($serverURI[1] == 'error') {
    $mustacheService = new mustacheService('error', [], 'template');
    $mustacheService->mustacheRenderer();
    exit;
}

if (isset($_COOKIE['SESSION'])) {
    $tourCMSServiceOperator = new tourCMSService('o');
    $tourCMSServiceAgent = new tourCMSService('a');

    #IN PROGRESS (The post variables are going to be in a match)
    try {
        $userLogged = new loginController();
        if ($userLogged->userLogged() == false) {
            header('Location: ' . $serverName);
        }
    } catch (\Throwable $th) {
        header('Location: ' . $serverName . '/error');
        exit();
    }
    if (isset($_POST['selectedChannel'])) {
        header('Location: ' . $serverName . '/tours/' . $_POST['selectedChannel']);
    }
    if (isset($_POST['selectedTour'])) {
        header('Location: ' . $serverName . '/tour/' . $_POST['selectedTourChannel'] . '/' . $_POST['selectedTour']);
    }
    if (isset($_POST['selectedBookingTour'])) {
        header('Location: ' . $serverName . '/booking/' . $_POST['selectedBookingTourChannel'] . '/' . $_POST['selectedBookingTour']);
    }
    try {
        $results = [];
        $template = '';
        switch ($serverURI[1]) {
            case 'channels':
                $results = $tourCMSServiceAgent->getTourCMSData( 'channels');
                $template = 'channels';
                break;

            case 'tours':
                $results = $tourCMSServiceAgent->getTourCMSData( 'tours', $serverURI[2]);
                $template = 'tours';
                break;

            case 'tour': case 'booking':
                $results = $tourCMSServiceAgent->getTourCMSData( $serverURI[1], $serverURI[2], '', $serverURI[3]);
                $template = $serverURI[1];
                break;
            
            case 'availability':
                $params = explode('?',$serverURI[3]);
                $results = $tourCMSServiceAgent->getTourCMSData( 'availability', $serverURI[2], $params[1], $params[0]);
                $template = 'availability';
                break;
            case 'bookingForm':
                $template = 'bookingForm';
                $results = ['data'=>$_POST];
                break;
            case 'bookingScreen':
                $results = $tourCMSServiceAgent->getTourCMSData( 'bookingScreen', $_POST['channel_id'], $_POST, $_POST['tour_id']);
                $template = 'bookingScreen';
                break;

            case '/':
                header('Location: ' . $serverName . '/channels');
                exit();

        }
        $mustacheService = new mustacheService($template, $results, 'template');
        $mustacheService->mustacheRenderer();

    } catch (\Throwable $th) {
        header('Location: ' . $serverName . '/error');
        exit();
    }



} else {
    $mustacheService = new mustacheService('login', [], 'template');
    $mustacheService->mustacheRenderer();
    if (isset($_POST['uname']) && isset($_POST['psw'])) {
        try {
            $login = new loginController();
            $login->userLogged([$_POST['uname'] => $_POST['psw']]);
            header('Location: ' . $serverName . '/channels');
            exit();
        } catch (\Throwable $th) {
            header('Location: ' . $serverName . '/error');
            exit();
        }
    } else{
        if ($serverURI[1] != '/') {
            header('Location: ' . $serverName);
            exit();
        }
    }

}



