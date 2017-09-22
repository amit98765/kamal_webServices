<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if (is_null($_GET['session_id']))
    echo 'Session id was not passed';
else
{
    $sessionid = $_GET['session_id'];

    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    echo '<players>';

    // retunr game name, table type
    $query4 = "select * from table_gamesessions where session_id = $sessionid";
    $result4 = mysqli_query($dbc, $query4);

    $creatorid = 0;
    if (mysqli_num_rows($result4) > 0)
    {
        $row4 = mysqli_fetch_row($result4);
        echo '<game_type>' . $row4[0] . '</game_type>';
        echo '<table_type>' . $row4[2] . '</table_type>';
        $creatorid = $row4['creator_userid'];
    }

    $query5 = "select * from omaha_bets where session_id = $sessionid";
    $result5 = mysqli_query($dbc, $query5);

    $registeredids = array();

    if (mysqli_num_rows($result5) > 0)
    {
        while ($row5 = mysqli_fetch_array($result5))
        {
            echo '<player>';

            echo '<userId>';
            echo $row5['user_id'];
            echo '</userId>';

            $query6 = "select gold, chips, name from user_cash, user_details where user_cash.user_id = user_details.user_id and user_details.user_id=" . $row5['user_id'];
            $result6 = mysqli_query($dbc, $query6);

            if (mysqli_num_rows($result6) > 0)
            {
                $row6 = mysqli_fetch_row($result6);
                echo '<gold>';
                echo $row6[0];
                echo '</gold>';

                echo '<chips>';
                echo $row6[1];
                echo '</chips>';

                echo '<name>';
                echo $row6[2];
                echo '</name>';

                echo '<chips1>';
                echo number_format($row6[1]);
                echo '</chips1>';
            }

            echo '<status>';
            echo 'Registered';
            echo '</status>';

            echo '</player>';
            array_push($registeredids, $row5['user_id']);
        }
    }

    $query = "select * from invitations where session_id = $sessionid";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) > 0)
    {
        while ($row = mysqli_fetch_array($result))
        {
            echo '<player>';

            echo '<userid>';
            echo $row['invitation_to'];
            echo '</userid>';

            $query1 = "select gold, chips, name from user_cash, user_details where user_cash.user_id = user_details.user_id and user_details.user_id=" . $row['invitation_to'];
            $result1 = mysqli_query($dbc, $query1);

            if (mysqli_num_rows($result1) > 0)
            {
                $row1 = mysqli_fetch_row($result1);
                echo '<gold>';
                echo $row1[0];
                echo '</gold>';

                echo '<chips>';
                echo $row1[1];
                echo '</chips>';

                echo '<chips1>';
                echo number_format($row1[1]);
                echo '</chips1>';

                echo '<name>';
                echo $row1[2];
                echo '</name>';
            }
            echo '<status>';
            echo 'Invitation Sent';
            echo '</status>';

            echo '</player>';
        }
    }
    
    $alreg = join(',', $registeredids);
    
    $query3 = "select * from games_players where session_id = $sessionid and user_id not in ($alreg)";
    $result3 = mysqli_query($dbc, $query3);

    if (mysqli_num_rows($result3) > 0)
    {

        while ($row3 = mysqli_fetch_array($result3))
        {
            echo '<player>';

            echo '<userid>';
            echo $row3['user_id'];
            echo '</userid>';

            $query4 = "select gold, chips, name from user_cash, user_details where user_cash.user_id = user_details.user_id and user_details.user_id=" . $row3['user_id'];
            $result4 = mysqli_query($dbc, $query4);

            if (mysqli_num_rows($result4) > 0)
            {
                $row4 = mysqli_fetch_row($result4);
                echo '<gold>';
                echo $row4[0];
                echo '</gold>';

                echo '<chips>';
                echo $row4[1];
                echo '</chips>';

                echo '<name>';
                echo $row4[2];
                echo '</name>';

                echo '<chips1>';
                echo number_format($row4[1]);
                echo '</chips1>';
            }
            echo '<status>';

            if ($row3['user_id'] != $creatorid)
                echo 'Invitation Accepted';
            else
                echo 'Not Registered';

            echo '</status>';

            echo '</player>';
        }
    }


    echo '</players>';

    mysqli_close($dbc);
}
?>
