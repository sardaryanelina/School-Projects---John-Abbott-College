<?php

// =================== Admin Section =================== 

require_once 'vendor/autoload.php';
require_once 'ini.php';

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// $app->get('/admin', function ($request, $response, $args) {
//     return $this->view->render($response, 'admin.html.twig');
// });

// Define app routes

// ADMIN main interface first display (index)
$app->get('/admin', function ($request, $response, $args) {

    $trainingList = DB::query("SELECT a.id as training_id, b.id as u_id, b.firstname, b.lastname, b.email, a.date_start, a.date_end, c.name, d.balance " . "FROM training as a INNER JOIN users as b ON a.user_id = b.id INNER JOIN spaceships as c ON a.spaceship_id = c.id INNER JOIN customers as d ON b.id = d.user_id ORDER BY date_start DESC");

    $adminList = DB::query("SELECT u.id, u.firstname, u.lastname, u.email, u.created FROM users as u WHERE u.type = 1 ORDER BY u.created DESC");

    $usersList = DB::query("SELECT u.id, u.firstname, u.lastname, u.email, u.created FROM users as u WHERE u.type = 0 ORDER BY u.created DESC");

    foreach ($adminList as &$al) {
        $al['created'] = date('Y-m-d', strtotime($al['created']));
    }

    foreach ($usersList as &$ul) {
        $ul['created'] = date('Y-m-d', strtotime($ul['created']));
    }

    $starshipTrainingList = DB::query("SELECT s.id, s.name, COUNT(s.id) as total FROM training as t, spaceships as s WHERE t.spaceship_id = s.id GROUP BY s.id");

    $customersList = DB::query("SELECT * FROM customers ORDER BY id ASC");

    foreach($customersList as &$c) {
        if(strlen($c['comment']) > 20) {
            $commentCut = substr($c['comment'], 0, 10);
            $c['comment'] = $commentCut . "...";
        }
    }

    $spaceshipsList = DB::query("SELECT * FROM spaceships ORDER BY id ASC");

    if (!$trainingList || !$adminList || !$starshipTrainingList || !$customersList || !$spaceshipsList) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    return $this->view->render($response, 'admin/admin_index.html.twig', ['trainingList' => $trainingList, 'starshipTrainingList' => $starshipTrainingList, 'adminList' => $adminList, 'usersList' => $usersList, 'customersList' => $customersList, 'spaceshipsList' => $spaceshipsList]);
    // print_r($trainingList);
    //return $response->write("");
});


// STATE 1: ADD - EDIT User first display
$app->get('/admin/{u:admin|user}/{op:edit|add}[/{id:[0-9]+}]', function ($request, $response, $args) {
    //CASE 1: op is add (id not given)
    //CASE 2: op is edit (id given)
    $id = $args['id'];
    if( ($args['op'] == 'add' && !empty($id)) || ($args['op'] == 'edit' && empty($id)) ) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }
    if($args['op'] == 'edit') {
        if($args['u'] == 'admin') {
            $user = DB::queryFirstRow("SELECT * FROM users WHERE id=%d and type=1", $args['id']);
        } else {
            $user = DB::queryFirstRow("SELECT * FROM users WHERE id=%d and type=0", $args['id']);
        }
        if (!$user) {
            $response = $response->withStatus(404);
            return $this->view->render($response, 'admin/not_found.html.twig');
        }
    } else {
        $user = [];
    }

    // $admin['created'] = date('Y-m-d', strtotime($admin['created']));

    return $this->view->render($response, 'admin/users_addedit.html.twig', ['u' => $user, 'op' => $args['op'], 'type' => $args['u']]);
});

