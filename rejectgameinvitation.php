<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';


// grab user passed variables
//$handlerid = $_GET['handler_id'];
$userid = $_GET['user_id'];
$messageid = $_GET['message_id'];

//sanity check user passed variables
if (!is_numeric($userid) || !is_numeric($messageid)) {

    // both expected to be numeric. If they were not, say Unsuccessful
    echo '<status>0</status>';
} else {
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    // fetch handler id of this message id
    $querypre = "select handler_id from messages where id = $messageid";
    $resultpre = mysqli_query($dbc, $querypre);
    if (mysqli_num_rows($resultpre) > 0) {

        $handlerid = NULL;
        while ($rowpre = mysqli_fetch_array($resultpre)) {
            $handlerid = $rowpre[0];
        }

        // before doing anything grab the data for sending push
        $query2 = "select invitation_from, invitation_to, session_id from invitations where id = $handlerid";
        $result2 = mysqli_query($dbc, $query2);
        if (mysqli_num_rows($result2) > 0) {
            $invitation_from = "";
            $invitation_to = "";
            $session = 0;
            while ($row2 = mysqli_fetch_array($result2)) {
                $invitation_from = $row2[0];
                $invitation_to = $row2[1];
                $session = $row2[2];
            }

            // fetch device token of invitation_from and name of request_to
            // grab devicetoken of this user
            $query4 = "select status, device_token from current_login_status where user_id = $invitation_from";
            $result4 = mysqli_query($dbc, $query4);

            $receiverisonline = FALSE;
            $devicetokenofreceiver = NULL;

            if (mysqli_num_rows($result4) != 0) {
                while ($row4 = mysqli_fetch_array($result4)) {
                    if ($row4[0] == 1) {
                        $receiverisonline = TRUE;
                        $devicetokenofreceiver = $row4[1];
                        break;
                    }
                }
            }

            // if receiveer is online, get current device token, otherwise main device token

            if (is_null($devicetokenofreceiver)) {
                $query5 = "select devicetoken from user_details where user_id = $invitation_from";
                $result5 = mysqli_query($dbc, $query5);
                if (mysqli_num_rows($result5) != 0) {
                    while ($row5 = mysqli_fetch_array($result5)) {
                        $devicetokenofreceiver = $row5[0];
                    }
                } else {
                    exit("An unexpected error occured. Please retry");
                }
            }


            // select name of invitation_to
            $query6 = "select name from user_details where user_id = $invitation_to";
            $result6 = mysqli_query($dbc, $query6);
            $nameofinvited = "A friend";
            if (mysqli_num_rows($result6) > 0) {
                while ($row6 = mysqli_fetch_array($result6)) {
                    $nameofinvited = $row6[0];
                }
            } else {
                echo 'nAME not found. This case was never assumed to happen';
            }
            // write query to update status of invitation
            $query = "delete from invitations where id = $handlerid";
            mysqli_query($dbc, $query);
            if (mysqli_affected_rows($dbc) != 0) {

                //also delete that row from the notifications
                $query2 = "delete from messages where user_id = $userid and id = $messageid";
                mysqli_query($dbc, $query2);
                echo '<status>1</status>';
            } else {

                // says unsuccessful, if no row was affected.
                echo '<status>0</status>';
            }
            // now send the push
            sendpush($nameofinvited, $session);
        } else {
            echo '<status>0</status>';
        }

        mysqli_close($dbc);
    } else {
        echo '<status>0</status>';
    }
}

// function sendpush here 
function sendpush($name, $sessionid) {


    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");


    $gametype = fetchgametype($sessionid);
    $pushids = array();

    $query1 = "select user_id from games_players where session_id = $sessionid";
    $result1 = mysqli_query($dbc, $query1);

    if (mysqli_num_rows($result1) > 0) {
        while ($row1 = mysqli_fetch_array($result1)) {
            array_push($pushids, $row1[0]);
        }
    }

// Actual $deviceToken = $devicetokenofthereceiver;
// Put your private key's passphrase here:
    $passphrase = 'abcd';

// Put your alert message here:
    // $message = $nameofinvitor . ' has invited you for ' . $thisgametype;
////////////////////////////////////////////////////////////////////////////////

    $ctx = stream_context_create();
    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

    // Create the payload body
    $message = $name . ' has rejected to play ' . $gametype;

    $body['aps'] = array(
        'alert' => $message,
        'sound' => '4'
    );

// Encode the payload as JSON
    $payload = json_encode($body);

    for ($i = 0; $i < count($pushids); $i++) {

// Open a connection to the APNS server
        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

// Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', fetchdevicetoken($pushids[$i])) . pack('n', strlen($payload)) . $payload;

        echo '-->'.fetchdevicetoken($pushids[$i]).'<--';
// Send it to the server
        fwrite($fp, $msg, strlen($msg));

// Close the connection to the server
        fclose($fp);
    }
    mysqli_close($dbc);
}

?>