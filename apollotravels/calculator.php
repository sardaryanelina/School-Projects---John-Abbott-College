<?php

require_once 'vendor/autoload.php';
require_once 'ini.php';

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// STATE 1: first display
$app->get('/calculator', function (Request $request, Response $response, $args) {
    $step = null;
    $valuesList = ['step' => $step];
    return $this->view->render($response, 'calculator.html.twig', ['v' => $valuesList]);
});

// STATE 2&3: receiving form submission
$app->post('/calculator',   function (Request $request, Response $response, $args) use ($log) {
    // user should be logged in already from delivery page
    if (!isset($_SESSION['user'])) { // refuse if user not logged in
        $response = $response->withStatus(403); // 403 - access forbidden
        return $this->view->render($response, 'error_access_denied.html.twig');
    }

    $step = $request->getParam('step');
    $sum = 0;

    // get values
    if ($step == 1) {
        $_SESSION['height'] = $request->getParam('height');
    } elseif ($step == 2) {
        $_SESSION['width'] = $request->getParam('width');
    } elseif ($step == 3) {
        $weight = $request->getParam('weight');
        try {

            $height_value = DB::queryFirstRow("SELECT * FROM calculator WHERE attribute_name=%s", $_SESSION['height']);
            $width_value = DB::queryFirstRow("SELECT * FROM calculator WHERE attribute_name=%s", $_SESSION['width']);
            $weight_value = DB::queryFirstRow("SELECT * FROM calculator WHERE attribute_name=%s", $weight);

            $sum = $height_value['attribute_value'] + $width_value['attribute_value'] + $weight_value['attribute_value'];
            $sum = number_format($sum, 2, '.', ' ');
        } catch (Exception  $e) {
            print_r($e->getMessage());
        }
    }

    $valuesList = ['step' => $step, 'sum' => $sum];
    $log->debug(sprintf("Total: %s calculated by %s %s, email: %s,  from %s", $sum, $_SESSION['user']['firstname'], $_SESSION['user']['lastname'], $_SESSION['user']['email'], $_SERVER['REMOTE_ADDR']));
    return $this->view->render($response, "calculator.html.twig",  ['v' => $valuesList]);
});
