<?php

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

$userid = $_GET['user_id'];
$count  = $_GET['count'];

$dbc = mysqli_connect(host, user, password, database)
        or die("Error connecing database");


//fetch room name of this user
$query3  = "select job_name from user_job where user_id = $userid";
$result3 = mysqli_query($dbc, $query3);

if (mysqli_num_rows($result3) > 0)
{
    $row3     = mysqli_fetch_row($result3);
    $roomname = $row3[0];

    $chipstodeduct = 0;
    if ($roomname == "Poker Pro")
    {
        $chipstodeduct = 20000;
    }
    elseif ($roomname == "Rockstar")
    {
        $chipstodeduct = 100000;
    }
    elseif ($roomname == "Business Executive")
    {
        $chipstodeduct = 2500;
    }
    elseif ($roomname == "Average Joe")
    {
        $chipstodeduct = 500;
    }
    else
    {
        $chipstodeduct = 500;
    }

    $chipstoadd = $count * $chipstodeduct;

    $querymy4 = "update user_cash set chips = chips + $chipstoadd where user_id = $userid";
    if (mysqli_query($dbc, $querymy4))
    {
        if ($count == 2)
        {
            $querymy5 = "update user_cash set reward = reward-1 where user_id = $userid";
            mysqli_query($dbc, $querymy5);
        }
        echo '<result>';
        echo '<status>1</status>';

        echo '<reward>';
        $querymy6  = "select reward from user_cash where user_id = $userid";
        $resultmy6 = mysqli_query($dbc, $querymy6);

        if (mysqli_num_rows($resultmy6) > 0)
        {
            $rowmy6 = mysqli_fetch_row($resultmy6);
            echo $rowmy6[0];
        }
        echo '</reward>';

        echo '</result>';

        if ($count == 1)
        {
            $time = date("Y-m-d H:i:s", time());

            $query2 = "update user_cash set datetime = '$time', status = 0 where user_id= $userid";
            mysqli_query($dbc, $query2);
            if (mysqli_affected_rows($dbc) == 1)
            {
                $query5 = "update user_details set is_new_user = 0 where user_id = $userid";
                mysqli_query($dbc, $query5);
            }
        }
    }
    else
        echo '<status>0</status>';
}
?>
