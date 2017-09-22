<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

$userid = $_GET['user_id'];
$sessonid = $_GET['session_id'];

//sanity check user passed variables
if (!is_numeric($userid) || !is_numeric($sessionid))
{
    // both expected to be numeric. If they were not, say Unsuccessful
    echo '<status>0</status>';
}
else
{
    // check if all all 6 players are registered and started playing
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    $query = "select * from omaha_bets where session_id = $sessionid";
    $result = mysqli_query($dbc, $query);

    if (mysqli_num_rows($result) != 6)
    {
        echo '<status>0</status>';
    }
    else
    {
        echo '<users>';

        $querym1 = "select status from omaha_bets where session_id = $sessionid and user_id = $userid";
        $resultm1 = mysqli_query($dbc, $querym1);
        if (mysqli_num_rows($resultm1) > 0)
        {
            $rowm1 = mysqli_fetch_row($resultm1);
            $firsttime = $rowm1[0];

            echo '<first_time>';
            echo $row12[0];
            echo '</first_time>';

            if ($firsttime == 0)
            {
                echo '</users>';
                exit();
            }
            else
            {
                // check if 6 players have started
                $querym3 = "select * from omaha_bets where session_id = $sessionid and status = 1";
                $resultm3 = mysqli_query($dbc, $querym3);
                if (mysqli_num_rows($result3) == 6)
                {
                    $query1 = "select status, datetime from omaha_game_data where session_id = $sessionid";
                    $result1 = mysqli_query($dbc, $query1);
                    if (mysqli_num_rows($result1) > 0)
                    {
                        $row1 = mysqli_fetch_row($result1);
                        $gamestatus = $row1[0];
                        $gametime = $row1[1];

                        if ($gamestatus == 0)
                        {
                            $pushids = array();
                            $rowidofdealer = 0;

                            while ($rowm3 = mysqli_fetch_array($resultm3))
                            {
                                array_push($pushids, $rowm3['user_id']);
                            }

                            // update dealertime
                            $query9 = "select * from omaha_bets where session_id= $sessionid and is_dealer = 0 order by datetime limit 1 ";
                            $result9 = mysqli_query($dbc, $query9);
                            if (mysqli_num_rows($result9) > 0)
                            {
                                $row9 = mysqli_fetch_row($result9);
                                $dealerid = $row9['user_id'];

                                $query10 = "update omaha_bets set datetime = '" . date('Y-m-d H:i:s', time()) . "', is_dealer=1 where session_id = $sessionid and user_id = $dealerid";
                                mysqli_query($dbc, $query10);

                                $rowidofdealer = $row9['id'];
                            }
                            else
                            {
                                // no one was dealer . make a dealer
                                $query100 = "update omaha_bets set is_dealer = 0 where session_id = $sessionid";
                                mysqli_query($dbc, $query100);

                                $query101 = "update omaha_bets set is_dealer = 1 where session_id = $sessionid limit 1 ";
                                mysqli_query($dbc, $query101);
                                
                                $rowidofdealer = 1;
                            }
                            
                            if($rowidofdealer < 4)
                            {
                                
                            }
                            $query2 = "select * from omaha_bets where player_status = 0 and session_id = $sessionid order by datetime limit 2";
                            $result2 = mysqli_query($dbc, $query2);

                            if (mysqli_num_rows($result2) > 0)
                            {
                                $count = 1;
                                while ($row2 = mysqli_fetch_array($result2))
                                {
                                    $amountbet = $count * 25;
                                    $newtime = date('Y-m-d H:i:s', time() + $amountbet);
                                    $query3 = "update omaha_bets set amount_bet = $amountbet, datetime = '$newtime', chips_available = " . 1500 - $amountbet . " where session_id = $sessionid and user_id =" . $row2['user_id'];
                                    $result3 = mysqli_query($dbc, $query3);

                                    $count++;
                                }

                                // activate the next player
                                $query4 = "select * from omaha_bets where player_status = 0 and session_id = $sessionid order by datetime limit 1";
                                $result4 = mysqli_query($dbc, $query4);

                                if (mysqli_num_rows($result4) > 0)
                                {
                                    $row4 = mysqli_query($dbc, $query4);
                                    $playeruserid = $row4['user_id'];

                                    $query5 = "update omaha_bets set player_status = 1 where user_id = $playeruserid and session_id = $sessionid";
                                    mysqli_query($dbc, $query5);

                                    // also update game status
                                    $newgametime = date('Y-m-d H:i:s', time() + 20);
                                    $query7 = "update omaha_game_data set datetime = '$newgametime', status = 2, round=1 where session_id = $sessionid";
                                    mysqli_query($dbc, $query7);

                                    echo '<activePlayer>';
                                    echo $playeruserid;
                                    echo '</activePlayer>';

                                    echo '<seconds>';
                                    echo 20;
                                    echo '<seconds>';

                                    $pushmessage = "Omaha has started. Its " . fetchname($playeruserid, $dbc) . "'s turn going on";
                                    sendpushtoplayers($pushids, $pushmessage);
                                }
                            }
                        }
                        else
                        {

                            // check if there is an active player
                            $query8 = "select * from omaha_bets where session_id = $sessionid and player_status = 1";
                            $result8 = mysqli_query($dbc, $query8);

                            if (mysqli_num_rows($result8) > 0)
                            {
                                $row8 = mysqli_fetch_row($result);
                                $thisuserid = $row8['user_id'];

                                //check if time of this user is remaining
                                if ((strtotime($gametime) - time()) > 0)
                                {
                                    // time is remaining for this player. so return it
                                    echo '<activePlayer>';
                                    echo $thisuserid;
                                    echo '</activePlayer>';

                                    echo '<seconds>';
                                    echo (strtotime($gametime) - time());
                                    echo '<seconds>';
                                }
                                else
                                {
                                    // time for this player has skipped
                                    //check this player
                                    foldPlayer($thisuserid, $sessionid, $dbc);

                                    // give turn to new player

                                    $query9 = "select * from omaha_bets where session_id = $sessionid and player_status = 0 and is_folded = 0 order by datetime limit 1";
                                    $result9 = mysqli_query($dbc, $result9);

                                    if (mysqli_num_rows($result) > 0)
                                    {
                                        $row9 = mysqli_fetch_row($result9);
                                        $thisuserid = $row9['user_id'];

                                        $query10 = "update omaha_bets set player_status = 1 where user_id = $thisuserid and session_id = $sessionid";
                                        mysqli_query($dbc, $query10);

                                        // also update time of game and return seconds remsining
                                        $query11 = "update omaha_game_data set datetime = '" . date("Y-m-d H:i:s", time() + 20) . "' where session_id = $sessionid";
                                        mysqli_query($dbc, $query11);

                                        echo '<activePlayer>';
                                        echo $thisuserid;
                                        echo '</activePlayer>';

                                        echo '<seconds>';
                                        echo 20;
                                        echo '<seconds>';
                                    }
                                    else
                                    {
                                        // check if everybody has bet equally
                                        $query21 = "select amount from omaha_bets where session_id = $sessionid";
                                        $result21 = mysqli_query($dbc, $query21);
                                        if (mysqli_num_rows($result21) > 0)
                                        {
                                            $allbetsarray = array();
                                            while ($row21 = mysqli_fetch_array($result21))
                                            {
                                                array_push($allbetsarray, $row21[0]);
                                            }
                                            if (count(array_unique($allbetsarray)) == 1)
                                            {
                                                startNextRound($sessionid, $dbc);
                                            }
                                        }


                                        // stat new round
                                        // activeate a player
                                        $query9 = "select * from omaha_bets where session_id = $sessionid and player_status = 0 and is_folded = 0 order by datetime limit 1";
                                        $result9 = mysqli_query($dbc, $result9);

                                        if (mysqli_num_rows($result) > 0)
                                        {
                                            $row9 = mysqli_fetch_row($result9);
                                            $thisuserid = $row9['user_id'];

                                            $query10 = "update omaha_bets set player_status = 1 where user_id = $thisuserid and session_id = $sessionid";
                                            mysqli_query($dbc, $query10);

                                            // also update time of game and return seconds remsining
                                            $query11 = "update omaha_game_data set datetime = '" . date("Y-m-d H:i:s", time() + 20) . "' where session_id = $sessionid";
                                            mysqli_query($dbc, $query11);

                                            echo '<activePlayer>';
                                            echo $thisuserid;
                                            echo '</activePlayer>';

                                            echo '<seconds>';
                                            echo 20;
                                            echo '<seconds>';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $query2 = "select table_type from table_gamesessions where session_id = $sessionid";
            $result2 = mysqli_query($dbc, $query2);
            if (mysqli_num_rows($result2) != 0)
            {
                while ($row2 = mysqli_fetch_array($result2))
                {
                    echo '<table_type>';
                    echo $row2[0];
                    echo '</table_type>';
                }
            }

            $query12 = "select * from omaha_game_data where session_id = $sessionid";
            $result12 = mysqli_query($dbc, $query12);

            if (mysqli_num_rows($result12) > 0)
            {
                $row12 = mysqli_fetch_row($result12);
                echo '<dealer_id>';
                echo $row12['dealer_userid'];
                echo '</dealer_id>';

                echo '<table_cards>';
                echo $row12['cards'];
                echo '</table_cards>';
            }

            // now form main tags to return 
            $query13 = "select 
                player_status, amount_bet, chips_available, cards, user_id, name, gold, chips, icon_name 
                from 
                omaha_bets 
                left join user_details on user_details.user_id = omaha_bets.user_id
                left join user_cash on user_cash.user_id = omaha_bets.user_id
                left join user_icon on user_icon.user_id =  omaha_bets.user_id
                where 
                session_id =$sessionid";

            $result13 = mysqli_query($dbc, $query13);
            if (mysqli_num_rows($result13) > 0)
            {
                while ($row13 = mysqli_fetch_array($result13))
                {
                    echo '<user>';

                    echo '<user_id>';
                    echo $row13['user_id'];
                    echo '</user_id>';

                    echo '<cards>';
                    echo $row13['cards'];
                    echo '</cards>';

                    echo '<status>';
                    echo $row13['player_status'];
                    echo '</status>';

                    echo '<name>';
                    echo $row13['name'];
                    echo '</name>';

                    echo '<gift>';

// fetch the oldest gift sent to this user only if this is user who has called the page
                    if ($row[4] == $userid)
                    {

                        $timezone = "Asia/Calcutta";
                        if (function_exists('date_default_timezone_set'))
                            date_default_timezone_set($timezone);

                        $currdate = date('Y-m-d  H:i:s');
                        $newdate = date('Y-m-d  H:i:s', strtotime($currdate . '+0 seconds'));

                        $query6 = "select * from gift_box where sent_to = $row[0] and status = 1 and datetime > '$newdate'";
                        $result6 = mysqli_query($dbc, $query6);

                        if (mysqli_num_rows($result6) > 0)
                        {
                            
                        }
                        else
                        {

                            // fetch the oldest 1 unread gift
                            $query7 = "select * from gift_box where status = 0 and session_id = $sessionid and sent_to = $userid order by datetime asc limit 1";
                            $result7 = mysqli_query($dbc, $query7);

                            if (mysqli_num_rows($result7) == 1)
                            {

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
                                if ($result8)
                                {

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



                                if (mysqli_num_rows($result9) > 0)
                                {
                                    while ($row9 = mysqli_fetch_array($result9))
                                    {
                                        $otherplayersid = $row9['user_id'];

                                        setmessage($message2, $otherplayersid, $giftid, 3, $dbc);

                                        array_push($pushids, $otherplayersid);
                                    }
                                }


                                // check if there are items in array
                                if (count($pushids) > 0)
                                {
                                    $passphrase = 'abcd';
                                    $ctx = stream_context_create();
                                    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                                    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                                    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                                    if (!$fp)
                                        exit("Failed to connect: $err $errstr" . PHP_EOL);

                                    for ($i = 0; $i < count($pushids); $i++)
                                    {

                                        if ($i == 0)
                                        {
                                            $body['aps'] = array(
                                                'alert' => $nameofreceiver . ' received ' . $giftname . ' as a gift from You',
                                                'sound' => '3'
                                            );
                                        }
                                        else
                                        {

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
                    }
                    else
                    {

                        // check if this user has any active gift

                        $timezone = "Asia/Calcutta";
                        if (function_exists('date_default_timezone_set'))
                            date_default_timezone_set($timezone);

                        $currdate = date('Y-m-d  H:i:s');
                        $newdate = date('Y-m-d  H:i:s', strtotime($currdate . '+50 seconds'));

                        $query6 = "select * from gift_box where sent_to = $row[0] and status = 1 and datetime > '$newdate'";
                        $result6 = mysqli_query($dbc, $query6);

                        if (mysqli_num_rows($result6) > 0)
                        {

                            // fetch gift name and remaining time
                            while ($row6 = mysqli_fetch_array($result6))
                            {

                                echo '<gift_name>';
                                echo $row6['gift_name'];
                                echo '</gift_name>';
                            }
                        }
                    }

                    echo '</gift>';

                    // fetch latest message of this player
                    $query5 = "select message, status, id from game_messages where session_id = $sessionid and user_id= $row[4] order by datetime desc limit 1";
                    $result5 = mysqli_query($dbc, $query5);

                    $printmessage = NULL;
                    $printmessagetype = NULL;
                    $handlerid = NULL;

                    if (mysqli_num_rows($result5) > 0)
                    {
                        while ($row5 = mysqli_fetch_array($result5))
                        {
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

                    echo '</user>';

                    // delete a row from message table now
                    $query4 = "delete from messages where handler_id = $handlerid and message_type=3 and user_id = $userid";
                    mysqli_query($dbc, $query4);
                }
            }

            echo '</users>';
        }
    }
    mysqli_close($dbc);
}
?>