<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';


// chech whether all required parameters were passed.
if (!isset($_GET['user_id']) || !isset($_GET['action']) || !isset($_GET['target']))
{
    echo 'Some required parameters were not passed.';
    exit(0);
}

$userid = $_GET['user_id'];
$action = $_GET['action'];
$target = $_GET['target'];


// sanity check user passed data
if ($target != "chips" && $target != "gold")
{
    echo 'Invalid target specified';
}
else
{
    if (is_numeric($userid) && !is_null($action))
    {
        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
/*
        $query  = "select $target from user_cash where user_id = $userid";
        $result = mysqli_query($dbc, $query);

        if (mysqli_num_rows($result) != 0)
        {
            
        */
            $querym = "update user_cash set $target = $target + $action where user_id = $userid";
            if (mysqli_query($dbc, $querym))
            {
                echo '<status>1</status>';
                 publishDailyWinnings($userid, $action, $target , $dbc);
            }

//            $chipscount = 0;
//            while ($row        = mysqli_fetch_array($result))
//            {
//                $chipscount = $row[0];
//            }
//
//            // got earlier chips. just add it to what was passed.
//            $newchipscount = $chipscount + $action;
//
//            $timezone = "Asia/Calcutta";
//
//            if (function_exists('date_default_timezone_set'))
//                date_default_timezone_set($timezone);
//
//            $time = date("Y-m-d H:i:s", time());
//
//            // before updating again check if it has been 24 hours since last update was given
//            $query4 = "select * from user_cash where user_id = $userid";
//
//            $result4 = mysqli_query($dbc, $query4);
//
//            if (mysqli_num_rows($result4) == 1)
//            {
//                while ($row4 = mysqli_fetch_array($result4))
//                {
//
//                    $query2 = "update user_cash set $target = $newchipscount, datetime = '$time', status = 0 where user_id= $userid";
//                    mysqli_query($dbc, $query2);
//                    if (mysqli_affected_rows($dbc) == 1)
//                    {
//                        $query5 = "update user_details set is_new_user = 0 where user_id = $userid";
//                        mysqli_query($dbc, $query5);
//                        
//                        // it was correct
//                        echo '<status>1</status>';
//                    }
//                    elseif (mysqli_affected_rows($dbc) > 1)
//                    {
//                        //panic
//                        echo '<status>2</status>';
//                    }
//                    elseif (mysqli_affected_rows($dbc) < 1)
//                    {
//                        // failed
//                        echo '<status>0</status>';
//                    }
//                }
//            }
//            else
//            {
//                echo 'no row exists for this user in user_cash table';
//            }
//        
        /*    
        }
        else
        {

            if (($action) >= 0)
            {

                $timezone = "Asia/Calcutta";

                if (function_exists('date_default_timezone_set'))
                    date_default_timezone_set($timezone);

                $time = date("Y-m-d H:i:s", time());

                $query3 = "insert into user_cash (user_id, $target, datetime) values ($userid, $action, '$time')";
                if (mysqli_query($dbc, $query3))
                {
                    echo '<status>1</status>';
                     publishDailyWinnings($userid, $action, $target , $dbc);
                }
                else
                    echo '<status>0</status>';
            }
            else
            {
                // no chips available to the user to deduct
                echo '<status>0</status>';
            }
        }
        */
        mysqli_close($dbc);
    }
    else
    {
        echo 'Either userid was not numeric, or action was null';
    }
}
?>
