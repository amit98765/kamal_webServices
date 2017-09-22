<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

// grab all variables passed through GET
$userid = $_GET['user_id'];
$gametype = $_GET['game_type'];
$tabletype = $_GET['table_type'];
$optiontype = $_GET['owner_type'];

// Check for latest session Id
$dbc = mysqli_connect(host, user, password, database)
        or die("Error Connecting database");

// sanity check userpassed data

if (!is_numeric($userid) || is_null($gametype) || is_null($tabletype)) {
    echo '<result><status>0</status></result>';
} else {

// fetch all session ids corresponding to game_type
    $query = "select session_id from table_gamesessions where game_type = '$gametype'";
    $result = mysqli_query($dbc, $query);

    if (mysqli_num_rows($result) == 0) {
        // there is some error 
        echo '<result><status>0</status></result>';
    } else {

        $userisactive = FALSE;

        // fetch all session ids one by one
        while ($row = mysqli_fetch_array($result)) {
            $sessionid = $row[0];

            // now check if this user is active for any game or not
            $query2 = "select * from games_players where session_id = $sessionid and user_id = $userid";
            $result2 = mysqli_query($dbc, $query2);
            if (mysqli_num_rows($result2) != 0) {

                // there was no active user, so give status 1
                echo '<result><status>1</status><session_id>' . $sessionid . '</session_id><newGame>0</newGame></result>';
                $userisactive = TRUE;

                break;
            }
        }

        // if user is not active 
        if (!$userisactive) {


            if ($optiontype == 1) {
                // put the data in the database
                $query2 = "insert into table_gamesessions(game_type, table_type, creator_userid) values('$gametype', '$tabletype', $userid)";
                mysqli_query($dbc, $query2);
                if (mysqli_affected_rows($dbc) == 1) {
                    echo '<result>';
                    echo '<status>1</status>';
                    echo '<newGame>1</newGame>';
                    echo '<session_id>';

                    $latestsessionid = mysqli_insert_id($dbc);
                    echo $latestsessionid;

                    echo '</session_id>';

                    echo '</result>';

                    $status = 0;
                    if ($gametype == 'omaha') {
                        $status = 1;
                    }

                    // also make an entry of this user in the players table
                    $query3 = "insert into games_players(session_id, user_id, status) values ($latestsessionid, $userid, $status)";
                    mysqli_query($dbc, $query3);

                    $message = 'Started Playing ' . ucfirst(fetchgametype($latestsessionid, $dbc));
                    insertIntoFeed($userid, $message, $dbc);
                    setlatestmessageid($latestsessionid, $userid, $message, 1, $dbc);
                } else {
                    echo '<result><status>0</status></result>';
                }
            } else {
                // get a game where gametype is same, and there are not enough players to play.
                $query2 = "select session_id from table_gamesessions where game_type='$gametype' and table_type='$tabletype' and type=0";
                $result2 = mysqli_query($dbc, $query2);
                if (mysqli_num_rows($result2) > 0) {
                    $found = FALSE;
                    while ($row2 = mysqli_fetch_array($result2)) {
                        $thissessionid = $row2[0];

                        // is this person already invited for this game
                        $queryy1 = "select id from invitations where session_id = $thissessionid and invitation_to = $userid";
                        $resulty1 = mysqli_query($dbc, $queryy1);

                        if (mysqli_num_rows($resulty1) > 0) {
                            $queryy2 = "delete from invitations where session_id = $thissessionid and invitation_to = $userid";
                            mysqli_query($dbc, $queryy2);

                            $rowy1 = mysqli_fetch_row($resulty1);
                            $queryy3 = "delete from messages where handler_id=$rowy1[0]";
                            mysqli_query($dbc, $queryy3);

                            // make him part of this game directly
                            $status = 0;
                            if ($gametype == 'Omaha') {
                                $status = 1;
                            }

                            $query5 = "insert into games_players(session_id, user_id, status) values ($thissessionid, $userid, $status)";
                            mysqli_query($dbc, $query5);

                            $message = 'Started Playing ' . ucfirst(fetchgametype($thissessionid, $dbc));
                            insertIntoFeed($userid, $message, $dbc);

                            echo '<result>';
                            echo '<status>1</status>';
                            echo '<newGame>0</newGame>';
                            echo '<session_id>';
                            echo $thissessionid;
                            echo '</session_id>';

                            $message2 = 'Started Playing ' . ucfirst(fetchgametype($thissessionid, $dbc));


                            setlatestmessageid($thissessionid, $userid, $message2, 1, $dbc);
                            echo '</result>';


                            giveRewardPoint($userid, $gametype, $thissessionid, $dbc);

                            // check if this player is already invited in this game
                            // also send a push to all players of this game ... 
                            $pushids = array();
                            $queryuser6 = "select user_id from games_players where session_id = $thissessionid and user_id not in ($userid)";
                            $resultuser6 = mysqli_query($dbc, $queryuser6);

                            if (mysqli_num_rows($resultuser6) > 0) {
                                while ($rowuser6 = mysqli_fetch_array($resultuser6)) {
                                    $pushdevicetoken = fetchdevicetoken($rowuser6[0], $dbc);
                                    array_push($pushids, $pushdevicetoken);
                                }
                            }
                            // check if there are some items in the pusharray
                            if (count($pushids) > 0) {

                                // Put your private key's passphrase here:
                                $passphrase = 'abcd';

                                //set a message and send push to all players of this game
                                $name = fetchname($userid);

                                $gametype = fetchgametype($sessionid);

                                $message = $name . ' has joined ' . $gametype . ' game.';

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

                                    // Build the binary notification
                                    $msg = chr(0) . pack('n', 32) . pack('H*', $pushids[$i]) . pack('n', strlen($payload)) . $payload;

                                    // Send it to the server
                                    fwrite($fp, $msg, strlen($msg));
                                }
                                // Close the connection to the server
                                fclose($fp);
                            }


                            $found = TRUE;


                            break;
                        } else {

                            $query3 = "select * from invitations where session_id = $thissessionid";
                            $result3 = mysqli_query($dbc, $query3);
                            $totalinvited = mysqli_num_rows($result3);

                            $query4 = "select * from games_players where session_id = $thissessionid";
                            $result4 = mysqli_query($dbc, $query4);
                            $totalplaying = mysqli_num_rows($result4);

                            $tocompare = 4;

                            if (strtolower($gametype) == "blackjack") {
                                $tocompare = 3;
                            }


                            if (($totalinvited + $totalplaying) < $tocompare) {
                                // make this player player of this game, and break
                                // also make an entry of this user in the players table

                                $status = 0;
                                if ($gametype == 'Omaha') {
                                    $status = 1;
                                }

                                $query5 = "insert into games_players(session_id, user_id, status) values ($thissessionid, $userid, $status)";
                                mysqli_query($dbc, $query5);

                                $message = 'Started Playing ' . ucfirst(fetchgametype($thissessionid, $dbc));
                                insertIntoFeed($userid, $message, $dbc);

                                echo '<result>';
                                echo '<status>1</status>';
                                echo '<newGame>0</newGame>';
                                echo '<session_id>';
                                echo $thissessionid;
                                echo '</session_id>';

                                $message2 = 'Started Playing ' . ucfirst(fetchgametype($thissessionid, $dbc));


                                setlatestmessageid($thissessionid, $userid, $message2, 1, $dbc);
                                echo '</result>';


                                giveRewardPoint($userid, $gametype, $thissessionid, $dbc);

                                // check if this player is already invited in this game
                                // also send a push to all players of this game ... 
                                $pushids = array();
                                $queryuser6 = "select user_id from games_players where session_id = $thissessionid and user_id not in ($userid)";
                                $resultuser6 = mysqli_query($dbc, $queryuser6);

                                if (mysqli_num_rows($resultuser6) > 0) {
                                    while ($rowuser6 = mysqli_fetch_array($resultuser6)) {
                                        $pushdevicetoken = fetchdevicetoken($rowuser6[0], $dbc);
                                        array_push($pushids, $pushdevicetoken);
                                    }
                                }
                                // check if there are some items in the pusharray
                                if (count($pushids) > 0) {

                                    // Put your private key's passphrase here:
                                    $passphrase = 'abcd';

                                    //set a message and send push to all players of this game
                                    $name = fetchname($userid);

                                    $gametype = fetchgametype($sessionid);

                                    $message = $name . ' has joined ' . $gametype . ' game.';

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

                                        // Build the binary notification
                                        $msg = chr(0) . pack('n', 32) . pack('H*', $pushids[$i]) . pack('n', strlen($payload)) . $payload;

                                        // Send it to the server
                                        fwrite($fp, $msg, strlen($msg));
                                    }
                                    // Close the connection to the server
                                    fclose($fp);
                                }


                                $found = TRUE;


                                break;
                            }
                        }
                    }
                    if (!$found) {
                        $query2 = "insert into table_gamesessions(game_type, table_type, creator_userid, type) values('$gametype', '$tabletype', $userid, 0)";
                        mysqli_query($dbc, $query2);
                        if (mysqli_affected_rows($dbc) == 1) {
                            echo '<result>';
                            echo '<status>1</status>';

                            echo '<session_id>';

                            $latestsessionid = mysqli_insert_id($dbc);
                            echo $latestsessionid;

                            echo '</session_id>';
                            echo '<newGame>1</newGame>';
                            echo '</result>';

                            $status = 0;
                            if ($gametype == 'omaha') {
                                $status = 1;
                            }

                            // also make an entry of this user in the players table
                            $query3 = "insert into games_players(session_id, user_id, status) values ($latestsessionid, $userid, $status)";
                            mysqli_query($dbc, $query3);

                            $message = 'Started Playing ' . ucfirst(fetchgametype($latestsessionid, $dbc));
                            insertIntoFeed($userid, $message, $dbc);
                            setlatestmessageid($latestsessionid, $userid, $message, 1, $dbc);
                        } else {
                            echo '<result><status>0</status></result>';
                        }
                    }
                } else {
                    $query2 = "insert into table_gamesessions(game_type, table_type, creator_userid, type) values('$gametype', '$tabletype', $userid, 0)";
                    mysqli_query($dbc, $query2);
                    if (mysqli_affected_rows($dbc) == 1) {
                        echo '<result>';
                        echo '<status>1</status>';

                        echo '<session_id>';

                        $latestsessionid = mysqli_insert_id($dbc);
                        echo $latestsessionid;

                        echo '</session_id>';
                        echo '<newGame>1</newGame>';
                        echo '</result>';

                        $status = 0;
                        if ($gametype == 'omaha') {
                            $status = 1;
                        }

                        // also make an entry of this user in the players table
                        $query3 = "insert into games_players(session_id, user_id, status) values ($latestsessionid, $userid, $status)";
                        mysqli_query($dbc, $query3);

                        $message = 'Started Playing ' . ucfirst(fetchgametype($latestsessionid, $dbc));
                        insertIntoFeed($userid, $message, $dbc);
                        setlatestmessageid($latestsessionid, $userid, $message, 1, $dbc);
                    } else {
                        echo '<result><status>0</status></result>';
                    }
                }
            }
        }
    }
}

mysqli_close($dbc);
?>