<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

// check if requiredd fields were passed in url
if (is_null($_GET['session_id']) || is_null($_GET['user_id']) || is_null($_GET['amount']))
{
    echo 'session_id, or user_id or action was not passed';
}
else
{
    setTimeZone();
    
    // grab the variable
    $sessionid = $_GET['session_id'];
    $userid    = $_GET['user_id'];
    $amount    = $_GET['amount'];

    //sanity check the variable
    if (!is_numeric($sessionid))
    {
        echo 'There is unexpected error';
    }
    else
    {
        // fetch all session ids playing gme in this session id
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");

        $query1 = "update blackjack_bets set amount = '$amount' where user_id = $userid and session_id = $sessionid";
        if (mysqli_query($dbc, $query1))
        {
            echo '<status>1</status>';
            setlatestmessage($sessionid, $userid, 'Bet ' . $amount . ' chips', 1, $dbc);

            // also check if all other players of this session have also bet
            $query2  = "select * from blackjack_bets where session_id = $sessionid and amount = 0 and user_id !=0 ";
            $result2 = mysqli_query($dbc, $query2);

            if (mysqli_num_rows($result2) == 0)
            {
                // we need to immidiately start the game

                givecardstoall($dbc, $sessionid);


                $querym1  = "select * from blackjack_bets where session_id = $sessionid and amount != '0'  and user_id != 0 and player_status = 0 order by datetime limit 1";
                $resultm1 = mysqli_query($dbc, $querym1);

                if (mysqli_num_rows($resultm1) > 0)
                {
                    $rowm1        = mysqli_fetch_row($resultm1);
                    $playeruserid = $rowm1[1];

                    $queryn1 = "update blackjack_bets set player_status = '1' where session_id = $sessionid and user_id = $playeruserid";
                    mysqli_query($dbc, $queryn1);
                    
                    setTimeZone();
                    $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                    $queryn2 = "update blackjack_game_data set status = 2, datetime='$date2'
                                          where session_id = $sessionid";

                    mysqli_query($dbc, $queryn2);

                    $query3  = "select user_id from blackjack_bets where session_id = $sessionid";
                    $result3 = mysqli_query($dbc, $query3);

                    $pushids = array();
                    if (mysqli_num_rows($result3) > 0)
                    {
                        while ($row3 = mysqli_fetch_array($result3))
                        {
                            array_push($pushids, $row3[0]);
                        }
                    }
                    $message = "BlackJack Game starts";

                    //also send a push to all players notifying the change
                    sendpushtoplayers($pushids, $message);
                }
            }
        }
        else
        {
            echo '<status>0</status>';
        }
        mysqli_close($dbc);
    }
}
?>