//STATE 2&3: ADD - EDIT User Receiving submission
$app->post('/admin/{u:admin|user}/{op:edit|add}[/{id:[0-9]+}]', function ($request, $response, $args) use ($log) {
    //CASE 1: op is add (id not given)
    //CASE 2: op is edit (id given)
    $op = $args['op'];
    if (($op == 'add' && !empty($args['id'])) || ($op == 'edit' && empty($args['id']))) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    $firstname = $request->getParam('firstname');
    $lastname = $request->getParam('lastname');
    $email = $request->getParam('email');
    $pass1 = $request->getParam('pass1');
    $pass2 = $request->getParam('pass2');

    //declare error list
    $errorList = ['firstname' => '', 'lastname' => '', 'email' => '', 'password' => '', 'password_confirm' => ''];

    //verif firstname
    $result = verifyUserName1($firstname);
    if ($result !== TRUE) {
        $firstname = ''; //reset
        $errorList['firstname'] = $result; //insert into errorList
    }

    //verif lastname
    $result = verifyUserName1($lastname);
    if($result !== TRUE) {
        $lastname = ''; //reset
        $errorList['lastname'] = $result; //insert into errorList
    }

    //verif email
    if($args['u'] == 'admin') {
        if ((filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE) || (preg_match("/@admin.ca/", $email) != 1)) {
            $email = ''; //reset
            $errorList['email'] = "Invalid admin email"; //insert into errorList
        }

        // is email already in use BY ANOTHER ACCOUNT???
        if ($op == 'edit') {
            $record = DB::queryFirstRow("SELECT u.id, u.firstname, u.email, u.password FROM users as u WHERE email=%s AND id!=%d", $email, $args['id']);
        } else { // add has no id yet
            $record = DB::queryFirstRow("SELECT u.id, u.firstname, u.email, u.password FROM users as u WHERE email=%s", $email);
        }

    } else {
        if ((filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE) || (preg_match("/@admin.ca/", $email) == 1)) {
            $email = ''; //reset
            $errorList['email'] = "Invalid email"; //insert into errorList
        }

        // is email already in use BY ANOTHER ACCOUNT???
        if ($op == 'edit') {
            $record = DB::queryFirstRow("SELECT u.id, u.firstname, u.email, u.password FROM users as u WHERE email=%s AND id!=%d", $email, $args['id']);
        } else { // add has no id yet
            $record = DB::queryFirstRow("SELECT u.id, u.firstname, u.email, u.password FROM users as u WHERE email=%s", $email);
        }
    }

    if ($record) {
        $email = ''; //reset
        $errorList['email'] = "This email is already in use"; //insert into errorList
    }

    // verify password always on add, and on edit/update only if it was given
    if ($op == 'add' || $pass1 != '') {
        $result = verifyPasswordStrenght($pass1, $pass2);
        if ($result !== TRUE) {
            $errorList['password'] = $result; // append to array
        }
    }

    // hash the password anyways
    global $passwordPepper;
    $pwdPeppered = hash_hmac("sha256", $pass1, $passwordPepper); //pass1???
    $pwdHashed = password_hash($pwdPeppered, PASSWORD_DEFAULT); // PASSWORD_ARGON2ID);
    $pass1 = $pwdHashed;


    //
    if ((array_filter($errorList))) {
        $log->info(sprintf($args['u'] . " add/adit failed for %s %s, email %s from %s", $firstname, $lastname, $email, $_SERVER['REMOTE_ADDR']));
        return $this->view->render(
            $response,
            'admin/users_addedit.html.twig',
            ['errorList' => $errorList, 'u' => ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email], 'op' => $op, 'type' => $args['u']]
        );
    } else {
        if ($op == 'add') {
            DB::insert('users', ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'password' => $pass1, 'created' => date('Y-m-d'), 'type' => $args['u'] == 'admin' ? 1 : 0]);
            $log->debug(sprintf($args['u'] . " add succeeded for %s %s, email %s from %s", $firstname, $lastname, $email, $_SERVER['REMOTE_ADDR']));
            return $this->view->render($response, 'admin/users_addedit_success.html.twig', ['op' => $op]);
        } else {
            $data = ['firstname' => $firstname, 'lastname' => $lastname, 'email' => $email];
            if ($pass1 != '') { // only update the password if it was provided and 'push' it to data array
                $data['password'] = $pass1;
            }
            DB::update('users', $data, "id=%d", $args['id']);
            $log->debug(sprintf($args['u'] . " edit succeeded for %s %s, email %s from %s", $firstname, $lastname, $email, $_SERVER['REMOTE_ADDR']));
            return $this->view->render($response, 'admin/users_addedit_success.html.twig', ['op' => $op, 'type' => $args['u']]);
        }
    }
});

