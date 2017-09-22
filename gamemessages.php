<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';


// check if required variables are passed or not
if (!isset($_GET['session_id']) || !isset($_GET['players_ids']) || !isset($_GET['senders_userid']) || !isset($_GET['message'])) {
    echo 'you did not pass session_id or players_ids or senders_userid or message';
} else {

    // sanity check userpassed data
    $canproceed = TRUE;
    if (!is_numeric($_GET['session_id']) || is_null($_GET['players_ids']) || !is_numeric($_GET['senders_userid']) || is_null($_GET['message'])) {
        $canproceed = FALSE;
        echo '<status>0</status>';
    }


    if ($canproceed) {


        // grab all the variables
        $sessionid = $_GET['session_id'];
        $playersids = $_GET['players_ids'];
        $sendersuserid = $_GET['senders_userid'];
        $message1 = $_GET['message'];

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");

        // fetch name of person sent the message
        $name = fetchname($sendersuserid, $dbc);

        // fetch the game type 
        $gametype = fetchgametype($sessionid);

        // check if correct gametype is returned
        if ($gametype == "") {
            echo '<status>0</status>';
        } else {
            // put name before actual message
            $message = $name . '(' . $gametype . '): ' . $message1;

            // insert the message into latest messages table
            $setmessage = setlatestmessageid($sessionid, $sendersuserid, $message1, 0, $dbc);

            // check if row was inserted
            if (!$setmessage) {
                echo '<status>0</status>';
            } else {

                $handlerid = $setmessage;

                // for each user insert the row in messages table
                // form an array of all userids passed
                $useridsexploded = explode(',', $playersids);

                // make an array for push
                $pushtokenids = array();

                $insertedintomessages = FALSE;
                for ($i = 0; $i < count($useridsexploded); $i++) {
                    $thisuserid = $useridsexploded[$i];
                    $query = "insert into messages(message, message_type, user_id, handler_id) values ('$message', 3, $thisuserid, $handlerid)";
                    if (mysqli_query($dbc, $query)) {

                        $insertedintomessages = TRUE;
                        $devicetokenofreceiver = fetchdevicetoken($thisuserid, $dbc);
                        if (!is_null($devicetokenofreceiver)) {
                            // push this token in array
                            array_push($pushtokenids, $devicetokenofreceiver);
                        }
                    }
                }

                // if the rows were inserted into the table

                if ($insertedintomessages) {
                    if (count($pushtokenids) > 0) {
                        echo '<status>1</status>';

                        $passphrase = 'abcd';

                        $ctx = stream_context_create();
                        stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                        // Create the payload body
                        $body['aps'] = array(
                            'alert' => $message,
                            'sound' => '3'
                        );

                        // Encode the payload as JSON
                        $payload = json_encode($body);

                        for ($i = 0; $i < count($pushtokenids); $i++) {

                            // Open a connection to the APNS server
                            $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                            if (!$fp)
                                exit("Failed to connect: $err $errstr" . PHP_EOL);

                            // Build the binary notification
                            $msg = chr(0) . pack('n', 32) . pack('H*', $pushtokenids[$i]) . pack('n', strlen($payload)) . $payload;

                            // Send it to the server
                            fwrite($fp, $msg, strlen($msg));
                            // Close the connection to the server
                            fclose($fp);
                        }
                    }
                }
            }
        }
        mysqli_close($dbc);
    }
}
?>