<?php
header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

// chech whether all required parameters were passed.
if (!isset($_GET['message_id']) || !isset($_GET['message'])) {
    echo 'message_id or message was not passed.';
    exit(0);
}

$dbc = mysqli_connect(host, user, password, database)
        or die("Error connecting database");


$messageid = (int) $_GET['message_id'];
$message1 = mysqli_real_escape_string($dbc, $_GET['message']);


// find the associated users
// fetch message type from the message id
$query = "select * from messages where id = $messageid";
$result = mysqli_query($dbc, $query);
if (mysqli_num_rows($result) == 1) {

    // if row was found, grab the messagtype
    $messagetype = NULL;
    $handlerid = NULL;
    $messagefrom = NULL;

    while ($row = mysqli_fetch_array($result)) {
        $messagetype = $row['message_type'];
        $handlerid = $row['handler_id'];
        $messagefrom = $row['user_id'];
    }

    if ($messagetype == 1) {
        $query2 = "select invitation_from from invitations where id = $handlerid";
        $result2 = mysqli_query($dbc, $query2);

        // if a row was returned
        if (mysqli_num_rows($result2) == 1) {
            $messageto = NULL;
            while ($row2 = mysqli_fetch_array($result2)) {
                $messageto = (int) $row2[0];
            }
// make an entry in the database now
            // need name of message sender, and devictoken of message receiver
            $query5 = "select name from user_details where user_id = $messagefrom";
            $result5 = mysqli_query($dbc, $query5);
            $nameinpush = NULL;
            if (mysqli_num_rows($result5) == 1) {
                while ($row5 = mysqli_fetch_array($result5)) {
                    $nameinpush = $row5[0];
                }
            } else {
                $nameinpush = "A friend : ";
            }
            $message = $nameinpush . ': ' . $message1;
            $query3 = "insert into messages(message, message_type, handler_id, user_id) values ('$message', 2, $messagefrom, $messageto)";
            mysqli_query($dbc, $query3);
            if (mysqli_affected_rows($dbc) == 1) {

                // invoke a push here 
                // name is found, also find the active devicetoken of the other user
                // check whether requested person is online or not
                $query6 = "select status, device_token from current_login_status where user_id = $messageto";
                $result6 = mysqli_query($dbc, $query6);

                $receiverisonline = FALSE;
                $devicetokenofreceiver = NULL;

                if (mysqli_num_rows($result6) != 0) {
                    while ($row6 = mysqli_fetch_array($result6)) {
                        if ($row6[0] == 1) {
                            $receiverisonline = TRUE;
                            $devicetokenofreceiver = $row6[1];
                            break;
                        }
                    }
                }

                // if receiveer is online, get current device token, otherwise main device token

                if (is_null($devicetokenofreceiver)) {
                    $query7 = "select devicetoken from user_details where user_id = $messageto";
                    $result7 = mysqli_query($dbc, $query7);
                    if (mysqli_num_rows($result7) != 0) {
                        while ($row7 = mysqli_fetch_array($result7)) {
                            $devicetokenofreceiver = $row7[0];
                        }
                    }
                }
                sendpush($devicetokenofreceiver, $nameinpush, $message);
                echo '<status>1</status>';
            } else {
                echo '<status>0</status>';
            }
        }
    } elseif ($messagetype == 2) {
        $messageto = $handlerid;
        // need name of message sender, and devictoken of message receiver
        $query5 = "select name from user_details where user_id = $messagefrom";
        $result5 = mysqli_query($dbc, $query5);
        $nameinpush = NULL;
        if (mysqli_num_rows($result5) == 1) {
            while ($row5 = mysqli_fetch_array($result5)) {
                $nameinpush = $row5[0];
            }
        } else {
            $nameinpush = "A friend : ";
        }

        $message = $nameinpush . ': ' . $message1;
        $query3 = "insert into messages(message, message_type, handler_id, user_id) values ('$message', 2, $messagefrom, $messageto)";
        mysqli_query($dbc, $query3);
        if (mysqli_affected_rows($dbc) == 1) {

            // invoke a push here 
            // need name of message sender, and devictoken of message receiver
            $query5 = "select name from user_details where user_id = $messagefrom";
            $result5 = mysqli_query($dbc, $query5);
            $nameinpush = NULL;
            if (mysqli_num_rows($result5) == 1) {
                while ($row5 = mysqli_fetch_array($result5)) {
                    $nameinpush = $row5[0];
                }
            } else {
                $nameinpush = "A friend : ";
            }

            // name is found, also find the active devicetoken of the other user
            // check whether requested person is online or not
            $query6 = "select status, device_token from current_login_status where user_id = $messageto";
            $result6 = mysqli_query($dbc, $query6);

            $receiverisonline = FALSE;
            $devicetokenofreceiver = NULL;

            if (mysqli_num_rows($result6) != 0) {
                while ($row6 = mysqli_fetch_array($result6)) {
                    if ($row6[0] == 1) {
                        $receiverisonline = TRUE;
                        $devicetokenofreceiver = $row6[1];
                        break;
                    }
                }
            }

            // if receiveer is online, get current device token, otherwise main device token

            if (is_null($devicetokenofreceiver)) {
                $query7 = "select devicetoken from user_details where user_id = $messageto";
                $result7 = mysqli_query($dbc, $query7);
                if (mysqli_num_rows($result7) != 0) {
                    while ($row7 = mysqli_fetch_array($result7)) {
                        $devicetokenofreceiver = $row7[0];
                    }
                }
            }
            sendpush($devicetokenofreceiver, $nameinpush, $message);
            echo '<status>1</status>';
        } else {
            echo '<status>0</status>';
        }
    }
}

function sendpush($devicetokenofreceiver, $nameofsender, $message) {

// Put your device token here (without spaces):
    $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

// Actual $deviceToken = $devicetokenofthereceiver;
// Put your private key's passphrase here:
    $passphrase = 'abcd';

// Put your alert message here:
    $message = $message;

////////////////////////////////////////////////////////////////////////////////

    $ctx = stream_context_create();
    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

    if (!$fp)
        exit("Failed to connect: $err $errstr" . PHP_EOL);


// Create the payload body
    $body['aps'] = array(
        'alert' => $message,
        'sound' => '1'
    );

// Encode the payload as JSON
    $payload = json_encode($body);

// Build the binary notification
    $msg = chr(0) . pack('n', 32) . pack('H*', $devicetokenofreceiver) . pack('n', strlen($payload)) . $payload;

// Send it to the server
    $result = fwrite($fp, $msg, strlen($msg));

// Close the connection to the server
    fclose($fp);
}
?>