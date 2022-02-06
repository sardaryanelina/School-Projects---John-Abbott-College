<?php
// include functions
require "functions.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project of "Developing Web Services"</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <ul>
        <!-- <li><a href="/ipd25/project/homepage" class="active">Home</a></li>-->
        <li><a href="index.php" class="active">Home</a></li>
        <!-- <li><a href="/ipd25/project/jokes">Jokes</a></li>-->
        <li><a href="joke.php">Jokes</a></li>
        <!-- <li><a href="/ipd25/project/quotes">Quotes</a></li>-->
        <li><a href="quoteoftheday.php">Quotes</a></li>
    </ul>

    <h2>Developing Web Services - project</h2>
    <h4 class="teamproject">Student: Elina Sardaryan
        <div>
            <?php
            $ip_add = $_SERVER['REMOTE_ADDR'];
            $base_url = "http://ipwhois.app/json/";
            // visit https://imard.ca/project/ 
            $current_url = $base_url .  $ip_add; // this is used on server
            $current_url = $base_url . "173.176.160.149"; // hardcoded ip address, as for localhost it is ::1
            $data = callAPI($current_url);
            $city = $data->city;
            echo "City: " . $city . "<br/>";

            $base_weather_url = "api.openweathermap.org/data/2.5/weather?";
            $current_weather_url = $base_weather_url . "q=$city&appid=c0f56b379be10e69cd0116f0fc4c1b41";
            $data_weather = callAPI($current_weather_url);
            $temperature = $data_weather->main->temp; // in kelvin, convert to celsius
            $feelslike = $data_weather->main->feels_like; // in kelvin, convert to celsius
            $tempmin = $data_weather->main->temp_min; // in kelvin, convert to celsius
            $tempmax = $data_weather->main->temp_max; // in kelvin, convert to celsius
            $pressure = $data_weather->main->pressure; // hecto Pascal  - hPa
            $humidity = $data_weather->main->humidity;

            echo  "Tempareture: " . ($temperature - 273.15) . "C<sup>o</sup><br/>Feels like: " . ($feelslike - 273.15) . "C<sup>o</sup><br/>Min temp: " . ($tempmin - 273.15) . "C<sup>o</sup><br/>Max temp: " . ($tempmax - 273.15) . "C<sup>o</sup><br/>Pressure: " . $pressure . " hPa<br/>Humidity: " . $humidity . "%";
            ?>
        </div>

        <div><a href="joke.php"><img class="jokes" src="images/jokes.jpg" alt="jokes"="joke.php"></a>
            <a href="quoteoftheday.php"><img class="quotes" src="images/quotes.jpg" alt="quotes" href="quote.php"></a>
        </div>
        <div>
            <button><a href="joke.php"> Go to Jokes </a></button>
            <button><a href="quoteoftheday.php"> Go to Quote </a></button>
        </div>

        <br /> <br /> <br />

</body>

</html>