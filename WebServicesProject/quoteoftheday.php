<?php
// include functions
require "functions.php";
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Quote with Image</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="joke.php">Jokes</a></li>
        <li><a href="quoteoftheday.php" class="active">Quotes</a></li>
    </ul>
    <h2>Quote of the day</h2>
    <h1>
        <?php
        $base_url = "https://zenquotes.io/api";
        $current_url = $base_url . "/random";
        $data = null;
        while (!is_array($data)) {
            $data = callAPI($current_url);
        }

        // var_dump($data);
        echo ($data[0]->q) . "<br/><br/>";
        ?>
    </h1>
    <div class="imageContainer">
        <a id="imageLink" href="#">
            <img alt="" id="unsplashImage" />
        </a>
    </div>
    <div>
        <button>
            <a href="index.php">
                << Homepage</a>
        </button>
        <button><a href="quoteoftheday.php">Next quote >> </a></button>
    </div>
    <p class="imageDetails">Photo from <a href="https://www.unsplash.com">Unsplash</a> by <a id="creator" href="#">NAME</a></p>

    <script>
        let clientID = "O7H9DEJCIYsvYdK9dLCPW_CxsUaxc7k56pFJBXlmIAc";
        let endpoint = `https://api.unsplash.com/photos/random/?client_id=${clientID}`;

        let imageElement = document.querySelector("#unsplashImage");
        let imageLink = document.querySelector("#imageLink");
        let creator = document.querySelector("#creator");

        fetch(endpoint)
            .then(function(response) {
                // console.log(response.json());
                return response.json();
            }) // .then((response) => response.json()) // simplified version
            .then(function(jsonData) {
                // console.log(jsonData);
                imageElement.src = jsonData.urls.regular;
                imageLink.setAttribute("href", jsonData.links.html);
                creator.innerText = jsonData.user.name;
                creator.setAttribute("href", jsonData.user.portfolio_url);
            })
            .catch(function(error) {
                console.log("Error: " + error);
            });
    </script>
</body>

</html>