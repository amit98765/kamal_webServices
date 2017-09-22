<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

// chech whether all required parameters were passed.
if (!isset($_GET['message_id'])) {
    echo 'message_id was not passed.';
    exit(0);
}

$dbc = mysqli_connect(host, user, password, database)
        or die("Errior connecting database");

$messageid = $_GET['message_id'];
//$message = mysqli_real_escape_string($dbc, $_GET['message']);
// find the associated users
// fetch message type from the message id
$query = "select * from messages where id = $messageid";
$result = mysqli_query($dbc, $query);


if (mysqli_num_rows($result) == 1) {
    $handlerid = NULL;
    //  $messagefrom = NULL;
// if row was found, grab the messagtype
    while ($row = mysqli_fetch_array($result)) {
        $handlerid = $row['handler_id'];
        //$messagefrom = $row['user_id'];
    }


    $query5 = "select invitation_from, session_id from invitations where id = $handlerid";
    $result5 = mysqli_query($dbc, $query5);

    if (mysqli_num_rows($result5) == 1) {
// check if the session id exists
        $invitationfrom = NULL;
        $gotsessionid = NULL;

        while ($row5 = mysqli_fetch_array($result5)) {
            $gotsessionid = $row5[1];
            $invitationfrom = $row5[0];
        }

        // check if session exists in main table
        $query6 = "select * from table_gamesessions where session_id = $gotsessionid";
        $result6 = mysqli_query($dbc, $query6);

        if (mysqli_num_rows($result6) == 1) {
            // check if the user message_from is active for the current game
            $query7 = "select * from games_players where user_id = $invitationfrom and session_id = $gotsessionid";
            $result7 = mysqli_query($dbc, $query7);

            if (mysqli_num_rows($result7) == 1) {
                // user exists
                echo '<status>1</status>';
            } else {
                // check if there are some other active users for this game
                $query8 = "select * from games_players where session_id = $gotsessionid";
                $result8 = mysqli_query($dbc, $query8);

                if (mysqli_num_rows($result8) > 0) {
                    // other players available
                    echo '<status>2</status>';
                } else {
                    // game does not exist anymore
                    echo '<status>3</status>';

                    // delete the message and invitation
                    $query9 = "delete from invitations where id = $handlerid";
                    mysqli_query($dbc, $query9);

                    $query10 = "delete from messages where id = $messageid";
                    mysqli_query($dbc, $query10);
                }
            }
        } else {
            // game does not exist
            echo '<status>3</status>';

            // delete the message and invitation
            $query9 = "delete from invitations where id = $handlerid";
            mysqli_query($dbc, $query9);

            $query10 = "delete from messages where id = $messageid";
            mysqli_query($dbc, $query10);
        }
    } else {

        // game does not exist
        echo '<status>3</status>';

        // delete the message 

        $query10 = "delete from messages where id = $messageid";
        mysqli_query($dbc, $query10);
        
    }
} else {
    echo '<status>0</status>';
}
mysqli_close($dbc);
?>