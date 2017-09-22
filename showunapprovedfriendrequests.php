<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

echo '<requests>';

$userid = $_GET['userid'];

$dbc = mysqli_connect(host, user, password, database)
        or die("Error connecting database");
$query = "select * from friend_requests where request_to = $userid and status=0";
$result = mysqli_query($dbc, $query);
if (mysqli_num_rows($result) == 0) {

    mysqli_close($dbc);
} else {
    while ($row = mysqli_fetch_array($result)) {
        $requestsendersuserid = $row[0];
        $timeofsendingrequest = $row[3];

        // fetch details of given userid
        $query2 = "select user_details.user_id, name,email_id, icon_name from user_details left join user_icon on user_details.user_id = user_icon.user_id having user_details.user_id= $requestsendersuserid";
        $result2 = mysqli_query($dbc, $query2);

        if (mysqli_num_rows($result2) != 0) {
            while ($row2 = mysqli_fetch_array($result2)) {
                echo '<request>';
                echo '<user_id>' . $row2[0] . '</user_id>';
                echo '<name>' . $row2[1] . '</name>';
                echo '<email_id>' . $row2[2] . '</email_id>';
                echo '<icon_name>' . $row2[3] . '</icon_name>';

                echo '<time>';

                $daysago = getdaysago($timeofsendingrequest);
                if ($daysago < 1) {

                    $diff2 = time() - strtotime($timeofsendingrequest);

                    $hours2 = ($diff2 / (60 * 60));

                    if ($hours2 > 1) {
                        echo round($hours2, 0) . ' hours ago';
                    } else {
                        $minutes2 = $hours2 * 60;
                        echo round($minutes2, 0) . ' mins ago';
                    }
                } elseif ($daysago < 7) {
                    echo $daysago . ' days ago';
                } elseif ($daysago < 30) {
                    echo ceil($daysago / 7) . ' weeks ago';
                } elseif ($daysago < 365) {
                    echo ceil($daysago / 30) . ' montes ago';
                } else {
                    echo ceil($daysago / 365) . ' years ago';
                }
                echo '</time>';
                // embed request time here 

                echo '</request>';
            }
        } else {
            
        }
    }
    // set status of friend requests as read
    $query5 = "update friend_requests set read_status = 1 where request_to = $userid";
    mysqli_query($dbc, $query5);
    mysqli_close($dbc);
}
echo '</requests>';

function getdaysago($datethis) {
    $timezone = "Asia/Calcutta";
    if (function_exists('date_default_timezone_set'))
        date_default_timezone_set($timezone);

    $then = strtotime($datethis);
    $diff = time() - $then;
    $daysthis = floor($diff / (60 * 60 * 24));
    return $daysthis;
}

?>
