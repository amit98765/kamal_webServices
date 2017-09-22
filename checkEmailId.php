<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

if (!isset($_GET['email_id']))
{
    echo 'Email_id was not passed';
}
else
{
    $dbc = mysqli_connect(host, user, password, database)
            or die('ERROR CONNECTING DATABASE');

    $emailid = $_GET['email_id'];
    $query   = "select * from user_details where email_id = '$emailid'";
    $result  = mysqli_query($dbc, $query);

    if (mysqli_num_rows($result) > 0)
    {
        echo '<status>2</status>';
    }
    else
    {
        echo '<status>1</status>';
    }
    mysqli_close($dbc);
}
?>
