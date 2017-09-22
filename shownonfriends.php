<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

$userid = $_GET['userid'];

if (is_numeric($userid)) {
    $friendsids = array();
    echo '<friends>';
    // grab a list of friends of this user
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");
    $query = "select request_to from friend_requests where request_from = $userid";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) != 0) {
        while ($row = mysqli_fetch_array($result)) {
            array_push($friendsids, $row[0]);
        }
    }
    $query2 = "select request_from from friend_requests where request_to = $userid";
    $result2 = mysqli_query($dbc, $query2);
    if (mysqli_num_rows($result2) != 0) {
        while ($row2 = mysqli_fetch_array($result2)) {
            array_push($friendsids, $row2[0]);
        }
    }
    array_push($friendsids, 0);
    
    $stringosuserids = "";
    for($i=0; $i<count($friendsids); $i++)
    {
        $stringosuserids .= $friendsids[$i] .',';
    }
    
    $stringosuseridsfinal = substr($stringosuserids, 0, strlen($stringosuserids)-1);
    
    $query3 = "select user_id from user_details where user_id not in ($stringosuseridsfinal)";
    
    //fetch details of these userids and print them.
    $result3 = mysqli_query($dbc, $query3);
    if(mysqli_num_rows($result3) != 0)
    {
        while($row3 = mysqli_fetch_array($result3))
        {
            $gotuserid = $row3['user_id'];
            $querymy2 = "SELECT name, email_id, status from user_details, current_login_status where current_login_status.user_id = user_details.user_id and user_details.user_id = $gotuserid";
            $resultmy2 = mysqli_query($dbc, $querymy2);
             if (mysqli_num_rows($resultmy2) != 0) {
                echo '<friend>';
                while ($rowmy2 = mysqli_fetch_array($resultmy2)) {
                    
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
    }
    echo '</friends>';
    mysqli_close($dbc);
}
?>

