<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

echo '<result>';
// grab parameters passed with post

$emailid  = strtolower($_GET['email_id']);
$name     = $_GET['name'];
$password = $_GET['password'];
$tokenid  = $_GET['devicetoken'];

$dbc = mysqli_connect(host, user, password, database)
        or die('ERROR CONNECTING DATABASE');

$query = "insert into user_details(name, email_id, password, devicetoken)values('$name', '$emailid', '$password', '$tokenid')";
mysqli_query($dbc, $query);

if (mysqli_affected_rows($dbc) == 1)
{
    $gotuserid = mysqli_insert_id($dbc);

    $query4 = "insert into user_icon(user_id, icon_name) values ($gotuserid, 'Dice')";
    mysqli_query($dbc, $query4);

    echo '1-' . $gotuserid;
    
    // sign up was successful
    // so make a entry for this user in the login table
    $querylast = "insert into current_login_status(user_id, status, device_token) values($gotuserid, '0', '$tokenid')";

    mysqli_query($dbc, $querylast);

    // give user a Dice Icon as His Purchased Icon.
    $querylast1 = "insert into purchased_icons(user_id, icon_name) values ($gotuserid, 'Dice') ";

    mysqli_query($dbc, $querylast1);
}
else
{
    echo '0';
}
mysqli_close($dbc);

echo '</result>';
?>
