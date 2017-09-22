<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if (is_null($_GET['user_id']) || is_null($_GET['facebook_ids']) || is_null($_GET['facebook_id']))
{
    echo '<status>0</status>';
}
else
{
    $userid = $_GET['user_id'];
    $facebookid = $_GET['facebook_id'];
    $facebookids = $_GET['facebook_ids'];
    $facebookidsexploded = explode(',', $facebookids);

    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    // set my facebook id first
    $query2 = "select facebook_id from user_details where user_id = $userid";
    $result2 = mysqli_query($dbc, $query2);
    if (mysqli_num_rows($result2) > 0)
    {
        $row2 = mysqli_fetch_row($result2);
        if (is_null($row2[0]) || ($row2[0] == ""))
        {
            $query3 = "update user_details set facebook_id = '$facebookid' where user_id = $userid";
            mysqli_query($dbc, $query3);
        }
        else
        {
            $earlieridsexploded = explode(',', $row2[0]);
            if (!(in_array($facebookid, $earlieridsexploded)))
            {
                $earlieridsexploded[] = "$facebookid";
                $newIdString = join(',', $earlieridsexploded);
                $query3 = "update user_details set facebook_id = '$newIdString' where user_id = $userid";
                mysqli_query($dbc, $query3);
            }
        }
    }
    echo '<users>';
    for ($i = 0; $i < count($facebookidsexploded); $i++)
    {
        // get details of first userid
        $query = "select user_details.user_id, user_details.email_id, user_details.name, icon_name 
             from user_details left join user_icon on user_details.user_id = user_icon.user_id
            having
            user_details.user_id in (select user_id from user_details where facebook_id like '%" . $facebookidsexploded[$i] . "%' and user_id != $userid)";

        $result = mysqli_query($dbc, $query);
        if (mysqli_num_rows($result) > 0)
        {
            while ($row = mysqli_fetch_array($result))
            {
                echo '<user>';

                echo '<user-name>';
                echo $row['name'];
                echo '</user-name>';

                echo '<user-id>';
                echo $row['0'];
                echo '</user-id>';

                echo '<user-email_id>';
                echo $row['email_id'];
                echo '</user-email_id>';

                echo '<user-icon>';
                if ($row['icon_name'] != "")
                    echo $row['icon_name'];
                else
                    echo '-';
                echo '</user-icon>';

                echo '<status>';
                $query4 = "select status from friend_requests where request_from = $userid and request_to = $row[0]";
                $result4 = mysqli_query($dbc, $query4);
                if (mysqli_num_rows($result4) == 0)
                {

                    $query5 = "select * from friend_requests where request_to = $userid and request_from = $row[0]";
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
    }
    echo '</users>';
    mysqli_close($dbc);
}
?>
