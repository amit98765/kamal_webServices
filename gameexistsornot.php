<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

// check if variables are passed or not
if (!isset($_GET['user_id']) || !isset($_GET['game_type'])) {
    exit('game_type or user_id was not passed');
}

// grab user passed variables
//$handlerid = $_GET['handler_id'];
$userid = (int) $_GET['user_id'];
$game_type = trim($_GET['game_type']);

$dbc = mysqli_connect(host, user, password, database)
        or die("Error connecting database");

// fetch all session ids corresponding to game_type
$query = "select session_id from table_gamesessions where game_type = '$game_type'";
$result = mysqli_query($dbc, $query);

if (mysqli_num_rows($result) == 0) {
    // there is some error 
    echo '<result><status>0</status></result>';
} else {

    $userisactive = FALSE;

    // fetch all session ids one by one
    while ($row = mysqli_fetch_array($result)) {
        $sessionid = $row[0];

        // now check if this user is active for any game or not
        $query2 = "select * from games_players where session_id = $sessionid and user_id = $userid";
        $result2 = mysqli_query($dbc, $query2);
        if (mysqli_num_rows($result2) != 0) {

            // there was no active user, so give status 1
            echo '<result><status>2</status><session_id>' . $sessionid . '</session_id></result>';
            $userisactive = TRUE;
            break;
        }
    }

    // if user is not active 
    if (!$userisactive) {

        // find chips of this user
        $chips = getchips($userid);
        echo '<result><status>1</status><chips>' . $chips . '</chips></result>';
    }
}

mysqli_close($dbc);
?>