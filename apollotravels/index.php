<?php

//Error list for mac***
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// important session 
session_start();

// lazy-loading library classes
require_once 'vendor/autoload.php';
require_once 'ini.php';
require_once 'admin.php';

// like import in Java
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once 'users.php';
require_once 'charge.php';
require_once 'calculator.php';

// first display of the form test file
$app->get('/paysuccess', function ($request, $response, $args) {
    return $this->view->render($response, 'paysuccess.html.twig');
});

// first display of the form test file
$app->get('/dragon', function ($request, $response, $args) {
    return $this->view->render($response, 'dragon.html.twig');
});

// first display of the form test file
$app->get('/starship', function ($request, $response, $args) {
    return $this->view->render($response, 'starship.html.twig');
});

// first display of the form test file
$app->get('/temp', function ($request, $response, $args) {
    return $this->view->render($response, 'temp.html.twig');
});

// first display of the form test file
$app->get('/master', function ($request, $response, $args) {
    return $this->view->render($response, 'master.html.twig');
});


// STATE 1: first display
$app->get('/', function ($request, $response, $args) {
    return $this->view->render($response, 'landing.html.twig');
});


$app->get('/test', function ($request, $response, $args) {
    return $this->view->render($response, 'test.html.twig');
});

$app->get('/test2', function ($request, $response, $args) {
    return $this->view->render($response, 'test2.html.twig');
});

// STATE 1: first display
$app->get('/error_access_denied', function ($request, $response, $args) {
    return $this->view->render($response, 'error_access_denied.html.twig');
});

$app->get('/internalerror', function ($request, $response, $args) {
    return $this->view->render($response, 'error_internal.html.twig');
});

// Run app
$app->run();
