<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if (is_null($_GET['session_id']) || is_null($_GET['user_id']))
    echo 'Session_id or user_id was not passed';
else
{
    $sessionid = $_GET['session_id'];
    $userid = $_GET['user_id'];

    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    // check table type of this sessionid
    $query = "select table_type from table_gamesessions where session_id = $sessionid";
    $result = mysqli_query($dbc, $query);

    if (mysqli_num_rows($result) == 0)
    {
        // game has expired
        echo '<status>0</status>';
    }
    else
    {
        $row = mysqli_fetch_row($result);
        $tabletype = $row[0];

        // deduct chips from users account
        if (increasedecreasechips($userid, convertChips($tabletype), 0, $dbc))
        {

            $query1 = "select * from omaha_game_data where session_id = $sessionid";
            $result1 = mysqli_query($dbc, $query1);

            if (mysqli_num_rows($result1) == 0)
            {
                $query3 = "insert into omaha_game_data(session_id, dealer_userid) values($sessionid, $userid)";
                mysqli_query($dbc, $query3);
            }

            // also set status of this player as 1
            $query2 = "insert into omaha_bets(session_id, user_id) values ( $sessionid, $userid)";
            mysqli_query($dbc, $query2);

            // send push to all
            $query3 = "select * from omaha_bets where session_id = $sessionid";
            $result3 = mysqli_query($dbc, $query3);
            $noofregdplayers = mysqli_num_rows($result3);

            if ($noofregdplayers == 6)
            {
                $pushids = array();
                $query4 = "select user_id from omaha_bets where session_id = $sessionid";
                $result4 = mysqli_query($dbc, $query4);
                if (mysqli_num_rows($result4) > 0)
                {
                    while ($row4 = mysqli_fetch_array($result4))
                    {
                        array_push($pushids, $row4[0]);
                    }
                    $message = "All players have registered for Omaha Game. So join them to play Omaha";
                    sendpushtoplayers($pushids, $message);
                }
                echo '<status>2</status>';
            }
            else
            {
                $pushids = array();
                $query4 = "select user_id from omaha_bets where session_id = $sessionid";
                $result4 = mysqli_query($dbc, $query4);
                if (mysqli_num_rows($result4) > 0)
                {
                    while ($row4 = mysqli_fetch_array($result4))
                    {
                        array_push($pushids, $row4[0]);
                    }
                    $playersleft = 6-$noofregdplayers;
                    $message = ucfirst(fetchname($userid, $dbc)) . ' registered for Omaha game. But still there are ' . $playersleft . ' players left to register';
                    sendpushtoplayers($pushids, $message);
                }
                echo '<status>1</status>';
            }
        }
        else
            echo '<status>0</status>';
    }
}
?>
