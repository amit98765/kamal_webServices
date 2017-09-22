<?php

require_once 'variables/dbconnectionvariables.php';

//****************************************************************************************

function setmessage($message, $receiversid, $handlerid, $messagetype = 3, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "insert into messages(message, message_type, user_id, handler_id) values ('$message', $messagetype, $receiversid, $handlerid)";
    mysqli_query($dbc, $query);
    if (mysqli_affected_rows($dbc) == 1)
    {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    } else
    {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//****************************************************************************************
//
//****************************************************************************************
function setchips($userid, $chips, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "update user_cash set chips = $chips where user_id = $userid";
    mysqli_query($dbc, $query);

    if (mysqli_affected_rows($dbc) == 1)
    {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    } else
    {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

// **************************************************************************************
// 
// ***********************************************************************************
function getchips($userid, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $noofchips = NULL;

    $query = "select chips from user_cash where user_id = $userid";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) != 0)
    {
        while ($row = mysqli_fetch_array($result))
        {
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
function fetchdevicetoken($messageto, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query6 = "select status, device_token from current_login_status where user_id = $messageto";
    $result6 = mysqli_query($dbc, $query6);

    $receiverisonline = FALSE;
    $devicetokenofreceiver = NULL;

    if (mysqli_num_rows($result6) != 0)
    {
        while ($row6 = mysqli_fetch_array($result6))
        {
            if ($row6[0] == 1)
            {
                $receiverisonline = TRUE;
                $devicetokenofreceiver = $row6[1];
                break;
            }
        }
    }

    // if receiveer is online, get current device token, otherwise main device token

    if (is_null($devicetokenofreceiver))
    {

        $query7 = "select devicetoken from user_details where user_id = $messageto";
        $result7 = mysqli_query($dbc, $query7);
        if (mysqli_num_rows($result7) != 0)
        {
            while ($row7 = mysqli_fetch_array($result7))
            {
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
function fetchgametype($sessionid, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "select game_type from table_gamesessions where session_id = " . $sessionid;

    $result = mysqli_query($dbc, $query);

    $gametype = "";
    if (mysqli_num_rows($result) == 1)
    {
        while ($row = mysqli_fetch_array($result))
        {
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
function fetchname($messagefrom, $dbc = FALSE)
{

    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");

        $toclose = TRUE;
    }

    $query5 = "select name from user_details where user_id = $messagefrom";
    $result5 = mysqli_query($dbc, $query5);
    $nameinpush = NULL;
    if (mysqli_num_rows($result5) == 1)
    {
        while ($row5 = mysqli_fetch_array($result5))
        {
            $nameinpush = $row5[0];
        }
    }
    else
    {
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
function setlatestmessage($sessionid, $userid, $message, $status = 0, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // there was no row, so just add a row
    $query2 = "insert into game_messages(session_id, user_id, message, status) values ( $sessionid, $userid, '$message', $status)";
    mysqli_query($dbc, $query2);

    // if a row was affected, close connection and send true
    if (mysqli_affected_rows($dbc) == 1)
    {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
    else
    {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************

function increasedecreasechips($userid, $chips, $status, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // fetch no of chips of this user
    $alreadyhavechips = getchips($userid);

    // check if we have got the chips
    if (is_null($alreadyhavechips))
    {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    } else
    {
        // get new chips count
        if ($status == 1)
        {
            $newchipscount = $alreadyhavechips + $chips;
        }
        else
        {
            $newchipscount = $alreadyhavechips - $chips;
        }
        // set this as the no of chips of this user
        $done = setchips($userid, $newchipscount);

        // check if it is done successfully
        if ($done)
        {

            if ($toclose)
                mysqli_close($dbc);

            return TRUE;
        }
        else
        {

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

function format_cash($cash)
{

    // strip any commas 
    $cash = (0 + STR_REPLACE(',', '', $cash));

    // make sure it's a number...
    IF (!IS_NUMERIC($cash))
    {
        RETURN FALSE;
    }

    // filter and format it 
    IF ($cash > 1000000000000)
    {
        RETURN ROUND(($cash / 1000000000000), 1) . ' T';
    }
    ELSEIF ($cash > 1000000000)
    {
        RETURN ROUND(($cash / 1000000000), 1) . ' B';
    }
    ELSEIF ($cash > 1000000)
    {
        RETURN ROUND(($cash / 1000000), 1) . ' M';
    }
    ELSEIF ($cash > 1000)
    {
        RETURN ROUND(($cash / 1000), 1) . ' K';
    }

    RETURN NUMBER_FORMAT($cash);
}

//****************************************************************************************
function setlatestmessageid($sessionid, $userid, $message, $status = 0, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // there was no row, so just add a row
    $query2 = "insert into game_messages(session_id, user_id, message, status) values ( $sessionid, $userid, '$message', $status)";
    mysqli_query($dbc, $query2);

    // if a row was affected, close connection and send true
    if (mysqli_affected_rows($dbc) == 1)
    {
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

function checkrouletteresult($selectedcases, $winningnumber)
{

// check if the winning number exists in selected cases
    if (in_array($winningnumber, $selectedcases, TRUE))
    {

        return TRUE;
    }
    else
    {

        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************


function getroulettemultiplier($selectedcasesarray, $winningnumber)
{

    // we already know that the person is winner
    $multiple = 1;

    // if a single number was bet
    if (count($selectedcasesarray) == 1)
    {

        // he has bet single number, put all possible cases in an array,then check for special cases
        $specialcasesarray = array("00", "0", "1", "2", "3");

        // check if the number that has been bet, is a special number
        if (in_array($winningnumber, $specialcasesarray, TRUE))
        {

            // pay him six times
            $multiple = 6;
        }
        else
        {

            $multiple = 35;
        }
    }
    elseif (count($selectedcasesarray) == 2)
    {

        $multiple = 17;
    }
    elseif (count($selectedcasesarray) == 3)
    {

        $multiple = 11;
    }
    elseif (count($selectedcasesarray) == 4)
    {

        $multiple = 8;
    }
    elseif (count($selectedcasesarray) == 6)
    {

        $multiple = 5;
    }
    elseif (count($selectedcasesarray) == 12)
    {

        $multiple = 2;
    }
    elseif (count($selectedcasesarray) == 18)
    {

        $multiple = 1;
    }
    return $multiple;
}

//
//****************************************************************************************
//
//****************************************************************************************


function rouletteGameSeconds($sessionid, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // set time one first
    setTimeZone();

    $query3 = "select datetime from roulette_game_data where session_id = $sessionid";
    $result3 = mysqli_query($dbc, $query3);

    if (mysqli_num_rows($result3) != 0)
    {
        $row3 = mysqli_fetch_row($result3);
        $time = strtotime($row3[0]) - time();

        // this is the time remaining for roulette 
        if ($time > 0)
        {
            if ($toclose)
                mysqli_close($dbc);
            return $time;
        }
        else
        {
            // initialte the session again

            $date3 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+47 seconds'));
            $query7 = "update roulette_game_data set datetime = '$date3' where session_id = $sessionid";
            mysqli_query($dbc, $query7);

            if (mysqli_affected_rows($dbc) == 1)
            {
                if ($toclose)
                    mysqli_close($dbc);
                return 45;
            } else
            {
                if ($toclose)
                    mysqli_close($dbc);
                return FALSE;
            }
        }
    } else
    {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************


function setTimeZone()
{

    $timezone = "Asia/Calcutta";
    if (function_exists('date_default_timezone_set'))
        date_default_timezone_set($timezone);
}

//
//****************************************************************************************
//
//****************************************************************************************

function currentRouletteGameStatus($sessionid, $dbc = FALSE)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "select status from roulette_game_data where session_id = $sessionid";
    $result = mysqli_query($dbc, $query);

    if (mysqli_num_rows($result) > 0)
    {
        $row = mysqli_fetch_row($result);
        if ($toclose)
            mysqli_close($dbc);
        return $row[0];
    }
    else
    {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************


function checksomeonebetrouletteornot($sessionid, $dbc)
{
    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "select * from roulette_bets where session_id = $sessionid and cases is not null and amount != 0";
    $result = mysqli_query($dbc, $query);

    if (mysqli_num_rows($result) == 0)
    {
        // no one has bet, retuen false
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
    else
    {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
}

//****************************************************************************************
//
//****************************************************************************************

function convertChips($chips)
{
    // check if M exists
    $result = strpos($chips, 'M');

    if ($result === FALSE)
    {
        // check if it is a K
        $result2 = strpos($chips, 'K');
        if ($result2 === FALSE)
        {
            // check if it is a B
            $result3 = strpos($chips, 'B');

            if ($result3 === FALSE)
            {
                return $chips;
            }
            else
            {
                // create a substring, and return
                $returnable = (substr($chips, 0, strlen($chips) - 1) * 1000000000);
                return $returnable;
            }
        }
        else
        {
            // create a substring, and return
            $returnable = (substr($chips, 0, strlen($chips) - 1) * 1000);
            return $returnable;
        }
    }
    else
    {
        // create a substring, and return
        $returnable = (substr($chips, 0, strlen($chips) - 1) * 1000000);
        return $returnable;
    }
}

//****************************************************************************************
//
//****************************************************************************************

function convertBackChips($chips)
{
    if ($chips < 0)
    {
        $chips = -$chips;
    }
    if ($chips >= 1000000000)
    {
        RETURN ROUND(($chips / 1000000000), 0) . 'B';
    }
    elseif ($chips >= 1000000)
    {
        RETURN ROUND(($chips / 1000000), 0) . 'M';
    }
    elseif ($chips >= 1000)
    {
        RETURN ROUND(($chips / 1000), 0) . 'K';
    }
    else
    {
        return $chips;
    }
}

//****************************************************************************************
//
//****************************************************************************************

function getnewcard($alreadygivencards)
{
    //generate  a random number
    $card = (string) rand(1, 52);
    if (in_array($card, $alreadygivencards, TRUE))
    {
        return getnewcard($alreadygivencards);
    }
    elseif (is_null($card))
    {
        return getnewcard($alreadygivencards);
    }
    else
        return $card;
}

//****************************************************************************************
//
//****************************************************************************************

function givecardstoall($dbc, $sessionid)
{

    $toclose = FALSE;

    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }
    $alreadygivencards = array();

    // check how many active users are there 
    $query12 = "select * from blackjack_bets where session_id = $sessionid and amount!='0' ";
    $result12 = mysqli_query($dbc, $query12);

    if (mysqli_num_rows($result12) > 0)
    {
        // for each user generate two cards
        while ($row12 = mysqli_fetch_array($result12))
        {
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
    else
    {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
}

//****************************************************************************************
//
//****************************************************************************************

function getcardvalue($card)
{
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


function doblackjackcalculations($sessionid, $dbc, $delay = FALSE)
{

    $toclose = FALSE;
    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $alreadygivencards = array();

    $query10 = "select cards from blackjack_bets where session_id = $sessionid";
    $result10 = mysqli_query($dbc, $query10);

    if (mysqli_num_rows($result10) > 0)
    {
        while ($row10 = mysqli_fetch_array($result10))
        {
            if (!is_null($row10[0]))
            {
                $cardsexploded = explode(',', $row10[0]);
                $alreadygivencards = array_merge(array_unique(array_merge($alreadygivencards, $cardsexploded)));
            }
        }
    }

    //now give two cards to the dealer
    $querym1 = "select cards from blackjack_bets where session_id = $sessionid and user_id = 0";
    $resultm1 = mysqli_query($dbc, $querym1);

    if (mysqli_num_rows($resultm1) > 0)
    {
        $rowm1 = mysqli_fetch_row($resultm1);
        $dealercards = $rowm1[0];
        $dealercardexploded = explode(',', $dealercards);

        $dealercard1 = $dealercardexploded[0];
        $dealercard2 = $dealercardexploded[1];

        $valdealercard1 = getcardvalue($dealercard1);
        $valdealercard2 = getcardvalue($dealercard2);

        if (($valdealercard1 == 1 ) || ($valdealercard1 == 14 ) || ($valdealercard1 == 27 ) || ($valdealercard1 == 40 ))
            $valdealercard1 = 11;
        elseif ($valdealercard2 == 1 || ($valdealercard1 == 14 ) || ($valdealercard1 == 27 ) || ($valdealercard1 == 40 ))
            $valdealercard2 = 11;

        $dealertotal = $valdealercard1 + $valdealercard2;

        $queryu = "update blackjack_bets set player_status = '1' where session_id = $sessionid and user_id = 0";
        mysqli_query($dbc, $queryu);



        while ($dealertotal < 17)
        {

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
            if (mysqli_num_rows($resultn1) > 0)
            {
                while ($rown1 = mysqli_fetch_array($resultn1))
                {
                    array_push($allpushids, $rown1[0]);
                }
            }


            if ($allpushids > 0)
            {

                $passphrase = 'abcd';
                $ctx = stream_context_create();
                stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                if (!$fp)
                    exit("Failed to connect: $err $errstr" . PHP_EOL);

                for ($i = 0; $i < count($allpushids); $i++)
                {

                    $body['aps'] = array(
                        'alert' => 'Dealer hit a card in Blackjack',
                        'sound' => '3'
                    );

                    $payload = json_encode($body);

                    $devicetoken = fetchdevicetoken($allpushids[$i], $dbc);

                    $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                    fwrite($fp, $msg, strlen($msg));
                }

                fclose($fp);
            }
            if (($dealercard3 == 1 ) || ($dealercard3 == 14 ) || ($dealercard3 == 27 ) || ($dealercard3 == 40 ))
            {
                $dealertotal += $valdealercard3;
                if ($dealertotal > 21)
                    $dealertotal -= 10;
            }
            else
            {
                $dealertotal += $valdealercard3;
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

        if (mysqli_num_rows($result1) > 0)
        {
            while ($row1 = mysqli_fetch_array($result1))
            {
                $playeruserid = $row1[0];
                $playeramount = $row1[1];
                $playercards = $row1[2];

                // check if some person has splitted
                $playercardssplitted = explode(':', $playercards);
                $playeramountsplitted = explode(':', $playeramount);

                $playerwonorlose = 0;
                for ($i = 0; $i < count($playeramountsplitted); $i++)
                {

                    if ($playeramountsplitted[$i] != 0)
                    {
                        $totalofcards = 0;
                        $cardsofthisuser = explode(',', $playercardssplitted[$i]);
                        for ($j = 0; $j < count($cardsofthisuser); $j++)
                        {
                            $cardvalue11set = FALSE;
                            if (( getcardvalue($cardsofthisuser[$j]) == 1 ) ||
                                    ( getcardvalue($cardsofthisuser[$j]) == 14 ) ||
                                    ( getcardvalue($cardsofthisuser[$j]) == 27 ) ||
                                    ( getcardvalue($cardsofthisuser[$j]) == 40 ))
                            {
                                if (!$cardvalue11set)
                                {
                                    $totalofcards += 11;
                                    if ($totalofcards > 21)
                                        $totalofcards -=10;

                                    $cardvalue11set = TRUE;
                                }
                                else
                                    $totalofcards +=1;
                            }
                            else
                            {
                                $totalofcards += getcardvalue($cardsofthisuser[$j]);
                            }
                        }
                        if ($dealertotal > 21 && $totalofcards > 21)
                        {
                            
                        }
                        else
                        {
                            if ($dealertotal > 21)
                                $dealertotal = 0;
                            elseif ($totalofcards > 21)
                                $totalofcards = 0;

                            if ($totalofcards > $dealertotal)
                            {
                                $playerwonorlose += convertChips($playeramountsplitted[$i]);
                            }
                            elseif ($totalofcards < $dealertotal)
                            {
                                $playerwonorlose -= convertChips($playeramountsplitted[$i]);
                            }
                        }
                    }
                }
                //check how much this player has won or lose 
                if ($playerwonorlose > 0)
                {
                    $message = "+ " . convertBackChips($playerwonorlose);

                    setlatestmessage($sessionid, $playeruserid, $message, 1, $dbc);

                    increasedecreasechips($playeruserid, $playerwonorlose, 1, $dbc);

                    $pushids[] = array($playerwonorlose, $playeruserid);

                    $dealerwonorlose -= $playerwonorlose;

                    $feedmsg = fetchname($playeruserid, $dbc) . ' won ' . convertBackChips($playerwonorlose) . ' chips in ' . fetchgametype($sessionid, $dbc);
                    insertIntoFeed($playeruserid, $feedmsg, $dbc);
                }
                if ($playerwonorlose < 0)
                {
                    $message = "- " . convertBackChips($playerwonorlose);

                    setlatestmessage($sessionid, $playeruserid, $message, 1, $dbc);

                    increasedecreasechips($playeruserid, -$playerwonorlose, 0, $dbc);

                    $pushids[] = array($playerwonorlose, $playeruserid);

                    $dealerwonorlose -= $playerwonorlose;
                }
                if ($playerwonorlose == 0)
                {
                    $message = "Tie";

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

                if ($pushids[$i][0] > 0)
                {
                    $body['aps'] = array(
                        'alert' => 'You won ' . convertBackChips($pushids[$i][0]) . ' chips in BlackJack',
                        'sound' => '3'
                    );
                }
                else if ($pushids[$i][0] < 0)
                {

                    $body['aps'] = array(
                        'alert' => 'You lose ' . convertBackChips(- $pushids[$i][0]) . ' chips in BlackJack',
                        'sound' => '3'
                    );
                }
                else
                {

                    $body['aps'] = array(
                        'alert' => 'You won/lose nothing in BlackJack',
                        'sound' => '3'
                    );
                }

                $payload = json_encode($body);

                $devicetoken = fetchdevicetoken($pushids[$i][1], $dbc);

                $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                fwrite($fp, $msg, strlen($msg));
            }

            fclose($fp);

            if ($toclose)
                mysqli_close($dbc);

            return TRUE;
        }
    }
}

//****************************************************************************************
//
//****************************************************************************************

function sendthepush($message, $userid, $dbc, $sessionid)
{

    $pushids = array();

    $queryn1 = "select user_id from blackjack_bets where user_id != 0 and session_id =" . $sessionid;
    $result1 = mysqli_query($dbc, $queryn1);
    if (mysqli_num_rows($result1) > 0)
    {
        while ($row1 = mysqli_fetch_array($result1))
        {
            array_push($pushids, $row1[0]);
        }
    }

    $passphrase = 'abcd';
    $ctx = stream_context_create();
    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

    $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

    if (!$fp)
        exit("Failed to connect: $err $errstr" . PHP_EOL);
    for ($i = 0; $i < count($pushids); $i++)
    {

        $body['aps'] = array(
            'alert' => fetchname($userid) . "'" . strtoupper($message) . "' in Blackjack Game",
            'sound' => '3'
        );

        $payload = json_encode($body);

        $devicetoken = fetchdevicetoken($pushids[$i], $dbc);

        $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

        fwrite($fp, $msg, strlen($msg));
    }
    fclose($fp);
}

//****************************************************************************************
//
//****************************************************************************************

function givecardstolatecomers($sessionid, $dbc = FALSE)
{

    $toclose = FALSE;
    if (!$dbc)
    {

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    // give cards to persons who have not been given cards 
    $query10 = "select cards from blackjack_bets where session_id = $sessionid";
    $result10 = mysqli_query($dbc, $query10);
    $alreadygivencards = array();

    if (mysqli_num_rows($result10) > 0)
    {
        while ($row10 = mysqli_fetch_array($result10))
        {
            if (!is_null($row10[0]))
            {
                $cardsexploded = explode(',', $row10[0]);
                $alreadygivencards = array_merge(array_unique(array_merge($alreadygivencards, $cardsexploded)));
            }
        }
    }

    //now give two cards to the dealer
    $querym1 = "select user_id from blackjack_bets where session_id = $sessionid and cards = ''";
    $resultm1 = mysqli_query($dbc, $querym1);

    if (mysqli_num_rows($resultm1) > 0)
    {
        while ($rowm1 = mysqli_fetch_array($resultm1))
        {
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
function insertIntoFeed($userid, $message, $dbc)
{
    $toclose = FALSE;

    if (!$dbc)
    {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "insert into casino_feeds(user_id, message) values ($userid, '$message')";
    if (mysqli_query($dbc, $query))
    {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
    else
    {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//****************************************************************************************
//
//****************************************************************************************
function getTimeAgo($time)
{
    setTimeZone();

    //check how many days have passed
    $dayspassed = floor((time() - strtotime($time)) / (60 * 60 * 24));
    if ($dayspassed == 0)
    {
        // check how many hours have passed
        $hourspassed = floor((time() - strtotime($time)) / (60 * 60));
        $minutespassed = floor((time() - strtotime($time) - ($hourspassed * 60 * 60)) / (60));

        if ($hourspassed > 0)
            return $hourspassed . ' hr ' . $minutespassed . ' mins ago';
        else
            return $minutespassed . ' mins ago';
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
function checkPlayer($userid, $sessionid, $dbc = FALSE)
{
    $toclose = FALSE;
    if (!$dbc)
    {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    setlatestmessage($sessionid, $userid, 'Check', 0, $dbc);

    $query = "update omaha_bets set player_status = 2 where user_id = $userid and session_id= $sessionid";

    if (mysqli_query($dbc, $query))
    {
        if ($toclose)
            mysqli_close($dbc);
        return TRUE;
    }
    else
    {
        if ($toclose)
            mysqli_close($dbc);
        return FALSE;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************
function callPlayer($userid, $sessionid, $dbc = FALSE)
{
    $toclose = FALSE;
    if (!$dbc)
    {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }
    setlatestmessage($sessionid, $userid, 'Call', 0, $dbc);

    // fetch highest bet
    $query1 = "select min_bet from omaha_game_data where session_id = $sessionid";
    $result1 = mysqli_query($dbc, $query1);

    if (mysqli_num_rows($result1) > 0)
    {
        $row1 = mysqli_fetch_row($result);
        $bettoplace = $row1[0];

        // increase bet of person to this a(mount
        $query2 = "select amount from omaha_bets where session_id = $sessionid and user_id = $userid";
        $result2 = mysqli_query($dbc, $query2);

        if (mysqli_num_rows($result2) > 0)
        {
            $row2 = mysqli_fetch_row($result2);
            $prevbet = $row2[0];

            $newbet = $prevbet + $bettoplace;

            $query3 = "update omaha_bets set player_status = 2, amount = $newbet where user_id = $userid and session_id = $sessionid";
            if (mysqli_query($dbc, $query3))
            {
                if ($toclose)
                    mysqli_close($dbc);
                return TRUE;
            }
            else
            {
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
function startNextRound($sessionid, $dbc = FALSE)
{
    $toclose = FALSE;
    if (!$dbc)
    {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }


    // collect all chips and put in pot also 
    $amounttoadd = 0;
    $playerseligible = "";
    $potsize = 0;

    $query3 = "select sum(amount_bet) from omaha_bets where session_id = $sessionid";
    $result3 = mysqli_query($dbc, $query3);
    if (mysqli_num_rows($result3) > 0)
    {
        $row3 = mysqli_fetch_row($result3);
        $amounttoadd = $row3[0];
    }

    $query4 = "select user_id, amount_bet from omaha_bets where session_id = $sessionid and is_folded !=0 and chips_available > 0";
    $result4 = mysqli_query($dbc, $query4);
    if (mysqli_num_rows($result4) > 0)
    {
        while ($row4 = mysqli_fetch_array($result4))
        {
            $playerseligible1 = $row4[0] . ',';
            $potsize = $row4[1];
        }
        $playerseligible = substr($playerseligible1, 0, strlen($playerseligible1) - 1);
    }

    $query5 = "select max(id) from omaha_pots where session_id = $sessionid";
    $result5 = mysqli_query($dbc, $query5);
    if (mysqli_num_rows($result5) > 0)
    {
        $row5 = mysqli_fetch_row($result5);
        $query6 = "update omaha_bets set pot_amount = pot_amount + $amounttoadd, pot_players = '$playerseligible', pot_size = $potsize where id = $row5[0]";
        mysqli_query($dbc, $query6);
    }
    else
    {
        $query6 = "insert into omaha_pots (pot_amount, pot_players, session_id, pot_size) values($amounttoadd, '$playerseligible', $sessionid, $potsize)";
        mysqli_query($dbc, $query6);
    }

    $query1 = "update omaha_bets set player_status = 0, amount_bet=0 where session_id = $sessionid";
    mysqli_query($dbc, $query1);

    $query12 = "select round from omaha_game_data where session_id = $sessionid";
    $result12 = mysqli_query($dbc, $query12);

    if (mysqli_num_rows($result12) > 0)
    {
        $row12 = mysqli_fetch_row($result12);
        $olderround = $row12[0];

        switch ($olderround)
        {

            case 1:
                // put 3 cards on table
                $alreadygivencards = array();
                $query2 = "select cards from omaha_bets where session_id = $sessionid";
                $result2 = mysqli_query($dbc, $query2);

                if (mysqli_num_rows($result2) > 0)
                {
                    while ($row2 = mysqli_fetch_array($result2))
                    {
                        $cardsexploded = explode(',', $row2[0]);
                        for ($i = 0; $i < count($cardsexploded); $i++)
                        {
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

                $query3 = "update omaha_bets set cards='$allcards', round=3 where session_id = $sessionid and user_id = $thisuserid";
                mysqli_query($dbc, $query3);

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
                break;
            case 2:
                // append 1 more card to table
                $alreadygivencards = array();
                $query2 = "select cards from omaha_bets where session_id = $sessionid";
                $result2 = mysqli_query($dbc, $query2);

                if (mysqli_num_rows($result2) > 0)
                {
                    while ($row2 = mysqli_fetch_array($result2))
                    {
                        $cardsexploded = explode(',', $row2[0]);
                        for ($i = 0; $i < count($cardsexploded); $i++)
                        {
                            array_push($alreadygivencards, $cardsexploded[$i]);
                        }
                    }
                }

                $query3 = "select cards from omaha_game_data where session_id = $sessionid";
                $result3 = mysqli_query($dbc, $query3);
                if (mysqli_num_rows($result3) > 0)
                {
                    $row3 = mysqli_fetch_row($result3);
                    $oldtablecards = $row3[0];
                    $oldtablecardsexploded = explode(',', $oldtablecards);
                    for ($i = 0; $i < count($oldtablecardsexploded); $i++)
                    {
                        array_push($alreadygivencards, $oldtablecardsexploded[$i]);
                    }
                    // now generate one new card and append
                    $card1 = getnewcard($alreadygivencards);
                    array_push($alreadygivencards, $card1);

                    $newcards = $oldtablecards . ',' . $card1;

                    $query3 = "update omaha_bets set cards='$newcards', round=4 where session_id = $sessionid and user_id = $thisuserid";
                    mysqli_query($dbc, $query3);

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

                break;
            case 3:
                // append 1 more card to table
                $alreadygivencards = array();
                $query2 = "select cards from omaha_bets where session_id = $sessionid";
                $result2 = mysqli_query($dbc, $query2);

                if (mysqli_num_rows($result2) > 0)
                {
                    while ($row2 = mysqli_fetch_array($result2))
                    {
                        $cardsexploded = explode(',', $row2[0]);
                        for ($i = 0; $i < count($cardsexploded); $i++)
                        {
                            array_push($alreadygivencards, $cardsexploded[$i]);
                        }
                    }
                }

                $query3 = "select cards from omaha_game_data where session_id = $sessionid";
                $result3 = mysqli_query($dbc, $query3);
                if (mysqli_num_rows($result3) > 0)
                {
                    $row3 = mysqli_fetch_row($result3);
                    $oldtablecards = $row3[0];
                    $oldtablecardsexploded = explode(',', $oldtablecards);
                    for ($i = 0; $i < count($oldtablecardsexploded); $i++)
                    {
                        array_push($alreadygivencards, $oldtablecardsexploded[$i]);
                    }
                    // now generate one new card and append
                    $card1 = getnewcard($alreadygivencards);
                    array_push($alreadygivencards, $card1);

                    $newcards = $oldtablecards . ',' . $card1;

                    $query3 = "update omaha_bets set cards='$newcards', round=5 where session_id = $sessionid and user_id = $thisuserid";
                    mysqli_query($dbc, $query3);
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

                break;
            case 4;

                doOmahaCalculations($sessionid, $dbc);
                $qery5 = "update omaha_game_data set round=0, status=0 where session_id = $sessionid";
                mysqli_query($dbc, $query5);

                $query6 = "update omaha_bets set is_folded= 0, amount_bet = 0, player_status = 0, cards= '' where session_id = $sessionid";
                mysqli_query($dbc, $query6);

                break;
        }
        return $olderround;
    }
}

//
//****************************************************************************************
//
//****************************************************************************************
function sendpushtoplayers($pushids, $message)
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

        $body['aps'] = array(
            'alert' => $message,
            'sound' => '3'
        );

        $payload = json_encode($body);

        $devicetoken = fetchdevicetoken($pushids[$i], $dbc);

        $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

        fwrite($fp, $msg, strlen($msg));
    }
    fclose($fp);
}

//
//****************************************************************************************
//
//****************************************************************************************

function foldPlayer($userid, $sessionid, $dbc)
{
    $toclose = FALSE;
    if (!$dbc)
    {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $query = "update omaha_bets set player_status = 2, cards= '', is_folded = 1 where session_id = $sessionid and user_id = $userid";
    mysqli_query($dbc, $query);

    // also take this players userid out of all the availablepots for this session
    $query6 = "select * from omaha_pots where session_id = $sessionid";
    $result6 = mysqli_query($dbc, $query6);
    if (mysqli_num_rows($result6) > 0)
    {
        while ($row6 = mysqli_fetch_array($result6))
        {
            $potid = $row6['id'];
            $potplayers = $row6['pot_players'];
            $potplayersexploded = explode(',', $potplayers);
            $thisuserarray = array($userid);
            $newplayersarray = array_diff($potplayersexploded, $thisuserarray);

            $newplayers = join(',', $newplayersarray);

            $query7 = "update omaha_pots set pot_players = '$newplayers' where id = $potid";
            mysqli_query($dbc, $query7);
        }
    }

    $query2 = "select amount_bet from omaha_bets where user_id = $userid and session_id = $sessionid";
    $result2 = mysqli_query($dbc, $query2);

    if (mysqli_num_rows($result2) > 0)
    {
        $row2 = mysqli_fetch_row($result2);
        $amountbet = $row2[0];
        if ($amountbet != 0)
        {
            $query3 = "update omaha_bets set amount_bet = 0 where user_id = $userid and session_id = $sessionid";
            mysqli_query($dbc, $query3);

            $query4 = "select id, pot_amount from omaha_pots where session_id = $sessionid";
            $result4 = mysqli_query($dbc, $query4);

            if (mysqli_num_rows($result4) > 0)
            {
                $potamount = 0;
                $potid = 0;
                while ($row4 = mysqli_fetch_array($result4))
                {
                    $potamount = $row4[1];
                    $potid = $row4[0];
                }

                $newpotamount = $potamount + $amountbet;
                $query5 = "update omaha_pots set pot_amount = $newpotamount where id = $potid";
                mysqli_query($dbc, $query5);
            }
            else
            {
                $query5 = "insert into omaha_pots (pot_amount, session_id) values ($amountbet, $sessionid)";
                mysqli_query($dbc, $query5);
            }
        }
    }
}

//
//****************************************************************************************
//
//****************************************************************************************
function doOmahaCalculations($sessionid, $dbc= FALSE)
{
    $toclose = FALSE;
    if (!$dbc)
    {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $toclose = TRUE;
    }

    $tablecards = '';
    $tablecardscombinations = array();
    $query1 = "select cards from omaha_game_data where session_id = $sessionid";
    $result1 = mysqli_query($dbc, $query1);
    if (mysqli_num_rows($result1) > 0)
    {
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

    // first of all find winner
    $query = "select * from omaha_bets where session_id = $sessionid and is_folded = 0 and chips_available > 0";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) > 0)
    {
        while ($row = mysqli_fetch_array($result))
        {
            $thisplayerid = $row['user_id'];

            $thisplayercards = $row['cards'];
            $thisplayercardsexploded = explode(',', $thisplayercards);
            $thisplayercardsarray = array();

            array_push($thisplayercardsarray, $thisplayercardsexploded[0] . ',' . $thisplayercardsexploded[1]);
            array_push($thisplayercardsarray, $thisplayercardsexploded[0] . ',' . $thisplayercardsexploded[2]);
            array_push($thisplayercardsarray, $thisplayercardsexploded[0] . ',' . $thisplayercardsexploded[3]);
            array_push($thisplayercardsarray, $thisplayercardsexploded[1] . ',' . $thisplayercardsexploded[2]);
            array_push($thisplayercardsarray, $thisplayercardsexploded[1] . ',' . $thisplayercardsexploded[3]);
            array_push($thisplayercardsarray, $thisplayercardsexploded[2] . ',' . $thisplayercardsexploded[3]);

            $allpossiblecombinationsforthisplayer = array();
            for ($i = 0; $i < count($tablecardscombinations); $i++)
            {
                for ($j = 0; $j < count($thisplayercardsarray); $j++)
                {
                    array_push($allpossiblecombinationsforthisplayer, $tablecardscombinations[$i] . ',' . $thisplayercardsarray[$j]);
                }
            }

            // now i have all the combinations for this plAYer, and i have to choose the best one
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
                sort($allpossiblecombinationsforthisplayer[$i]);

            $resultsarray = array();

            // check for royal flush
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                if ((($allpossiblecombinationsforthisplayer[$i][0] == 1)
                        || ($allpossiblecombinationsforthisplayer[$i][0] == 14)
                        || ($allpossiblecombinationsforthisplayer[$i][0] == 27)
                        || ($allpossiblecombinationsforthisplayer[$i][0] == 40))
                        &&
                        ((($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 1)
                        && ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 1)
                        && ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 1)
                        && ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 1))))
                {
                    // player has done a royal flush
                    $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_card) values($sessionid, $thisplayerid, '$allpossiblecombinationsforthisplayer[$i]', 1, ' " . $allpossiblecombinationsforthisplayer[$i][4] . "')";
                    mysqli_query($dbc, $query21);

                    break;
                }
                else
                {
                    // check for straight flush
                    if ((($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 1)
                            && ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 1)
                            && ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 1)
                            && ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 1)))
                    {
                        // player has a stright flush

                        $query21 = "insert into omaha_results(session_id, user_id, combination, rank) values($sessionid, $thisplayerid, '$allpossiblecombinationsforthisplayer[$i]', 2)";
                        mysqli_query($dbc, $query21);
                        break;
                    }
                    else
                    {
                        // check for 4 of a kind
                        $uniquevalues1 = array_count_values($allpossiblecombinationsforthisplayer[$i]);
                        $uniquevalues = array_flip($uniquevalues1);
                        if (!is_null($uniquevalues[4]))
                        {
                            // player has 4 of a kind
                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank) values($sessionid, $thisplayerid, '$allpossiblecombinationsforthisplayer[$i]', 3)";
                            mysqli_query($dbc, $query21);
                            break;
                        }
                        else
                        {
                            // check for full house
                            if (!is_null($uniquevalues[3]) && !is_null($uniquevalues[2]))
                            {
                                // player has a full house
                                $query21 = "insert into omaha_results(session_id, user_id, combination, rank) values($sessionid, $thisplayerid, '$allpossiblecombinationsforthisplayer[$i]', 4)";
                                mysqli_query($dbc, $query21);
                                break;
                            }
                            else
                            {
                                // check for flush
                                if (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][1]) < 13)
                                {
                                    // player has a flush
                                    $query21 = "insert into omaha_results(session_id, user_id, combination, rank) values($sessionid, $thisplayerid, '$allpossiblecombinationsforthisplayer[$i]', 5)";
                                    mysqli_query($dbc, $query21);
                                    break;
                                }
                                else
                                {
                                    // check for a straight
                                    if ((($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 1)
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
                                            && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 1))
                                            || ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 14)
                                            || ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 27)
                                            || ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 40))
                                    {
                                        // player fas  striaght
                                        $query21 = "insert into omaha_results(session_id, user_id, combination, rank) values($sessionid, $thisplayerid, '$allpossiblecombinationsforthisplayer[$i]', 6)";
                                        mysqli_query($dbc, $query21);
                                        break;
                                    }
                                    else
                                    {
                                        // check for 3 of a kind
                                        if (!is_null($uniquevalues[3]))
                                        {
                                            // player has 3 of a kind
                                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank) values($sessionid, $thisplayerid, '$allpossiblecombinationsforthisplayer[$i]', 7)";
                                            mysqli_query($dbc, $query21);
                                            break;
                                        }
                                        else
                                        {
                                            // check for a 2 pair
                                            if ((!is_null($uniquevalues[2])) && (!is_null($uniquevalues[1])))
                                            {
                                                // player has a pair
                                                $query21 = "insert into omaha_results(session_id, user_id, combination, rank) values($sessionid, $thisplayerid, '$allpossiblecombinationsforthisplayer[$i]', 8)";
                                                mysqli_query($dbc, $query21);
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // loop breaks here
            $query23 = "select min(rank) from omaha_results where session_id = $sessionid";
            $result23 = mysqli_query($dbc, $query23);
            if (mysqli_num_rows($result23) > 0)
            {
                $row23 = mysqli_fetch_row($result23);
                $maxrank = $row23[0];

                $query24 = "select * from omaha_results where rank = $maxrank";
                $result24 = mysqli_query($dbc, $query24);
                $noofsamerankers = mysqli_num_rows($result24);


                if ($noofsamerankers > 0)
                {

                    if ($noofsamerankers == 1)
                    {
                        $row24 = mysqli_fetch_row($result24);

                        $query25 = "select * from omaha_pots where pot_players like '%";
                    }
                    else
                    {
                        // split the amount
                    }
                }
            }
        }
    }
}

?>