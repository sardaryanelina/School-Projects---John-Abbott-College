<?php

require_once 'vendor/autoload.php';
require_once 'ini.php';

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// STATE 1: first display
$app->get('/register', function (Request $request, Response $response, $args) {
    return $this->view->render($response, 'register.html.twig');
});

// STATE 2: submission of the completed form 
$app->post('/register', function (Request $request, Response $response, $args) use ($log) {

   /* $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = '6Lew1NsaAAAAAJVqHkMy7L66VS_eAOGpcEXfKTDr';
    $recaptcha_response = $_POST['recaptcha_response'];

    // // Make and decode POST request:
     $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
     $recaptcha = json_decode($recaptcha, true);

    // Take action based on the score returned:
    if ($recaptcha->score < 0.5) {
        $response = $response->withStatus(403);
        return $this->view->render($response, 'error_access_denied.html.twig');
    }*/

    $firstname = test_input($request->getParam('firstname'));
    $lastname  = test_input($request->getParam('lastname'));
    $email = test_input($request->getParam('email'));
    $password = test_input($request->getParam('password'));
    $password_confirm = test_input($request->getParam('password_confirm'));
    $errorList = ['firstname' => '', 'lastname' => '', 'email' => '', 'password' => '', 'password_confirm' => ''];

    // validation first name
    $result = verifyUserName($firstname);
    if (empty($firstname)) {
        $errorList['firstname'] = "First name must not be empty."; // append to array
    } elseif ($result !== true) {
        $firstname = '';
        $errorList['firstname'] = $result; // append to array
    }
    // validation last name
    $result = verifyUserName($lastname);
    if (empty($lastname)) {
        $errorList['lastname'] = "Last name must not be empty."; // append to array
    } elseif ($result !== true) {
        $lastname = '';
        $errorList['lastname'] = $result; // append to array
    }
    // validation email
    if (empty($email)) {
        $errorList['email'] = "Email must not be empty."; // append to array
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
        $errorList['email'] = "Email does not look valid";
    } else {
        // is email already in use?
        $record = DB::queryFirstRow("SELECT id FROM users WHERE email=%s", $email);
        if ($record) {
            $email = "";
            $errorList['email'] = "This email is already registered";
        }
    }
    // validation password
    $result = verifyPasswordQuailty($password);
    if (empty($password)) {
        $errorList['password'] = "Password must not be empty."; // append to array
    } elseif ($result !== true) {
        $errorList['password'] = $result;
    }
    if ($password !== $password_confirm) {
        $errorList['password_confirm'] = "Passwords do not match.";
    }
    // to hash the password
    global $passwordPepper;
    $pwdPeppered = hash_hmac("sha256", $password, $passwordPepper);
    $pwdHashed = password_hash($pwdPeppered, PASSWORD_DEFAULT); // PASSWORD_ARGON2ID);

    // display error messages
    if (array_filter($errorList)) {
        $valuesList = ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'password' => $password, 'password_confirm' => $password_confirm];
        $log->info(sprintf("Register failed for for %s %s, email %s from %s", $firstname, $lastname, $email, $_SERVER['REMOTE_ADDR']));
        return $this->view->render($response, 'register.html.twig', ['v' => $valuesList, 'e' => $errorList]);
    } else {
        $now = new DateTime(null, new DateTimeZone('America/New_York'));
        DB::insert('users', ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'password' => $pwdHashed, 'type' => 0, 'created' => $now]);
        $log->debug(sprintf("Register successful for %s %s, email: %s,  from %s", $firstname, $lastname, $email, $_SERVER['REMOTE_ADDR']));
        return $this->view->render($response, 'registerconfirm.html.twig');
    }
});

// STATE 1: first display
$app->get('/contact', function (Request $request, Response $response, $args) {
    return $this->view->render($response, 'contact.html.twig');
});

