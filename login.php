<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

if (is_null($_GET['email_id']) || is_null($_GET['password']))
{
    echo 'not all variables were passed';
    exit();
}
echo '<result>';

$email        = strtolower($_GET['email_id']);
$password     = $_GET['password'];



$dbc = mysqli_connect(host, user, password, database)
        or die("Error connecting database");

$query = "select password, devicetoken from user_details where email_id = '$email'";

$result = mysqli_query($dbc, $query);

$numrows = mysqli_num_rows($result);

if ($numrows == 1)
{

    $gotpassword    = "";
    $gotdevicetoken = "";

    while ($row = mysqli_fetch_array($result))
    {

        $gotpassword = $row['password'];

        $gotdevicetoken = $row['devicetoken'];
    }

    if ($password == $gotpassword)
    {

        $gotuserid    = "";
        $prevdevtoken = "";

        $query3   = "select user_id, devicetoken from user_details where email_id='$email'";
        $result3  = mysqli_query($dbc, $query3);
        $numrows3 = mysqli_num_rows($result3);

        if ($numrows3 > 0)
        {

            while ($row3 = mysqli_fetch_array($result3))
            {
                $gotuserid    = $row3['user_id'];
                $prevdevtoken = $row3['devicetoken'];
            }
        }
        echo '<status>';
        echo '1';
        echo '</status>';

        echo '<userid>';
        echo $gotuserid;
        echo '</userid>';

        echo '<friends>';
        $query7  = "select * from friend_requests where request_from = $gotuserid";
        $result7 = mysqli_query($dbc, $query7);
        if (mysqli_num_rows($result7) > 0)
        {
            echo '1';
        }
        else
        {
            $query8  = "select * from friend_requests where request_to = $gotuserid and status=1";
            $result8 = mysqli_query($dbc, $query8);
            if (mysqli_num_rows($result8) > 0)
            {
                echo '1';
            }
            else
                echo '0';
        }
        echo '</friends>';

        // need a room and a job for this user

        $query5  = "select job_name from user_job where user_id = $gotuserid";
        $result5 = mysqli_query($dbc, $query5);
        if (mysqli_num_rows($result5) == 0)
        {
            echo '<jobname>';
            echo '0';
            echo '</jobname>';
        }
        else
        {
            while ($row5 = mysqli_fetch_array($result5))
            {
                echo '<jobname>';
                echo $row5[0];
                echo '</jobname>';
            }
        }

        $query6  = "select room_name from user_room where user_id = $gotuserid";
        $result6 = mysqli_query($dbc, $query6);
        if (mysqli_num_rows($result6) == 0)
        {
            echo '<roomname>';
            echo '0';
            echo '</roomname>';
        }
        else
        {
            while ($row6 = mysqli_fetch_array($result6))
            {
                echo '<roomname>';
                echo $row6[0];
                echo '</roomname>';
            }
        }

        /*
          if ($gotdevicetoken != $devicetoken) {
          $query2 = "update user_details set current_device_token = '$devicetoken' where email_id='$email'";
          $result2 = mysqli_query($dbc, $query2);
         * 
         */

        // set login status as logged in 

        mysqli_close($dbc);
    }
    else
    {
        echo '<status>';
        echo '0';
        echo '</status>';
        mysqli_close($dbc);
    }
}
else
{
    echo '<status>';
    echo '0';
    echo '</status>';
    mysqli_close($dbc);
}
echo '</result>';
?>