//STATE 1: Delete users first display
$app->get('/admin/{u:admin|user}/delete/{id:[0-9]+}', function ($request, $response, $args) {
    $user = DB::queryFirstRow("SELECT * FROM users WHERE id=%d", $args['id']);
    if (!$user) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    $user['created'] = date('Y-m-d', strtotime($user['created']));

    return $this->view->render($response, 'admin/users_delete.html.twig', ['u' => $user, 'type' => $args['u']]);
});

//STATE 2: Delete users
$app->post('/admin/{u:admin|user}/delete/{id:[0-9]+}', function ($request, $response, $args) {
    DB::delete('users', "id=%d", $args['id']);
    return $this->view->render($response, 'admin/users_delete_success.html.twig', ['type' => $args['u']]);
});

//STATE 1: Display customers_more
$app->get('/admin/customers_more', function ($request, $response, $args) {
    $customers = DB::query("SELECT * FROM customers");
    if (!$customers) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    foreach($customers as &$c) {
        if($c['gender'] === 0) {
            $c['gender'] = "Male";
        } else {
            $c['gender'] = "Female";
        }

        if (strlen($c['street_address']) > 20) {
            $addressCut = substr($c['street_address'], 0, 10);
            $c['street_address'] = $addressCut . "...";
        }

        if (strlen($c['comment']) > 20) {
            $commentCut = substr($c['comment'], 0, 10);
            $c['comment'] = $commentCut . "...";
        }
    }

    return $this->view->render($response, 'admin/customers_more.html.twig', ['customersList' => $customers]);
});

// STATE 1: ADD - EDIT customers first display
$app->get('/admin/{u:customer}/{op:edit|add}[/{id:[0-9]+}]', function ($request, $response, $args) {
    //CASE 1: op is add (id not given)
    //CASE 2: op is edit (id given)
    $id = $args['id'];
    $users = '';
    if (($args['op'] == 'add' && !empty($id)) || ($args['op'] == 'edit' && empty($id))) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }
    if ($args['op'] == 'edit') {
        $cust = DB::queryFirstRow("SELECT * FROM customers WHERE id=%d", $args['id']);
        if (!$cust) {
            $response = $response->withStatus(404);
            return $this->view->render($response, 'admin/not_found.html.twig');
        }
    } else {
        $cust = [];
        $users = DB::query("SELECT id FROM users");
        if ($users == '') {
            $response = $response->withStatus(404);
            return $this->view->render($response, 'admin/not_found.html.twig');
        }
    }

    // $admin['created'] = date('Y-m-d', strtotime($admin['created']));

    return $this->view->render($response, 'admin/customers_addedit.html.twig', ['c' => $cust, 'users' => $users, 'op' => $args['op']]);
});