// STATE 2&3: receiving form submission
$app->post('/contact', function (Request $request, Response $response, $args) use ($log) {
   /* $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = '6Lew1NsaAAAAAJVqHkMy7L66VS_eAOGpcEXfKTDr';
    $recaptcha_response = $_POST['recaptcha_response'];

    // Make and decode POST request:
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);

    // Take action based on the score returned:
    if ($recaptcha->score < 0.5) {
        $response = $response->withStatus(403);
        return $this->view->render($response, 'error_access_denied.html.twig');
    }*/

    $firstname = test_input($request->getParam('firstname'));
    $lastname = test_input($request->getParam('lastname'));
    $email = test_input($request->getParam('email'));
    $message = test_input($request->getParam('message'));
    $message = strip_tags($message, "<p><ul><li><em><ol><h3><h4><span>");
    $date = new DateTime('NOW');

    if (isset($_SESSION['user'])) {
        $userid = $_SESSION['user']['id'];
    } else {
        $userid = null;
    }
    $errorList = ['firstname' => '', 'lastname' => '', 'email' => '', 'message' => ''];
    // var_dump($_SESSION);

    // validation
    //[a-zA-Z0-9\.\(\)'\",\\ -]{1,150}
    $result = verifyUserName($firstname);
    if ($result !== true) {
        $firstname = "";
        $errorList['firstname'] = $result; // append to array
    }

    $result = verifyUserName($lastname);
    if ($result !== true) {
        $lastname = "";
        $errorList['lastname'] = $result; // append to array
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE || strlen($email) > 320) {
        $email = "";
        $errorList['email'] = "Invalid email."; // append to array
    }

    if (strlen($message) < 5 || strlen($message) > 2000) {
        // $message = ""; // keep even if it is invalid
        $errorList['message'] = "Message must be 5 - 2000 characters long"; // append to array
    }

    if (array_filter($errorList)) { // STATE 2: errors - show and redisplay the form
        $valuesList = ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'message' => $message];
        $log->info(sprintf("Contact us_ message failed for %s %s, email %s from %s", $firstname, $lastname, $email, $_SERVER['REMOTE_ADDR']));
        $message = 'Invalid input';
        setFlashMessage($message);
        return $this->view->render($response, "contact.html.twig", ['errorList' => $errorList, 'v' => $valuesList, 'flashmessage' => $_SESSION['flashMessage']]);
    } else { // STATE 3: success
        //  $date = new DateTime(null, new DateTimeZone('America/New_York'));
        DB::insert('contact_form', ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'message' => $message, 'user_id' => $userid, 'date' => $date]);
        $log->debug(sprintf("Message sent successfully by %s %s, email: %s, %s", $firstname, $lastname, $email, $_SERVER['REMOTE_ADDR']));
        return $this->view->render($response, "contact_success.html.twig");
        // $message = 'Your message was sent successfully';
        // setFlashMessage($message);
        // return $this->view->render($response, "contact.html.twig", ['flashmessage' => $_SESSION['flashMessage']]);
    }
});

// STATE 1: first display
$app->get('/login', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html.twig');
});

// STATE 2 & 3: receiving form submission
$app->post('/login', function (Request $request, Response $response, $args) use ($log) {

    $email = test_input($request->getParam('email'));
    $password = test_input($request->getParam('password'));
    //
    $record = DB::queryFirstRow("SELECT id,firstname,lastname, email,password,type, created FROM users WHERE email=%s", $email);
    $loginSuccess = false;
    if ($record) {
        global $passwordPepper;
        $pwdPeppered = hash_hmac("sha256", $password, $passwordPepper);
        $pwdHashed = $record['password'];
        if (password_verify($pwdPeppered, $pwdHashed)) {
            $loginSuccess = true;
        }
    }
    if (!$loginSuccess) {
        $log->info(sprintf("Login failed for email %s from %s", $email, $_SERVER['REMOTE_ADDR']));
        return $this->view->render($response, 'login.html.twig', ['error' => true]);
    } else {
        unset($record['password']); // for security reasons remove password from session
        $_SESSION['user'] = $record; // remember user logged in
        $log->debug(sprintf("Login successful for email %s, uid=%d, from %s", $email, $record['id'], $_SERVER['REMOTE_ADDR']));
        $message = 'Hello ' . $_SESSION['user']['firstname'] . ' , your are logged in';
        setFlashMessage($message);
        return $response->withRedirect("/", 301, ['userSession' => $_SESSION['user']]);
        //return $this->view->render($response, 'landing.html.twig', ['userSession' => $_SESSION['user'], 'flashMessage' => $_SESSION['flashMessage']]);
    }
});
// logout page 
$app->get('/logout', function ($request, $response, $args) use ($log) {
    $log->debug(sprintf("Logout successful for uid=%d, from %s", @$_SESSION['user']['id'], $_SERVER['REMOTE_ADDR']));
    unset($_SESSION['user']);
    return $this->view->render($response, 'logout.html.twig', ['userSession' => null]);
});

