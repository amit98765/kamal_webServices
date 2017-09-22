<?php

require_once 'variables/dbconnectionvariables.php';

//****************************************************************************************

function setmessage($message, $receiversid, $handlerid, $messagetype = 3, $dbc = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "insert into messages(message, message_type, user_id, handler_id) values ('$message', $messagetype, $receiversid, $handlerid)";
    mysqli_query($dbc, $query);
    if (mysqli_affected_rows($dbc) == 1) {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    } else {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//****************************************************************************************
//
//****************************************************************************************
function setchips($userid, $chips, $dbc = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "update user_cash set chips = $chips where user_id = $userid";
    if (mysqli_query($dbc, $query)) {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    } else {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

// **************************************************************************************
//
// ***********************************************************************************
function getchips($userid, $dbc = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $noofchips = NULL;

    $query = "select chips from user_cash where user_id = $userid";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) != 0) {
        while ($row = mysqli_fetch_array($result)) {
            $noofchips = $row[0];
        }
    }
    if ($toclose)
        mysqli_close($dbc);
    return $noofchips;
}

// ****************************************************************************************
//
//
//****************************************************************************************
function fetchdevicetoken($messageto, $dbc = FALSE, $isGame = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

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
    if ($toclose)
        mysqli_close($dbc);

    return $devicetokenofreceiver;
}

////****************************************************************************************
//
//
//--------------------------------------------------------------------------------------
//
//****************************************************************************************
function fetchgametype($sessionid, $dbc = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "select game_type from table_gamesessions where session_id = " . $sessionid;

    $result = mysqli_query($dbc, $query);

    $gametype = "";
    if (mysqli_num_rows($result) == 1) {
        while ($row = mysqli_fetch_array($result)) {
            $gametype = $row[0];
        }
    }
    if ($toclose)
        mysqli_close($dbc);

    return $gametype;
}

//********************************************************************************
//
//****************************************************************************************
function fetchname($messagefrom, $dbc = FALSE) {

    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");

        $toclose = TRUE;
    }

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
    if ($toclose)
        mysqli_close($dbc);
    return $nameinpush;
}

//****************************************************************************************
//
//
//****************************************************************************************
function setlatestmessage($sessionid, $userid, $message, $status = 0, $dbc = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // there was no row, so just add a row
    $query2 = "insert into game_messages(session_id, user_id, message, status) values ( $sessionid, $userid, '$message', $status)";
    mysqli_query($dbc, $query2);

    // if a row was affected, close connection and send true
    if (mysqli_affected_rows($dbc) == 1) {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
    else {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************

function increasedecreasechips($userid, $chips, $status, $dbc = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // fetch no of chips of this user
    $alreadyhavechips = getchips($userid);

    // check if we have got the chips
    if (is_null($alreadyhavechips)) {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    } else {
        // get new chips count
        if ($status == 1) {
            $newchipscount = $alreadyhavechips + $chips;
        } else {
            $newchipscount = $alreadyhavechips - $chips;
        }
        // set this as the no of chips of this user
        $done = setchips($userid, $newchipscount, $dbc);



        // check if it is done successfully
        if ($done) {

            if ($toclose)
                mysqli_close($dbc);

            return TRUE;
        }
        else {

            if ($toclose)
                mysqli_close($dbc);

            return FALSE;
        }
    }
}

//
//****************************************************************************************
//
//****************************************************************************************

function format_cash($cash) {

    // strip any commas
    $cash = (0 + STR_REPLACE(',', '', $cash));

    // make sure it's a number...
    IF (!IS_NUMERIC($cash)) {
        RETURN FALSE;
    }

    // filter and format it
    IF ($cash > 1000000000000) {
        RETURN ROUND(($cash / 1000000000000), 1) . ' T';
    } ELSEIF ($cash > 1000000000) {
        RETURN ROUND(($cash / 1000000000), 1) . ' B';
    } ELSEIF ($cash > 1000000) {
        RETURN ROUND(($cash / 1000000), 1) . ' M';
    } ELSEIF ($cash > 1000) {
        RETURN ROUND(($cash / 1000), 1) . ' K';
    }

    RETURN NUMBER_FORMAT($cash);
}

//****************************************************************************************
function setlatestmessageid($sessionid, $userid, $message, $status = 0, $dbc = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // there was no row, so just add a row
    $query2 = "insert into game_messages(session_id, user_id, message, status) values ( $sessionid, $userid, '$message', $status)";
    mysqli_query($dbc, $query2);

    // if a row was affected, close connection and send true
    if (mysqli_affected_rows($dbc) == 1) {
        $id = mysqli_insert_id($dbc);

        if ($toclose)
            mysqli_close($dbc);

        return $id;
    }

    if ($toclose)
        mysqli_close($dbc);

    return FALSE;
}

//
//****************************************************************************************
//
//****************************************************************************************

function checkrouletteresult($selectedcases, $winningnumber) {

    // check if the winning number exists in selected cases
    if (in_array($winningnumber, $selectedcases, TRUE)) {

        return TRUE;
    } else {

        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************


function getroulettemultiplier($selectedcasesarray, $winningnumber) {

    // we already know that the person is winner
    $multiple = 1;

    // if a single number was bet
    if (count($selectedcasesarray) == 1) {

        $multiple = 37;
    } elseif (count($selectedcasesarray) == 2) {

        $multiple = 18;
    } elseif (count($selectedcasesarray) == 3) {

        $multiple = 12;
    } elseif (count($selectedcasesarray) == 4) {

        $multiple = 9;
    } elseif (count($selectedcasesarray) == 6) {

        $multiple = 6;
    } elseif (count($selectedcasesarray) == 12) {

        $multiple = 3;
    } elseif (count($selectedcasesarray) == 18) {

        $multiple = 2;
    }
    return $multiple;
}

//
//****************************************************************************************
//
//****************************************************************************************


function rouletteGameSeconds($sessionid, $dbc = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // set time one first
    setTimeZone();

    $query3 = "select datetime from roulette_game_data where session_id = $sessionid";
    $result3 = mysqli_query($dbc, $query3);

    if (mysqli_num_rows($result3) != 0) {
        $row3 = mysqli_fetch_row($result3);
        $time = strtotime($row3[0]) - time();

        // this is the time remaining for roulette
        if ($time > 0) {
            if ($toclose)
                mysqli_close($dbc);
            return $time;
        }
        else {
            // initialte the session again

            $date3 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+47 seconds'));
            $query7 = "update roulette_game_data set datetime = '$date3' where session_id = $sessionid";
            mysqli_query($dbc, $query7);

            if (mysqli_affected_rows($dbc) == 1) {
                if ($toclose)
                    mysqli_close($dbc);
                return 45;
            } else {
                if ($toclose)
                    mysqli_close($dbc);
                return FALSE;
            }
        }
    } else {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************


function setTimeZone() {

    $timezone = "Asia/Calcutta";
    if (function_exists('date_default_timezone_set'))
        date_default_timezone_set($timezone);
}

//
//****************************************************************************************
//
//****************************************************************************************

function currentRouletteGameStatus($sessionid, $dbc = FALSE) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "select status from roulette_game_data where session_id = $sessionid";
    $result = mysqli_query($dbc, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_row($result);
        if ($toclose)
            mysqli_close($dbc);
        return $row[0];
    }
    else {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************


function checksomeonebetrouletteornot($sessionid, $dbc) {
    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "select * from roulette_bets where session_id = $sessionid and cases is not null and amount != 0";
    $result = mysqli_query($dbc, $query);

    if (mysqli_num_rows($result) == 0) {
        // no one has bet, retuen false
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
    else {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
}

//****************************************************************************************
//
//****************************************************************************************

function convertChips($chips) {
    // check if M exists
    $result = strpos($chips, 'M');

    if ($result === FALSE) {
        // check if it is a K
        $result2 = strpos($chips, 'K');
        if ($result2 === FALSE) {
            // check if it is a B
            $result3 = strpos($chips, 'B');

            if ($result3 === FALSE) {
                return $chips;
            } else {
                // create a substring, and return
                $returnable = (substr($chips, 0, strlen($chips) - 1) * 1000000000);
                return $returnable;
            }
        } else {
            // create a substring, and return
            $returnable = (substr($chips, 0, strlen($chips) - 1) * 1000);
            return $returnable;
        }
    } else {
        // create a substring, and return
        $returnable = (substr($chips, 0, strlen($chips) - 1) * 1000000);
        return $returnable;
    }
}

//****************************************************************************************
//
//****************************************************************************************

function convertBackChips($chips) {
    if ($chips < 0) {
        $chips = -$chips;
    }
    if ($chips >= 1000000000) {
        RETURN ROUND(($chips / 1000000000), 0) . 'B';
    } elseif ($chips >= 1000000) {
        RETURN ROUND(($chips / 1000000), 0) . 'M';
    } elseif ($chips >= 1000) {
        RETURN ROUND(($chips / 1000), 0) . 'K';
    } else {
        return $chips;
    }
}

//****************************************************************************************
//
//****************************************************************************************

function getnewcard($alreadygivencards) {
    //generate  a random number
    $card = (string) rand(1, 52);
    if (in_array($card, $alreadygivencards, TRUE)) {
        return getnewcard($alreadygivencards);
    } elseif (is_null($card)) {
        return getnewcard($alreadygivencards);
    }
    else
        return $card;
}

//****************************************************************************************
//
//****************************************************************************************

function givecardstoall($dbc, $sessionid) {

    $toclose = FALSE;

    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }
    $alreadygivencards = array();

    // check how many active users are there
    $query12 = "select * from blackjack_bets where session_id = $sessionid and amount!='0' ";
    $result12 = mysqli_query($dbc, $query12);

    if (mysqli_num_rows($result12) > 0) {
        // for each user generate two cards
        while ($row12 = mysqli_fetch_array($result12)) {
            $thisuserid = $row12['user_id'];

            // generate a random number between 1 and 52
            $card1 = getnewcard($alreadygivencards);
            array_push($alreadygivencards, $card1);

            $card2 = getnewcard($alreadygivencards);
            array_push($alreadygivencards, $card2);

            //set these as cards for this user
            $timenow = date('Y-m-d H:i:s');
            $query13 = "update blackjack_bets set cards = '$card1,$card2', player_status = '0' where session_id = $sessionid and user_id = $thisuserid";
            mysqli_query($dbc, $query13);
        }
        $dcard1 = getnewcard($alreadygivencards);
        array_push($alreadygivencards, $dcard1);

        $dcard2 = getnewcard($alreadygivencards);
        array_push($alreadygivencards, $dcard2);

        $timenow = date('Y-m-d H:i:s');
        $query13 = "update blackjack_bets set cards = '$dcard1,$dcard2', datetime = '$timenow', player_status = '0', amount='0' where session_id = $sessionid and user_id = 0";
        mysqli_query($dbc, $query13);

        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
    else {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
}

//****************************************************************************************
//
//****************************************************************************************

function getcardvalue($card) {
    if ($card < 11)
        return $card;
    elseif ($card < 14)
        return 10;
    elseif ($card < 24)
        return $card - 13;
    elseif ($card < 27)
        return 10;
    elseif ($card < 37)
        return $card - 26;
    elseif ($card < 40)
        return 10;
    elseif ($card < 50)
        return $card - 39;
    elseif ($card < 53)
        return 10;
}

//****************************************************************************************
//
//****************************************************************************************

function getcardvalueOmaha($card) {
    if (($card == 1) || ($card == 14) || ($card == 27) || ($card == 40)) {
        return 14;
    } elseif ($card % 13 == 0) {
        return 13;
    } else {
        return $card % 13;
    }
}

//****************************************************************************************
//
//****************************************************************************************


function doblackjackcalculations($sessionid, $dbc, $delay = True) {

    $toclose = FALSE;
    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $total11taken = 0;

    $alreadygivencards = array();

    $query10 = "select cards from blackjack_bets where session_id = $sessionid";
    $result10 = mysqli_query($dbc, $query10);

    if (mysqli_num_rows($result10) > 0) {
        while ($row10 = mysqli_fetch_array($result10)) {
            if (!is_null($row10[0])) {
                $cardsexploded = explode(',', $row10[0]);
                $alreadygivencards = array_merge(array_unique(array_merge($alreadygivencards, $cardsexploded)));
            }
        }
    }

    //now give two cards to the dealer
    $querym1 = "select cards from blackjack_bets where session_id = $sessionid and user_id = 0";
    $resultm1 = mysqli_query($dbc, $querym1);

    if (mysqli_num_rows($resultm1) > 0) {
        $rowm1 = mysqli_fetch_row($resultm1);
        $dealercards = $rowm1[0];
        $dealercardexploded = explode(',', $dealercards);

        $dealercard1 = $dealercardexploded[0];
        $dealercard2 = $dealercardexploded[1];

        $valdealercard1 = getcardvalue($dealercard1);
        $valdealercard2 = getcardvalue($dealercard2);

        if (($valdealercard1 == 1 ) || ($valdealercard1 == 14 ) || ($valdealercard1 == 27 ) || ($valdealercard1 == 40 )) {
            $valdealercard1 = 11;
            $total11taken++;
        } elseif ($valdealercard2 == 1 || ($valdealercard1 == 14 ) || ($valdealercard1 == 27 ) || ($valdealercard1 == 40 )) {
            $valdealercard2 = 11;
            $total11taken++;
        }

        $dealertotal = $valdealercard1 + $valdealercard2;

        $queryu = "update blackjack_bets set player_status = '1' where session_id = $sessionid and user_id = 0";
        mysqli_query($dbc, $queryu);



        while ($dealertotal < 17) {

            $dealercard3 = getnewcard($alreadygivencards);
            array_push($alreadygivencards, $dealercard3);

            $valdealercard3 = getcardvalue($dealercard3);

            setlatestmessage($sessionid, 0, 'Hit', 0, $dbc);
            $dealercards .= ',' . $dealercard3;


            $querym = "update blackjack_bets set cards = '$dealercards' where session_id = $sessionid and user_id = 0";
            mysqli_query($dbc, $querym);


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

                $body['aps'] = array(
                    'alert' => 'Dealer hit a card in Blackjack!',
                    'sound' => '3'
                );

                $payload = json_encode($body);

                for ($i = 0; $i < count($allpushids); $i++) {
                    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                    if (!$fp)
                        exit("Failed to connect: $err $errstr" . PHP_EOL);


                    $devicetoken = fetchdevicetoken($allpushids[$i], $dbc);

                    $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                    fwrite($fp, $msg, strlen($msg));

                    fclose($fp);
                }
            }



            if (($dealercard3 == 1 ) || ($dealercard3 == 14 ) || ($dealercard3 == 27 ) || ($dealercard3 == 40 )) {
                $valdealercard3 = 11;
                $dealertotal += $valdealercard3;
                if ($dealertotal > 21)
                    $dealertotal -= 10;
            }
            else {
                $dealertotal += $valdealercard3;
                if ($dealertotal > 21) {
                    if ($total11taken > 0) {
                        $dealertotal -= 10;
                        $total11taken--;
                    }
                }
            }
            if ($delay)
                sleep(3);
        }

        // check if dealer has already lost
        // fetch details of all other players

        $query1 = "select user_id, amount, cards from blackjack_bets
		where session_id = $sessionid
		and
		amount != '0' and amount != '0:0' and cards !='' and cards != ':'";

        $result1 = mysqli_query($dbc, $query1);

        $pushids = array();

        $dealerwonorlose = 0;

        $player11isset = 0;

        if (mysqli_num_rows($result1) > 0) {
            while ($row1 = mysqli_fetch_array($result1)) {
                $playeruserid = $row1[0];
                $playeramount = $row1[1];
                $playercards = $row1[2];

                // check if some person has splitted
                $playercardssplitted = explode(':', $playercards);
                $playeramountsplitted = explode(':', $playeramount);

                $playerwonorlose = 0;
                for ($i = 0; $i < count($playeramountsplitted); $i++) {
                    if ($playeramountsplitted[$i] != 0) {
                        $totalofcards = 0;
                        $cardsofthisuser = explode(',', $playercardssplitted[$i]);
                        for ($j = 0; $j < count($cardsofthisuser); $j++) {
                            $cardvalue11set = FALSE;
                            if (( getcardvalue($cardsofthisuser[$j]) == 1 ) ||
                                    ( getcardvalue($cardsofthisuser[$j]) == 14 ) ||
                                    ( getcardvalue($cardsofthisuser[$j]) == 27 ) ||
                                    ( getcardvalue($cardsofthisuser[$j]) == 40 )) {
                                if (!$cardvalue11set) {
                                    $player11isset++;
                                    $totalofcards += 11;
                                    if ($totalofcards > 21)
                                        $totalofcards -=10;

                                    $cardvalue11set = TRUE;
                                }
                                else
                                    $totalofcards +=1;
                            }
                            else {
                                $totalofcards += getcardvalue($cardsofthisuser[$j]);
                                if (($totalofcards > 21 ) && ($player11isset > 0)) {
                                    $totalofcards -= 10;
                                    $player11isset--;
                                }
                            }
                        }
                        if ($dealertotal > 21 && $totalofcards > 21) {
                            
                        } else {
                            if ($dealertotal > 21)
                                $dealertotal = 0;
                            elseif ($totalofcards > 21)
                                $totalofcards = 0;

                            if ($totalofcards > $dealertotal) {
                                $playerwonorlose += convertChips($playeramountsplitted[$i]);
                            } elseif ($totalofcards < $dealertotal) {
                                $playerwonorlose -= convertChips($playeramountsplitted[$i]);
                            }
                        }
                    }
                }
                //check how much this player has won or lose
                if ($playerwonorlose > 0) {
                    $message = "+ " . convertBackChips($playerwonorlose);

                    setlatestmessage($sessionid, $playeruserid, $message, 1, $dbc);

                    increasedecreasechips($playeruserid, $playerwonorlose * 2, 1, $dbc);

                    $pushids[] = array($playerwonorlose, $playeruserid);

                    $dealerwonorlose -= $playerwonorlose;

                    $feedmsg = 'Won ' . convertBackChips($playerwonorlose) . ' chips in ' . fetchgametype($sessionid, $dbc);

                    insertIntoFeed($playeruserid, $feedmsg, $dbc);

                    addWinningsLosings($playeruserid, $sessionid, $playerwonorlose, true, $dbc);

                    $newdate = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+5 seconds'));

                    $queryconfetti = "update blackjack_bets set confetti_status = '$newdate', confetti_value=1 where session_id = $sessionid and user_id= $playeruserid";
                    mysqli_query($dbc, $queryconfetti);

                    publishDailyWinnings($playeruserid, $playerwonorlose, 'chips', $dbc);
                }
                if ($playerwonorlose < 0) {
                    $message = "- " . convertBackChips($playerwonorlose);

                    setlatestmessage($sessionid, $playeruserid, $message, 1, $dbc);

//                    increasedecreasechips($playeruserid, -$playerwonorlose, 1, $dbc);

                    $pushids[] = array($playerwonorlose, $playeruserid);

                    $dealerwonorlose -= $playerwonorlose;

                    addWinningsLosings($playeruserid, $sessionid, $playerwonorlose, TRUE, $dbc);


                    $newdate = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+5 seconds'));
                    $queryconfetti = "update blackjack_bets set confetti_status = '$newdate', confetti_value=2 where session_id = $sessionid and user_id= $playeruserid";
                    mysqli_query($dbc, $queryconfetti);

                    publishDailyWinnings($playeruserid, $playerwonorlose, 'chips', $dbc);
                }
                if ($playerwonorlose == 0) {
                    $message = "Tie";

                    $total = 0;

                    // also return amount bet to this player
                    for ($i = 0; $i < count($playeramountsplitted); $i++) {
                        $total += convertChips($playeramountsplitted[$i]);
                    }

                    increasedecreasechips($playeruserid, $total, 1, $dbc);
                    setlatestmessage($sessionid, $playeruserid, $message, 1, $dbc);

                    $pushids[] = array($playerwonorlose, $playeruserid);
                }
            }
        }
        //  echo '------------------------------------->>>> ' . $dealerwonorlose . '<<<<------------------------------------------------';
        if ($dealerwonorlose == 0)
            $dealermessage = "Tie";
        elseif ($dealerwonorlose > 0)
            $dealermessage = '+ ' . convertBackChips($dealerwonorlose);
        elseif ($dealerwonorlose < 0)
            $dealermessage = '- ' . convertBackChips(-$dealerwonorlose);

        setlatestmessage($sessionid, 0, $dealermessage, 1, $dbc);

        // set the status of game to zero and reset player data
        $querymy1 = "update blackjack_bets set player_status = '0', cards = '', amount = '0' where session_id = $sessionid";
        mysqli_query($dbc, $querymy1);

        $querymy2 = "update blackjack_game_data set status = 0 where session_id = $sessionid";
        mysqli_query($dbc, $querymy2);

        // check if there are items in array
        if (count($pushids) > 0) {
            $passphrase = 'abcd';
            $ctx = stream_context_create();
            stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
            stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);



            for ($i = 0; $i < count($pushids); $i++) {

                $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                if (!$fp)
                    exit("Failed to connect: $err $errstr" . PHP_EOL);

                if ($pushids[$i][0] > 0) {
                    $body['aps'] = array(
                        'alert' => 'You just won ' . convertBackChips($pushids[$i][0]) . ' chips in Blackjack!',
                        'sound' => '3'
                    );
                } else if ($pushids[$i][0] < 0) {

                    $body['aps'] = array(
                        'alert' => 'You just lost ' . convertBackChips(- $pushids[$i][0]) . ' chips in Blackjack!',
                        'sound' => '3'
                    );
                } else {

                    $body['aps'] = array(
                        'alert' => 'You won your money back in Blackjack!',
                        'sound' => '3'
                    );
                }

                $payload = json_encode($body);

                $devicetoken = fetchdevicetoken($pushids[$i][1], $dbc);

                $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                fwrite($fp, $msg, strlen($msg));
                fclose($fp);
            }



            if ($toclose)
                mysqli_close($dbc);

            return TRUE;
        }
    }
}

//****************************************************************************************
//
//****************************************************************************************

function sendthepush($message, $userid, $dbc, $sessionid) {

    $pushids2 = array();


    $queryn1 = "select user_id from blackjack_bets where user_id != 0 and session_id =" . $sessionid;
    $result1 = mysqli_query($dbc, $queryn1);
    if (mysqli_num_rows($result1) > 0) {
        while ($row1 = mysqli_fetch_array($result1)) {
            array_push($pushids2, fetchdevicetoken($row1[0]));
        }
    }

    $passphrase = 'abcd';
    $ctx = stream_context_create();
    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

    if (!$fp)
        exit("Failed to connect: $err $errstr" . PHP_EOL);

    $msg = fetchname($userid) . ' ' . $message . ' in Blackjack!';
    $result = 'Start' . PHP_EOL;

    $body['aps'] = array(
        'alert' => $msg,
        'sound' => '3'
    );

    $payload = json_encode($body);


    for ($i = 0; $i < count($pushids2); $i++) {

        $msg = chr(0) . pack('n', 32) . pack('H*', $pushids2[$i]) . pack('n', strlen($payload)) . $payload;


        $result = fwrite($fp, $msg, strlen($msg));

        if ($result)
            echo 'success' . "\n";
        else {
            echo 'fail' . "\n";
        }
    }
    fclose($fp);
}

//****************************************************************************************
//
//****************************************************************************************

function givecardstolatecomers($sessionid, $dbc = FALSE) {

    $toclose = FALSE;
    if (!$dbc) {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // give cards to persons who have not been given cards
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

    //now give two cards to the dealer
    $querym1 = "select user_id from blackjack_bets where session_id = $sessionid and cards = ''";
    $resultm1 = mysqli_query($dbc, $querym1);

    if (mysqli_num_rows($resultm1) > 0) {
        while ($rowm1 = mysqli_fetch_array($resultm1)) {
            $muserid = $rowm1[0];

            $newcard1 = getnewcard($alreadygivencards);
            array_push($alreadygivencards, $newcard1);

            $newcard2 = getnewcard($alreadygivencards);
            array_push($alreadygivencards, $newcard2);


            $query13 = "update blackjack_bets set cards = '$newcard1,$newcard2', player_status = '0' where session_id = $sessionid and user_id = $muserid";
            mysqli_query($dbc, $query13);
        }
    }
    if ($toclose)
        mysqli_close($dbc);
}

//****************************************************************************************
//
//****************************************************************************************
function insertIntoFeed($userid, $message, $dbc) {
    $toclose = FALSE;

    if (!$dbc) {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "insert into casino_feeds(user_id, message) values ($userid, '$message')";
    if (mysqli_query($dbc, $query)) {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
    else {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//****************************************************************************************
//
//****************************************************************************************
function getTimeAgo($time) {
    setTimeZone();

    //check how many days have passed
    $dayspassed = floor((time() - strtotime($time)) / (60 * 60 * 24));
    if ($dayspassed == 0) {
        // check how many hours have passed
        $hourspassed = floor((time() - strtotime($time)) / (60 * 60));
        $minutespassed = floor((time() - strtotime($time) - ($hourspassed * 60 * 60)) / (60));

        if ($hourspassed > 0)
            return $hourspassed . ' hr ' . $minutespassed . ' mins ago';
        else {

            if ($minutespassed != 0)
                return $minutespassed . ' mins ago';
            else
                return time() - strtotime($time) . ' seconds ago';
        }
    }
    elseif ($dayspassed == 1)
        return $dayspassed . ' day ago';
    else
        return $dayspassed . ' days ago';
}

//
//****************************************************************************************
//
//****************************************************************************************
function checkPlayer($userid, $sessionid, $dbc = FALSE) {
    $toclose = FALSE;
    if (!$dbc) {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    setlatestmessage($sessionid, $userid, 'Check', 0, $dbc);

    $query = "update omaha_bets set player_status = 2 where user_id = $userid and session_id= $sessionid";

    if (mysqli_query($dbc, $query)) {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
    else {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************
function callPlayer($userid, $sessionid, $dbc = FALSE) {
    $toclose = FALSE;
    if (!$dbc) {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }
    setlatestmessage($sessionid, $userid, 'Call', 0, $dbc);

    // fetch highest bet
    $query1 = "select min_bet from omaha_game_data where session_id = $sessionid";
    $result1 = mysqli_query($dbc, $query1);

    if (mysqli_num_rows($result1) > 0) {
        $row1 = mysqli_fetch_row($result1);
        $bettoplace = $row1[0];

        // increase bet of person to this a(mount
        $query2 = "select amount from omaha_bets where session_id = $sessionid and user_id = $userid";
        $result2 = mysqli_query($dbc, $query2);

        if (mysqli_num_rows($result2) > 0) {
            $row2 = mysqli_fetch_row($result2);
            $prevbet = $row2[0];

            $newbet = $prevbet + $bettoplace;

            $query3 = "update omaha_bets set player_status = 2, amount = $newbet where user_id = $userid and session_id = $sessionid";
            if (mysqli_query($dbc, $query3)) {
                if ($toclose)
                    mysqli_close($dbc);
                return TRUE;
            }
            else {
                if ($toclose)
                    mysqli_close($dbc);
                return FALSE;
            }
        }
    }
}

//
//****************************************************************************************
//
//****************************************************************************************
function startNextRound($sessionid, $dbc = FALSE) {
    $toclose = FALSE;
    if (!$dbc) {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // collect all chips and put in pot also
    $amounttoadd = 0;
    $playerseligible = "";
    $playerseligible1 = "";

    $query9 = "select user_id from omaha_bets where session_id = $sessionid and is_folded= 0 and chips_available != 0";
    $result9 = mysqli_query($dbc, $query9);
    if (mysqli_num_rows($result9) > 0) {
        while ($row9 = mysqli_fetch_array($result9)) {
            $playerseligible1 .= $row9[0] . ',';
        }
        $playerseligible = substr($playerseligible1, 0, strlen($playerseligible1) - 1);
    }

    $query3 = "select sum(amount_bet) from omaha_bets where session_id = $sessionid";
    $result3 = mysqli_query($dbc, $query3);
    if (mysqli_num_rows($result3) > 0) {
        $row3 = mysqli_fetch_row($result3);
        $amounttoadd = $row3[0];
    }

    $query5 = "select max(id) from omaha_pots where session_id = $sessionid and pot_type=1";
    $result5 = mysqli_query($dbc, $query5);
    if (mysqli_num_rows($result5) > 0) {
        $row5 = mysqli_fetch_row($result5);
        if (!is_null($row5[0])) {
            $query6 = "update omaha_pots set pot_amount = pot_amount + $amounttoadd where id = $row5[0]";
            mysqli_query($dbc, $query6);
        } else {
            $query6 = "insert into omaha_pots (pot_amount, pot_players, session_id, pot_type) values($amounttoadd, '$playerseligible', $sessionid, 1)";
            mysqli_query($dbc, $query6);
        }
    }


    $query1 = "update omaha_bets set player_status = 0, amount_bet=0 where session_id = $sessionid";
    mysqli_query($dbc, $query1);

    $query12 = "select round from omaha_game_data where session_id = $sessionid";
    $result12 = mysqli_query($dbc, $query12);

    if (mysqli_num_rows($result12) > 0) {
        $row12 = mysqli_fetch_row($result12);
        $olderround = $row12[0];

        switch ($olderround) {

            case 1:
                // put 3 cards on table
                $alreadygivencards = array();
                $query2 = "select cards from omaha_bets where session_id = $sessionid";
                $result2 = mysqli_query($dbc, $query2);

                if (mysqli_num_rows($result2) > 0) {
                    while ($row2 = mysqli_fetch_array($result2)) {
                        $cardsexploded = explode(',', $row2[0]);
                        for ($i = 0; $i < count($cardsexploded); $i++) {
                            array_push($alreadygivencards, $cardsexploded[$i]);
                        }
                    }
                }
                // generate 3 cards
                $card1 = getnewcard($alreadygivencards);
                array_push($alreadygivencards, $card1);

                $card2 = getnewcard($alreadygivencards);
                array_push($alreadygivencards, $card2);

                $card3 = getnewcard($alreadygivencards);
                array_push($alreadygivencards, $card3);

                $allcards = $card1 . ',' . $card2 . ',' . $card3;

                $query3 = "update omaha_game_data set cards='$allcards', round=2, status=2 where session_id = $sessionid";
                mysqli_query($dbc, $query3);

                $query9 = "select * from omaha_bets where session_id = $sessionid and player_status = 0 and is_folded = 0 order by datetime limit 1";
                $result9 = mysqli_query($dbc, $query9);

                if (mysqli_num_rows($result9) > 0) {
                    $row9 = mysqli_fetch_row($result9);
                    $thisuserid = $row9[2];

                    $query10 = "update omaha_bets set player_status = 1 where user_id = $thisuserid and session_id = $sessionid";
                    mysqli_query($dbc, $query10);

                    // also update time of game and return seconds remsining
                    $query11 = "update omaha_game_data set datetime = '" . date("Y-m-d H:i:s", time() + 20) . "' where session_id = $sessionid";
                    mysqli_query($dbc, $query11);

                    echo '<seconds>';
                    echo 20;
                    echo '</seconds>';
                }
                break;
            case 2:
                // append 1 more card to table
                $alreadygivencards = array();
                $query2 = "select cards from omaha_bets where session_id = $sessionid";
                $result2 = mysqli_query($dbc, $query2);

                if (mysqli_num_rows($result2) > 0) {
                    while ($row2 = mysqli_fetch_array($result2)) {
                        $cardsexploded = explode(',', $row2[0]);
                        for ($i = 0; $i < count($cardsexploded); $i++) {
                            array_push($alreadygivencards, $cardsexploded[$i]);
                        }
                    }
                }

                $query3 = "select cards from omaha_game_data where session_id = $sessionid";
                $result3 = mysqli_query($dbc, $query3);
                if (mysqli_num_rows($result3) > 0) {
                    $row3 = mysqli_fetch_row($result3);
                    $oldtablecards = $row3[0];
                    $oldtablecardsexploded = explode(',', $oldtablecards);
                    for ($i = 0; $i < count($oldtablecardsexploded); $i++) {
                        array_push($alreadygivencards, $oldtablecardsexploded[$i]);
                    }
                    // now generate one new card and append
                    $card1 = getnewcard($alreadygivencards);
                    array_push($alreadygivencards, $card1);

                    $newcards = $oldtablecards . ',' . $card1;

                    $query3 = "update omaha_game_data set cards='$newcards', round=3, status=2  where session_id = $sessionid";
                    mysqli_query($dbc, $query3);

                    $query9 = "select * from omaha_bets where session_id = $sessionid and player_status = 0 and is_folded = 0 order by datetime limit 1";
                    $result9 = mysqli_query($dbc, $query9);

                    if (mysqli_num_rows($result9) > 0) {
                        $row9 = mysqli_fetch_row($result9);
                        $thisuserid = $row9[2];

                        $query10 = "update omaha_bets set player_status = 1 where user_id = $thisuserid and session_id = $sessionid";
                        mysqli_query($dbc, $query10);

                        // also update time of game and return seconds remsining
                        $query11 = "update omaha_game_data set datetime = '" . date("Y-m-d H:i:s", time() + 20) . "', status=2 where session_id = $sessionid";
                        mysqli_query($dbc, $query11);

                        echo '<seconds>20</seconds>';
                    }
                }

                break;
            case 3:
                // append 1 more card to table
                $alreadygivencards = array();
                $query2 = "select cards from omaha_bets where session_id = $sessionid";
                $result2 = mysqli_query($dbc, $query2);

                if (mysqli_num_rows($result2) > 0) {
                    while ($row2 = mysqli_fetch_array($result2)) {
                        $cardsexploded = explode(',', $row2[0]);
                        for ($i = 0; $i < count($cardsexploded); $i++) {
                            array_push($alreadygivencards, $cardsexploded[$i]);
                        }
                    }
                }

                $query3 = "select cards from omaha_game_data where session_id = $sessionid";
                $result3 = mysqli_query($dbc, $query3);
                if (mysqli_num_rows($result3) > 0) {
                    $row3 = mysqli_fetch_row($result3);
                    $oldtablecards = $row3[0];
                    $oldtablecardsexploded = explode(',', $oldtablecards);
                    for ($i = 0; $i < count($oldtablecardsexploded); $i++) {
                        array_push($alreadygivencards, $oldtablecardsexploded[$i]);
                    }
                    // now generate one new card and append
                    $card1 = getnewcard($alreadygivencards);
                    array_push($alreadygivencards, $card1);

                    $newcards = $oldtablecards . ',' . $card1;

                    $query3 = "update omaha_game_data set cards='$newcards', round=4, status=2  where session_id = $sessionid";
                    mysqli_query($dbc, $query3);

                    $query9 = "select * from omaha_bets where session_id = $sessionid and player_status = 0 and is_folded = 0 order by datetime limit 1";
                    $result9 = mysqli_query($dbc, $query9);

                    if (mysqli_num_rows($result9) > 0) {
                        $row9 = mysqli_fetch_row($result9);
                        $thisuserid = $row9[2];

                        $query10 = "update omaha_bets set player_status = 1 where user_id = $thisuserid and session_id = $sessionid";
                        mysqli_query($dbc, $query10);

                        // also update time of game and return seconds remsining
                        $query11 = "update omaha_game_data set datetime = '" . date("Y-m-d H:i:s", time() + 20) . "' where session_id = $sessionid";
                        mysqli_query($dbc, $query11);

                        echo '<seconds>20</seconds>';
                    }
                }

                break;
            case 4;

                doOmahaCalculations($sessionid, $dbc);
                break;
        }
        return $olderround;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************
function sendpushtoplayers($pushids, $message, $dbc = FALSE, $soundtype = 3) {
    $toclose = FALSE;
    if (!$dbc) {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
    }
    $passphrase = 'abcd';
    $ctx = stream_context_create();
    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

    if (!$fp)
        exit("Failed to connect: $err $errstr" . PHP_EOL);
    for ($i = 0; $i < count($pushids); $i++) {

        $body['aps'] = array(
            'alert' => $message,
            'sound' => $soundtype
        );

        $payload = json_encode($body);

        $devicetoken = fetchdevicetoken($pushids[$i], $dbc);

        $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

        fwrite($fp, $msg, strlen($msg));
    }
    fclose($fp);
    if ($toclose)
        mysqli_close($dbc);
}

//
//****************************************************************************************
//
//****************************************************************************************
function doOmahaCalculations($sessionid, $dbc = FALSE) {
    $toclose = FALSE;
    if (!$dbc) {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }


    $tablecards = '';
    $tablecardscombinations = array();
    $query1 = "select cards from omaha_game_data where session_id = $sessionid";
    $result1 = mysqli_query($dbc, $query1);
    if (mysqli_num_rows($result1) > 0) {
        $row1 = mysqli_fetch_row($result1);
        $tablecards = $row1[0];
    }

//prepare combinations for this player
    $tablecardsexploded = explode(',', $tablecards);

    array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[1] . ',' . $tablecardsexploded[2]);
    array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[1] . ',' . $tablecardsexploded[3]);
    array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[1] . ',' . $tablecardsexploded[4]);
    array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[2] . ',' . $tablecardsexploded[3]);
    array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[2] . ',' . $tablecardsexploded[4]);
    array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[3] . ',' . $tablecardsexploded[4]);
    array_push($tablecardscombinations, $tablecardsexploded[1] . ',' . $tablecardsexploded[2] . ',' . $tablecardsexploded[3]);
    array_push($tablecardscombinations, $tablecardsexploded[1] . ',' . $tablecardsexploded[2] . ',' . $tablecardsexploded[4]);
    array_push($tablecardscombinations, $tablecardsexploded[2] . ',' . $tablecardsexploded[3] . ',' . $tablecardsexploded[4]);
    array_push($tablecardscombinations, $tablecardsexploded[1] . ',' . $tablecardsexploded[3] . ',' . $tablecardsexploded[4]);

// first of all find winner
    $query = "select * from omaha_bets where session_id = $sessionid and is_folded = 0 ";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $thisplayerid = $row['user_id'];
            $thisplayercards = $row['cards'];

            $thisplayercardsexploded = explode(',', $thisplayercards);

            $thisplayercardsarray = array();

            $allpossiblecombinationsforthisplayer = array();

            if (fetchgametype($sessionid, $dbc) == "Omaha") {
                array_push($thisplayercardsarray, $thisplayercardsexploded[0] . ',' . $thisplayercardsexploded[1]);
                array_push($thisplayercardsarray, $thisplayercardsexploded[0] . ',' . $thisplayercardsexploded[2]);
                array_push($thisplayercardsarray, $thisplayercardsexploded[0] . ',' . $thisplayercardsexploded[3]);
                array_push($thisplayercardsarray, $thisplayercardsexploded[1] . ',' . $thisplayercardsexploded[2]);
                array_push($thisplayercardsarray, $thisplayercardsexploded[1] . ',' . $thisplayercardsexploded[3]);
                array_push($thisplayercardsarray, $thisplayercardsexploded[2] . ',' . $thisplayercardsexploded[3]);



                for ($i = 0; $i < count($tablecardscombinations); $i++) {
                    $tablecardsbroken = explode(',', $tablecardscombinations[$i]);
                    $tablecard1 = $tablecardsbroken[0];
                    $tablecard2 = $tablecardsbroken[1];
                    $tablecard3 = $tablecardsbroken[2];

                    for ($j = 0; $j < count($thisplayercardsarray); $j++) {
                        $thisplayercardsbroken = explode(',', $thisplayercardsarray[$j]);
                        $thisplayercard1 = $thisplayercardsbroken[0];
                        $thisplayercard2 = $thisplayercardsbroken[1];

                        $allpossiblecombinationsforthisplayer[] = array($tablecard1, $tablecard2, $tablecard3, $thisplayercard1, $thisplayercard2);
                    }
                }
            } else {
                $allcardsforthisplayer = $thisplayercards . ',' . $tablecards;

                $allcardsforthisplayerexploded = explode(',', $allcardsforthisplayer);

                //now form all combinations for these cards
                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[5]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[1], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[3], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);

                $allpossiblecombinationsforthisplayer[] = array($allcardsforthisplayerexploded[0], $allcardsforthisplayerexploded[2], $allcardsforthisplayerexploded[4], $allcardsforthisplayerexploded[5], $allcardsforthisplayerexploded[6]);
            }
            // now i have all the combinations for this plAYer, and i have to choose the best one
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {

                usort($allpossiblecombinationsforthisplayer[$i], function($a, $b) {
                            if ($b % 13 == $a % 13)
                                return 0;

                            if (( $a % 13 == 0) && ($b % 13 == 1))
                                return -1;
                            if (( $b % 13 == 0) && ($a % 13 == 1))
                                return 1;
                            if ($a % 13 == 0 || $a % 13 == 1)
                                return 1;
                            if ($b % 13 == 0 || $b % 13 == 1)
                                return -1;
                            return ($a % 13 < $b % 13) ? -1 : 1;
                        });
            }



            $found = FALSE;

            // check for royal flush
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                // for every combination, check if it forms an royal flush
                if ((($allpossiblecombinationsforthisplayer[$i][0] == 1)
                        || ($allpossiblecombinationsforthisplayer[$i][0] == 14)
                        || ($allpossiblecombinationsforthisplayer[$i][0] == 27)
                        || ($allpossiblecombinationsforthisplayer[$i][0] == 40))
                        &&
                        ((($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 9)
                        && ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 1)
                        && ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 1)
                        && ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 1)))) {
                    $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 1," . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                    mysqli_query($dbc, $query21);
                    $found = TRUE;
                    break;
                }
            }

            if (!$found) {
                for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                    // check for straight flush
                    $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);
                    if (
                            ($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 1)
                            && ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 1)
                            && ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 1)
                            && ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 1)
                            && ($allpossiblecombinationsforthisplayer[$i][1] != 1)
                            && ($allpossiblecombinationsforthisplayer[$i][1] != 14)
                            && ($allpossiblecombinationsforthisplayer[$i][1] != 27)
                            && ($allpossiblecombinationsforthisplayer[$i][1] != 40)
                            && ($allpossiblecombinationsforthisplayer[$i][2] != 1)
                            && ($allpossiblecombinationsforthisplayer[$i][2] != 14)
                            && ($allpossiblecombinationsforthisplayer[$i][2] != 27)
                            && ($allpossiblecombinationsforthisplayer[$i][2] != 40)
                            && ($allpossiblecombinationsforthisplayer[$i][3] != 1)
                            && ($allpossiblecombinationsforthisplayer[$i][3] != 14)
                            && ($allpossiblecombinationsforthisplayer[$i][3] != 27)
                            && ($allpossiblecombinationsforthisplayer[$i][3] != 40)) {

                        // try to select older result, if was available
                        $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                        $result22 = mysqli_query($dbc, $query22);

                        if (mysqli_num_rows($result22) == 0) {
                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 2, " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                            mysqli_query($dbc, $query21);
                            $found = TRUE;
                        } else {
                            $row22 = mysqli_fetch_row($result22);
                            $earlierhighercard = $row22[0];
                            $combinationexplode = explode(',', $earlierhighercard);

                            for ($p = count($combinationexplode) - 1; $p < -1; $p--) {
                                if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                    mysqli_query($dbc, $query23);
                                    break;
                                } elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (!$found) {
                for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                    $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                    $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                    $uniquevalues1 = array_count_values($arraytocountvalues);

                    $uniquevalues = array_flip($uniquevalues1);


                    // check for 4 of a kind
                    if (array_key_exists(4, $uniquevalues)) {

                        // player has 4 of a kind
                        $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                        $result22 = mysqli_query($dbc, $query22);

                        if (mysqli_num_rows($result22) == 0) {
                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 3, " . getcardvalueOmaha($uniquevalues[4]) . " )";
                            mysqli_query($dbc, $query21);
                            $found = TRUE;
                        } else {
                            $row22 = mysqli_fetch_row($result22);
                            $earlierhighercard = $row22[0];
                            $combinationexplode = explode(',', $earlierhighercard);

                            for ($p = count($combinationexplode) - 1; $p < -1; $p--) {
                                if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($uniquevalues[4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                    mysqli_query($dbc, $query23);
                                    break;
                                } elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (!$found) {
                for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                    $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                    $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                    $uniquevalues1 = array_count_values($arraytocountvalues);

                    $uniquevalues = array_flip($uniquevalues1);



                    // check for full house
                    if (array_key_exists(3, $uniquevalues) && array_key_exists(2, $uniquevalues)) {

                        // player has a full house
                        $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                        $result22 = mysqli_query($dbc, $query22);

                        if (mysqli_num_rows($result22) == 0) {
                            $toinsert1 = 0;
                            $toinsert2 = 0;
                            if (getcardvalueOmaha($uniquevalues[3]) > getcardvalueOmaha($uniquevalues[2])) {
                                $toinsert1 = getcardvalueOmaha($uniquevalues[3]);
                                $toinsert2 = getcardvalueOmaha($uniquevalues[2]);
                            } else {
                                $toinsert2 = getcardvalueOmaha($uniquevalues[3]);
                                $toinsert1 = getcardvalueOmaha($uniquevalues[2]);
                            }
                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards, highest_cards2) values($sessionid, $thisplayerid, '$dbcombination', 4, $toinsert1, $toinsert2)";
                            mysqli_query($dbc, $query21);
                            $found = TRUE;
                        } else {
                            $row22 = mysqli_fetch_row($result22);
                            $earlierhighercard = $row22[0];
                            $combinationexplode = explode(',', $earlierhighercard);

                            for ($p = count($combinationexplode) - 1; $p < -1; $p--) {
                                if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($uniquevalues[4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                    mysqli_query($dbc, $query23);
                                    break;
                                } elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (!$found) {
                for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                    // check for flush
                    $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);
                    if (
                            (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][0]) < 13)
                            && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][0]) > 0)
                            && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][2]) < 13)
                            && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][2]) > 0)
                            && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3]) < 13)
                            && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3]) > 0)
                            && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][1]) < 13)
                            && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][1]) > 0)
                    ) {
                        // player has a flush
                        $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                        $result22 = mysqli_query($dbc, $query22);

                        if (mysqli_num_rows($result22) == 0) {
                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 5, " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                            mysqli_query($dbc, $query21);
                            $found = TRUE;
                        } else {
                            $row22 = mysqli_fetch_row($result22);
                            $earlierhighercard = $row22[0];
                            $combinationexplode = explode(',', $earlierhighercard);

                            for ($p = count($combinationexplode) - 1; $p < -1; $p--) {
                                if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                    mysqli_query($dbc, $query23);
                                    break;
                                } elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (!$found) {
                for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                    $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);
                    // check for a straight
                    if (
                            (($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 1)
                            || ($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 14)
                            || ($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 27)
                            || ($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 40))
                            && (($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 1)
                            || ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 14)
                            || ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 27)
                            || ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 40))
                            && (($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 1)
                            || ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 14)
                            || ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 27)
                            || ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 40))
                            && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 1)
                            || ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 14)
                            || ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 27)
                            || ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 40))
                    ) {
                        // player fas  striaght
                        $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                        $result22 = mysqli_query($dbc, $query22);

                        if (mysqli_num_rows($result22) == 0) {
                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 6, " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                            mysqli_query($dbc, $query21);
                            $found = TRUE;
                        } else {
                            $row22 = mysqli_fetch_row($result22);
                            $earlierhighercard = $row22[0];
                            $combinationexplode = explode(',', $earlierhighercard);

                            for ($p = count($combinationexplode) - 1; $p < -1; $p--) {
                                if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                    mysqli_query($dbc, $query23);
                                    break;
                                } elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (!$found) {

                for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                    $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                    // before doing this, we must create a new array, conting the vales in the array
                    $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                    $uniquevalues1 = array_count_values($arraytocountvalues);

                    $uniquevalues = array_flip($uniquevalues1);


                    // check for 3 of a kind
                    if (array_key_exists(3, $uniquevalues)) {
                        if (!is_null($uniquevalues[3])) {
                            // player has 3 of a kind
                            // player has a full house
                            $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                            $result22 = mysqli_query($dbc, $query22);

                            if (mysqli_num_rows($result22) == 0) {
                                $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 7, " . getcardvalueOmaha($uniquevalues[3]) . " )";
                                mysqli_query($dbc, $query21);
                                $found = TRUE;
                            } else {
                                $row22 = mysqli_fetch_row($result22);
                                $earlierhighercard = $row22[0];
                                $combinationexplode = explode(',', $earlierhighercard);

                                for ($p = count($combinationexplode) - 1; $p < -1; $p--) {
                                    if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                        $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                        mysqli_query($dbc, $query23);
                                        break;
                                    } elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!$found) {
                for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                    $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                    $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                    $uniquevalues1 = array_count_values($arraytocountvalues);

                    $uniquevalues = array_flip($uniquevalues1);
                    // check for a 2 pair

                    if (count($uniquevalues1) == 3 && array_key_exists(2, $uniquevalues)) {

                        // player has a pair
                        $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                        $result22 = mysqli_query($dbc, $query22);

                        if (mysqli_num_rows($result22) == 0) {
                            // FIND both highest cards
                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards, highest_cards2) values($sessionid, $thisplayerid, '$dbcombination', 8, 1, 2)";
                            mysqli_query($dbc, $query21);
                            $found = TRUE;
                        } else {
                            $row22 = mysqli_fetch_row($result22);
                            $earlierhighercard = $row22[0];
                            $combinationexplode = explode(',', $earlierhighercard);

                            for ($p = count($combinationexplode) - 1; $p < -1; $p--) {
                                if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                    mysqli_query($dbc, $query23);
                                    break;
                                } elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (!$found) {

                for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                    $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                    $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                    $uniquevalues1 = array_count_values($arraytocountvalues);

                    $uniquevalues = array_flip($uniquevalues1);

                    // check for a 1
                    if (array_key_exists(2, $uniquevalues)) {
                        if (!is_null($uniquevalues[2])) {
                            // player has a pair
                            $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                            $result22 = mysqli_query($dbc, $query22);

                            if (mysqli_num_rows($result22) == 0) {
                                $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 9, " . getcardvalueOmaha($uniquevalues[2]) . ")";
                                mysqli_query($dbc, $query21);
                                $found = TRUE;
                            } else {
                                $row22 = mysqli_fetch_row($result22);
                                $earlierhighercard = $row22[0];
                                $combinationexplode = explode(',', $earlierhighercard);

                                for ($p = count($combinationexplode) - 1; $p < -1; $p--) {
                                    if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                        $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                        mysqli_query($dbc, $query23);
                                        break;
                                    } elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (!$found) {
                for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++) {
                    // last case
                    $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);
                    $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                    $result22 = mysqli_query($dbc, $query22);

                    if (mysqli_num_rows($result22) == 0) {
                        $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 10,  " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                        mysqli_query($dbc, $query21);
                        $found = TRUE;
                    } else {
                        $row22 = mysqli_fetch_row($result22);
                        $earlierhighercard = $row22[0];
                        $combinationexplode = explode(',', $earlierhighercard);

                        for ($p = count($combinationexplode) - 1; $p < -1; $p--) {
                            if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                mysqli_query($dbc, $query23);
                                break;
                            } elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p])) {
                                break;
                            }
                        }
                    }
                }
            }
        }


        // loop breaks here
        $query23 = "select min(rank) from omaha_results where session_id = $sessionid";
        $result23 = mysqli_query($dbc, $query23);
        if (mysqli_num_rows($result23) > 0) {
            $row23 = mysqli_fetch_row($result23);
            $maxrank = $row23[0];

            $query24 = "select user_id, combination from omaha_results where rank = $maxrank and session_id = $sessionid";
            $result24 = mysqli_query($dbc, $query24);
            $noofwinners = mysqli_num_rows($result24);

            $winners = array();
            // put all winners in an array
            if ($noofwinners > 0) {
                while ($row24 = mysqli_fetch_array($result24)) {
                    $winners[] = array($row24[0], $row24[1]);
                }
            }


            usort($winners, function($a, $b) {
                        $a1exploded = explode(',', $a[1]);
                        $b1exploded = explode(',', $b[1]);
                        for ($i = count($a1exploded) - 1; $i > -1; $i--) {
                            if ($a1exploded[$i] % 13 == $b1exploded[$i] % 13)
                                continue;
                            elseif ($a1exploded[$i] % 13 < $b1exploded[$i] % 13) {
                                return -1;
                            } elseif ($a1exploded[$i] % 13 > $b1exploded[$i] % 13) {
                                return 1;
                            }
                        }
                    });


            $finalwinners = array_reverse($winners);
            $total = count($finalwinners);


            // now i have to keep only those values where card values are same
            for ($i = 1; $i < $total; $i++) {
                $winnercardsexploded = explode(',', $finalwinners[0][1]);
                $thiscardsexploded = explode(',', $finalwinners[$i][1]);

                for ($j = 0; $j < count($winnercardsexploded); $j++) {
                    if ($winnercardsexploded[$j] % 13 != $thiscardsexploded[$j] % 13) {
                        unset($finalwinners[$i]);
                        break;
                    }
                }
            }


            // now i have to form strategy for giving back the pots
            $winneruserids = array();
            for ($i = 0; $i < count($finalwinners); $i++) {
                array_push($winneruserids, $finalwinners[$i][0]);
            }



            $potplayers = array();
            $query25 = "select pot_players, pot_amount from omaha_pots where session_id = $sessionid and pot_type=1";
            $result25 = mysqli_query($dbc, $query25);

            $mainpotamount = 0;
            if (mysqli_num_rows($result25) > 0) {
                $row25 = mysqli_fetch_row($result25);
                $potplayers = explode(',', $row25[0]);
                $mainpotamount = $row25[1];
            }

            // now intersect both array to find common winners
            $eligiblewinners = array_merge(array_intersect($potplayers, $winneruserids));
            if (array_key_exists(0, $eligiblewinners)) {
                // there was/were person or persons winning main pot

                $noofwinners = count($eligiblewinners);
                $eachplayerwon = round($mainpotamount / $noofwinners, 0);

                for ($i = 0; $i < count($eligiblewinners); $i++) {
                    $query27 = "update omaha_bets set chips_available = chips_available + $eachplayerwon where session_id = $sessionid and user_id = $eligiblewinners[$i]";
                    mysqli_query($dbc, $query27);

                    setlatestmessage($sessionid, $eligiblewinners[$i], "Won " . $eachplayerwon . ' chips from main pot', 1, $dbc);
                    $msg = "Won " . $eachplayerwon . " chips in Omaha";
                    insertIntoFeed($eligiblewinners[$i], $msg, $dbc);
                }
                // delete the main pot
                $query28 = "delete from omaha_pots where session_id = $sessionid and pot_type =1";
                mysqli_query($dbc, $query28);
            } else {
                // no one won main pot. so just return it to contributors
                $eachplayerwon = round($mainpotamount / count($potplayers), 0);
                for ($i = 0; $i < count($potplayers); $i++) {
                    $query27 = "update omaha_bets set chips_available = chips_available + $eachplayerwon where session_id = $sessionid and user_id = $potplayers[$i]";
                    mysqli_query($dbc, $query27);

                    setlatestmessage($sessionid, $potplayers[$i], "Returned " . $eachplayerwon . ' chips from main pot', 1, $dbc);
                }
                // delete the main pot
                $query28 = "delete from omaha_pots where session_id = $sessionid and pot_type =1";
                mysqli_query($dbc, $query28);
            }

            // check for other pots also
            $query29 = "select * from omaha_pots where session_id = $sessionid and pot_type = 0";
            $result29 = mysqli_query($dbc, $query29);
            if (mysqli_num_rows($result29) > 0) {
                while ($row29 = mysqli_fetch_array($result29)) {
                    $thispotid = $row29[0];
                    $thispotamount = $row29[1];
                    $thispotplayers = $row29[2];

                    $thispotplayersexploded = explode(',', $thispotplayers);
                    $thispotwinners = array_merge(array_intersect($thispotplayersexploded, $winneruserids));

                    if (array_key_exists(0, $thispotwinners)) {

                        // there was/were person or persons winning main pot
                        $noofwinners = count($thispotwinners);
                        $eachplayerwon = round($thispotamount / $noofwinners, 0);

                        for ($i = 0; $i < count($thispotwinners); $i++) {
                            $query27 = "update omaha_bets set chips_available = chips_available + $eachplayerwon where session_id = $sessionid and user_id = $thispotwinners[$i]";
                            mysqli_query($dbc, $query27);

                            setlatestmessage($sessionid, $thispotwinners[$i], "Won " . $eachplayerwon . ' chips from side pot', 1, $dbc);
                        }
                        // delete the main pot
                        $query28 = "delete from omaha_pots where session_id = $sessionid and id = $thispotid";
                        mysqli_query($dbc, $query28);
                    } else {
                        // no one won main pot. so just return it to contributors
                        $eachplayerwon = round($thispotamount / count($thispotplayersexploded), 0);
                        for ($i = 0; $i < count($thispotplayersexploded); $i++) {
                            $query27 = "update omaha_bets set chips_available = chips_available + $eachplayerwon where session_id = $sessionid and user_id = $thispotplayersexploded[$i]";
                            mysqli_query($dbc, $query27);

                            setlatestmessage($sessionid, $thispotplayersexploded[$i], "Returned " . $eachplayerwon . ' chips from side pot', 1, $dbc);
                        }
                        // delete the main pot
                        $query28 = "delete from omaha_pots where session_id = $sessionid and id = $thispotid";
                        mysqli_query($dbc, $query28);
                    }
                }
            }

            //reward the people who have 0 chips left now
            $query30 = "select * from omaha_bets where is_rewarded = 1 and session_id = $sessionid";
            $result30 = mysqli_query($dbc, $query30);
            $alreadyrewarded = mysqli_num_rows($result30);

            $query31 = "select * from omaha_bets where session_id = $sessionid and is_rewarded = 0 and chips_available = 0 order by datetime";
            $result31 = mysqli_query($dbc, $query31);

            if (mysqli_num_rows($result31) > 0) {
                while ($row31 = mysqli_fetch_array($result31)) {
                    $thisrowid = $row31[0];
                    $thisuserid = $row31['user_id'];

                    if ($alreadyrewarded < 3) {
                        $query32 = "update omaha_bets set is_rewarded = 1 where id = $thisrowid";
                        mysqli_query($dbc, $query32);
                        $alreadyrewarded++;
                    } elseif ($alreadyrewarded == 3) {
                        $chipstogive = convertBackChips(fetchgametype($sessionid, $dbc));
                        increasedecreasechips($thisuserid, $chipstogive, 1, $dbc);
                        setlatestmessage($sessionid, $thisuserid, "Rewarded " . $chipstogive . ' chips : 3rd pos', 1, $dbc);

                        addWinningsLosings($this, $sessionid, $chipstogive, true, $dbc);

                        $query32 = "update omaha_bets set is_rewarded = 1 where session_id = $sessionid and user_id = $thisuserid";
                        mysqli_query($dbc, $query32);
                        $alreadyrewarded++;
                    } elseif ($alreadyrewarded == 4) {
                        $chipstogive = 2 * convertBackChips(fetchgametype($sessionid, $dbc));
                        increasedecreasechips($thisuserid, $chipstogive, 1, $dbc);
                        setlatestmessage($sessionid, $thisuserid, "Rewarded " . $chipstogive . ' chips : 2nd pos', 1, $dbc);

                        addWinningsLosings($thisuserid, $sessionid, $chipstogive, true, $dbc);

                        $query32 = "update omaha_bets set is_rewarded = 1 where session_id = $sessionid and user_id = $thisuserid";
                        mysqli_query($dbc, $query32);
                        $alreadyrewarded++;
                    } elseif ($alreadyrewarded == 5) {
                        $chipstogive = 3 * convertBackChips(fetchgametype($sessionid, $dbc));
                        increasedecreasechips($thisuserid, $chipstogive, 1, $dbc);
                        setlatestmessage($sessionid, $thisuserid, "Rewarded " . $chipstogive . ' chips : 1st pos', 1, $dbc);

                        addWinningsLosings($thisuserid, $sessionid, $chipstogive, true, $dbc);

                        $query32 = "update omaha_bets set is_rewarded = 1 where session_id = $sessionid and user_id = $thisuserid";
                        mysqli_query($dbc, $query32);
                        $alreadyrewarded++;

                        // end the game now 
                        $query70 = "delete from table_gamesessions where session_id = $sessionid";
                        mysqli_query($dbc, $query70);

                        $query71 = "delete from invitations where session_id = $sessionid";
                        mysqli_query($dbc, $query71);

                        $query72 = "delete from table_gamesessions where session_id = $sessionid";
                        mysqli_query($dbc, $query72);

                        $query73 = "delete from games_players where session_id = $sessionid";
                        mysqli_query($dbc, $query73);

                        $query74 = "delete from omaha_bets where session_id = $sessionid";
                        mysqli_query($dbc, $query74);

                        $query75 = "delete from omaha_game_data where session_id = $sessionid";
                        mysqli_query($dbc, $query75);

                        $query76 = "delete from omaha_pots where session_id = $sessionid";
                        mysqli_query($dbc, $query76);

                        $query77 = "delete from omaha_results where session_id = $sessionid";
                        mysqli_query($dbc, $query77);

                        exit();
                    }
                }
            }

            //empty result table
            $query33 = "delete from omaha_results where session_id = $sessionid";
            mysqli_query($dbc, $query33);

            $query34 = "update omaha_bets set amount_bet = 0, player_status = 0, cards = '', is_folded = 0 where session_id = $sessionid";
            mysqli_query($dbc, $query34);

            $query35 = "update omaha_game_data set round = 1, status = 2, cards = '' where session_id = $sessionid";
            mysqli_query($dbc, $query35);

            $query35 = "select id from omaha_bets where is_dealer = 1 and session_id = $sessionid";
            $result35 = mysqli_query($dbc, $query35);


            if (mysqli_num_rows($result35) > 0) {
                $row35 = mysqli_fetch_row($result35);
                $olddealer = $row35[0];
                $query36 = "update omaha_bets set is_dealer = 2 where id = $row35[0] and session_id = $sessionid";
                mysqli_query($dbc, $query36);

                // create new dealer, set small and big blind
                $newdealer = 0;

// create new dealer, set small and big blind

                $query22 = "select id from omaha_bets where session_id = $sessionid and id < $olddealer limit 1";
                $result22 = mysqli_query($dbc, $query22);
                if (mysqli_num_rows($result22) > 0) {
                    $row22 = mysqli_fetch_row($result22);
                    $newdealer = $row22[0];
                } else {
                    $query23 = "select id from omaha_bets where session_id = $sessionid order by id limit 1";
                    $result23 = mysqli_query($dbc, $query23);
                    if (mysqli_num_rows($result23) > 0) {
                        $row23 = mysqli_fetch_row($result23);
                        $newdealer = $row23[0];
                    }
                }


                $query10 = "update omaha_bets set datetime = '" . date('Y-m-d H:i:s', time()) . "', is_dealer=1 where id = $newdealer";
                mysqli_query($dbc, $query10);

                $bigblind = 0;
                $smallblind = 0;

                $query40 = "select id from omaha_bets where session_id = $sessionid and id < $newdealer limit 2";
                $result40 = mysqli_query($dbc, $query40);

                if (mysqli_num_rows($result40) == 0) {
                    $query41 = "select id from omaha_bets where session_id = $sessionid order by id limit 2";
                    $result41 = mysqli_query($dbc, $query41);
                    if (mysqli_num_rows($result41) > 0) {
                        while ($row41 = mysqli_fetch_array($result41)) {
                            if ($smallblind == 0)
                                $smallblind = $row41[0];
                            else
                                $bigblind = $row41[0];
                        }
                    }
                }
                elseif (mysqli_num_rows($result40) == 1) {
                    $row40 = mysqli_fetch_row($result40);
                    $smallblind = $row40[0];

                    $query41 = "select id from omaha_bets where session_id = $sessionid order by id limit 1";
                    $result41 = mysqli_query($dbc, $query41);
                    if (mysqli_num_rows($result41) > 0) {
                        while ($row41 = mysqli_fetch_array($result41)) {
                            if ($smallblind == 0)
                                $smallblind = $row41[0];
                            else
                                $bigblind = $row41[0];
                        }
                    }
                }
                elseif (mysqli_num_rows($result40) == 2) {
                    while ($row40 = mysqli_fetch_array($result40)) {
                        if ($smallblind == 0)
                            $smallblind = $row40[0];
                        else
                            $bigblind = $row40[0];
                    }
                }


                $amountbet = 25;
                $newtime = date('Y-m-d H:i:s', time() + $amountbet);
                $query113 = "update omaha_bets set amount_bet = $amountbet, datetime = '$newtime', chips_available = chips_available-$amountbet where id = " . $smallblind;
                $result113 = mysqli_query($dbc, $query113);

                $query30 = "select user_id from omaha_bets where id = $smallblind";
                $result30 = mysqli_query($dbc, $query30);
                if (mysqli_num_rows($result30) > 0) {
                    $row30 = mysqli_fetch_row($result30);
                    setlatestmessage($sessionid, $row30[0], "Small Blind - 25 chips", 1, $dbc);
                }

                $amountbet2 = 50;
                $newtime2 = date('Y-m-d H:i:s', time() + $amountbet2);
                $query114 = "update omaha_bets set amount_bet = $amountbet2, datetime = '$newtime2', chips_available=chips_available-$amountbet2  where id = " . $bigblind;
                $result114 = mysqli_query($dbc, $query114);

                $query30 = "select user_id from omaha_bets where id = $bigblind";
                $result30 = mysqli_query($dbc, $query30);
                if (mysqli_num_rows($result30) > 0) {
                    $row30 = mysqli_fetch_row($result30);
                    setlatestmessage($sessionid, $row30[0], "Big Blind - 50 chips", 1, $dbc);
                }

                $alreadygivencards = array();

                $query2 = "select user_id from omaha_bets where session_id = $sessionid and chips_available != 0";
                $result2 = mysqli_query($dbc, $query2);

                if (mysqli_num_rows($result2) > 0) {
                    if (fetchgametype($sessionid, $dbc) == "Omaha") {
                        while ($row2 = mysqli_fetch_array($result2)) {
                            $thisuserid = $row2[0];
                            $card1 = getnewcard($alreadygivencards);
                            array_push($alreadygivencards, $card1);

                            $card2 = getnewcard($alreadygivencards);
                            array_push($alreadygivencards, $card2);

                            $card3 = getnewcard($alreadygivencards);
                            array_push($alreadygivencards, $card3);

                            $card4 = getnewcard($alreadygivencards);
                            array_push($alreadygivencards, $card4);

                            $allcards = $card1 . ',' . $card2 . ',' . $card3 . ',' . $card4;

                            $query3 = "update omaha_bets set cards='$allcards' where session_id = $sessionid and user_id = $thisuserid";
                            mysqli_query($dbc, $query3);

                            $query4 = "update omaha_game_data set round = 1 where session_id = $sessionid";
                            mysqli_query($dbc, $query4);
                        }
                    } else {
                        while ($row2 = mysqli_fetch_array($result2)) {
                            $thisuserid = $row2[0];
                            $card1 = getnewcard($alreadygivencards);
                            array_push($alreadygivencards, $card1);

                            $card2 = getnewcard($alreadygivencards);
                            array_push($alreadygivencards, $card2);

                            $allcards = $card1 . ',' . $card2;

                            $query3 = "update omaha_bets set cards='$allcards' where session_id = $sessionid and user_id = $thisuserid";
                            mysqli_query($dbc, $query3);

                            $query4 = "update omaha_game_data set round = 1 where session_id = $sessionid";
                            mysqli_query($dbc, $query4);
                        }
                    }
                }

                // activate the next player
                $query4 = "select * from omaha_bets where player_status = 0 and session_id = $sessionid and chips_available != 0 order by datetime limit 1";
                $result4 = mysqli_query($dbc, $query4);
                if (mysqli_num_rows($result4) > 0) {
                    $row4 = mysqli_fetch_row($result4);
                    $playeruserid = $row4[2];

                    $query5 = "update omaha_bets set player_status = 1 where user_id = $playeruserid and session_id = $sessionid";
                    mysqli_query($dbc, $query5);

                    // also update game status
                    $newgametime = date('Y-m-d H:i:s', time() + 20);
                    $query7 = "update omaha_game_data set datetime = '$newgametime', status = 2, round=1 where session_id = $sessionid";
                    mysqli_query($dbc, $query7);


                    echo '<seconds>';
                    echo 20;
                    echo '</seconds>';

                    $pushids = array();
                    $query33 = "select user_id from omaha_bets where session_id = $sessionid";
                    $result33 = mysqli_query($dbc, $query33);
                    if (mysqli_num_rows($result33) > 0) {
                        while ($row33 = mysqli_fetch_array($result33)) {
                            array_push($pushids, $row33['user_id']);
                        }
                    }

                    $pushmessage = "Omaha starts again";
                    sendpushtoplayers($pushids, $pushmessage, $dbc);
                }
            }
        }
    }
}

