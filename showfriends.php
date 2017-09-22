<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

$userid = $_GET['userid'];
$sessionid = $_GET['session_id'];

if (is_numeric($userid) && is_numeric($sessionid)) {

    // fetch all users that are invited or part of the game
    $userids2 = array();
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    $queryme1 = "select invitation_to from invitations where session_id = $sessionid";
    $resultme1 = mysqli_query($dbc, $queryme1);

    if (mysqli_num_rows($resultme1) != 0) {
        while ($row = mysqli_fetch_array($resultme1)) {
            array_push($userids2, $row[0]);
        }
    }

    $queryme2 = "select user_id from games_players where session_id = $sessionid";
    $resultme2 = mysqli_query($dbc, $queryme2);
    if (mysqli_num_rows($resultme2) != 0) {
        while ($row2 = mysqli_fetch_array($resultme2)) {
            array_push($userids2, $row2[0]);
        }
    }

    // one array is formed
    // form the other
    $userids = array();

    echo '<friends>';
    if (fetchgametype($sessionid, $dbc) != "Omaha") {
        echo '<cancallusers>';

        // fetch game type for this sessionid
        $gametype = fetchgametype($sessionid);
        if (is_null($gametype)) {
            echo '0';
        } else {
            if (($gametype == 'slots') || ($gametype == 'roulette')) {

                $noofplayers = 0;

// check enteries in players table

                $query = "select * from games_players where session_id = $sessionid";
                $result = mysqli_query($dbc, $query);

                $noofplayers += (int) mysqli_num_rows($result);

// now check how many players are invited
                $query2 = "select * from invitations where session_id = $sessionid";
                $result2 = mysqli_query($dbc, $query2);

                $noofplayers += (int) mysqli_num_rows($result2);
                $players = 4 - $noofplayers;
                echo $players;
            } elseif ($gametype == 'blackjack') {
                $noofplayers = 0;

// check enteries in players table

                $query = "select * from games_players where session_id = $sessionid";
                $result = mysqli_query($dbc, $query);

                $noofplayers += (int) mysqli_num_rows($result);

// now check how many players are invited
                $query2 = "select * from invitations where session_id = $sessionid";
                $result2 = mysqli_query($dbc, $query2);

                $noofplayers += (int) mysqli_num_rows($result2);
                $players = 3 - $noofplayers;
                echo $players;
            } else {
                $noofplayers = 0;

// check enteries in players table

                $query = "select * from games_players where session_id = $sessionid";
                $result = mysqli_query($dbc, $query);

                $noofplayers += (int) mysqli_num_rows($result);

// now check how many players are invited
                $query2 = "select * from invitations where session_id = $sessionid";
                $result2 = mysqli_query($dbc, $query2);

                $noofplayers += (int) mysqli_num_rows($result2);
                $players = 6 - $noofplayers;
                echo $players;
            }
        }
        echo '</cancallusers>';
    }

    // grab a list of friends of this user
    $query = "select request_to from friend_requests where request_from = $userid and status = 1";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) != 0) {
        while ($row = mysqli_fetch_array($result)) {
            array_push($userids, $row[0]);
        }
    }

    $query2 = "select request_from from friend_requests where request_to = $userid and status=1";
    $result2 = mysqli_query($dbc, $query2);
    if (mysqli_num_rows($result2) != 0) {
        while ($row2 = mysqli_fetch_array($result2)) {
            array_push($userids, $row2[0]);
        }
    }

    // form a final array
    $finalarray = array_diff($userids, $userids2);

    // array is formed now form a query to fetch details of all users

    $finaluseridslist = join(',', $finalarray);

    if (count($finalarray) > 0) {
        $querymy1 = "SELECT user_details.user_id, name, email_id, status from user_details, current_login_status
    where current_login_status.user_id = user_details.user_id and user_details.user_id in (-5,$finaluseridslist) and status = 1 order by name";
        $resultmy1 = mysqli_query($dbc, $querymy1);
        if (mysqli_num_rows($resultmy1) != 0) {
            echo '<online>';
            while ($rowmy1 = mysqli_fetch_array($resultmy1)) {

                echo '<friend>';

                echo '<userid>';
                echo $rowmy1[0];
                echo '</userid>';

                echo '<name>';
                echo $rowmy1[1];
                echo '</name>';

                echo '<emailid>';
                echo $rowmy1[2];
                echo '</emailid>';

                echo '<iconname>';
                $querymy3 = "select icon_name from user_icon where user_id = $rowmy1[0]";
                $resultmy3 = mysqli_query($dbc, $querymy3);
                if (mysqli_num_rows($resultmy3) == 0) {
                    echo 'Dice';
                } else {
                    while ($rowmy3 = mysqli_fetch_array($resultmy3)) {
                        echo $rowmy3[0];
                    }
                }
                echo '</iconname>';
                echo '</friend>';
            }
            echo '</online>';
        }
        $querymy2 = "SELECT user_details.user_id, name, email_id, status from user_details, current_login_status
    where current_login_status.user_id = user_details.user_id and user_details.user_id in ($finaluseridslist,-5) and status = 0 order by name";
        $resultmy2 = mysqli_query($dbc, $querymy2);
        if (mysqli_num_rows($resultmy2) != 0) {
            echo '<offline>';
            while ($rowmy2 = mysqli_fetch_array($resultmy2)) {

                echo '<friend>';

                echo '<userid>';
                echo $rowmy2[0];
                echo '</userid>';

                echo '<name>';
                echo $rowmy2[1];
                echo '</name>';

                echo '<emailid>';
                echo $rowmy2[2];
                echo '</emailid>';

                echo '<iconname>';
                $querymy3 = "select icon_name from user_icon where user_id = $rowmy2[0]";
                $resultmy3 = mysqli_query($dbc, $querymy3);
                if (mysqli_num_rows($resultmy3) == 0) {
                    echo 'Dice';
                } else {
                    while ($rowmy3 = mysqli_fetch_array($resultmy3)) {
                        echo $rowmy3[0];
                    }
                }
                echo '</iconname>';
                echo '</friend>';
            }
            echo '</offline>';
        }
    }
    echo '</friends>';
    mysqli_close($dbc);
}
?>