// STATE 1: first display
$app->get('/booking', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['user'])) { // refuse if user not logged in
        $response = $response->withStatus(403);
        return $this->view->render($response, 'error_access_denied.html.twig');
    }
    $valueList = DB::queryFirstRow("SELECT * FROM customers WHERE user_id=%s", $_SESSION['user']['id']);
    return $this->view->render($response, 'booking.html.twig', ['user' => $_SESSION['user'], 'v' => $valueList]);
});


// STATE 2 & 3: receiving form submission
$app->post('/booking', function (Request $request, Response $response, $args) use ($log) {
    if (!isset($_SESSION['user'])) { // refuse if user not logged in
        $response = $response->withStatus(403);
        return $this->view->render($response, 'error_access_denied.html.twig');
    }
    // reading user inputs
    $street_address = test_input($request->getParam('street_address'));
    $city = test_input($request->getParam('city'));
    $province = test_input($request->getParam('province'));
    $postalcode = test_input($request->getParam('postalcode'));
    $country = test_input($request->getParam('country'));
    $phone = test_input($request->getParam('phone'));
    $gender = test_input($request->getParam('gender'));
    $age = test_input($request->getParam('age'));
    $height = test_input($request->getParam('height'));
    $weight = test_input($request->getParam('weight'));
    $insurance = test_input($request->getParam('insurance'));
    $comment = test_input($request->getParam('comment'));
    $spaceship = test_input($request->getParam('spaceship'));
    $date_start = test_input($request->getParam('date_start'));
    $date_end =  date('Y-m-d', strtotime($date_start . ' + 13 days'));
    $errorList = ['street_address' => '', 'city' => '', 'province' => '', 'postalcode' => '', 'country' => '', 'phone' => '', 'gender' => '', 'age' => '', 'height' => '', 'weight' => '', 'insurance' => '', 'comment' => '', 'spaceship' => '', 'date_start' => '', 'date_end' => ''];

    // validate user inputs
    if (empty($street_address)) {
        $errorList['street_address'] = "Street address must not be empty."; // append to array
    }
    if (empty($city)) {
        $errorList['city'] = "City must not be empty."; // append to array
    } elseif (preg_match('/^[a-zA-Z0-9\ ]{1,100}$/', $city) != 1) { // no match
        $errorList['city'] = "City must be 1-100 characters long and consist of letters,digits and spaces.";
    }
    if (empty($province)) {
        $errorList['province'] = "Province must not be empty."; // append to array
    } elseif (preg_match('/^[a-zA-Z\ ]{1,100}$/', $province) != 1) { // no match
        $errorList['province'] = "Province must be 1-100 characters long and consist of letters and spaces.";
    }
    if (empty($postalcode)) {
        $errorList['postalcode'] = "Postal code must not be empty."; // append to array
    } elseif (preg_match('/^[a-zA-Z0-9\ ]{1,20}$/', $postalcode) != 1) { // no match
        $errorList['province'] = "Postal Code must be 1-20 characters long and consist of letters, digits and spaces.";
    }
    if (empty($country)) {
        $errorList['country'] = "Country must not be empty."; // append to array
    } elseif (preg_match('/^[a-zA-Z\ ]{1,100}$/', $country) != 1) { // no match
        $errorList['country'] = "Country must be 1-100 characters long and consist of letters and spaces.";
    }
    if (empty($phone)) {
        $errorList['phone'] = "Phone must not be empty."; // append to array
    } elseif (preg_match('/^[0-9\ ]{1,20}$/', $phone) != 1) { // no match
        $errorList['phone'] = "Phone must be 1-15 characters long and consist of digits and spaces.";
    }
    if (empty($gender)) {
        $errorList['gender'] = "Gender must not be empty."; // append to array
    }
    if (empty($age)) {
        $errorList['age'] = "Age must not be empty."; // append to array
    } elseif (preg_match('/^[0-9\ ]{1,3}$/', $age) != 1) { // no match
        $errorList['age'] = "Age must be digits only.";
    } elseif ($age < 21 || $age > 70) { // no match
        $errorList['age'] = "Age restrictions: must be between 21 and 70 y/o.";
    }
    if (empty($height)) {
        $errorList['height'] = "Height must not be empty."; // append to array
    } elseif (preg_match('/^[0-9\ ]{1,3}$/', $height) != 1) { // no match
        $errorList['height'] = "Height must be digits only.";
    } elseif ($height < 160 || $height > 190) { // no match
        $errorList['height'] = "Height restrictions: must be between 160 and 190 cm tall.";
    }
    if (empty($weight)) {
        $errorList['weight'] = "Weight must not be empty."; // append to array
    } elseif (preg_match('/^[0-9\ ]{1,3}$/', $weight) != 1) { // no match
        $errorList['weight'] = "Weight must be digits only.";
    } elseif ($weight < 50 || $weight > 95) { // no match
        $errorList['weight'] = "Weight restrictions: must be between 50 and 95 kg.";
    }
    if (empty($insurance)) {
        $errorList['insurance'] = "Insurance must not be empty."; // append to array
    } elseif (preg_match('/^[a-zA-Z0-9\ ]{1,250}$/', $insurance) != 1) { // no match
        $errorList['insurance'] = "Insuranse must be 1-100 characters long and consist of letters,digits and spaces.";
    }
    if (strlen($comment) > 300) { // no match
        $errorList['comment'] = "Comment is up to 300 characters long.";
    }
    if (empty($spaceship)) {
        $errorList['spaceship'] = "Spaceship must not be empty."; // append to array
    }
    $date_now = date("Y-m-d");
    if (empty($date_start)) {
        $errorList['date_start'] = "Start date must not be empty."; // append to array
    } elseif ($date_start <= $date_now) { // no match
        $errorList['date_start'] = "Start date should be after today.";
    }
    if ($date_end <= $date_start && !empty($date_end)) { // no match
        $errorList['date_end'] = "End date should be after start date.";
    }
    // get spaceship id
    $spaceship_id = DB::queryFirstRow("SELECT id FROM spaceships WHERE name=%s", $spaceship);
    // check the start date in DB
    $checkStartDate = DB::queryFirstRow("SELECT date_start, spaceship_id FROM training WHERE date_start BETWEEN %s AND %s", $date_start, $date_end);
    // check the end date in DB
    $checkEndDate = DB::queryFirstRow("SELECT date_end, spaceship_id FROM training WHERE date_end BETWEEN %s AND %s", $date_start, $date_end);
    // check the overlap of booking periods
    if ($checkStartDate && $checkStartDate['spaceship_id'] === $spaceship_id['id']) {
        $d = $checkStartDate['date_start'];
        $dateUpdate =  date('Y-m-d', strtotime($d . ' + 14 days'));
        $errorList['date_start'] = "Please choose a date after $dateUpdate or a different spaceship.";
    }
    if ($checkEndDate && $checkEndDate['spaceship_id'] === $spaceship_id['id']) {
        $d = $checkEndDate['date_end'];
        $dateUpdate =  date('Y-m-d', strtotime($d));
        $errorList['date_start'] = "Please choose a date after $dateUpdate or a different spaceship.";
    }

    // check gender
    if ($gender == "male") {
        $gender = 1;
    } else {
        $gender = 0;
    }


    // display error messages
    if (array_filter($errorList)) {
        $valuesList = ['street_address' => $street_address, 'city' => $city, 'province' => $province, 'postalcode' => $postalcode, 'country' => $country, 'phone' => $phone, 'age' => $age, 'height' => $height, 'weight' => $weight, 'insurance' => $insurance, 'comment' => $comment, 'date_start' => $date_start, 'date_end' => $date_end];
        $log->info(sprintf("Booking failed for for %s %s, email %s from %s", $_SESSION['user']['firstname'], $_SESSION['user']['lastname'], $_SESSION['user']['email'], $_SERVER['REMOTE_ADDR']));
        return $this->view->render($response, 'booking.html.twig', ['v' => $valuesList, 'e' => $errorList]);
    } else {
        $now = new DateTime(null, new DateTimeZone('America/New_York'));
        if (empty(DB::query("SELECT * FROM customers WHERE user_id=%s", $_SESSION['user']['id']))) {
            DB::insert('customers', ['user_id' => $_SESSION['user']['id'], 'street_address' => $street_address, 'city' => $city, 'province' => $province, 'postalcode' => $postalcode, 'country' => $country, 'gender' => $gender, 'age' => $age, 'height' => $height, 'weight' => $weight, 'insurance' => $insurance, 'phone' => $phone, 'comment' => $comment, 'balance' => 0]);
        }
        DB::insert('training', ['user_id' => $_SESSION['user']['id'], 'date_start' => $date_start, 'date_end' => $date_end, 'spaceship_id' => $spaceship_id['id'], 'created' => $now]);
        $log->debug(sprintf("Register successful for %s %s, email: %s,  from %s", $_SESSION['user']['firstname'], $_SESSION['user']['lastname'], $_SESSION['user']['email'], $_SERVER['REMOTE_ADDR']));
        return $this->view->render($response, 'bookingconfirm.html.twig');
    }
});

