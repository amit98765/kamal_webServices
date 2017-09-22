<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

$userid = $_GET['userid'];

if (is_numeric($userid)) {
    echo '<games>';
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");
    $query = "select * from purchased_games where user_id = $userid";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) == 0) {
        echo '0';
        echo '</games>';
        mysqli_close($dbc);
    } else {
        while ($row = mysqli_fetch_array($result)) {
            echo '<game>';
            echo $row[2];
            echo '</game>';
        }
        echo '</game>';
        mysqli_close($dbc);
    }
}
?>
