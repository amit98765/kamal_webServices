<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

$userid = $_GET['userid'];

if (is_numeric($userid)) {
    echo '<friends>';
    // grab a list of friends of this user
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    $query2 = "select request_from from friend_requests where request_to = $userid and status=0";
    $result2 = mysqli_query($dbc, $query2);
    if (mysqli_num_rows($result2) != 0) {
        while ($row2 = mysqli_fetch_array($result2)) {

            //form another query to fetch details of friends
            $querymy2 = "SELECT name, email_id, status from user_details, current_login_status where current_login_status.user_id = user_details.user_id and user_details.user_id = $row2[0]";
            $resultmy2 = mysqli_query($dbc, $querymy2);
            if (mysqli_num_rows($resultmy2) != 0) {
                while ($rowmy2 = mysqli_fetch_array($resultmy2)) {
                    echo '<friend>';
                    echo '<userid>';
                    echo $row2[0];
                    echo '</userid>';
                    echo '<name>';
                    echo $rowmy2[0];
                    echo '</name>';
                    echo '<emailid>';
                    echo $rowmy2[1];
                    echo '</emailid>';
                    echo '<status>';
                    echo $rowmy2[2];
                    echo '</status>';
                    echo '<iconname>';
                    $querymy4 = "select icon_name from user_icon where user_id = $row2[0]";
                    $resultmy4 = mysqli_query($dbc, $querymy4);
                    if (mysqli_num_rows($resultmy4) == 0) {
                        echo 'Dice';
                    } else {
                        while ($rowmy4 = mysqli_fetch_array($resultmy4)) {
                            echo $rowmy4[0];
                        }
                    }
                    echo '</iconname>';
                    echo '</friend>';
                }
            } else {
                echo 'data missing';
            }
        }
        
        // set status of friend requests as read
        $query5 = "update friend_requests set read_status = 1 where request_to = $userid";
        mysqli_query($dbc, $query5);
    }
    echo '</friends>';
    mysqli_close($dbc);
} else {
    echo '<friend>0</friend>';
}
?>