// STATE 1: first display
$app->get('/bookingdeposit', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['user'])) { // refuse if user not logged in
        $response = $response->withStatus(403); // 403 - access forbidden
        return $this->view->render($response, 'error_access_denied.html.twig');
    }
    return $this->view->render($response, 'bookingdeposit.html.twig');
});

// STATE 1: first display
$app->get('/delivery', function (Request $request, Response $response, $args) {
    if (!isset($_SESSION['user'])) { // refuse if user not logged in
        $response = $response->withStatus(403); // 403 - access forbidden
        return $this->view->render($response, 'error_access_denied.html.twig');
    }
    return $this->view->render($response, 'delivery.html.twig');
});

// STATE 2&3: receiving form submission
$app->post('/delivery', function (Request $request, Response $response, $args) use ($log) {
   /* $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = '6Lew1NsaAAAAAJVqHkMy7L66VS_eAOGpcEXfKTDr';
    $recaptcha_response = $_POST['recaptcha_response'];

    // Make and decode POST request:
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);

    // Take action based on the score returned:
    if ($recaptcha->score < 0.5) {
        $response = $response->withStatus(403);
        return $this->view->render($response, 'error_access_denied.html.twig');
    }*/

    if (!isset($_SESSION['user'])) { // refuse if user not logged in
        $response = $response->withStatus(403); // 403 - access forbidden
        return $this->view->render($response, 'error_access_denied.html.twig');
    }

    $phone = test_input($request->getParam('phone'));
    $weight = test_input($request->getParam('weight'));
    $date = test_input($request->getParam('date'));
    $description = test_input($request->getParam('description'));
    $description_strp = strip_tags($description, "<p><ul><li><em><ol><h3><h4><span>"); // only allow this tags
    $image = test_input($request->getParam('image'));
    $spaceship = test_input($request->getParam('spaceship'));
    $userid = $_SESSION['user']['id'];
    $errorList = ['firstname' => '', 'lastname' => '', 'phone' => '', 'weight' => '', 'date' => '', 'description' => '', 'image' => '', 'spaceship' => ''];

    if (empty($phone)) {
        $errorList['phone'] = "Phone must not be empty."; // append to array
    } elseif (preg_match('/^[0-9\ ]{1,15}$/', $phone) != 1) { // no match
        $errorList['phone'] = "Phone must be 1-15 characters long and consist of digits and spaces.";
    }

    if (empty($weight)) {
        $errorList['weight'] = "Weight must not be empty."; // append to array
    } elseif (preg_match('/^[0-9]{1,2}$/', $weight) != 1) { // no match
        $errorList['weight'] = "Weight must be digits only and less than 99kg.";
    }

    $current_date =  date('Y-m-d'); //new DateTime(null, new DateTimeZone('America/New_York')); //date('Y-m-d');
    if (empty($date)) {
        $errorList['date'] = "Date must not be empty."; // append to array
    } elseif ($date <= $current_date) { // no match
        $errorList['date'] = "Date should be after today.";
    }

    if (strlen($description_strp) < 5 || strlen($description_strp) > 1000) {
        // $description = ""; // keep even if it is invalid
        $errorList['description'] = "Message must be 5 - 1000 characters long"; // append to array
    }

    if (empty($spaceship)) { // will not happen, because one radio button is checked by default
        $errorList['spaceship'] = "Spaceship must not be empty."; // append to array
    }

    // get spaceship id
    $spaceship_id = DB::queryFirstRow("SELECT id FROM spaceships WHERE name=%s", $spaceship);

    // verify image
    $hasPhoto = false;
    $mimeType = "";
    $uploadedImage = $request->getUploadedFiles()['image'];
    if ($uploadedImage->getError() != UPLOAD_ERR_NO_FILE) { // was anything uploaded?
        // print_r($uploadedImage->getError());
        $hasPhoto = true;
        $result = verifyUploadedPhoto($uploadedImage, $mimeType);
        if (
            $result !== TRUE
        ) {
            $errorList['image'] = $result;
        }
    }

    if (array_filter($errorList)) { // STATE 2: errors - show and redisplay the form
        $valuesList = ['phone' => $phone, 'weight' => $weight, 'date' => $date, 'description' => $description_strp, 'image' => $image, 'spaceship' => $spaceship];
        $log->debug(sprintf("Register successful for %s %s, email: %s,  from %s", $_SESSION['user']['firstname'], $_SESSION['user']['lastname'], $_SESSION['user']['email'], $_SERVER['REMOTE_ADDR']));
        return $this->view->render($response, "delivery.html.twig", ['errorList' => $errorList, 'v' => $valuesList]);
    } else { // STATE 3: success
        $photoData = null;
        if ($hasPhoto) {
            $photoData = file_get_contents($uploadedImage->file);
        }
        $current_date = new DateTime(null, new DateTimeZone('America/New_York')); // =new DateTime('NOW');
        DB::insert('deliveries', ['user_id' => $userid, 'delivery_date' => $date, 'weight' => $weight, 'spaceship_id' => $spaceship_id['id'], 'note' => $description_strp, 'imageData' => $photoData, 'imageMimeType' => $mimeType, 'contact_number' => $phone,  'curr_date' => $current_date]);
        $log->debug(sprintf("Register successful for %s %s, email: %s,  from %s", $_SESSION['user']['firstname'], $_SESSION['user']['lastname'], $_SESSION['user']['email'], $_SERVER['REMOTE_ADDR']));
        $message = 'Thank you, your delivery order is submitted';
        setFlashMessage($message);
        return $response->withRedirect("/delivery", 301);
        // return $this->view->render($response, "delivery.html.twig", ['flashmessage' => $_SESSION['flashMessage']]);
    }
});



