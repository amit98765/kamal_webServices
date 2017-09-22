<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

// check if required variables were set 
if (!isset($_GET['session_id']) || !isset($_GET['user_id'])) {
    echo 'session_id or user_id were not set';
} else {

// fetch the variables
    $sessionid = $_GET['session_id'];
    $userid = (int) $_GET['user_id'];

    $done = FALSE;

    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

//check if this user exists first
    $query_exist = "select * from games_players where session_id=$sessionid and user_id = $userid";
    $result_exist = mysqli_query($dbc, $query_exist);

    if (mysqli_num_rows($result_exist) > 0) {

// fetch how many users are active for this session id
        $query = "select * from games_players where session_id = $sessionid";
        $result = mysqli_query($dbc, $query);

// if no rows did exist, the user has already quit the game, so return successful status
// and also delete the rows
        if (mysqli_num_rows($result) == 1) {
            $querymy10 = "select wins from games_players where session_id = $sessionid and user_id = $userid";
            $resultmy10 = mysqli_query($dbc, $querymy10);

            $winnings = 0;
            if (mysqli_num_rows($resultmy10) > 0) {
                $rowmy10 = mysqli_fetch_row($resultmy10);
                $winnings = $rowmy10[0];
            }

// delete all rows from session and game players table
            $query2 = "delete from games_players where esession_id = $sessionid";
            mysqli_query($dbc, $query2);

            $query3 = "delete from table_gamesessions where session_id = $sessionid";
            mysqli_query($dbc, $query3);

            $query4 = "delete from game_messages where session_id=  $sessionid and user_id = $userid";
            mysqli_query($dbc, $query4);

// return status 1
            echo '<result><status>1</status>';
            echo '<winnings>' . $winnings . '</winnings>';
            echo '<winnings2>' . number_format($winnings) . '</winnings2>';


            echo '</result>';
            $done = TRUE;
        } elseif (mysqli_num_rows($result) > 1) {

            $querymy10 = "select wins from games_players where session_id = $sessionid and user_id = $userid";
            $resultmy10 = mysqli_query($dbc, $querymy10);

            $winnings = 0;
            if (mysqli_num_rows($resultmy10) > 0) {
                $rowmy10 = mysqli_fetch_row($resultmy10);
                $winnings = $rowmy10[0];
            }

            $query4 = "delete from game_messages where session_id=  $sessionid and user_id = $userid";
            mysqli_query($dbc, $query4);

// only this user is active for this game, so delete the rows from both the tables
            $query2 = "delete from games_players where session_id = $sessionid and user_id = $userid";
            mysqli_query($dbc, $query2);

            if (mysqli_affected_rows($dbc) == 1) {
//successful
                echo '<result><status>1</status>';

                $rowmy10 = mysqli_fetch_row($resultmy10);
                echo '<winnings>' . $winnings . '</winnings>';
                echo '<winnings2>' . number_format($winnings) . '</winnings2>';

                echo '</result>';
                $done = TRUE;
            } else {
                echo '<status>0</status>';
            }
        } else {
            echo '<status>1</status>';
        }

        if (fetchgametype($sessionid) == 'blackjack') {
            $query10 = "delete from blackjack_bets where session_id = $sessionid and user_id = $userid";
            mysqli_query($dbc, $query10);
        }


        if (fetchgametype($sessionid) == 'roulette') {
            $query10 = "delete from roulette_bets where session_id = $sessionid and user_id = $userid";
            mysqli_query($dbc, $query10);
        }

// also delete gifts for this user in this game
        $query_gift_delete = "delete from gift_box where sent_to = $userid and session_id=$sessionid";
        mysqli_query($dbc, $query_gift_delete);
        mysqli_close($dbc);
    } else {
        echo '<status>1</status>';
    }

    $pushids = array();
    if ($done) {



        //fetch all active users of this game
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");

        $query = "select user_id from games_players where session_id = $sessionid and user_id != $userid";
        $result = mysqli_query($dbc, $query);

        if (mysqli_num_rows($result) > 0) {

            $name = fetchname($userid, $dbc);
            $gametype = fetchgametype($sessionid, $dbc);

            $message = $name . ' has quit the ' . $gametype . ' game.';

            while ($row = mysqli_fetch_array($result)) {
                $thisuserid = $row[0];

                // insert the message into messages
                $query2 = "insert into messages(message, message_type, user_id, handler_id) values('$message', 4, $thisuserid, $sessionid)";
                mysqli_query($dbc, $query2);

                array_push($pushids, $thisuserid);
            }
        }
        mysqli_close($dbc);
    }
    /*
     * all players to notify, a person has quit
     * sound type 3
     * message type 4 
     */

    if (count($pushids) > 0) {

        // send the push now
        // Put your device token here (without spaces):
        $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

        // Actual $deviceToken = $devicetokenofthereceiver;
        // Put your private key's passphrase here:
        $passphrase = 'abcd';

        // Put your alert message here:
        ////////////////////////////////////////////////////////////////////////////////

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

        // Open a connection to the APNS server
        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

        $name = fetchname($userid);
        $gametype = fetchgametype($sessionid);

        $message = $name . ' has quit the ' . $gametype . ' game.';

        for ($i = 0; $i < count($pushids); $i++) {
            // Create the payload body
            $body['aps'] = array(
                'alert' => $message,
                'sound' => '4'
            );

            // Encode the payload as JSON
            $payload = json_encode($body);

            $pushtokenid = fetchdevicetoken($pushids[$i]);

            // Build the binary notification
            $msg = chr(0) . pack('n', 32) . pack('H*', $pushtokenid) . pack('n', strlen($payload)) . $payload;

            // Send it to the server
            fwrite($fp, $msg, strlen($msg));
        }
        fclose($fp);
    }
}
?> 