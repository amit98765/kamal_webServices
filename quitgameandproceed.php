<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

// chech whether all required parameters were passed.
if (!isset($_GET['session_id']) || !isset($_GET['user_id']) || !isset($_GET['message_id']) || !isset($_GET['game_type'])) {
    echo 'message_id or user_id was not passed.';
    exit(0);
}

// grab the variables
$sessionid = $_GET['session_id'];
$userid = (int) $_GET['user_id'];
$messageid = $_GET['message_id'];
$gametype = trim($_GET['game_type']);

$dbc = mysqli_connect(host, user, password, database)
        or die("Errior connecting database");

$pushids = array();

// we have found the game type
// check if the user is a part of this type of game

$query2 = "select session_id from table_gamesessions where game_type = '$gametype' and session_id != $sessionid";
$result2 = mysqli_query($dbc, $query2);
if (mysqli_num_rows($result2) > 0) {
    while ($row2 = mysqli_fetch_array($result2)) {
        $thissessionid = $row2[0];

        // for each session id, check how many users are playing this game
        $query3 = "select * from games_players where session_id = $thissessionid and user_id = $userid";
        $result3 = mysqli_query($dbc, $query3);


        if (mysqli_num_rows($result3) == 1) {
            // check other active players
            $querymy = "select * from games_players where session_id = $thissessionid";
            $resultmy = mysqli_query($dbc, $querymy);

            if (mysqli_num_rows($resultmy) == 1) {
                // more than 1 active player
                $query5 = "delete from games_players where session_id = $thissessionid and user_id = $userid";
                mysqli_query($dbc, $query5);
            } else {
                // delete all the players entries, as there may still be some inactive users
                $query4 = "delete from games_players where session_id = $thissessionid";
                mysqli_query($dbc, $query4);

                if (mysqli_affected_rows($dbc) > 0) {
                    $query5 = "delete from table_gamesessions where session_id = $thissessionid";
                    mysqli_query($dbc, $query5);

                    if (mysqli_affected_rows($dbc) == 1) {
                        break;
                    }
                }
            }
        }
    }

    // check what type of game this is 
    $playerstatus = 0;
    $gametype = fetchgametype($sessionid, $dbc);

    if ($gametype == "slots")
        $playerstatus = 1;

    // now make an entry o this user in the active players list in new game
    $query6 = "insert into games_players(session_id, user_id, status) values ($sessionid, $userid, $playerstatus)";
    mysqli_query($dbc, $query6);

    if (mysqli_affected_rows($dbc) == 1) {

        // fetch handlerid according to the given message id and delete that row
        $query7 = "select handler_id from messages where id = $messageid";
        $result7 = mysqli_query($dbc, $query7);
        if (mysqli_num_rows($result7) > 0) {
            $handlerid = "";
            while ($row7 = mysqli_fetch_array($result7)) {
                $handlerid = $row7[0];
            }
            // delete this row

            $query8 = "delete from messages where id = $messageid";
            mysqli_query($dbc, $query8);

            // delete the row from handler id
            $query9 = "select * from invitations where id = $handlerid";
            $result9 = mysqli_query($dbc, $query9);
            if (mysqli_num_rows($result9) > 0) {
                $query10 = "delete from invitations where id = $handlerid";
                mysqli_query($dbc, $query10);
            }
        }

        echo '<status>1</status>';

        //set a message and send push to all players of this game
        $name = fetchname($userid, $dbc);

        $gametype = fetchgametype($sessionid, $dbc);

        $message = $name . ' became part of ' . $gametype . ' game.';
        $message1 = 'Started playing ' . fetchgametype($sessionid, $dbc);
        insertIntoFeed($userid, $message1, $dbc);

        // set this message now for all the players
        $query6 = "select user_id from games_players where session_id = $sessionid and user_id not in ($userid)";
        $result6 = mysqli_query($dbc, $query6);

        if (mysqli_num_rows($result6) > 0) {
            while ($row6 = mysqli_fetch_array($result6)) {
                $thisuserid = $row6[0];
                $query7 = "insert into messages(message, message_type, handler_id, user_id) values('$message', 4, $sessionid, $thisuserid)";
                $result7 = mysqli_query($dbc, $query7);

                // fetch device token of this user and push it in an array
                $pushdevicetoken = fetchdevicetoken($thisuserid);
                array_push($pushids, $pushdevicetoken);
            }
        }

        // check whether to give recommendation points or not
        $query17 = "select recommendors_user_id from recommendations where recommended_user_id = $userid  and status = 0";
        $result17 = mysqli_query($dbc, $query17);

        $recommenddata = array();
        if (mysqli_num_rows($result17) == 0) {
            $query8 = "select id, recommended_user_id from recommendations where recommendors_user_id = $userid  and status = 0";
            $result8 = mysqli_query($dbc, $query8);

            if (mysqli_num_rows($result8) > 0) {
                $row8 = mysqli_fetch_row($result8);
                $recommenddata[] = array($row8[0], $row8[1]);
            }
        } else {
            $row7 = mysqli_fetch_row($result7);
            $recommenddata[] = array($row7[0], $row7[1]);
        }

        if (count($recommenddata) > 0) {
            switch ($gametype) {
                case 'slots':
                    $query9 = "select * from games_players where session_id = $sessionid and user_id in ($userid, $recommenddata[0][1])";
                    $result9 = mysqli_query($dbc, $query9);

                    if (mysqli_num_rows($result9) == 2) {
                        $query10 = "update recommendations set status = 1 where id = $recommenddata[0][0]";
                        mysqli_query($dbc, $query10);
                    }
                    break;

                case 'roulette':
                    $query9 = "select * from roulette_bets where session_id = $sessionid and user_id in ($userid, $recommenddata[0][1])";
                    $result9 = mysqli_query($dbc, $query9);

                    if (mysqli_num_rows($result9) == 2) {
                        $query10 = "update recommendations set status = 1 where id = $recommenddata[0][0]";
                        mysqli_query($dbc, $query10);
                    }
                    break;

                case 'blackjack':
                    $query9 = "select * from blackjack_bets where session_id = $sessionid and user_id in ($userid, $recommenddata[0][1])";
                    $result9 = mysqli_query($dbc, $query9);

                    if (mysqli_num_rows($result9) == 2) {
                        $query10 = "update recommendations set status = 1 where id = $recommenddata[0][0]";
                        mysqli_query($dbc, $query10);
                    }
                    break;

                default :
                    break;
            }
        }
    }
} else {
    // this case was never assumed.
    echo '<status>0</status>';
}

mysqli_close($dbc);

// send the push if there were some ids 
if (count($pushids) > 0) {

    // Put your device token here (without spaces):
    $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

    // Actual $deviceToken = $devicetokenofthereceiver;
    // Put your private key's passphrase here:
    $passphrase = 'abcd';

    //set a message and send push to all players of this game
    $name = fetchname($userid);

    $gametype = fetchgametype($sessionid);

    $message = $name . ' became part of ' . $gametype . ' game.';

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
            'sound' => '4'
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
?>