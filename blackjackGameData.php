<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

error_reporting(E_ALL);

// check if required fields were passed in url
if (is_null($_GET['session_id']) || is_null($_GET['user_id'])) {
    echo 'session_id or user_id was not passed';
} else {
    setTimeZone();

    // grab the variables
    $sessionid = $_GET['session_id'];
    $userid = $_GET['user_id'];

    //sanity check the variable
    if (!is_numeric($sessionid)) {
        echo 'There is unexpected error';
    } else {

        $pushids = array();

        echo '<users>';

        // check no of active players of this game
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");

        $query12 = "select status from games_players where session_id = $sessionid and user_id = $userid";
        $result12 = mysqli_query($dbc, $query12);
        if (mysqli_num_rows($result12) != 0) {
            $row12 = mysqli_fetch_row($result12);

            // if the user was first time dont provide any information 
            if ($row12[0] == 0) {
                //check if the game already exists
                $query = "select * from blackjack_game_data where session_id = $sessionid";
                $result = mysqli_query($dbc, $query);

                if (mysqli_num_rows($result) == 0) {
                    // this is first player of this game
                    $query2 = "insert into blackjack_game_data(session_id) values ($sessionid)";
                    mysqli_query($dbc, $query2);

                    $query4 = "insert into blackjack_bets( user_id, session_id) values( $userid, $sessionid)";
                    mysqli_query($dbc, $query4);

                    // create a dealer account
                    $query5 = "insert into blackjack_bets(user_id, session_id) values (0, $sessionid)";
                    mysqli_query($dbc, $query5);

                    setlatestmessage($sessionid, 0, 'Welcome', 0, $dbc);
                } else {
                    //check if this player is already a part of the game
                    $query9 = "select * from blackjack_bets where session_id = $sessionid and user_id = $userid";
                    $result9 = mysqli_query($dbc, $query9);

                    if (mysqli_num_rows($result9) == 0) {

                        // also make an entry in bets table
                        $query5 = "insert into blackjack_bets( user_id, session_id) values( $userid, $sessionid)";
                        mysqli_query($dbc, $query5);

                        $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d H:i:s') . '+20 seconds'));
                        $query6 = "update blackjack_bets set datetime = '$date2' where session_id = $sessionid and user_id = 0";
                        mysqli_query($dbc, $query6);
                    }
                }

                setlatestmessage($sessionid, $userid, 'Start Playing Game', 1, $dbc);

                // update this player status as online
                $query3 = "update games_players set status = '1' where session_id = $sessionid and user_id = $userid";
                mysqli_query($dbc, $query3);


                //send push to others 
                $queryn1 = "select user_id from blackjack_bets where session_id =  $sessionid and user_id not in ($userid,0)";
                $result1 = mysqli_query($dbc, $queryn1);
                if (mysqli_num_rows($result1) > 0) {
                    while ($row1 = mysqli_fetch_array($result1)) {
                        array_push($pushids, $row1[0]);
                    }
                }

                if ($pushids > 0) {
                    $passphrase = 'abcd';
                    $ctx = stream_context_create();
                    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                    if (!$fp)
                        exit("Failed to connect: $err $errstr" . PHP_EOL);
                    for ($i = 0; $i < count($pushids); $i++) {

                        $body['aps'] = array(
                            'alert' => fetchname($userid, $dbc) . ' Starts playing BlackJack',
                            'sound' => '3'
                        );

                        $payload = json_encode($body);

                        $devicetoken = fetchdevicetoken($pushids[$i], $dbc);

                        $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                        fwrite($fp, $msg, strlen($msg));
                    }
                    fclose($fp);
                }
            }

            $querym1 = "select status, datetime from blackjack_game_data where session_id = $sessionid";
            $resultm1 = mysqli_query($dbc, $querym1);

            if (mysqli_num_rows($resultm1) > 0) {
                $rowm1 = mysqli_fetch_row($resultm1);

                $blackjackstatus = $rowm1[0];
                $blackjacktime = $rowm1[1];

                if ($blackjackstatus == 0) {
                    $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));
                    $querym2 = "update blackjack_game_data set status = 1, datetime = '$date2' where session_id = $sessionid";
                    mysqli_query($dbc, $querym2);

                    echo '<blackjackStatus>1</blackjackStatus>';
                    echo '<seconds>30</seconds>';
                } elseif ($blackjackstatus == 1) {
                    // check if 30 seconds had passed
                    if (( strtotime($blackjacktime) - time()) > 0) {
                        echo '<blackjackStatus>';
                        echo 1;
                        echo '</blackjackStatus>';

                        echo '<seconds>';
                        echo (strtotime($blackjacktime) - time());
                        echo '</seconds>';
                    } else {
                        givecardstoall($dbc, $sessionid);

                        //check how much time have passed
                        $playerstoskip = floor((time() - strtotime($blackjacktime)) / 20);
                        $playerstoskip2 = $playerstoskip;

                        $querym18 = "select * from blackjack_bets where session_id = $sessionid and amount = '0' and cards = '' and user_id !=0  order by datetime ";
                        $resultm18 = mysqli_query($dbc, $querym18);

                        if (mysqli_num_rows($resultm18) > 0) {
                            while ($rowm18 = mysqli_fetch_array($resultm18)) {
                                $myuserid = $rowm18[1];
                                $message = "Missed Bet Time";
                                setlatestmessage($sessionid, $myuserid, $message, 1, $dbc);
                            }
                        }



                        $querym1 = "select * from blackjack_bets where session_id = $sessionid and amount != '0'  and user_id != 0 order by datetime";
                        $resultm1 = mysqli_query($dbc, $querym1);
                        $noofplayers = mysqli_num_rows($resultm1);


                        $set = FALSE;
                        $whetherskipped = FALSE;

                        if ($noofplayers > 0) {
                            while ($rowm1 = mysqli_fetch_array($resultm1)) {

                                $playeruserid = $rowm1[1];
                                $playerstatus = $rowm1[5];
                                $playerstatusexploded = explode(':', $playerstatus);


                                for ($i = 0; $i < count($playerstatusexploded); $i++) {

                                    if ($playerstoskip > 0) {
                                        //set latest message
                                        $playerstatusexploded[$i] = '2';
                                        $newstatus = join(':', $playerstatusexploded);

                                        $querym6 = "update blackjack_bets set player_status = '$newstatus' where user_id = $playeruserid and session_id = $sessionid";
                                        mysqli_query($dbc, $querym6);

                                        setlatestmessage($sessionid, $playeruserid, 'Auto Stand', 0, $dbc);
                                        $playerstoskip--;
                                        $whetherskipped = TRUE;
                                    } else {
                                        if (!$set) {
                                            if ($playerstatusexploded[$i] == 0) {
                                                $playerstatusexploded[$i] = 1;
                                                $newplayerstatus = join(':', $playerstatusexploded);

                                                $queryn1 = "update blackjack_bets set player_status = '$newplayerstatus' where session_id = $sessionid and user_id = $playeruserid";
                                                mysqli_query($dbc, $queryn1);

                                                $secondstoadd = ( $playerstoskip2 * 20) + 20;

                                                $date2 = date('Y-m-d  H:i:s', strtotime($blackjacktime . '+' . $secondstoadd . ' seconds'));
                                                $queryn2 = "update blackjack_game_data set status = 2, datetime='$date2'
                                          where session_id = $sessionid";

                                                mysqli_query($dbc, $queryn2);

                                                echo '<blackjackStatus>';
                                                echo 2;
                                                echo '</blackjackStatus>';

                                                echo '<seconds>';
                                                echo (strtotime($blackjacktime) - time() + 20 + ( $playerstoskip2 * 20));
                                                echo '</seconds>';


                                                $set = TRUE;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));
                            $querym2 = "update blackjack_game_data set status = 1, datetime = '$date2' where session_id = $sessionid";
                            mysqli_query($dbc, $querym2);

                            echo '<blackjackStatus>1</blackjackStatus>';
                            echo '<seconds>30</seconds>';
                        }
                        if (!$set && $whetherskipped) {
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

                            doblackjackcalculations($sessionid, $dbc);

                            echo '<blackjackStatus>';
                            echo 3;
                            echo '</blackjackStatus>';
                        }
                    }
                } elseif ($blackjackstatus == 2) {
                    $playerstoskip = floor((time() - strtotime($blackjacktime)) / 20);
                    $playerstoskip2 = $playerstoskip;

                    $queryn1 = "select * from blackjack_bets where session_id = $sessionid and player_status like '%1%'";
                    $resultn1 = mysqli_query($dbc, $queryn1);

                    if (mysqli_num_rows($resultn1) > 0) {

                        $rown1 = mysqli_fetch_row($resultn1);
                        $activeplayerid = $rown1[1];
                        $activeplayerstatus = $rown1[5];

                        // ch9eck if it is still going on
                        if ((strtotime($blackjacktime) - time()) > 0) {
                            echo '<blackjackStatus>';
                            echo 2;
                            echo '</blackjackStatus>';

                            echo '<seconds>';
                            echo (strtotime($blackjacktime) - time());
                            echo '</seconds>';
                        } else {
                            $activeplayerstatusexploded = explode(':', $activeplayerstatus);
                            for ($i = 0; $i < count($activeplayerstatusexploded); $i++) {
                                if ($activeplayerstatusexploded[$i] == 1) {
                                    $activeplayerstatusexploded[$i] = "2";
                                    $newstatus = join(':', $activeplayerstatusexploded);

                                    $queryn3 = "update blackjack_bets set player_status = '$newstatus' where session_id = $sessionid and user_id = $activeplayerid";
                                    mysqli_query($dbc, $queryn3);

                                    setlatestmessage($sessionid, $activeplayerid, 'Auto Stand', 1, $dbc);

                                    sendthepush("hit and stand", $activeplayerid, $dbc, $sessionid);

                                    sleep(2);
                                }
                            }

                            //set status of all to stand
                            //check which player is going on
                            $querym4 = "select * from blackjack_bets where session_id = $sessionid and player_status like '%0%' and user_id != '0' and amount != '0' and cards != ''  order by datetime";
                            $resultm4 = mysqli_query($dbc, $querym4);
                            if (mysqli_num_rows($resultm4) > 0) {
                                $found = FALSE;
                                $whetherskipped = FALSE;

                                while ($rowm4 = mysqli_fetch_array($resultm4)) {

                                    $thisuserid = $rowm4['user_id'];
                                    $thisstatus = $rowm4['player_status'];
                                    $thisstatusexploded = explode(':', $thisstatus);

                                    for ($i = 0; $i < count($thisstatusexploded); $i++) {

                                        if ($playerstoskip > 0) {
                                            $thisstatusexploded[$i] = '2';
                                            $newstatus = join(':', $thisstatusexploded);

                                            $querym6 = "update blackjack_bets set player_status = '$newstatus' where user_id = $thisuserid and session_id = $sessionid";
                                            mysqli_query($dbc, $querym6);


                                            //set latest message
                                            setlatestmessage($sessionid, $thisuserid, 'Auto Stand', 0, $dbc);
                                            $playerstoskip--;

                                            $whetherskipped = TRUE;
                                        } else {
                                            if (!$found) {
                                                if ($thisstatusexploded[$i] == 0) {
                                                    // make it 1
                                                    $thisstatusexploded[$i] = '1';
                                                    $newstatus = join(':', $thisstatusexploded);

                                                    $querym6 = "update blackjack_bets set player_status = '$newstatus' where user_id = $thisuserid and session_id = $sessionid";
                                                    mysqli_query($dbc, $querym6);

                                                    $secondstoadd = ( $playerstoskip2 * 20) + 20;

                                                    $date2 = date('Y-m-d  H:i:s', strtotime($blackjacktime . '+' . $secondstoadd . ' seconds'));

                                                    $queryn2 = "update blackjack_game_data set status = 2, datetime='$date2'
                                                                       where session_id = $sessionid";

                                                    mysqli_query($dbc, $queryn2);

                                                    echo '<blackjackStatus>';
                                                    echo 2;
                                                    echo '</blackjackStatus>';

                                                    echo '<seconds>';
                                                    echo (strtotime($blackjacktime) - time() + 20 + ( $playerstoskip2 * 20));
                                                    echo '</seconds>';

                                                    sendthepush("stand and push", $thisuserid, $dbc, $sessionid);

                                                    $found = TRUE;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                if (!$found && $whetherskipped) {
                                    // means all have been skipped.
                                    //perform calculations 
                                    //end the game
                                    if ((strtotime($blackjacktime) - time() + 40 + ( $playerstoskip2 * 20)) > 0) {

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
                                        echo '<blackjackStatus>';
                                        echo 3;
                                        echo '</blackjackStatus>';
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

                                        echo '<blackjackStatus>';
                                        echo 3;
                                        echo '</blackjackStatus>';
                                    }
                                }
                            } else {

                                if ((strtotime($blackjacktime) - time() + 40 + ( $playerstoskip2 * 20)) > 0) {



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
                                    echo '<blackjackStatus>';
                                    echo 3;
                                    echo '</blackjackStatus>';
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


                                    doblackjackcalculations($sessionid, $dbc);
                                    echo '<blackjackStatus>';
                                    echo 3;
                                    echo '</blackjackStatus>';
                                }
                            }
                        }
                    } else {
                        
                    }
                } elseif ($blackjackstatus == 3) {
                    $querym1 = "select player_status from blackjack_bets where session_id = $sessionid and user_id = 0";
                    $resultm1 = mysqli_query($dbc, $querym1);

                    if (mysqli_num_rows($resultm1) > 0) {
                        $rowm1 = mysqli_fetch_row($resultm1);
                        if ($rowm1[0] == 0) {
                            if (( strtotime($blackjacktime) - time() + 30) > 0) {
                                doblackjackcalculations($sessionid, $dbc, TRUE);

                                echo '<blackjackStatus>';
                                echo 0;
                                echo '</blackjackStatus>';
                            } else {
                                doblackjackcalculations($sessionid, $dbc, TRUE);

                                echo '<blackjackStatus>';
                                echo 0;
                                echo '</blackjackStatus>';
                            }
                        }
//                              
                        else {
                            echo '<blackjackStatus>';
                            echo 3;
                            echo '</blackjackStatus>';
                        }
                    }
                }
            }
        }

        $query2 = "select table_type from table_gamesessions where session_id = $sessionid";
        $result2 = mysqli_query($dbc, $query2);

        if (mysqli_num_rows($result2) != 0) {
            while ($row2 = mysqli_fetch_array($result2)) {
                echo '<table_type>';
                echo $row2[0];
                echo '</table_type>';
            }

            $querymy5 = "select * from blackjack_bets where user_id =0 and session_id = $sessionid";
            $resultmy5 = mysqli_query($dbc, $querymy5);

            if (mysqli_num_rows($resultmy5) > 0) {
                $rowmy5 = mysqli_fetch_row($resultmy5);


                echo '<dealerCards>';
                echo $rowmy5[4];
                echo '</dealerCards>';

                echo '<dealerTotal>';
                // check if its dealer's turn  going on
                $gamestatus = 0;

                $querya = "select status from blackjack_game_data where session_id = $sessionid";
                $resulta = mysqli_query($dbc, $querya);
                if (mysqli_num_rows($resulta) > 0) {
                    $rowa = mysqli_fetch_row($resulta);
                    $gamestatus = $rowa[0];
                }

                // fetch second card of dealer
                $querya1 = "select cards from blackjack_bets where user_id=0 and session_id = $sessionid";
                $resulta1 = mysqli_query($dbc, $querya1);
                if ($gamestatus != 3) {

                    if (mysqli_num_rows($resulta1) > 0) {
                        $rowa1 = mysqli_fetch_row($resulta1);
                        $dealercards = $rowa1[0];

                        // break dealercards by comma
                        if ($dealercards != "") {
                            $dealercardsexploded = explode(',', $dealercards);
                            $val = getcardvalue($dealercardsexploded[1]);
                            if ($val == 1)
                                echo '11';
                            else
                                echo $val;
                        }
                    }
                } else {
                    if (mysqli_num_rows($resulta1) > 0) {
                        $rowa1 = mysqli_fetch_row($resulta1);
                        $dealercards = $rowa1[0];

                        if ($dealercards != "") {
                            $totalofcards = 0;
                            $total11takendealer = 0;
                            // break cards by a comma
                            $cardsofthisuser = explode(',', $dealercards);
                            for ($j = 0; $j < count($cardsofthisuser); $j++) {
                                if (($cardsofthisuser[$j] == 1 ) || ($cardsofthisuser[$j] == 14 ) || ($cardsofthisuser[$j] == 27 ) || ($cardsofthisuser[$j] == 40 )) {
                                    $totalofcards += 11;
                                    $total11takendealer++;

                                    if ($totalofcards > 21) {
                                        if ($total11takendealer > 0) {
                                            $totalofcards -= 10;
                                            $total11takendealer--;
                                        }
                                    }
                                } else {
                                    $totalofcards += getcardvalue($cardsofthisuser[$j]);
                                    if ($totalofcards > 21) {
                                        if ($total11takendealer > 0) {
                                            $totalofcards -=10;
                                            $total11takendealer--;
                                        }
                                    }
                                }
                            }
                            echo $totalofcards;
                        }
                    }
                }
                echo '</dealerTotal>';

                $querymy6 = "select message, status, id from game_messages where session_id = $sessionid and user_id= 0 order by datetime desc limit 1 ";
                $resultmy6 = mysqli_query($dbc, $querymy6);

                if (mysqli_num_rows($resultmy6) > 0) {
                    $rowmy6 = mysqli_fetch_row($resultmy6);


                    echo '<dealerMessage>';
                    echo $rowmy6[0];
                    echo '</dealerMessage>';
                }
            }
            $query = "select user_details.user_id,games_players.status,invitations.status, name, gold, chips, icon_name, blackjack_bets.player_status, cards, amount, blackjack_bets.confetti_status, blackjack_bets.confetti_value
              from 
                user_details left join user_cash on user_details.user_id = user_cash.user_id 
                left join user_icon on user_details.user_id = user_icon.user_id 
                left join games_players on user_details.user_id = games_players.user_id 
                    and games_players.session_id = $sessionid
                left join invitations on user_details.user_id = invitations.invitation_to 
                    and invitations.session_id = $sessionid
              left join blackjack_bets on user_details.user_id = blackjack_bets.user_id 
                    and blackjack_bets.session_id = $sessionid and blackjack_bets.user_id != 0
                having user_details.user_id in 
                    (SELECT user_id
                    FROM games_players where session_id = $sessionid
                    union   
                    select invitation_to from invitations 
                    where session_id = $sessionid)
                        order by invitations.datetime asc, games_players.datetime asc 
            ";

            $result = mysqli_query($dbc, $query);



            if (mysqli_num_rows($result) != 0) {
                while ($row = mysqli_fetch_array($result)) {
                    echo '<user>';

                    echo '<user_id>';
                    echo $row[0];
                    echo '</user_id>';

                    echo '<cards>';
                    echo $row['cards'];
                    echo '</cards>';

                    echo '<sumOfCards>';

                    $sum_cards = array();

                    if ($row['cards'] != "") {
                        // try to explode cards with a ':'
                        $sum_cardsExploded = explode(':', $row['cards']);
                        for ($i = 0; $i < count($sum_cardsExploded); $i++) {

                            $totalofcards = 0;
                            $total11taken = 0;
                            // break cards by a comma
                            $cardsofthisuser = explode(',', $sum_cardsExploded[$i]);

                            for ($j = 0; $j < count($cardsofthisuser); $j++) {
                                if (($cardsofthisuser[$j] == 1 ) || ($cardsofthisuser[$j] == 14 ) || ($cardsofthisuser[$j] == 27 ) || ($cardsofthisuser[$j] == 40 )) {
                                    $totalofcards += 11;
                                    $total11taken++;

                                    if ($totalofcards > 21) {
                                        if ($total11taken > 0) {
                                            $totalofcards -= 10;
                                            $total11taken--;
                                        }
                                    }
                                } else {
                                    $totalofcards += getcardvalue($cardsofthisuser[$j]);
                                    if ($totalofcards > 21) {
                                        if ($total11taken > 0) {
                                            $totalofcards -=10;
                                            $total11taken--;
                                        }
                                    }
                                }
                            }
                            array_push($sum_cards, $totalofcards);
                        }
                    }
                    echo join(':', $sum_cards);
                    echo '</sumOfCards>';

                    if (is_null($row[1]) && !is_null($row[2])) {

                        // it means just an invitation is sent to this user
                        echo '<status>';
                        echo '1';
                        echo '</status>';
                    } elseif (!is_null($row[1]) && is_null($row[2])) {
                        echo '<status>';
                        echo '3';
                        echo '</status>';
                    }

                    echo '<confettiStatus>';
                    if (strtotime($row[10]) - time() > 0) {
                        echo $row[11];
                    } else {
                        echo '0';
                    }
                    echo '</confettiStatus>';

                    echo '<name>';
                    echo $row['name'];
                    echo '</name>';

                    echo '<handStatus>';
                    echo $row[7];
                    echo '</handStatus>';

                    echo '<gift>';

// fetch the oldest gift sent to this user only if this is user who has called the page
                    if ($row[0] == $userid) {

                        $timezone = "Asia/Calcutta";
                        if (function_exists('date_default_timezone_set'))
                            date_default_timezone_set($timezone);

                        $currdate = date('Y-m-d  H:i:s');
                        $newdate = date('Y-m-d  H:i:s', strtotime($currdate . '+0 seconds'));

                        $query6 = "select * from gift_box where sent_to = $row[0] and status = 1 and datetime > '$newdate'";
                        $result6 = mysqli_query($dbc, $query6);

                        if (mysqli_num_rows($result6) > 0) {
                            
                        } else {

                            // fetch the oldest 1 unread gift
                            $query7 = "select * from gift_box where status = 0 and session_id = $sessionid and sent_to = $userid order by datetime asc limit 1";
                            $result7 = mysqli_query($dbc, $query7);

                            if (mysqli_num_rows($result7) == 1) {

                                // if a row was returned, set its status as read, increase the time to 20 seconds, and set latest message and send push to all
                                $row7 = mysqli_fetch_row($result7);
                                $giftid = $row7[0];
                                $giftname = $row7[4];
                                $giftsentby = $row7[1];

                                // now update time and status of this row
                                $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+70 seconds'));

                                $query8 = "update gift_box set datetime = '$date2', status = 1 where id = $giftid";
                                $result8 = mysqli_query($dbc, $query8);

                                // it the row was updated successfully
                                if ($result8) {

                                    echo '<gift_name>';
                                    echo $giftname;
                                    echo '</gift_name>';
                                }

                                // set latest message of this user
                                $sendersname = fetchname($giftsentby, $dbc);
                                $message = "Gift from " . $sendersname;

                                setlatestmessage($sessionid, $userid, $message, 1, $dbc);

                                //find all players of this game and set message for all other players of this game
                                $query9 = "select * from games_players where session_id = $sessionid and user_id not in ($userid, $giftsentby)";
                                $result9 = mysqli_query($dbc, $query9);

                                array_push($pushids, $giftsentby);

                                // form a message
                                $nameofreceiver = fetchname($userid, $dbc);
                                $gametype = fetchgametype($sessionid);

                                $message2 = $nameofreceiver . ' received ' . $giftname . ' as a gift ' . ' from ' . $sendersname . ' in ' . $gametype . ' game.';



                                if (mysqli_num_rows($result9) > 0) {
                                    while ($row9 = mysqli_fetch_array($result9)) {
                                        $otherplayersid = $row9['user_id'];

                                        setmessage($message2, $otherplayersid, $giftid, 3, $dbc);

                                        array_push($pushids, $otherplayersid);
                                    }
                                }


                                // check if there are items in array
                                if (count($pushids) > 0) {
                                    $passphrase = 'abcd';
                                    $ctx = stream_context_create();
                                    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                                    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                                    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                                    if (!$fp)
                                        exit("Failed to connect: $err $errstr" . PHP_EOL);

                                    for ($i = 0; $i < count($pushids); $i++) {

                                        if ($i == 0) {
                                            $body['aps'] = array(
                                                'alert' => $nameofreceiver . ' received ' . $giftname . ' as a gift from You',
                                                'sound' => '3'
                                            );
                                        } else {

                                            $body['aps'] = array(
                                                'alert' => $message2,
                                                'sound' => '3'
                                            );
                                        }

                                        $payload = json_encode($body);

                                        $devicetoken = fetchdevicetoken($pushids[$i], $dbc);

                                        $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                                        fwrite($fp, $msg, strlen($msg));
                                    }

                                    fclose($fp);
                                }
                            }
                        }
                    } else {

                        // check if this user has any active gift

                        $timezone = "Asia/Calcutta";
                        if (function_exists('date_default_timezone_set'))
                            date_default_timezone_set($timezone);

                        $currdate = date('Y-m-d  H:i:s');
                        $newdate = date('Y-m-d  H:i:s', strtotime($currdate . '+50 seconds'));

                        $query6 = "select * from gift_box where sent_to = $row[0] and status = 1 and datetime > '$newdate'";
                        $result6 = mysqli_query($dbc, $query6);

                        if (mysqli_num_rows($result6) > 0) {

                            // fetch gift name and remaining time
                            while ($row6 = mysqli_fetch_array($result6)) {

                                echo '<gift_name>';
                                echo $row6['gift_name'];
                                echo '</gift_name>';
                            }
                        }
                    }

                    echo '</gift>';

                    // fetch latest message of this player
                    $query5 = "select message, status, id from game_messages where session_id = $sessionid and user_id= $row[0] order by datetime desc limit 1";
                    $result5 = mysqli_query($dbc, $query5);

                    $printmessage = NULL;
                    $printmessagetype = NULL;
                    $handlerid = NULL;

                    if (mysqli_num_rows($result5) > 0) {
                        while ($row5 = mysqli_fetch_array($result5)) {
                            $printmessage = $row5['message'];
                            $printmessagetype = $row5[1];
                            $handlerid = $row5['id'];
                        }
                    }
                    echo '<message_type>';
                    echo $printmessagetype;
                    echo '</message_type>';

                    echo '<message>';
                    echo $printmessage;
                    echo '</message>';

                    echo '<gold>';
                    echo $row['gold'];
                    echo '</gold>';

                    echo '<betStatus>';

                    if ($row[9] != 0)
                        echo 1;
                    else
                        echo 0;

                    echo '</betStatus>';

                    echo '<chips>';
                    echo $row['chips'];
                    echo '</chips>';

                    echo '<chips1>';
                    echo number_format($row['chips']);
                    echo '</chips1>';

                    echo '<chips2>';
                    echo convertBackChips($row['chips']);
                    echo '</chips2>';

                    echo '<icon_name>';
                    echo $row['icon_name'];
                    echo '</icon_name>';

                    echo '<playerStatus>';

                    $statusexploded = explode(':', $row[7]);
                    if (count($statusexploded) == 1)
                        echo $row[7];
                    else {
                        if (($statusexploded[0] == 1) || ($statusexploded[1] == 1))
                            echo 1;
                        else
                            echo $row[7];
                    }

                    echo '</playerStatus>';

                    // also need to add total of cards available to the user
                    echo '<total>';

                    echo '</total>';

                    echo '</user>';

                    // delete a row from message table now
                    $query4 = "delete from messages where handler_id = $handlerid and message_type=3 and user_id = $userid";
                    mysqli_query($dbc, $query4);
                }
            }
        }
        echo '</users>';
        mysqli_close($dbc);
    }
}
?>
