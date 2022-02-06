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
    <title>Programming jokes</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="joke.php" class="active">Jokes</a></li>
        <li><a href="quoteoftheday.php">Quotes</a></li>
    </ul>
    <h2>Programming jokes</h2>

    <h1>Joke of the day</h1>
    <div>
        <?php
        $base_url = "https://v2.jokeapi.dev/joke/Programming";
        $current_url = $base_url . "?type=single";
        $data = callAPI($current_url);
        echo $data->joke . "<br/><br/>";
        ?>
    </div>

    <p>~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~</p>
    <h1>Dialogue between programmers</h1>

    <div>
        <?php
        $base_url_twopart = "https://v2.jokeapi.dev/joke/Programming";
        $current_url = $base_url_twopart . "?type=twopart";
        $data = callAPI($current_url);
        echo "- " . $data->setup . "<br/><br/>- " . $data->delivery . "<br/><br/>";
        ?>
    </div>

    <p>~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~</p>
    <h1>Joke by a russian programmer</h1>
    <div>
        <?php
        $base_url = "https://v2.jokeapi.dev/joke/Programming?type=single";
        $base_url_translate = "https://api.funtranslations.com/translate/";
        $api_url = $base_url_translate . "russian-accent";
        $traslate_data = callAPI($base_url);
        $post_data = "text=$traslate_data->joke";
        //$post_data = "text=Welcome to our website!";

        $data = callPostAPI($api_url, $post_data);

        if (isset($data->success)) {
            echo $data->contents->translated;
        } else {
            echo "An error has occured!" . $data->error->message;
        }
        ?>
    </div>
    <div>
        <?php
        $base_url_img = "https://api.thecatapi.com/v1/images/search";
        ?>

        <img src="" />
    </div>
    <div>
        <button>
            <a href="index.php">
                << Homepage</a>
        </button>
        <button><a href="joke.php">Next joke >> </a></button>
    </div>
    <script>
    </script>
</body>

</html>

<!-- The six stages of debugging: 1. That can't happen. 2. That doesn't happen on my machine. 3. That shouldn't happen. 4. Why does that happen? 5. Oh, I see. 6. How did that ever work?
Number of items: If Bill Gates had a dime for every time Windows crashed ... Oh wait, he does.-->