//STATE 2&3: ADD - EDIT Customer Receiving submission
$app->post('/admin/{u:customer}/{op:edit|add}[/{id:[0-9]+}]', function ($request, $response, $args) use ($log) {
    //CASE 1: op is add (id not given)
    //CASE 2: op is edit (id given)
    $op = $args['op'];
    if (($op == 'add' && !empty($args['id'])) || ($op == 'edit' && empty($args['id']))) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    $userID = $request->getParam('userID');
    $address = $request->getParam('address');
    $city = $request->getParam('city');
    $province = $request->getParam('prov');
    $country = $request->getParam('country');
    $postalcode = $request->getParam('postalcode');
    $phone = $request->getParam('phone');
    $gender = $request->getParam('gender');
    $age = $request->getParam('age');
    $height = $request->getParam('height');
    $weight = $request->getParam('weight');
    $insurance = $request->getParam('insurance');
    $balance = $request->getParam('balance');
    $comment = $request->getParam('comment');

    //declare error list
    $errorList = ['userID' => '', 'address' => '', 'city' => '', 'province' => '', 'country' =>'', 'postalcode' =>'', 'phone' =>'', 'gender' =>'', 'age' =>'', 'height' =>'', 'weight' =>'', 'insurance' =>'', 'balance' =>'', 'comment' =>''];

    //verif userID
    //1. retrieve user
    $user = DB::queryFirstRow("SELECT * FROM users WHERE id=%d", $userID);
    //2. when editing, $user must not be empty
    if($op == 'edit' && !$user) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }
    // 3. customer needs to be a user
    if($op == 'add' && !$user) {
        $errorList['userID'] = "User ID doesn't exist - must be a user to be a customer";
    }
    // 4. check if user is already a customer
    if($op == 'add' && $user) {
        $alreadyCust = DB::queryFirstRow("SELECT * FROM customers WHERE user_id=%d", $userID);
        if($alreadyCust) {
            $errorList['userID'] = "User is already a customer - please try editing the customer's info instead";
        }
    }

    //verif street address
    if (empty($address)) {
        $errorList['address'] = "Street address must not be empty.";
    }

    // verif city
    if (empty($city)) {
        $errorList['city'] = "City must not be empty.";
    } elseif (preg_match('/^[a-zA-Z0-9\ ]{1,100}$/', $city) != 1) { // no match
        $errorList['city'] = "City must be 1-100 characters long and consist of letters,digits and spaces.";
    }

    // verif province
    if (empty($province)) {
        $errorList['province'] = "Province must not be empty.";
    } elseif (preg_match('/^[a-zA-Z\ ]{1,100}$/', $province) != 1) { // no match
        $errorList['province'] = "Province must be 1-100 characters long and consist of letters and spaces.";
    }

    // verif country
    if (empty($country)) {
        $errorList['country'] = "Country must not be empty.";
    } elseif (preg_match('/^[a-zA-Z\ ]{1,100}$/', $country) != 1) { // no match
        $errorList['country'] = "Country must be 1-100 characters long and consist of letters and spaces.";
    }

    // verif postalcode
    if (empty($postalcode)) {
        $errorList['postalcode'] = "Postal code must not be empty.";
    } elseif (preg_match('/^[A-Z0-9\ ]{1,20}$/', $postalcode) != 1) { // no match
        $errorList['province'] = "Postal Code must be 1-20 characters long and consist of uppercase letters, digits and spaces.";
    }

    // verif phone
    if (empty($phone)) {
        $errorList['phone'] = "Phone must not be empty.";
    } elseif (preg_match('/^[0-9\ ]{1,15}$/', $phone) != 1) { // no match
        $errorList['phone'] = "Phone must be 15 digits long";
    }

    // verif gender
    if (empty($gender)) {
        $errorList['gender'] = "Gender must not be empty.";
    } else if (!is_numeric($gender) || $gender < 0 || $gender > 1) {
        $errorList['gender'] = "Gender must be represented with a numeric value, 1 for Female and 0 for Male.";
    }

    // verif age
    if (empty($age)) {
        $errorList['age'] = "Age must not be empty.";
    } elseif (preg_match('/^[0-9\ ]{1,3}$/', $age) != 1) { // no match
        $errorList['age'] = "Age must be digits only.";
    } elseif ($age < 21 || $age > 70
    ) { // no match
        $errorList['age'] = "Age restrictions: must be between 21 and 70 y/o.";
    }

    // verif height
    if (empty($height)) {
        $errorList['height'] = "Height must not be empty.";
    } elseif (preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $height) != 1) { // no match
        $errorList['height'] = "Height must be digits only, 0.00 (cm)";
    } elseif ($height < 160 || $height > 190) { // no match
        $errorList['height'] = "Height restrictions: must be between 160 and 190 cm tall.";
    }

    // verif weight
    if (empty($weight)) {
        $errorList['weight'] = "Weight must not be empty.";
    } elseif (preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $weight) != 1) { // no match
        $errorList['weight'] = "Weight must be digits only, 0.00 (kg)";
    } elseif ($weight < 50 || $weight > 95) { // no match
        $errorList['weight'] = "Weight restrictions: must be between 50 and 95 kg.";
    }

    // verif insurance
    if (empty($insurance)) {
        $errorList['insurance'] = "Insurance must not be empty.";
    } elseif (preg_match('/^[a-zA-Z0-9\ ]{1,250}$/', $insurance) != 1) { // no match
        $errorList['insurance'] = "Insuranse must be 1-250 characters long and consist of letters,digits and spaces.";
    }

    // verif balance
    if(preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $balance) != 1) { // no match
        $errorList['balance'] = "Balance must be digits only, 0.00 ($)";
    }

    // verif comment
    if (strlen($comment) > 300) { // no match
        $errorList['comment'] = "Comment cannot exceed 300 characters";
    }

    $comment = strip_tags($comment, "<p><ul><li><br><hr><em><strong><i><b><ol><span>"); //<h3><h4><h5>


    //
    if ((array_filter($errorList))) {
        $log->info(sprintf($args['u'] . " add/adit failed for user %d, from %s", $userID, $_SERVER['REMOTE_ADDR']));
        //declare data list
        $data = ['id' => $args['id'], 'user_id' => $userID, 'street_address' => $address, 'city' => $city, 'province' => $province, 'country' => $country, 'postalcode' => $postalcode, 'phone' => $phone, 'gender' => $gender, 'age' => $age, 'insurance' => $insurance, 'height' => $height, 'weight' => $weight, 'balance' => $balance, 'comment' => $comment];
        return $this->view->render(
            $response,
            'admin/customers_addedit.html.twig',
            ['errorList' => $errorList, 'c' => $data, 'op' => $op, 'type' => $args['u']]
        );
    } else {
        if ($op == 'add') {
            DB::insert('customers', ['user_id' => $userID, 'street_address' => $address, 'city' => $city, 'province' => $province, 'postalcode' => $postalcode, 'country' => $country, 'gender' => $gender, 'age' => $age, 'height' => $height, 'weight' => $weight, 'insurance' => $insurance, 'balance' => $balance, 'phone' => $phone, 'comment' => $comment]);
            $log->debug(sprintf($args['u'] . " add succeeded for user %d, from %s", $userID, $_SERVER['REMOTE_ADDR']));
            return $this->view->render($response, 'admin/users_addedit_success.html.twig', ['op' => $op, 'type' => $args['u']]);
        } else {
            $data = ['id' => $args['id'], 'user_id' => $userID, 'street_address' => $address, 'city' => $city, 'province' => $province, 'country' => $country, 'postalcode' => $postalcode, 'phone' => $phone, 'gender' => $gender, 'age' => $age, 'insurance' => $insurance, 'height' => $height, 'weight' => $weight, 'balance' => $balance, 'comment' => $comment];
            DB::update('customers', $data, "id=%d", $args['id']);
            $log->debug(sprintf($args['u'] ." edit succeeded for user %d, from %s", $userID, $_SERVER['REMOTE_ADDR']));
            return $this->view->render($response, 'admin/users_addedit_success.html.twig', ['op' => $op, 'type' => $args['u']]);
        }
    }
});

