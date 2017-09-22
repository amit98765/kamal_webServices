<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if ((is_null($_GET['session_id'])) || (is_null($_GET['user_id'])) || (is_null($_GET['action'])) || (is_null($_GET['amount'])))
    echo 'session_id/user_id/action was not passed';
else
{
    $sessionid = $_GET['session_id'];
    $userid = $_GET['user_id'];
    $action = $_GET['action'];
    $amount = $_GET['amount'];

    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    switch ($action)
    {
        case 'fold':

            $query = "update omaha_bets set player_status = 2, cards= '', is_folded = 1 where session_id = $sessionid and user_id = $userid";
            mysqli_query($dbc, $query);

            $query2 = "update omaha_game_data set status = 1 where session_id = $sessionid";
            mysqli_query($dbc, $query2);

            setlatestmessage($sessionid, $userid, "Fold", 1, $dbc);

            $query3 = "select * from omaha_bets where session_id = $sessionid and is_folded = 0 and chips_available > 0";
            $result3 = mysqli_query($dbc, $query3);

            if (mysqli_num_rows($result3) < 2)
            {
                $query5 = "select user_id from omaha_bets where session_id = $sessionid and is_folded = 0 and chips_available > 0";
                $result5 = mysqli_query($dbc, $query5);

                $winnerid = 0;
                if (mysqli_num_rows($result5) > 0)
                {
                    $row5 = mysqli_fetch_row($result5);
                    $winnerid = $row5[0];
                }

                $query4 = "select sum(amount_bet) from omaha_bets where session_id = $sessionid";
                $result4 = mysqli_query($dbc, $query4);

                $amountwon = 0;
                if (mysqli_num_rows($result4) > 0)
                {
                    $row4 = mysqli_fetch_row($result4);
                    $amountwon = $row4[0];
                }

                $query7 = "select sum(pot_amount) from omaha_pots where session_id = $sessionid";
                $result7 = mysqli_query($dbc, $query7);
                if (mysqli_num_rows($result7) > 0)
                {
                    $row7 = mysqli_fetch_row($result7);

                    $thispotamount = $row7[0];


                    $amountwon += $thispotamount;
                }

                $query10 = "update omaha_bets set chips_available = chips_available + $amountwon where session_id = $sessionid and user_id = $winnerid";
                mysqli_query($dbc, $query10);

                $query9 = "delete from omaha_pots where session_id = $sessionid";
                mysqli_query($dbc, $query9);

                if ($amountwon > 0)
                {
                    setlatestmessageid($sessionid, $winnerid, "Won " . $amountwon . " chips", 1, $dbc);
                    insertIntoFeed($winnerid, "Won " . $amountwon . " chips in Omaha", $dbc);
                }
                
                // reset the game again
                $query12 = "update omaha_bets set amount_bet = 0, player_status = 0, cards = '', is_folded = 0 where session_id = $sessionid";
                mysqli_query($dbc, $query12);

                $query10 = "select id from omaha_bets where session_id = $sessionid and is_dealer = 1";
                $result10 = mysqli_query($dbc, $query10);

                if (mysqli_num_rows($result10) > 0)
                {
                    $row10 = mysqli_fetch_row($result10);
                    $olddealer = $row10[0];

                    $query11 = "update omaha_bets set is_dealer = 2 where id = $olddealer";
                    mysqli_query($dbc, $query11);

// create new dealer, set small and big blind
                    $newdealer = 0;
                    $query22 = "select id from omaha_bets where session_id = $sessionid and id < $olddealer limit 1";
                    $result22 = mysqli_query($dbc, $query22);
                    if (mysqli_num_rows($result22) > 0)
                    {
                        $row22 = mysqli_fetch_row($result22);
                        $newdealer = $row22[0];
                    }
                    else
                    {
                        $query23 = "select id from omaha_bets where session_id = $sessionid order by id limit 1";
                        $result23 = mysqli_query($dbc, $query23);
                        if (mysqli_num_rows($result23) > 0)
                        {
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

                    if (mysqli_num_rows($result40) == 0)
                    {
                        $query41 = "select id from omaha_bets where session_id = $sessionid order by id limit 2";
                        $result41 = mysqli_query($dbc, $query41);
                        if (mysqli_num_rows($result41) > 0)
                        {
                            while ($row41 = mysqli_fetch_array($result41))
                            {
                                if ($smallblind == 0)
                                    $smallblind = $row41[0];
                                else
                                    $bigblind = $row41[0];
                            }
                        }
                    }
                    elseif (mysqli_num_rows($result40) == 1)
                    {
                        $row40 = mysqli_fetch_row($result40);
                        $smallblind = $row40[0];

                        $query41 = "select id from omaha_bets where session_id = $sessionid order by id limit 1";
                        $result41 = mysqli_query($dbc, $query41);
                        if (mysqli_num_rows($result41) > 0)
                        {
                            while ($row41 = mysqli_fetch_array($result41))
                            {
                                if ($smallblind == 0)
                                    $smallblind = $row41[0];
                                else
                                    $bigblind = $row41[0];
                            }
                        }
                    }
                    elseif (mysqli_num_rows($result40) == 2)
                    {
                        while ($row40 = mysqli_fetch_array($result40))
                        {
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
                    if (mysqli_num_rows($result30) > 0)
                    {
                        $row30 = mysqli_fetch_row($result30);
                        setlatestmessage($sessionid, $row30[0], "Small Blind - 25 chips", 1, $dbc);
                    }

                    $amountbet2 = 50;
                    $newtime2 = date('Y-m-d H:i:s', time() + $amountbet2);
                    $query114 = "update omaha_bets set amount_bet = $amountbet2, datetime = '$newtime2', chips_available=chips_available-$amountbet2  where id = " . $bigblind;
                    $result114 = mysqli_query($dbc, $query114);

                    $query30 = "select user_id from omaha_bets where id = $bigblind";
                    $result30 = mysqli_query($dbc, $query30);
                    if (mysqli_num_rows($result30) > 0)
                    {
                        $row30 = mysqli_fetch_row($result30);
                        setlatestmessage($sessionid, $row30[0], "Big Blind - 50 chips", 1, $dbc);
                    }


                    $alreadygivencards = array();

                    $query2 = "select user_id from omaha_bets where session_id = $sessionid and chips_available != 0";
                    $result2 = mysqli_query($dbc, $query2);

                    if (mysqli_num_rows($result2) > 0)
                    {
                        if (fetchgametype($sessionid, $dbc) == "Omaha")
                        {
                            while ($row2 = mysqli_fetch_array($result2))
                            {
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
                        }
                        else
                        {
                            while ($row2 = mysqli_fetch_array($result2))
                            {
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
                    $query4 = "select * from omaha_bets where player_status = 0 and session_id = $sessionid and chips_available != 0 and is_folded = 0 order by datetime limit 1";
                    $result4 = mysqli_query($dbc, $query4);
                    if (mysqli_num_rows($result4) > 0)
                    {
                        $row4 = mysqli_fetch_row($dbc, $query4);
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
                    }
                }
            }
            $pushids = array();
            $query33 = "select user_id from omaha_bets where session_id = $sessionid";
            $result33 = mysqli_query($dbc, $query33);
            if (mysqli_num_rows($result33) > 0)
            {
                while ($row33 = mysqli_fetch_array($result33))
                {
                    array_push($pushids, $row33['user_id']);
                }
            }

            $pushmessage = ucfirst(fetchname($userid, $dbc)) . " folded in Omaha";
            sendpushtoplayers($pushids, $pushmessage, $dbc);
            break;
        /*
          case 'raise':
          $query = "update omaha_bets set amount_bet = amount_bet + $amount, chips_available = chips_available - $amount, player_status = 2 where session_id = $sessionid and user_id = $userid";
          mysqli_query($dbc, $query);

          $query2 = "update omaha_game_data set status = 1 where session_id = $sessionid";
          mysqli_query($dbc, $query2);

          break;
         */
        case 'bet':
            $query = "select max(amount_bet) from omaha_bets where session_id = $sessionid";
            $result = mysqli_query($dbc, $query);

            setlatestmessage($sessionid, $userid, "Bet " . $amount . " chips", 1, $dbc);

            $maxamount = 0;
            if (mysqli_num_rows($result) > 0)
            {
                $row = mysqli_fetch_row($result);
                $maxamount = $row[0];
            }

            if ($amount > $maxamount)
            {
                $query = "update omaha_bets set amount_bet = amount_bet + $amount, chips_available = chips_available - $amount, player_status = 2 where session_id = $sessionid and user_id = $userid";
                mysqli_query($dbc, $query);

                $query2 = "update omaha_game_data set status = 1 where session_id = $sessionid";
                mysqli_query($dbc, $query2);
            }
            else
            {
                // split the pot
                //detect currently active players
                $query = "update omaha_bets set chips_available = chips_available - $amount, player_status = 2 where session_id = $sessionid and user_id = $userid";
                mysqli_query($dbc, $query);

                $query5 = "select * from omaha_bets where session_id = $sessionid and is_folded = 0 and chips_available !=0";
                $result5 = mysqli_query($dbc, $query5);
                $currentactiveplayerscount = mysqli_num_rows($result5);

                $currentactiveplayers = '';
                $currentactiveplayers1 = "";
                if (mysqli_num_rows($result5) > 0)
                {
                    while ($row5 = mysqli_fetch_array($result5))
                    {
                        $currentactiveplayers1 .= $row5['user_id'] . ',';
                    }
                    $currentactiveplayers = substr($currentactiveplayers1, 0, strlen($currentactiveplayers1) - 1);
                }

                $query4 = "select pot_amount, pot_players, id from omaha_pots where session_id = $sessionid and pot_type=1 order by id desc limit 1";
                $result4 = mysqli_query($dbc, $query4);

                $oldpotamount = 0;
                $oldpotplayers = '';
                $oldpotid = 0;
                if (mysqli_num_rows($result4) > 0)
                {
                    $row4 = mysqli_fetch_row($result4);
                    $oldpotamount = $row4[0];
                    $oldpotplayers = $row4[1];
                    $oldpotid = $row4[2];

                    $query6 = "update omaha_pots set pot_amount = " . $currentactiveplayerscount * $amount . ", pot_type=0 where id = $oldpotid and session_id = $sessionid";
                    mysqli_query($dbc, $query6);

                    $newpotamount = $oldpotamount - ($currentactiveplayers * $amount);

                    $oldpotplayersarray = explode(',', $oldpotplayers);
                    for ($i = 0; $i < count($oldpotplayersarray); $i++)
                    {
                        if ($oldpotplayersarray[$i] == $userid)
                        {
                            unset($oldpotplayersarray[$i]);
                        }
                    }

                    $newpotplayers = implode(',', $oldpotplayersarray);

                    $query7 = "insert into omaha_pots(session_id, pot_players, pot_amount, pot_type) values($sessionid,'$newpotplayers' ,$newpotamount, 1)";
                    mysqli_query($dbc, $query7);
                }
                else
                {
                    /*
                     * BIG LOGICAL PROBLEM NOW.
                     * iF THERE WAS NO POT EARLIER, AND SOMEONE BET LOWER THAN CALL AMOUNT,
                     * A NEW POT NEEDS TO BE CREATED. BUT THIS WOULD ADD COMPLEXITTY
                     * MEANING THAT WHEN A POT WILL BE CREATED AFTER THIS ROUND, 
                     * IT NEEDS TO BE DETECTED, NOT TO UPDATE THAT POT, RATHER CREATE A NEW POT
                     * THIS MEANS REVISITING THE CODE ALL OVER AGAIN
                     * 
                     */
                    /*
                     * here, we have to deduct the amount from the people already bet, as well as, 
                     * the people who are left should automatically bet the same amount
                     * 
                     */
                    $query40 = "select user_id, amount_bet from omaha_bets where session_id = $sessionid and player_status = 2 and user_id not in ($userid) and chips_available > 0";
                    $result40 = mysqli_query($dbc, $query40);

                    if (mysqli_num_rows($result40) > 0)
                    {

                        $potamount = $amount;
                        $potplayers = array($userid);
                        while ($row40 = mysqli_fetch_array($result40))
                        {
                            $thispotplayer = $row40['user_id'];
                            array_push($potplayers, $thispotplayer);

                            $query21 = "update omaha_bets set pot_amount = pot_amount - $amount where session_id = $sessionid and user_id = $thispotplayer";
                            mysqli_query($dbc, $query21);

                            $potamount += $amount;
                        }
                        $newpotplayers = join(',', $potplayers);
                        $query7 = "insert into omaha_pots(session_id, pot_players, pot_amount, pot_type) values($sessionid,'$newpotplayers' ,$potamount, 0)";
                        mysqli_query($dbc, $query7);
                    }
                }
            }
            $pushids = array();
            $query33 = "select user_id from omaha_bets where session_id = $sessionid";
            $result33 = mysqli_query($dbc, $query33);
            if (mysqli_num_rows($result33) > 0)
            {
                while ($row33 = mysqli_fetch_array($result33))
                {
                    array_push($pushids, $row33['user_id']);
                }
            }

            $pushmessage = fetchname($userid, $dbc) . " bet " . $amount . ' chips';
            sendpushtoplayers($pushids, $pushmessage, $dbc);
            break;

        case 'check':

            $query = "update omaha_bets set player_status = 2 where session_id = $sessionid and user_id = $userid";
            mysqli_query($dbc, $query);

            setlatestmessage($sessionid, $userid, "Check", 1, $dbc);

            $query2 = "update omaha_game_data set status = 2 where session_id = $sessionid";
            mysqli_query($dbc, $query2);

            $pushids = array();
            $query33 = "select user_id from omaha_bets where session_id = $sessionid";
            $result33 = mysqli_query($dbc, $query33);
            if (mysqli_num_rows($result33) > 0)
            {
                while ($row33 = mysqli_fetch_array($result33))
                {
                    array_push($pushids, $row33[0]);
                }
            }

            $pushmessage = fetchname($userid, $dbc) . " Checked in Omaha";
            sendpushtoplayers($pushids, $pushmessage, $dbc);
            break;

        case 'call':
            $query3 = "select max(amount_bet) from omaha_bets where session_id = $sessionid";
            $result3 = mysqli_query($dbc, $query3);

            setlatestmessage($sessionid, $userid, "Call", 1, $dbc);

            $maxamount = 0;
            if (mysqli_num_rows($result3) > 0)
            {
                $row3 = mysqli_fetch_row($result3);
                $maxamount = $row3[0];
            }
            $mymax = 0;
            $query4 = "select amount_bet from omaha_bets where user_id = $userid and session_id = $sessionid";
            $result4 = mysqli_query($dbc, $query4);

            if (mysqli_num_rows($result4) > 0)
            {
                $row4 = mysqli_fetch_row($result4);
                $mymax = $row4[0];
            }

            $toadd = $maxamount - $mymax;

            $query = "update omaha_bets set amount_bet = amount_bet + $toadd, chips_available = chips_available - $toadd, player_status = 2 where session_id = $sessionid and user_id = $userid";
            mysqli_query($dbc, $query);

            $query2 = "update omaha_game_data set status = 1 where session_id = $sessionid";
            mysqli_query($dbc, $query2);

            $pushids = array();
            $query33 = "select user_id from omaha_bets where session_id = $sessionid";
            $result33 = mysqli_query($dbc, $query33);
            if (mysqli_num_rows($result33) > 0)
            {
                while ($row33 = mysqli_fetch_array($result33))
                {
                    array_push($pushids, $row33['user_id']);
                }
            }

            $pushmessage = fetchname($userid, $dbc) . " Called in Omaha ";
            sendpushtoplayers($pushids, $pushmessage, $dbc);
            break;

        default:
            break;
    }
    mysqli_close($dbc);
}
?>
