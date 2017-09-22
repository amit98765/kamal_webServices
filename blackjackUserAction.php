<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

// check if requiredd fields were passed in url
if (is_null($_GET['session_id']) || is_null($_GET['user_id']) || is_null($_GET['action'])) {
    echo 'session_id, or user_id or action was not passed';
} else {
    // grab the variable
    $sessionid = $_GET['session_id'];
    $userid = $_GET['user_id'];
    $action = $_GET['action'];
    $alreadygivencards = array();

    setTimeZone();

    //sanity check the variable
    if (!is_numeric($sessionid)) {
        echo 'There is unexpected error';
    } else {
        // fetch all session ids playing gme in this session id
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");

        switch ($action) {
            case 'stand':

                setlatestmessage($sessionid, $userid, "STAND", 1, $dbc);

                $query1 = "select player_status from blackjack_bets where session_id = $sessionid and user_id = $userid";
                $result1 = mysqli_query($dbc, $query1);

                if (mysqli_num_rows($result1) > 0) {
                    $row1 = mysqli_fetch_row($result1);
                    $playerstatus = $row1[0];

                    $playerstatusexploded = explode(':', $playerstatus);
                    for ($i = 0; $i < count($playerstatusexploded); $i++) {
                        if ($playerstatusexploded[$i] == 1) {
                            $playerstatusexploded[$i] = 2;

                            $newstatus = join(':', $playerstatusexploded);

                            $query2 = "update blackjack_bets set player_status = '$newstatus' where session_id = $sessionid and user_id = $userid";
                            mysqli_query($dbc, $query2);


                            sendthepush('Stand', $userid, $dbc, $sessionid);

                            sleep(2);

                            // find next player to activate
                            $query3 = "select user_id, player_status from blackjack_bets where player_status like '%0%' and session_id = $sessionid  and amount != '0' and cards != '' and user_id != 0 order by datetime limit 1";
                            $result3 = mysqli_query($dbc, $query3);

                            if (mysqli_num_rows($result3) > 0) {
//                                echo '<status>1</status>';

                                $query3 = "select user_id, player_status from blackjack_bets where player_status like '%0%' and session_id = $sessionid and amount != '0' and cards != '' and user_id != 0 order by datetime limit 1";
                                $result3 = mysqli_query($dbc, $query3);

                                if (mysqli_num_rows($result3) > 0) {
                                    $row3 = mysqli_fetch_row($result3);

                                    //renew session
                                    $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                                    $querym2 = "update blackjack_game_data set status = 2, datetime = '$date2' where session_id = $sessionid";
                                    mysqli_query($dbc, $querym2);

                                    $thisplayerstatus = $row3[1];
                                    $thisuserid = $row3[0];
                                    //set status of this userid as 1, and update time of gamedaa

                                    $thisplayerstatusexploded = explode(':', $thisplayerstatus);

                                    for ($i = 0; $i < count($thisplayerstatusexploded); $i++) {
                                        if ($thisplayerstatusexploded[$i] == 0) {
                                            $thisplayerstatusexploded[$i] = 1;

                                            $newstatus = join(':', $thisplayerstatusexploded);

                                            $query4 = "update blackjack_bets set player_status = '$newstatus' where user_id = $thisuserid and session_id = $sessionid";
                                            if (mysqli_query($dbc, $query4)) {
                                                echo '<blackjackStatus>2</blackjackStatus>';
                                                echo '<seconds>20</seconds>';

                                                sendthepush("Refresh", $userid, $dbc, $sessionid);
                                            }
                                        }
                                    }
                                } else {


                                    // make dealer status 1
                                    $querythis1 = "update blackjack_bets set player_status = '1' where user_id = 0 and session_id = $sessionid";
                                    mysqli_query($dbc, $querythis1);

                                    //make gamestatus 3
                                    $querymy2 = "update blackjack_game_data set status = 3 where session_id = $sessionid";
                                    mysqli_query($dbc, $querymy2);

                                    // send push to all players 
                                    $queryn1 = "select user_id from blackjack_bets where user_id !=0 and session_id = $sessionid";
                                    $resultn1 = mysqli_query($dbc, $queryn1);

                                    $allpushids = array();
                                    if (mysqli_num_rows($resultn1) > 0) {
                                        while ($rown1 = mysqli_fetch_array($resultn1)) {
                                            array_push($allpushids, $rown1[0]);
                                        }
                                    }


                                    if ($allpushids > 0) {

                                        $passphrase = 'abcd';
                                        $ctx = stream_context_create();
                                        stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                                        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                                        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                                        if (!$fp)
                                            exit("Failed to connect: $err $errstr" . PHP_EOL);

                                        for ($i = 0; $i < count($allpushids); $i++) {

                                            $body['aps'] = array(
                                                'alert' => 'Dealers turn starts',
                                                'sound' => '3'
                                            );

                                            $payload = json_encode($body);

                                            $devicetoken = fetchdevicetoken($allpushids[$i], $dbc);

                                            $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                                            fwrite($fp, $msg, strlen($msg));
                                        }

                                        fclose($fp);
                                    }

                                    sleep(2);

                                    doblackjackcalculations($sessionid, $dbc, TRUE);

                                    $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));
                                    $querym2 = "update blackjack_game_data set status = 1, datetime = '$date2' where session_id = $sessionid";
                                    mysqli_query($dbc, $querym2);

                                    echo '<blackjackStatus>1</blackjackStatus>';
                                    echo '<seconds>30</seconds>';
                                }
                            } else {


                                // make dealer status 1
                                $querythis1 = "update blackjack_bets set player_status = '1' where user_id = 0 and session_id = $sessionid";
                                mysqli_query($dbc, $querythis1);

                                //make gamestatus 3
                                $querymy2 = "update blackjack_game_data set status = 3 where session_id = $sessionid";
                                mysqli_query($dbc, $querymy2);

                                // send push to all players 
                                $queryn1 = "select user_id from blackjack_bets where user_id !=0 and session_id = $sessionid";
                                $resultn1 = mysqli_query($dbc, $queryn1);

                                $allpushids = array();
                                if (mysqli_num_rows($resultn1) > 0) {
                                    while ($rown1 = mysqli_fetch_array($resultn1)) {
                                        array_push($allpushids, $rown1[0]);
                                    }
                                }


                                if ($allpushids > 0) {

                                    $passphrase = 'abcd';
                                    $ctx = stream_context_create();
                                    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                                    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                                    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                                    if (!$fp)
                                        exit("Failed to connect: $err $errstr" . PHP_EOL);

                                    for ($i = 0; $i < count($allpushids); $i++) {

                                        $body['aps'] = array(
                                            'alert' => 'Dealers Is Playing Now',
                                            'sound' => '3'
                                        );

                                        $payload = json_encode($body);

                                        $devicetoken = fetchdevicetoken($allpushids[$i], $dbc);

                                        $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                                        fwrite($fp, $msg, strlen($msg));
                                    }

                                    fclose($fp);
                                }

                                sleep(2);

                                doblackjackcalculations($sessionid, $dbc, TRUE);
                            }
                        }
                    }
                }

                break;

            case 'split':
                $query10 = "select cards from blackjack_bets where session_id = $sessionid";
                $result10 = mysqli_query($dbc, $query10);

                $alreadygivencards = array();

                if (mysqli_num_rows($result10) > 0) {
                    while ($row10 = mysqli_fetch_array($result10)) {
                        if (!is_null($row10[0])) {
                            $cardsexploded = explode(',', $row10[0]);
                            $alreadygivencards = array_merge(array_unique(array_merge($alreadygivencards, $cardsexploded)));
                        }
                    }
                }
                $query1 = "select * from blackjack_bets where session_id = $sessionid and user_id = $userid";
                $result1 = mysqli_query($dbc, $query1);

                if (mysqli_num_rows($result1) > 0) {
                    $row1 = mysqli_fetch_row($result1);

                    //check if he has not already splitted
                    $oldstatus = $row1[5];
                    $oldamount = $row1[3];
                    $oldcards = $row1[4];

                    $oldstatusexploded = explode(':', $oldstatus);
                    if (count($oldstatusexploded) > 1) {
                        echo '<status>0</status>';
                    } else {
                        $newcard1 = getnewcard($alreadygivencards);
                        array_push($alreadygivencards, $newcard1);

                        $newcard2 = getnewcard($alreadygivencards);
                        array_push($alreadygivencards, $newcard2);

                        // break older comma separated cards
                        $oldcardsexploded = explode(',', $oldcards);

                        $newcards = $oldcardsexploded[0] . ',' . $newcard1 . ':' . $oldcardsexploded[1] . ',' . $newcard2;
                        $newstatus = "1:0";
                        $newamount = $oldamount . ':' . $oldamount;

                        //upload all these things
                        $query2 = "update blackjack_bets set amount = '$newamount', player_status = '$newstatus', cards = '$newcards' where session_id = $sessionid and user_id = $userid";
                        mysqli_query($dbc, $query2);

                        increasedecreasechips($userid, convertChips($oldamount), 3, $dbc);

                        // increase time of game to 20 seconds, 
                        //renew session
                        $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                        $querym2 = "update blackjack_game_data set status = 2, datetime = '$date2' where session_id = $sessionid";
                        if (mysqli_query($dbc, $querym2))
                            echo '<status>1</status>';
                        else
                            echo '<status>0</status>';

                        sendthepush('SPLIT', $userid, $dbc, $sessionid);
                    }
                }


                break;

            case 'hit':
                $query10 = "select cards from blackjack_bets where session_id = $sessionid";
                $result10 = mysqli_query($dbc, $query10);

                $alreadygivencards = array();

                if (mysqli_num_rows($result10) > 0) {
                    while ($row10 = mysqli_fetch_array($result10)) {
                        if ($row10[0] != "") {
                            
                            $cardsexploded =  explode(',', $row10[0]);
                            $alreadygivencards = array_merge(array_unique(array_merge($alreadygivencards, $cardsexploded)));
                        }
                    }
                }



                // give this player a card
                $newcard = getnewcard($alreadygivencards);

                $query2 = "select * from blackjack_bets where session_id = $sessionid and user_id = $userid";
                $result2 = mysqli_query($dbc, $query2);

                if (mysqli_num_rows($result2) > 0) {
                    $row2 = mysqli_fetch_row($result2);

                    $oldcards = $row2[4];
                    $oldstatus = $row2[5];

                    $statusexploded = explode(':', $oldstatus);
                    $oldcardsexploded = explode(':', $oldcards);

                    for ($i = 0; $i < count($oldcardsexploded); $i++) {
                        if ($statusexploded[$i] == 1) {
                            $oldcardsexploded[$i] .= ',' . $newcard;
                            $toinsertcards = join(':', $oldcardsexploded);

                            $query4 = "update blackjack_bets set cards = '$toinsertcards' where user_id = $userid and session_id = $sessionid";
                            if (mysqli_query($dbc, $query4)) {

                                echo '<status>1</status>';

                                // if all this is done, check if the player total becomes more than 21, 
                                // turn of this player should end immidiately.
                                $thisplayercardsexploded = explode(',', $oldcardsexploded[$i]);
                                $totalofcards = 0;
                                $total11taken = 0;
                                for ($j = 0; $j < count($thisplayercardsexploded); $j++) {
                                    if (($thisplayercardsexploded[$j] == 1 ) || ($thisplayercardsexploded[$j] == 14 ) || ($thisplayercardsexploded[$j] == 27 ) || ($thisplayercardsexploded[$j] == 40 )) {
                                        $totalofcards += 11;
                                        $total11taken++;

                                        if ($totalofcards > 21) {
                                            if ($total11taken > 0) {
                                                $totalofcards -= 10;
                                                $total11taken--;
                                            }
                                        }
                                    } else {
                                        $totalofcards += getcardvalue($thisplayercardsexploded[$j]);
                                        if ($totalofcards > 21) {
                                            if ($total11taken > 0) {
                                                $totalofcards -=10;
                                                $total11taken--;
                                            }
                                        }
                                    }
                                }

                                if ($totalofcards > 21) {
                                    setlatestmessage($sessionid, $userid, "HIT and STAND", 1, $dbc);



                                    // end turn of this player
                                    $statusexploded[$i] = 2;
                                    $toinsertstatus = join(':', $statusexploded);
                                    $query4 = "update blackjack_bets set player_status = '$toinsertstatus' where user_id = $userid and session_id = $sessionid";
                                    mysqli_query($dbc, $query4);

                                    sendthepush('Hit and stand', $userid, $dbc, $sessionid);
                                    sleep(2);

                                    // find next player to activate
                                    $query3 = "select user_id, player_status from blackjack_bets where player_status like '%0%' and session_id = $sessionid  and amount != '0' and cards != '' and user_id != 0 order by datetime limit 1";
                                    $result3 = mysqli_query($dbc, $query3);

                                    if (mysqli_num_rows($result3) > 0) {
                                        echo '<status>1</status>';

                                        $row3 = mysqli_fetch_row($result3);

                                        //renew session
                                        $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                                        $querym2 = "update blackjack_game_data set status = 2, datetime = '$date2' where session_id = $sessionid";
                                        mysqli_query($dbc, $querym2);

                                        $thisplayerstatus = $row3[1];
                                        $thisuserid = $row3[0];
                                        //set status of this userid as 1, and update time of gamedaa

                                        $thisplayerstatusexploded = explode(':', $thisplayerstatus);

                                        for ($i = 0; $i < count($thisplayerstatusexploded); $i++) {
                                            if ($thisplayerstatusexploded[$i] == 0) {
                                                $thisplayerstatusexploded[$i] = 1;

                                                $newstatus = join(':', $thisplayerstatusexploded);

                                                $query4 = "update blackjack_bets set player_status = '$newstatus' where user_id = $thisuserid and session_id = $sessionid";
                                                if (mysqli_query($dbc, $query4)) {
                                                    echo '<blackjackStatus>2</blackjackStatus>';
                                                    echo '<seconds>20</seconds>';

                                                    sendthepush("Refresh", $userid, $dbc, $sessionid);
                                                }
                                            }
                                        }
                                    } else {
                                        // make dealer status 1
                                        $querythis1 = "update blackjack_bets set player_status = '1' where user_id = 0 and session_id = $sessionid";
                                        mysqli_query($dbc, $querythis1);

                                        //make gamestatus 3
                                        $querymy2 = "update blackjack_game_data set status = 3 where session_id = $sessionid";
                                        mysqli_query($dbc, $querymy2);

                                        // send push to all players 
                                        $queryn1 = "select user_id from blackjack_bets where user_id !=0 and session_id = $sessionid";
                                        $resultn1 = mysqli_query($dbc, $queryn1);

                                        $allpushids = array();
                                        if (mysqli_num_rows($resultn1) > 0) {
                                            while ($rown1 = mysqli_fetch_array($resultn1)) {
                                                array_push($allpushids, $rown1[0]);
                                            }
                                        }


                                        if ($allpushids > 0) {

                                            $passphrase = 'abcd';
                                            $ctx = stream_context_create();
                                            stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                                            stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                                            $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                                            if (!$fp)
                                                exit("Failed to connect: $err $errstr" . PHP_EOL);

                                            for ($i = 0; $i < count($allpushids); $i++) {
                                                $body['aps'] = array(
                                                    'alert' => 'Dealer Is Playing Now',
                                                    'sound' => '3'
                                                );

                                                $payload = json_encode($body);
                                                $devicetoken = fetchdevicetoken($allpushids[$i], $dbc);
                                                $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;
                                                fwrite($fp, $msg, strlen($msg));
                                            }

                                            fclose($fp);
                                        }

                                        sleep(2);

                                        doblackjackcalculations($sessionid, $dbc, TRUE);
                                    }
                                } else {
                                    setlatestmessage($sessionid, $userid, "HIT", 1, $dbc);
                                }
                            }
                            else
                                echo '<status>0</status>';
                        }
                    }
                }
                sendthepush('hit', $userid, $dbc, $sessionid);
                break;

            case 'double':
                $query10 = "select cards from blackjack_bets where session_id = $sessionid";
                $result10 = mysqli_query($dbc, $query10);

                $alreadygivencards = array();
                if (mysqli_num_rows($result10) > 0) {
                    while ($row10 = mysqli_fetch_array($result10)) {
                        if (!is_null($row10[0])) {
                            $cardsexploded = (string) explode(',', $row10[0]);
                            $alreadygivencards = array_merge(array_unique(array_merge($alreadygivencards, $cardsexploded)));
                        }
                    }
                }

                $newcard = getnewcard($alreadygivencards);

                $query1 = "select amount, player_status, cards from blackjack_bets where user_id = $userid and session_id = $sessionid";
                $result1 = mysqli_query($dbc, $query1);

                if (mysqli_num_rows($result1) > 0) {
                    $row1 = mysqli_fetch_row($result1);

                    $amount = $row1[0];
                    $status = $row1[1];
                    $cards = $row1[2];

                    $statusexploded = explode(':', $status);
                    $amountexploded = explode(':', $amount);
                    $cardsexploded = explode(':', $cards);

                    for ($i = 0; $i < count($statusexploded); $i++) {
                        if ($statusexploded[$i] == 1) {
                            $mynewamount = convertBackChips(convertChips($amountexploded[$i]) * 2);

                            $amountexploded[$i] = $mynewamount;
                            $statusexploded[$i] = '2';
                            $cardsexploded[$i] = $cardsexploded[$i] . ',' . $newcard;

                            $newamount = implode(':', $amountexploded);
                            $newstatus = implode(':', $statusexploded);
                            $newcards = implode(':', $cardsexploded);

                            $query2 = "update blackjack_bets set player_status = '$newstatus', amount = '$newamount', cards='$newcards' where user_id = $userid and session_id = $sessionid";
                            if (mysqli_query($dbc, $query2)) {
                                setlatestmessage($sessionid, $userid, 'DOUBLE - Bet ' . $mynewamount . ' chips', 1, $dbc);
                                echo '<status>1</status>';

                                increasedecreasechips($userid, convertChips($amountexploded[$i]) / 2, 3, $dbc);

                                sendthepush('double', $userid, $dbc, $sessionid);

                                sleep(2);
                            }

                            else
                                echo '<status>0</status>';
                        }
                    }
                    // find next player to activate
                    $query3 = "select user_id, player_status from blackjack_bets where player_status like '%0%' and session_id = $sessionid  and amount != '0' and cards != '' and user_id != 0 order by datetime limit 1";
                    $result3 = mysqli_query($dbc, $query3);

                    if (mysqli_num_rows($result3) > 0) {
                        echo '<status>1</status>';
                        $row3 = mysqli_fetch_row($result3);

                        //renew session
                        $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                        $querym2 = "update blackjack_game_data set status = 2, datetime = '$date2' where session_id = $sessionid";
                        mysqli_query($dbc, $querym2);

                        $thisplayerstatus = $row3[1];
                        $thisuserid = $row3[0];
                        //set status of this userid as 1, and update time of gamedaa

                        $thisplayerstatusexploded = explode(':', $thisplayerstatus);

                        for ($i = 0; $i < count($thisplayerstatusexploded); $i++) {
                            if ($thisplayerstatusexploded[$i] == 0) {
                                $thisplayerstatusexploded[$i] = 1;

                                $newstatus = join(':', $thisplayerstatusexploded);

                                $query4 = "update blackjack_bets set player_status = '$newstatus' where user_id = $thisuserid and session_id = $sessionid";
                                if (mysqli_query($dbc, $query4)) {
                                    echo '<blackjackStatus>2</blackjackStatus>';
                                    echo '<seconds>20</seconds>';

                                    sendthepush("Refresh", $userid, $dbc, $sessionid);
                                }
                            }
                        }

                        sendthepush('Double', $userid, $dbc, $sessionid);
                    } else {
                        // make dealer status 1
                        $querythis1 = "update blackjack_bets set player_status = '1' where user_id = 0 and session_id = $sessionid";
                        mysqli_query($dbc, $querythis1);

                        //make gamestatus 3
                        $querymy2 = "update blackjack_game_data set status = 3 where session_id = $sessionid";
                        mysqli_query($dbc, $querymy2);

                        // send push to all players 
                        $queryn1 = "select user_id from blackjack_bets where user_id !=0 and session_id = $sessionid";
                        $resultn1 = mysqli_query($dbc, $queryn1);

                        $allpushids = array();
                        if (mysqli_num_rows($resultn1) > 0) {
                            while ($rown1 = mysqli_fetch_array($resultn1)) {
                                array_push($allpushids, $rown1[0]);
                            }
                        }


                        if ($allpushids > 0) {

                            $passphrase = 'abcd';
                            $ctx = stream_context_create();
                            stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                            stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                            $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                            if (!$fp)
                                exit("Failed to connect: $err $errstr" . PHP_EOL);

                            for ($i = 0; $i < count($allpushids); $i++) {

                                $body['aps'] = array(
                                    'alert' => 'Dealer Is Playing Now',
                                    'sound' => '3'
                                );

                                $payload = json_encode($body);

                                $devicetoken = fetchdevicetoken($allpushids[$i], $dbc);

                                $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                                fwrite($fp, $msg, strlen($msg));
                            }

                            fclose($fp);
                        }

                        sleep(2);

                        doblackjackcalculations($sessionid, $dbc, TRUE);
                    }
                }
                break;

            case 'surrender':

                $doingcalcs = FALSE;

                // deduct half of the chips he has bet
                $query1 = "select * from blackjack_bets where user_id = $userid and session_id = $sessionid";
                $result1 = mysqli_query($dbc, $query1);

                if (mysqli_num_rows($result1) > 0) {
                    $row1 = mysqli_fetch_row($result1);
                    $amount = $row1[3];
                    $status = $row1[5];
                    $cards = $row1[4];

                    $statusexploded = explode(':', $status);
                    $amountexploded = explode(':', $amount);
                    $cardsexploded = explode(':', $cards);

                    for ($i = 0; $i < count($statusexploded); $i++) {
                        if ($statusexploded[$i] == 1) {
                            $oldamount = $amountexploded[$i];
                            $amounttodecrease = convertChips($oldamount) / 2;

                            increasedecreasechips($userid, $amounttodecrease, 1, $dbc);

                            $amountexploded[$i] = '0';
                            $statusexploded[$i] = '2';


                            $newamount = join(":", $amountexploded);
                            $newstatus = join(':', $statusexploded);
                            $newcards = join(':', $cardsexploded);

                            //upload all these things
                            $query2 = "update blackjack_bets set amount = '$newamount', player_status = '$newstatus', cards = '$newcards' where session_id = $sessionid and user_id = $userid";
                            mysqli_query($dbc, $query2);

                            setlatestmessage($sessionid, $userid, 'SURRENDER  - ' . convertBackChips($amounttodecrease), 1, $dbc);

                            setlatestmessage($sessionid, 0, '+ ' . convertBackChips($amounttodecrease), 1, $dbc);

                            sendthepush('surrender', $userid, $dbc, $sessionid);
                            sleep(2);


                            // find next player to activate
                            $query3 = "select user_id, player_status from blackjack_bets where player_status like '%0%' and session_id = $sessionid  and amount != '0' and cards != '' and user_id != 0 order by datetime limit 1";
                            $result3 = mysqli_query($dbc, $query3);

                            if (mysqli_num_rows($result3) > 0) {
                                $row3 = mysqli_fetch_row($result3);

                                //renew session
                                $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                                $querym2 = "update blackjack_game_data set status = 2, datetime = '$date2' where session_id = $sessionid";
                                mysqli_query($dbc, $querym2);

                                $thisplayerstatus = $row3[1];
                                $thisuserid = $row3[0];
                                //set status of this userid as 1, and update time of gamedaa

                                $thisplayerstatusexploded = explode(':', $thisplayerstatus);

                                for ($i = 0; $i < count($thisplayerstatusexploded); $i++) {
                                    if ($thisplayerstatusexploded[$i] == 0) {
                                        $thisplayerstatusexploded[$i] = 1;

                                        $newstatus = join(':', $thisplayerstatusexploded);

                                        $query4 = "update blackjack_bets set player_status = '$newstatus' where user_id = $thisuserid and session_id = $sessionid";
                                        if (mysqli_query($dbc, $query4)) {
                                            echo '<blackjackStatus>2</blackjackStatus>';
                                            echo '<seconds>20</seconds>';

                                            sendthepush("Refresh", $userid, $dbc, $sessionid);
                                        }
                                    }
                                }
                                sendthepush('surrender', $userid, $dbc, $sessionid);
                                echo '<status>1</status>';
                            } else {
                                // check if some players have already bet
                                $query5 = "select user_id, player_status from blackjack_bets where player_status like '%2%' and session_id = $sessionid  and amount != '0' and cards != '' and user_id != 0 ";
                                $result5 = mysqli_query($dbc, $query5);

                                if (mysqli_num_rows($result5) == 0) {


                                    //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                                    //clear my and dealers cards
                                    $querymy5 = "update blackjack_bets set cards='', amount='0', player_status='0' where session_id=$sessionid ";
                                    mysqli_query($dbc, $querymy5);

                                    //also set the game status as running, and time +30 seconds
                                    $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));
                                    $querym2 = "update blackjack_game_data set status = 1, datetime = '$date2' where session_id = $sessionid";
                                    if (mysqli_query($dbc, $querym2))
                                        echo '<status>1</status>';
                                    else
                                        echo '<status>0</status>';

                                    //----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                                }

                                else {
                                    $doingcalcs = TRUE;

                                    // make dealer status 1
                                    $querythis1 = "update blackjack_bets set player_status = '1' where user_id = 0 and session_id = $sessionid";
                                    mysqli_query($dbc, $querythis1);

                                    //make gamestatus 3
                                    $querymy2 = "update blackjack_game_data set status = 3 where session_id = $sessionid";
                                    mysqli_query($dbc, $querymy2);


                                    $queryn1 = "select user_id from blackjack_bets where user_id !=0 and session_id = $sessionid";
                                    $resultn1 = mysqli_query($dbc, $queryn1);

                                    $allpushids = array();
                                    if (mysqli_num_rows($resultn1) > 0) {
                                        while ($rown1 = mysqli_fetch_array($resultn1)) {
                                            array_push($allpushids, $rown1[0]);
                                        }
                                    }


                                    if ($allpushids > 0) {

                                        $passphrase = 'abcd';
                                        $ctx = stream_context_create();
                                        stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                                        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                                        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                                        if (!$fp)
                                            exit("Failed to connect: $err $errstr" . PHP_EOL);

                                        for ($i = 0; $i < count($allpushids); $i++) {

                                            $body['aps'] = array(
                                                'alert' => 'Dealer Is Playing Now',
                                                'sound' => '3'
                                            );

                                            $payload = json_encode($body);

                                            $devicetoken = fetchdevicetoken($allpushids[$i], $dbc);

                                            $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                                            fwrite($fp, $msg, strlen($msg));
                                        }

                                        fclose($fp);
                                    }
                                    sleep(2);

                                    doblackjackcalculations($sessionid, $dbc, TRUE);
                                }
                            }
                        }
                    }
                    if (!$doingcalcs)
                        sendthepush('surrender', $userid, $dbc, $sessionid);
                }
                break;

            default :
                echo 'value of action variable is not supported';
                break;
        }
        mysqli_close($dbc);
    }
}
?>
