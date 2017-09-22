<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

$useridofrequestsender = $_GET['userid1'];
$useridofrequestreceiver = $_GET['userid2'];

if (!is_numeric($useridofrequestreceiver) || !is_numeric($useridofrequestsender)) {
    echo '<status>0</status>';
} else {
    //get the user id of request sender
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    $query2 = "delete from friend_requests where request_from = $useridofrequestsender and request_to = $useridofrequestreceiver";

    mysqli_query($dbc, $query2);
    if (mysqli_affected_rows($dbc) == 1) {
        echo '<status>1</status>';
    } else {
	$query3 = "delete from friend_requests where request_from=$useridofrequestreceiver and request_to = $useridofrequestsender";
	mysqli_query($dbc, $query3);
        if (mysqli_affected_rows($dbc) == 1) {
        echo '<status>1</status>';
        }
	else {
	echo '<status>1</status>';
	}
    }

    mysqli_close($dbc);
}
?>