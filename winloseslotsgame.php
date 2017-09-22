<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

// check if required variables were passed
if (!isset($_GET['session_id']) || !isset($_GET['user_id']) || !isset($_GET['combination'])
        || !isset($_GET['table_type']) || !isset($_GET['case'])) {

    echo 'You did not pass all variables';
} else {

    setTimeZone();

    // fetch all the variables
    $userid = $_GET['user_id'];
    $sessionid = $_GET['session_id'];
    $combination = (int) $_GET['combination'];
    $tabletype = (int) $_GET['table_type'];
    $case = (int) $_GET['case'];


    // check if the status is win or lose

    if (($case != '1') && ($case != '2') && ($case != '3') && ($case != '4') && ($case != '5') && ($case != '6') && ($case != '7') && ($case != '8') && ($case != '9') && ($case != '10')) {
        echo '<status>0</status>';
    } else {
        // prepare an array for sending push
        $pushids = array();

        //push winners userid in this array
        // array_push($pushids, $userid);
        // find how many chips to increase or decrease corresponding to the data provided
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");

        $query = "select case$case from slots_static_data where combination = $combination and table_type = $tabletype";
        $result = mysqli_query($dbc, $query);

        if (mysqli_num_rows($result) != 0) {
            $chipstoincreasedecrease = NULL;
            while ($row = mysqli_fetch_array($result)) {
                $chipstoincreasedecrease = $row[0];
            }

            // set variable status
            if ($combination != 10) {
                $status = 1;
                publishDailyWinnings($userid, $chipstoincreasedecrease, 'chips', $dbc);
            } else {
                $status = 0;
                publishDailyWinnings($userid, -$chipstoincreasedecrease, 'chips', $dbc);
            }


            // now increase or decrease the chips of this user
            $doneornot = increasedecreasechips($userid, $chipstoincreasedecrease, $status, $dbc);


            // check if chips were decreased
            if ($doneornot) {

                // ANYTHING HAPPENS afterwards, it should return successful

                if ($combination != 10) {
                    echo '<status>1</status>';
                } else {
                    echo '<status>2</status>';
                }

                //also set wins for this user 
                $query4 = "select wins from games_players where user_id = $userid and session_id = $sessionid";
                $result4 = mysqli_query($dbc, $query4);

                if (mysqli_num_rows($result4) != 0) {
                    $olderwins = 0;
                    while ($row4 = mysqli_fetch_array($result4)) {
                        $olderwins = $row4[0];
                    }

                    $newwins = 0;
                    if ($combination != 10) {
                        $newwins = $olderwins + $chipstoincreasedecrease;
                    } else {
                        $newwins = $olderwins - $chipstoincreasedecrease;
                    }

                    // set these as wins for this user
                    $query5 = "update games_players set wins = $newwins where session_id = $sessionid and user_id = $userid";
                    mysqli_query($dbc, $query5);
                }

                $newdate = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+5 seconds'));

                // prepare message for latest message table
                if ($combination != 10) {
                    $message = '+ ' . $chipstoincreasedecrease;
                    $message1 = "Won " . $chipstoincreasedecrease . ' in Slots';
                    insertIntoFeed($userid, $message1, $dbc);

                    $queryc1 = "select * from slots_confetti where session_id = $sessionid and user_id = $userid";
                    $resultc1 = mysqli_query($dbc, $queryc1);

                    if (mysqli_num_rows($resultc1) > 0) {
                        $queryc2 = "update slots_confetti set time = '$newdate', status = 1 where session_id = $sessionid and user_id = $userid";
                        mysqli_query($dbc, $queryc2);
                    } else {
                        $queryc3 = "insert into slots_confetti( user_id, status, time, session_id) values ( $userid, 1, '$newdate', $sessionid)";
                        mysqli_query($dbc, $queryc3);
                    }
                } else {
                    $message = '- ' . $chipstoincreasedecrease;
                }

                // set a latest message of this user
                setlatestmessage($sessionid, $userid, $message, 1);

                // find all the active player of this game
                $query2 = "select user_id from games_players where session_id = $sessionid and user_id not in ($userid)";
                $result2 = mysqli_query($dbc, $query2);

                if (mysqli_num_rows($result2) > 0) {
                    // form a message to be stored in the database
                    $nameofwinner = fetchname($userid);

                    $messagewinorlose = "";
                    if ($combination != 10) {
                        $messagewinorlose = 'Won';
                    } else {
                        $messagewinorlose = 'lost';
                    }



                    $gametype = fetchgametype($sessionid);

                    $message = $messagewinorlose . ' ' . $chipstoincreasedecrease . ' chips in ' . $gametype . ' game.';

                    insertIntoFeed($userid, $message, $dbc);

                    // fetch handler id of the row returned
                    $query3 = "select id from game_messages where session_id = $sessionid and user_id = $userid order by datetime desc limit 1";
                    $result3 = mysqli_query($dbc, $query3);

                    $handlerid = "";
                    if (mysqli_num_rows($result3) > 0) {
                        while ($row3 = mysqli_fetch_array($result3)) {
                            $handlerid = $row3[0];
                        }
                    }
                    // set a messaage for these users in the messages table
                    while ($row2 = mysqli_fetch_array($result2)) {
                        setmessage($message, $row2[0], $handlerid);
                        array_push($pushids, $row2[0]);
                    }
                }

                // send a push here 
                // send the push now 
                if (count($pushids) > 0) {

                    // Put your device token here (without spaces):
                    $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

                    // Actual $deviceToken = $devicetokenofthereceiver;
                    // Put your private key's passphrase here:
                    $passphrase = 'abcd';

                    $nameofwinner = fetchname($userid);

                    $messagewinorlose = "";
                    if ($combination != 10) {
                        $messagewinorlose = 'won';
                    } else {
                        $messagewinorlose = 'lost';
                    }

                    $gametype = fetchgametype($sessionid);

                    $message = $nameofwinner . ' ' . $messagewinorlose . ' ' . $chipstoincreasedecrease . ' chips in ' . $gametype . ' game.';



                    $ctx = stream_context_create();
                    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                    // Open a connection to the APNS server
                    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                    if (!$fp)
                        exit("Failed to connect: $err $errstr" . PHP_EOL);

                    for ($i = 0; $i < count($pushids); $i++) {
                        // Create the payload body
                        $body['aps'] = array(
                            'alert' => $message,
                            'sound' => '3'
                        );


                        // Encode the payload as JSON
                        $payload = json_encode($body);

                        // fetch device token of this user
                        $playerdevicetoken = fetchdevicetoken($pushids[$i]);

                        // Build the binary notification
                        $msg = chr(0) . pack('n', 32) . pack('H*', $playerdevicetoken) . pack('n', strlen($payload)) . $payload;

                        // Send it to the server
                        fwrite($fp, $msg, strlen($msg));
                    }
                    // Close the connection to the server
                    fclose($fp);
                }
            } else {
                echo '<status>0</status>';
            }
        } else {
            // there is an error fetching chips to add or reduce
            echo '<status>0</status>';
        }
        mysqli_close($dbc);
    }
}
?>
