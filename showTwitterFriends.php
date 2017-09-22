<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if (is_null($_GET['user_id']) || is_null($_POST['friends_ids']) || is_null($_GET['twitter_id']))
{
    echo '<status>0</status>';
}
else
{
    $userid             = $_GET['user_id'];
    $twitterid          = $_GET['twitter_id'];
    $twitterids         = $_POST['friends_ids'];
    $twitteridsexploded = explode(',', $twitterids);

    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    // set my twitter id first
    $query2  = "select twitter_id from user_details where user_id = $userid";
    $result2 = mysqli_query($dbc, $query2);
    if (mysqli_num_rows($result2) > 0)
    {
        $row2 = mysqli_fetch_row($result2);
        if (is_null($row2[0]) || ($row2[0] == ""))
        {
            $query3 = "update user_details set twitter_id = '$twitterid' where user_id = $userid";
            mysqli_query($dbc, $query3);
        }
        else
        {
            $earlieridsexploded = explode(',', $row2[0]);
            if (!(in_array($twitterid, $earlieridsexploded)))
            {
                $earlieridsexploded[] = "$twitterid";
                $newIdString          = join(',', $earlieridsexploded);
                $query3               = "update user_details set twitter_id = '$newIdString' where user_id = $userid";
                mysqli_query($dbc, $query3);
            }
        }
    }
    echo '<users>';

    $allids = array();
    for ($i = 0; $i < count($twitteridsexploded); $i++)
    {
        $query = "select distinct(user_id) from user_details where twitter_id like '%" . $twitteridsexploded[$i] . "%' and user_id != $userid";
        // get details of first userid
//        $query = "select user_details.user_id, user_details.email_id, user_details.name, icon_name 
//             from user_details left join user_icon on user_details.user_id = user_icon.user_id
//            having
//            user_details.user_id in (select distinct(user_id) from user_details where facebook_id like '%" . $facebookidsexploded[$i] . "%' and user_id != $userid)";

        $result = mysqli_query($dbc, $query);
        if (mysqli_num_rows($result) > 0)
        {
            while ($row = mysqli_fetch_array($result))
            {
                array_push($allids, $row[0]);
            }
        }
    }

    // find unique elements
    $uniqids      = array_unique($allids);
    $stringallids = join(',', $uniqids);
    $query3       = "select user_details.user_id, user_details.email_id, user_details.name, icon_name 
             from user_details left join user_icon on user_details.user_id = user_icon.user_id
            having
            user_details.user_id in ( $stringallids )";

    $result3 = mysqli_query($dbc, $query3);
    if (mysqli_num_rows($result3) > 0)
    {
        while ($row3 = mysqli_fetch_array($result3))
        {
            echo '<user>';

            echo '<user-name>';
            echo $row3['name'];
            echo '</user-name>';

            echo '<user-id>';
            echo $row3['0'];
            echo '</user-id>';

            echo '<user-email_id>';
            echo $row3['email_id'];
            echo '</user-email_id>';

            echo '<user-icon>';
            if ($row3['icon_name'] != "")
                echo $row3['icon_name'];
            else
                echo '-';
            echo '</user-icon>';

            echo '<status>';
            $query4  = "select status from friend_requests where request_from = $userid and request_to = $row3[0]";
            $result4 = mysqli_query($dbc, $query4);
            if (mysqli_num_rows($result4) == 0)
            {

                $query5  = "select * from friend_requests where request_to = $userid and request_from = $row3[0]";
                $result5 = mysqli_query($dbc, $query5);

                if (mysqli_num_rows($result5) != 0)
                {
                    while ($row5 = mysqli_fetch_array($result5))
                    {
                        if ($row5['status'] == 1)
                            echo '1';
                        else
                            echo '2';
                    }
                }
                else
                {
                    echo '0';
                }
            }
            else
            {
                echo '1';
            }
            echo '</status>';
            echo '</user>';
        }
    }
    echo '</users>';
    mysqli_close($dbc);
}
?>