// these functions return TRUE on success and string describing an issue on failure
function verifyUserName($name)
{
    if (preg_match('/^[a-zA-Z0-9\ \\._\'"-]{1,150}$/', $name) != 1) { // no match
        return "Name must be 1-150 characters long and consist of letters, digits, "
            . "spaces, dots, underscores, apostrophies, or minus sign.";
    }
    return TRUE;
}

function verifyPasswordQuailty($pass1)
{
    //"/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{6,100}$/"
    if (preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)[a-zA-Z0-9]{6,100}$/', $pass1) != 1) {
        return "Password must be at least 6 characters long and must contain at least one uppercase letter and one digit in it";
    }
    return TRUE;
}

// w3 code
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// used via AJAX
$app->get('/isemailtaken/[{email}]', function ($request, $response, $args) {
    $email = isset($args['email']) ? $args['email'] : "";
    $record = DB::queryFirstRow("SELECT id FROM users WHERE email=%s", $email);
    if ($record) {
        return $response->write("Email already in use");
    } else {
        return $response->write("");
    }
});



// returns TRUE on success
// returns a string with error message on failure
function verifyUploadedPhoto(Psr\Http\Message\UploadedFileInterface $photo, &$mime = null)
{
    if ($photo->getError() != 0) {
        return "Error uploading photo " . $photo->getError();
    }
    if ($photo->getSize() > 1024 * 1024) { // 1MiB
        return "File too big. 1MB max is allowed.";
    }
    $info = getimagesize($photo->file);
    if (!$info) {
        return "File is not an image";
    }
    // echo "\n\nimage info\n";
    // print_r($info);
    if ($info[0] < 200 || $info[0] > 1000 || $info[1] < 200 || $info[1] > 1000) {
        return "Width and height must be within 200-1000 pixels range";
    }
    $ext = "";
    switch ($info['mime']) {
        case 'image/jpeg':
            $ext = "jpg";
            break;
        case 'image/gif':
            $ext = "gif";
            break;
        case 'image/png':
            $ext = "png";
            break;
        default:
            return "Only JPG, GIF and PNG file types are allowed";
    }
    if (!is_null($mime)) {
        $mime = $info['mime'];
    }
    return TRUE;
}
