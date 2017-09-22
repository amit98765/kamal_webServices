<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

$userid = $_GET['userid'];

if (!is_numeric($userid)) {
    echo '<status>0</status>';
} else {
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting DataBase");
    $query = "update friend_requests set read_status = 1 where request_to = $userid";
    mysqli_query($dbc, $query);
    echo '<status>1</status>';
    mysqli_close($dbc);
}
?>
