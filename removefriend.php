<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
echo '<status>';

$requestfrom = $_GET['user1'];
$requestto = $_GET['user2'];

if (!is_numeric($requestto) || !is_numeric($requestfrom)) {
    echo '0';
    echo '</status>';
} else {
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");
    $query = "select * from friend_requests where request_from = $requestfrom and request_to = $requestto";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) == 1) {
        $query2 = "delete from friend_requests where request_from = $requestfrom and request_to = $requestto";
        mysqli_query($dbc, $query2);
        if (mysqli_affected_rows($dbc) == 1) {
            echo '1';
            echo '</status>';
            mysqli_close($dbc);
        } else {
            echo '0';
            echo '</status>';
            mysqli_close($dbc);
        }
    } else {
        $query3 = "select * from friend_requests where request_to = $requestfrom and request_from = $requestto";
        $result3 = mysqli_query($dbc, $query3);
        if (mysqli_num_rows($result3) == 1) {
            $query4 = "delete from friend_requests where request_to = $requestfrom and request_from = $requestto";
            mysqli_query($dbc, $query4);

            if (mysqli_affected_rows($dbc) == 1) {
                echo '1';
                echo '</status>';
                mysqli_close($dbc);
            } else {
                echo '0';
                echo '</status>';
                mysqli_close($dbc);
            }
        } else {
            echo '1';
            echo '</status>';
            mysqli_close($dbc);
        }
    }
}
?>
