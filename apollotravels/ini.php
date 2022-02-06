<?php

// lazy-loading library classes
require_once 'vendor/autoload.php';

// like import in Java
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler(dirname(__FILE__) . '/logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler(dirname(__FILE__) . '/logs/errors.log', Logger::ERROR));

// pass /vlAkDeQX99QM)b/
//MeekroDB: https://meekro.com/quickstart.html and https://github.com/SergeyTsalkov/meekrodb
/*DB::$dbName = 'apollotravels';
DB::$user = 'apollotravels';
DB::$password = '/vlAkDeQX99QM)b/';
DB::$host = 'localhost';
DB::$port = 3333; //Em: 8889 */

// Remote DB Connection:
// pass jtELTbV7QP7U

 if (
     strpos($_SERVER['HTTP_HOST'], "ipd24.ca") !== false
 ) {
     // hosting on ipd24.ca - 69.16.227.64
     DB::$dbName = 'cp5003_apollo';
     DB::$user = 'cp5003_apollotravels';
     DB::$password = 'jtELTbV7QP7U';
 } else { // local computer
     DB::$dbName = 'apollotravels';
     DB::$user = 'apollotravels';
     DB::$password = '/vlAkDeQX99QM)b/';
     DB::$host = 'localhost';
     DB::$port = 3333; //Em: 8889 - Anastassia and Elina: 3333
 }

DB::$error_handler = 'db_error_handler'; // runs on mysql query errors
DB::$nonsql_error_handler = 'db_error_handler'; // runs on library errors (bad syntax, etc)

function db_error_handler($params)
{
    global $log;
    // log first
    $log->error("Database error: " . $params['error']);
    if (isset($params['query'])) {
        $log->error("SQL query: " . $params['query']);
    }
    // redirect
    header("Location: /internalerror");
    die;
}

// Create and configure Slim app
$config = ['settings' => [
    'addContentLengthHeader' => false,
    'displayErrorDetails' => true
]];
$app = new \Slim\App($config);

// Fetch DI Container
$container = $app->getContainer();

// Register Twig View helper
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig(dirname(__FILE__) . '/templates', [
        'cache' => dirname(__FILE__) . '/tmplcache',
        'debug' => true, // This line should enable debug mode
    ]);
    //
    $view->getEnvironment()->addGlobal('test1', 'VALUE');
    // Instantiate and add Slim specific extension
    $router = $c->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
    return $view;
};

// All templates will be given userSession variable
$container['view']->getEnvironment()->addGlobal('userSession', $_SESSION['user'] ?? null);
$container['view']->getEnvironment()->addGlobal('flashMessage', getAndClearFlashMessage());

$passwordPepper = 'mmyb7oSAeXG9DTz2uFqu';


// LOGIN / LOGOUT USING FLASH MESSAGES TO CONFIRM THE ACTION

function setFlashMessage($message)
{
    $_SESSION['flashMessage'] = $message;
}

// returns empty string if no message, otherwise returns string with message and clears is
function getAndClearFlashMessage()
{
    if (isset($_SESSION['flashMessage'])) {
        $message = $_SESSION['flashMessage'];
        unset($_SESSION['flashMessage']);
        return $message;
    }
    return "";
}
