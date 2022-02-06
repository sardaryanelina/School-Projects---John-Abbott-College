<?php
// lazy-loading library classes
require_once 'vendor/autoload.php';
require_once 'ini.php';

// like import in Java
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


// Set your secret key. Remember to switch to your live secret key in production.
// See your keys here: https://dashboard.stripe.com/apikeys

\Stripe\Stripe::setApiKey('sk_test_51Itz2BGSGcqwiHoh1BSjF4vnK0ADvmPajkSLMGYcv1U70FxOjRUiBqTG1gpnwrKjLQTR2vZhWqIPfUROnulQtfD300429gRdEL');

$app->post('/bookingdeposit', function (Request $request, Response $response, $args) use ($log) {
    if (!isset($_SESSION['user'])) { // refuse if user not logged in
        $response = $response->withStatus(403);
        return $this->view->render($response, 'error_access_denied.html.twig');
    }
    // Token is created using Stripe Checkout or Elements!
    // Get the payment token ID submitted by the form:
    //$token = $_POST['stripeToken'];
    $token = test_input($request->getParam('stripeToken'));

    $customer = \Stripe\Customer::create(array(
        "email" => $_SESSION['user']['email'],
        "source" => $token
    ));

    $charge = \Stripe\Charge::create([
        'amount' => 10000000,
        'currency' => 'usd',
        'description' => 'Booking deposit',
        'customer' => $customer->id
    ]);
    
    $now = new DateTime(null, new DateTimeZone('America/New_York'));
    $service_id = DB::queryFirstField("SELECT MAX(id) as id FROM training");
    DB::insert('transactions', [
        'id' => $charge['id'], 
        'customer_id' => $_SESSION['user']['id'], 
        'service_id' => $service_id,
        'description' => $charge['description'],
        'amount' => $charge['amount'],
        'currency' => $charge['currency'],
        'status' => $charge['status'], 
        'created' => $now]);
    return $this->view->render($response, 'paysuccess.html.twig', ['confirm' => $charge['id'], 'userSession' => $_SESSION['user']]);
});
