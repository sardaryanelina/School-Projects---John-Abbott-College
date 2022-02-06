<?php

// make a CURL command and return json decoded object
function callAPI($url)
{
    // init my fetch URL command
    $ch = curl_init($url);

    // prevent screen output and save to variable
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // MAMP issues
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    // execute our fetch command and save to variable
    $results = curl_exec($ch);

    // close curl
    curl_close($ch);

    // convert my json string into an object
    $data = json_decode($results);
    // print_r($data);
    return $data;
}

function callPostAPI($url, $fields)
{
    $ch = curl_init($url); // initialize curl request

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // no output to screen
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // MAMP issues
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // MAMP issues

    // POST METHOD REQUIREMENTS!!
    // set HTTP request method to POST
    curl_setopt($ch, CURLOPT_POST, true);
    // set my POST fields
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $results = curl_exec($ch); // execute curl request

    curl_close($ch); // close curl request

    $data = json_decode($results);

    return $data;
}
