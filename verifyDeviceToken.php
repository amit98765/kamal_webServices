<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

$userid = $_GET['user_id'];
$devicetoken = $_GET['devicetoken'];

if (is_numeric($userid) && !is_null($devicetoken)) {
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error conneccting database");

    $query = "update current_login_status set device_token = '$devicetoken' where user_id = $userid";
    mysqli_query($dbc, $query);

    $query1 = "select devicetoken from user_details where user_id = $userid";
    $result1 = mysqli_query($dbc, $query1);

    if (mysqli_num_rows($result1) > 0) {
        $row1 = mysqli_fetch_row($result1);
        $devtoken = $row1[0];
        if ($devtoken == 0) {
            $query2 = "update user_details set devicetoken = '$devicetoken' where user_id = $userid";
            mysqli_query($dbc, $query2);
            echo '<status>0</status>';
        } elseif ($devtoken == $devicetoken) {
            echo '<status>0</status>';
        } else {
            echo '<status>1</status>';
        }
    }
}
?>