//STATE 1: Delete customer first display
$app->get('/admin/{u:customer}/delete/{id:[0-9]+}', function ($request, $response, $args) {
    $cust = DB::queryFirstRow("SELECT * FROM customers WHERE id=%d", $args['id']);
    if (!$cust) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    // $user['created'] = date('Y-m-d', strtotime($cust['created']));

    return $this->view->render($response, 'admin/users_delete.html.twig', ['c' =>$cust, 'type' => $args['u']]);
});

//STATE 2: Delete Customer
$app->post('/admin/{u:customer}/delete/{id:[0-9]+}', function ($request, $response, $args) {
    DB::delete('customers', "id=%d", $args['id']);
    return $this->view->render($response, 'admin/users_delete_success.html.twig', ['type' => $args['u']]);
});

// STATE 1: ADD - EDIT spaceship first display
$app->get('/admin/{u:spaceship}/{op:edit|add}[/{id:[0-9]+}]', function ($request, $response, $args) {
    //CASE 1: op is add (id not given)
    //CASE 2: op is edit (id given)
    if (($args['op'] == 'add' && !empty($args['id'])) || ($args['op'] == 'edit' && empty($args['id']))) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }
    
    if ($args['op'] == 'edit') {
        $spaceship = DB::queryFirstRow("SELECT * FROM spaceships WHERE id=%d", $args['id']);
        if (!$spaceship) {
            $response = $response->withStatus(404);
            return $this->view->render($response, 'admin/not_found.html.twig');
        }
    } else {
        $spaceship = [];
    }

    // $admin['created'] = date('Y-m-d', strtotime($admin['created']));

    return $this->view->render($response, 'admin/spaceship_addedit.html.twig', ['s' => $spaceship, 'op' => $args['op']]);
});