//
//****************************************************************************************
//
//****************************************************************************************
function convertToCardValues($array) {
    for ($i = 0; $i < count($array); $i++) {
        $array[$i] = $array[$i] % 13;
        if ($array[$i] == 0)
            $array[$i] = 13;
    }
    return $array;
}

//****************************************************************************************
function addWinningsLosings($userid, $sessionid, $amount, $winorlose, $dbc = false) {
    $toclose = FALSE;
    if (!$dbc) {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    if ($winorlose)
        $query = "update games_players set wins = wins + $amount where user_id = $userid and session_id = $sessionid";
    else
        $query = "update games_players set wins = wins - $amount where user_id = $userid and session_id = $sessionid";

    mysqli_query($dbc, $query);

    if ($toclose) {
        mysqli_close($dbc);
    }
}

function publishDailyWinnings($userid, $amount, $what = 'chips', $dbc = FALSE) {
    /*
     * 
     * task = 0  => subtract
     * task = 1     => add
     */

    $toclose = FALSE;
    if (!$dbc) {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $querydw = "select datetime_$what from daily_winnings where user_id = $userid";
    $resultdw = mysqli_query($dbc, $querydw);

    setTimeZone();
    $timenow = date('Y-m-d', time());

    if (mysqli_num_rows($resultdw) > 0) {
        $rowdata = mysqli_fetch_row($resultdw);

        $datetoday = date("Y-m-d", strtotime($timenow));
        $lastdate = date("Y-m-d", strtotime($rowdata[0]));
        if ($datetoday > $lastdate)
            $querydw2 = "update daily_winnings set datetime_$what = '$timenow', $what = $amount where user_id = $userid";
        else
            $querydw2 = "update daily_winnings set datetime_$what = '$timenow', $what = $what + $amount where user_id = $userid";
        mysqli_query($dbc, $querydw2);
    }
    else {
        $querydw2 = "insert into daily_winnings(user_id, datetime_$what, $what) values($userid, '$timenow', $amount)";
        mysqli_query($dbc, $querydw2);
    }
    if ($toclose) {
        mysqli_close($dbc);
    }
}

function giveRewardPoint($userid, $thisgamename, $gotsessionid, $dbc) {

    $pushdata = array();

    // check whether to give recommendation points or not
    $query7 = "select id, recommendors_user_id from recommendations where recommended_user_id = $userid and status = 0";
    $result7 = mysqli_query($dbc, $query7);

    $recommenddata = array();
    if (mysqli_num_rows($result7) > 0) {
        while ($row7 = mysqli_fetch_row($result7)) {
            $recommenddata[] = array($row7[0], $row7[1]);
        }
    }



    $query8 = "select id, recommended_user_id from recommendations where recommendors_user_id = $userid and status = 0";
    $result8 = mysqli_query($dbc, $query8);

    if (mysqli_num_rows($result8) > 0) {
        while ($row8 = mysqli_fetch_row($result8)) {
            $recommenddata[] = array($row8[0], $row8[1]);
        }
    }

    $recommenddatastring1 = "";

    // form recommend data string 
    for ($i1 = 0; $i1 < count($recommenddata); $i1++) {
        $recommenddatastring1 .= $recommenddata[$i1][1] . ',';
    }

    $recommenddatastring = substr($recommenddatastring1, 0, strlen($recommenddatastring1) - 1);

    if (count($recommenddata) > 0) {
        switch ($thisgamename) {
            case 'slots':

                $query9 = "select user_id from games_players where session_id = $gotsessionid and user_id in (-5, " . $recommenddatastring . ")";
                $result9 = mysqli_query($dbc, $query9);

                if (mysqli_num_rows($result9) > 0) {
                    while ($row9 = mysqli_fetch_array($result9)) {
                        //also give reward point to player
                        $query19 = "update user_cash set reward = reward + 1 where user_id = $userid";
                        mysqli_query($dbc, $query19);
                        $query20 = "update user_cash set reward = reward + 1 where user_id = " . $row9[0];
                        mysqli_query($dbc, $query20);

                        $query10 = "delete from recommendations where recommendors_user_id = $userid and recommended_user_id = $row9[0]";
                        mysqli_query($dbc, $query10);

                        if (mysqli_affected_rows($dbc) == 0) {
                            $query10 = "delete from recommendations where recommended_user_id = $userid and recommendors_user_id = $row9[0]";
                            mysqli_query($dbc, $query10);
                        }
                        $pushdata[] = array($userid, $row9[0]);
                    }
                }
                break;
            case 'roulette':

                $query9 = "select user_id from roulette_bets where session_id = $gotsessionid and user_id in (-5," . $recommenddatastring . ")";
                $result9 = mysqli_query($dbc, $query9);

                if (mysqli_num_rows($result9) > 0) {
                    while ($row9 = mysqli_fetch_array($result9)) {
                        //also give reward point to player
                        $query19 = "update user_cash set reward = reward + 1 where user_id = $userid";
                        mysqli_query($dbc, $query19);
                        $query20 = "update user_cash set reward = reward + 1 where user_id = " . $row9[0];
                        mysqli_query($dbc, $query20);

                        $query10 = "delete from recommendations where recommendors_user_id = $userid and recommended_user_id = $row9[0]";
                        mysqli_query($dbc, $query10);

                        if (mysqli_affected_rows($dbc) == 0) {
                            $query10 = "delete from recommendations where recommended_user_id = $userid and recommendors_user_id = $row9[0]";
                            mysqli_query($dbc, $query10);
                        }

                        $pushdata[] = array($userid, $row9[0]);
                    }
                }
                break;
            case 'blackjack':
                $query9 = "select user_id from blackjack_bets where session_id = $gotsessionid and user_id in (-5," . $recommenddatastring . ")";
                $result9 = mysqli_query($dbc, $query9);

                if (mysqli_num_rows($result9) > 0) {
                    //also give reward point to player
                    while ($row9 = mysqli_fetch_array($result9)) {
                        $query19 = "update user_cash set reward = reward + 1 where user_id = $userid";
                        mysqli_query($dbc, $query19);
                        $query20 = "update user_cash set reward = reward + 1 where user_id = " . $row9[0];
                        mysqli_query($dbc, $query20);

                        $query10 = "delete from recommendations where recommendors_user_id = $userid and recommended_user_id = $row9[0]";
                        mysqli_query($dbc, $query10);

                        if (mysqli_affected_rows($dbc) == 0) {
                            $query10 = "delete from recommendations where recommended_user_id = $userid and recommendors_user_id = $row9[0]";
                            mysqli_query($dbc, $query10);
                        }

                        $pushdata[] = array($userid, $row9[0]);
                    }
                }
                break;
            case "Holdem":
            case "Omaha":
                $query9 = "select * from omaha_bets where session_id = $gotsessionid and user_id in (0," . $recommenddata[0][1] . ")";
                $result9 = mysqli_query($dbc, $query9);

                if (mysqli_num_rows($result9) > 0) {
                    //also give reward point to player
                    $query19 = "update user_cash set reward = reward + 1 where user_id = $userid";
                    mysqli_query($dbc, $query19);
                    $query20 = "update user_cash set reward = reward + 1 where user_id = " . $recommenddata[0][1];
                    mysqli_query($dbc, $query20);

                    $query10 = "delete from recommendations set status = 1 where id =" . $recommenddata[0][0];
                    mysqli_query($dbc, $query10);

                    $pushdata[] = array($userid, $recommenddata[0][0]);
                }
                break;
            default :
                break;
        }
    }

    if (count($pushdata) > 0) {
        // Put your private key's passphrase here:
        $passphrase = 'abcd';



        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

        // Open a connection to the APNS server
        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

        for ($i = 0; $i < count($pushdata); $i++) {

            for ($j = 0; $j < 2; $j++) {

                if ($j == 0)
                    $tosend = 1;
                else
                    $tosend = 0;

                // Create the payload body
                $body['aps'] = array(
                    'alert' => 'You and ' . fetchname($pushdata[$i][$tosend]) . ' are given a reward point',
                    'sound' => '99'
                );

                // Encode the payload as JSON
                $payload = json_encode($body);

                // Build the binary notification
                $msg = chr(0) . pack('n', 32) . pack('H*', fetchdevicetoken($pushdata[$i][$j])) . pack('n', strlen($payload)) . $payload;

                // Send it to the server
                fwrite($fp, $msg, strlen($msg));
            }
        }
        // Close the connection to the server
        fclose($fp);
    }
}

?>
