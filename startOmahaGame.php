<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if (is_null($_GET['session_id']) || is_null($_GET['user_id']))
    echo 'Session_id or user_id was not passed';
else
{
    $sessionid = $_GET['session_id'];
    $userid = $_GET['user_id'];

    $dbc = mysqli_query(host, database, user, password)
            or die("Error connecting database");

    // check if all players have registered
    $query1 = "select * from omaha_bets where session_id = $sessionid";
    $result1 = mysqli_query($dbc, $query1);
    if (mysqli_num_rows($result1) == 6)
    {
        $query2 = "update omaha_bets set status = 1 where session_id = $sessionid and user_id = $userid";
        if (mysqli_query($dbc, $query2))
            echo '<status>1</status>';
        else
            echo '<status>2</status>';
    }
    else
    {
        echo '<status>0</status>';
    }
}
?>
