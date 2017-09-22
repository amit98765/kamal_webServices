<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if (!isset($_GET['user_id']) || !isset($_GET['handler_id']) || !isset($_GET['message']))
{
    echo 'user_id or handler_id or message was not passed';
}
else
{
    $dbc = mysqli_connect(host, user, password, database)
            or die('Error connecting database');

    $userid    = $_GET['user_id'];
    $handlerid = $_GET['handler_id'];
    $message   = ucfirst(fetchname($handlerid, $dbc)) . ': ' . $_GET['message'];

    $query = "insert into messages(message, message_type, handler_id, user_id) values('$message', 2, $handlerid, $userid)";
    if (mysqli_query($dbc, $query))
    {
        echo '<status>1</status>';

        $pushids = array($userid);
        sendpushtoplayers($pushids, $message, $dbc, 1);
    }
    else
    {
        echo '<status>0</status>';
    }
    mysqli_close($dbc);
}
?>
