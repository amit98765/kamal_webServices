<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

if (!isset($_GET['user_id']) || !isset($_GET['amount']) || !is_numeric($_GET['amount']))
{
    echo '<status>0</status>';
}
else
{
    $userid = $_GET['user_id'];
    $amount = $_GET['amount'];
    
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");
    
    $query = "update user_cash set chips = chips + $amount where user_id = $userid";
    mysqli_query($dbc, $query);
    
    if(mysqli_affected_rows($dbc) > 0)
    {
        echo '<status>1<status>';
    }
    else
    {
        echo '<status>0</status>';
    }
    mysqli_close($dbc);
}
?>