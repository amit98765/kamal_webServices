<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

if (!isset($_GET['user_id']) || !isset($_GET['facebook_id']))
{
    echo '<status>0</status>';
}
else
{
    $userid = $_GET['user_id'];
    $facebookid = $_GET['facebook_id'];

    if (is_null($userid) || is_null($facebookid))
    {
        echo '<status>0</status>';
    }
    else
    {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");

        $query = "update user_details set facebook_id = '$facebookid' where user_id = $userid";
        if (mysqli_query($dbc, $query))
            echo '<status>1</status>';
        else
            echo '<status>0</status>';
        mysqli_close($dbc);
    }
}
?>
