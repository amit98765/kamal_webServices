<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if (is_null($_GET['user_id']) || is_null($_GET['count']))
{
    echo '<status>0</status>';
}
else
{
    $userid = $_GET['user_id'];
    $count = $_GET['count'];

    // insert the data into the gift box table
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");
    $query = "update user_cash set jeffrey = 5 where user_id = $userid";
    if (mysqli_query($dbc, $query))
    {
        $message = "Bought 5 Jeffrey for $1";
        insertIntoFeed($userid, $message, $dbc);
        echo '<status>1</status>';
    }
    else
        echo '<status>0</status>';

    mysqli_close($dbc);
}
?>