//STATE 2&3: ADD - EDIT Spaceship Receiving submission
$app->post('/admin/{u:spaceship}/{op:edit|add}[/{id:[0-9]+}]', function ($request, $response, $args) use ($log) {
    //CASE 1: op is add (id not given)
    //CASE 2: op is edit (id given)
    $op = $args['op'];
    if (($op == 'add' && !empty($args['id'])) || ($op == 'edit' && empty($args['id']))) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    $weight = $request->getParam('weight');
    $name = $request->getParam('name');

    //declare error list
    $errorList = ['weight_capacity' => '', 'name' => ''];


    // verif weight
    if (empty($weight)) {
        $errorList['weight_capacity'] = "Weight must not be empty.";
    } elseif (preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $weight) != 1) { // no match
        $errorList['weight_capacity'] = "Weight must be greater than 0 and digits only, 0.00 (kg)";
    } elseif ($weight < 0) {
        $errorList['weight_capacity'] = "Weight must be greater than 0";
    }


    if(empty($name)) {
        $errorList['name'] = "Name must not be empty";
    }
    if(strlen($name) < 1 || strlen($name) > 150) {
        $errorList['name'] = "Name must be 1-150 characters";
    }
    $name = test_input($name);
    $name = strip_tags($name, "<p><ul><li><br><hr><em><strong><i><b><ol><span>"); //<h3><h4><h5>


    //
    if ((array_filter($errorList))) {
        $log->info(sprintf($args['u'] . " add/adit failed for spaceship %d, name: %s from %s", $args['id'], $name, $_SERVER['REMOTE_ADDR']));
        //declare data list
        $data = ['name' => $name, 'weight_capacity' => $weight];
        return $this->view->render(
            $response,
            'admin/spaceship_addedit.html.twig',
            ['errorList' => $errorList, 's' => $data, 'op' => $op, 'type' => $args['u']]
        );
    } else {
        if ($op == 'add') {
            DB::insert('spaceships', ['name' => $name, 'weight_capacity' => $weight]);
            $log->debug(sprintf($args['u'] ." add succeeded for spaceship, name: %s from %s", $name, $_SERVER['REMOTE_ADDR']));
            return $this->view->render($response, 'admin/users_addedit_success.html.twig', ['op' => $op, 'type' => $args['u']]);
        } else {
            //declare data list
            $data = ['name' => $name, 'weight_capacity' => $weight];
            DB::update('spaceships', $data, "id=%d", $args['id']);
            $log->debug(sprintf($args['u'] ." edit succeeded for spaceship %d, name: %s from %s", $args['id'], $name, $_SERVER['REMOTE_ADDR']));
            return $this->view->render($response, 'admin/users_addedit_success.html.twig', ['op' => $op, 'type' => $args['u']]);
        }
    }
});


