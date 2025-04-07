<?php


require(getenv('VENDOR'));
use TourCMS\OnBoarding\Controller\loginController;
use TourCMS\OnBoarding\Controller\mustacheController;
use TourCMS\OnBoarding\Helper\tourCMSService;
use TourCMS\OnBoarding\Config\env;


$serverURI = [];
$serverName = env::getEnvVariable('SERVER_BASE_NAME_PATH');

$_SERVER['REQUEST_URI'] == '/' ? $serverURI[1] = $_SERVER['REQUEST_URI'] : $serverURI = explode('/', $_SERVER['REQUEST_URI']);


if ($serverURI[1] == 'error') {
    $mustacheController = new mustacheController('error', [], 'template');
    $mustacheController->mustacheRenderer();
    exit;
}

if (isset($_COOKIE['SESSION'])) {
    $tourCMSService = new tourCMSService();

    #IN PROGRESS
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
    try {
        $results = [];
        $template = '';
        switch ($serverURI[1]) {
            case 'channels':
                $results = $tourCMSService->getTourCMSData('channels', 'channels');
                $template = 'channels';
                break;

            case 'tours':
                $results = $tourCMSService->getTourCMSData('tours', 'tours', $serverURI[2]);
                $template = 'tours';
                break;

            case 'tour':
                $results = $tourCMSService->getTourCMSData('tour', 'tour', $serverURI[2], '', $serverURI[3]);
                $template = 'tour';
                break;

            case '/':
                header('Location: ' . $serverName . '/channels');
                exit();

        }
        $mustacheController = new mustacheController($template, $results, 'template');
        $mustacheController->mustacheRenderer();

    } catch (\Throwable $th) {
        header('Location: ' . $serverName . '/error');
        exit();
    }



} else {
    if ($serverURI[1] != '/') {
        header('Location: ' . $serverName);
    }
    $mustacheController = new mustacheController('login', [], 'template');
    $mustacheController->mustacheRenderer();
    if (isset($_POST['uname']) && isset($_POST['psw'])) {
        try {
            $login = new loginController();
            $login->userLogged([$_POST['uname'] => $_POST['psw']]);
        } catch (\Throwable $th) {
            header('Location: ' . $serverName . '/error');
            exit();
        }
    }

}



