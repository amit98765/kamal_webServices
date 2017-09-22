<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

$userid = $_GET['user_id'];

$dbc = mysqli_connect(host, user, password, database)
        or die("Error connecting to database");

$query = "delete from messages where user_id = $userid";

if (mysqli_query($dbc, $query)) {
    echo '<status>1</status>';
} else {
    echo '<status>0</status>';
}
?>