//STATE 1: Delete spaceship first display
$app->get('/admin/{u:spaceship}/delete/{id:[0-9]+}', function ($request, $response, $args) {
    $spaceship = DB::queryFirstRow("SELECT * FROM spaceships WHERE id=%d", $args['id']);
    if (!$spaceship) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    // $user['created'] = date('Y-m-d', strtotime($cust['created']));

    return $this->view->render($response, 'admin/users_delete.html.twig', ['s' =>$spaceship, 'type' => $args['u']]);
});

//STATE 2: Delete Spaceship
$app->post('/admin/{u:spaceship}/delete/{id:[0-9]+}', function ($request, $response, $args) {
    DB::delete('spaceships', "id=%d", $args['id']);
    return $this->view->render($response, 'admin/users_delete_success.html.twig', ['type' => $args['u']]);
});

// STATE 1: ADD - EDIT training first display
$app->get('/admin/{u:training}/{op:edit|add}[/{id:[0-9]+}]', function ($request, $response, $args) {
    //CASE 1: op is add (id not given)
    //CASE 2: op is edit (id given)
    if (($args['op'] == 'add' && !empty($args['id'])) || ($args['op'] == 'edit' && empty($args['id']))) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    if ($args['op'] == 'edit') {
        $training = DB::queryFirstRow("SELECT * FROM training WHERE id=%d", $args['id']);
        if (!$training) {
            $response = $response->withStatus(404);
            return $this->view->render($response, 'admin/not_found.html.twig');
        }
    } else {
        $training = [];
    }

    // $admin['created'] = date('Y-m-d', strtotime($admin['created']));
    $training['date_start'] = date('Y-m-d', strtotime($training['date_start']));
    $training['date_end'] = date('Y-m-d', strtotime($training['date_end']));

    return $this->view->render($response, 'admin/training_addedit.html.twig', ['t' => $training, 'op' => $args['op']]);
});

//STATE 2&3: ADD - EDIT Training Receiving submission
$app->post('/admin/{u:training}/{op:edit|add}[/{id:[0-9]+}]', function ($request, $response, $args) use ($log) {
    //CASE 1: op is add (id not given)
    //CASE 2: op is edit (id given)
    $op = $args['op'];
    if (($op == 'add' && !empty($args['id'])) || ($op == 'edit' && empty($args['id']))) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    $userID = $request->getParam('uid');
    $spaceshipID = $request->getParam('sid');
    $startDate = $request->getParam('ds');
    $endDate = $request->getParam('de');


    //declare error list
    $errorList = ['userID' => '', 'spaceshipID' => '', 'startDate' => '', 'endDate' => ''];


    $user = DB::queryFirstRow("SELECT * FROM users WHERE id=%d", $userID);
    if(!$user) {
        $errorList['userID'] = "User doesn't exist";
    }

    $spaceship = DB::queryFirstRow("SELECT * FROM spaceships WHERE id=%d", $spaceshipID);
    if(!$spaceship) {
        $errorList['spaceshipID'] = "Spaceship doesn't exist";
    }

    $current_date =  date('Y-m-d');
    if (empty($startDate)) {
        $errorList['startDate'] = "Date must not be empty.";
    } elseif ($startDate <= $current_date) { // no match
        $errorList['startDate'] = "Date should be after today.";
    }

    $current_date =  date('Y-m-d');
    if (empty($endDate)) {
        $errorList['endDate'] = "Date must not be empty.";
    } elseif ($endDate <= $current_date || $endDate < $startDate) { // no match
        $errorList['endDate'] = "End date should be after start date";
    }

    //
    if ((array_filter($errorList))) {
        $log->info(sprintf($args['u'] . " add/adit failed for sapceship %d", $spaceshipID, $_SERVER['REMOTE_ADDR']));
        //declare data list
        $data = ['id' => $args['id'], 'user_id' => $userID, 'spaceship_id' => $spaceshipID, 'date_start' => $startDate, 'date_end' => $endDate];
        return $this->view->render(
            $response,
            'admin/training_addedit.html.twig',
            ['errorList' => $errorList, 't' => $data, 'op' => $op, 'type' => $args['u']]
        );
    } else {
        if ($op == 'add') {
            DB::insert('training', ['user_id' => $userID, 'date_start' => $startDate, 'date_end' => $endDate, 'spaceship_id' => $spaceshipID]);
            $log->debug(sprintf($args['u'] . " add succeeded for spaceship %d", $spaceshipID, $_SERVER['REMOTE_ADDR']));
            return $this->view->render($response, 'admin/users_addedit_success.html.twig', ['op' => $op, 'type' => $args['u']]);
        } else {
            //declare data list
            $data = ['id' => $args['id'], 'user_id' => $userID, 'spaceship_id' => $spaceshipID, 'date_start' => $startDate, 'date_end' => $endDate];
            DB::update('training', $data, "id=%d", $args['id']);
            $log->debug(sprintf($args['u'] ." edit succeeded for spaceship %d", $spaceshipID, $_SERVER['REMOTE_ADDR']));
            return $this->view->render($response, 'admin/users_addedit_success.html.twig', ['op' => $op, 'type' => $args['u']]);
        }
    }
});



