<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if (is_null($_GET['game_type']) || is_null($_GET['user_id']))
    echo 'Session id was not passed';
else
{

    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    $gametype = $_GET['game_type'];
    $userid   = $_GET['user_id'];

    //check if game of this session id exists
    // fetch all session ids corresponding to game_type
    $query1  = "select session_id from table_gamesessions where game_type = '$gametype'";
    $result1 = mysqli_query($dbc, $query1);

    if (mysqli_num_rows($result1) == 0)
    {
        echo '<result><status>0</status></result>';
    }
    else
    {
        $newgamecreatable = FALSE;
        while ($row1             = mysqli_fetch_array($result1))
        {
            $query5  = "select * from games_players where user_id = $userid and session_id = $row1[0]";
            $result5 = mysqli_query($dbc, $query5);

            if (mysqli_num_rows($result5) > 0)
            {
                //check if user is active for other game of this type
                // check if 5 players are invited for this game
                $query2           = "select * from invitations where session_id =$row1[0]";
                $result2          = mysqli_query($dbc, $query2);
                $noinvitedplayers = mysqli_num_rows($result2);

                $query3            = "select * from games_players where session_id = $row1[0]";
                $result3           = mysqli_query($dbc, $query3);
                $noplayersaccepted = mysqli_num_rows($result3);

                if (($noinvitedplayers + $noplayersaccepted) == 1)
                {
                    echo '<result><status>1</status><session_id>' . $row1[0] . '</session_id></result>';
                }
                elseif (($noinvitedplayers + $noplayersaccepted) == 6)
                {
                    //check if all have registered or not
                    $query4  = "select * from omaha_bets where session_id = $row1[0]";
                    $result4 = mysqli_query($dbc, $query4);

                    if (mysqli_num_rows($result4) == 6)
                    {
                        echo '<result><status>3</status><session_id>' . $row1[0] . '</session_id></result>';
                    }
                    else
                        echo '<result><status>2</status><session_id>' . $row1[0] . '</session_id></result>';
                }
                else
                {
                    echo '<result><status>2</status><session_id>' . $row1[0] . '</session_id></result>';
                }
                $newgamecreatable = TRUE;
                break;
            }
        }
        if (!$newgamecreatable)
        {
            echo '<result><status>0</status></result>';
        }
    }
    mysqli_close($dbc);
}
?>