//STATE 1: Delete training first display
$app->get('/admin/{u:training}/delete/{id:[0-9]+}', function ($request, $response, $args) {
    $trainings = DB::queryFirstRow("SELECT * FROM training as t WHERE id=%d", $args['id']);
    if (!$trainings) {
        $response = $response->withStatus(404);
        return $this->view->render($response, 'admin/not_found.html.twig');
    }

    // $user['created'] = date('Y-m-d', strtotime($cust['created']));

    return $this->view->render($response, 'admin/users_delete.html.twig', ['t' => $trainings, 'type' => $args['u']]);
});

//STATE 2: Delete Training
$app->post('/admin/{u:training}/delete/{id:[0-9]+}', function ($request, $response, $args) {
    DB::delete('training', "id=%d", $args['id']);
    return $this->view->render($response, 'admin/users_delete_success.html.twig', ['type' => $args['u']]);
});

//=====================================================================================================

//Middleware - Teacher's video
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

// Function to check string starting 
// with given substring 
function startsWith($string, $startString)
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
} 

$app->add(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
    $url = $request->getUri()->getPath();
    if (startsWith($url, "/admin")) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 1) { // refuse if user not logged in AS ADMIN
            $response = $response->withStatus(403);
            return $this->view->render($response, 'admin/error_access_denied.html.twig');
        }
    }
    return $next($request, $response);
});





//=====================================================================================================

//HELPER FUNCTIONS
function verifyUserName1($name)
{
    if (preg_match('/^[a-zA-Z0-9\ \\._\'"-]{1,150}$/', $name) != 1) { // no match
        return "Name must be 1-150 characters long and consist of letters, digits, "
            . "spaces, dots, underscores, apostrophies, or minus sign.";
    }
    return TRUE;
}

function verifyPasswordStrenght($pass1, $pass2)
{
    if (empty($pass1)) {
        return "Password must not be empty.";
    } elseif ($pass1 !== $pass2) {
        return "Passwords do not match";
    } else {

        if(preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)[a-zA-Z0-9]{6,100}$/', $pass1) != 1) {
            return "Password must be 6-100 characters long, "
            . "with at least one uppercase, one lowercase, and one digit in it";
        }

    }
    return TRUE;
}

// w3 code
function test_input1($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}




// to hash the password
// global $passwordPepper;
// $pwdPeppered = hash_hmac("sha256", $password, $passwordPepper);
// $pwdHashed = password_hash($pwdPeppered, PASSWORD_DEFAULT); // PASSWORD_ARGON2ID);