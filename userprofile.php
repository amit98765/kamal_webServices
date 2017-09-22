<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

/* set status as logged in when this page loads */
$userid = $_GET['user_id'];
setTimeZone();
echo '<user>';
if (!is_numeric($userid)) {
    echo '0';
    echo '</user>';
    exit();
} else {
    $dbc = mysqli_connect(host, user, password, database)
            or die('Error connecting database');
    $query = "select name, email_id, icon_name from user_details left join user_icon on user_details.user_id = user_icon.user_id where user_details.user_id = $userid";
    $result = mysqli_query($dbc, $query);
    if (mysqli_num_rows($result) != 0) {
        $username = "";
        $useremailid = "";
        $iconname = "";
        $jobname = "";
        $roomname = "";
        while ($row = mysqli_fetch_array($result)) {
            $username = $row['name'];
            $useremailid = $row['email_id'];
            $iconname = $row['icon_name'];
        }


        $query2 = "select room_name, job_name from user_job left join user_room on user_room.user_id = user_job.user_id where user_room.user_id = $userid";
        $result2 = mysqli_query($dbc, $query2);
        if (mysqli_num_rows($result2) == 0) {
            
        } else {
            while ($row2 = mysqli_fetch_array($result2)) {
                $jobname = $row2['job_name'];
                $roomname = $row2['room_name'];
            }
        }
        echo '<user-name>';
        echo $username;
        echo '</user-name>';


        $queryua = "select * from game_update limit 1";
        $resultua = mysqli_query($dbc, $queryua);

        if (mysqli_num_rows($resultua) > 0) {
            $rowua = mysqli_fetch_row($resultua);

            echo '<isUpdateAvailable>';
            echo $rowua[0];
            echo '</isUpdateAvailable>';
        }


        echo '<user-email_id>';
        echo $useremailid;
        echo '</user-email_id>';

        echo '<user-icon_name>';
        echo $iconname;
        echo '</user-icon_name>';

        echo '<user-job_name>';
        echo $jobname;
        echo '</user-job_name>';

        echo '<user-room_name>';
        echo $roomname;
        echo '</user-room_name>';

        echo '<unread_friend_requests>';

        $query5 = "select * from friend_requests where request_to = $userid and read_status = 0 and status=0";
        $result5 = mysqli_query($dbc, $query5);
        if (mysqli_num_rows($result5) == 0) {
            echo '0';
        } else {
            $counter = 0;
            while ($row5 = mysqli_fetch_array($result5)) {
                $counter++;
            }
            echo $counter;
        }
        echo '</unread_friend_requests>';
        echo '<paycheck>';

        $query19 = "select is_new_user from user_details where user_id = $userid";
        $result19 = mysqli_query($dbc, $query19);
        if (mysqli_num_rows($result19) > 0) {
            $row19 = mysqli_fetch_row($result19);
            if ($row19[0] == 0) {
                $query9 = "select * from user_cash where user_id = $userid";
                $result9 = mysqli_query($dbc, $query9);
                if (mysqli_num_rows($result9) == 1) {
                    // if there is a row, check whether it has been 24 hours to when last time, chips were given to user
                    // it has gone well, so proceed 
                    while ($row10 = mysqli_fetch_array($result9)) {
                        $timezone = "Asia/Calcutta";
                        if (function_exists('date_default_timezone_set'))
                            date_default_timezone_set($timezone);

                        $prevtime = strtotime($row10['datetime']);
                        if ((time() - $prevtime) > 60 * 60 * 24) {
                            echo '1';
                        } else {
                            echo '0';
                        }
                    }
                }
            } else {
                echo '1';

                $query12 = "select * from user_cash where user_id = $userid";
                $result12 = mysqli_query($dbc, $query12);
                if (mysqli_num_rows($result12) == 0) {
                    
                      $time = date("Y-m-d H:i:s", time());
                    $query11 = "insert into user_cash(user_id, roomrentdeductiontime) values($userid, '$time')";
                    mysqli_query($dbc, $query11);
                }
            }
        }


        echo '</paycheck>';
        echo '<unread_invitations>';

        $query6 = "select * from messages where user_id = $userid and status = 0";
        $result6 = mysqli_query($dbc, $query6);
        if (mysqli_num_rows($result6) == 0) {
            echo '0';
        } else {
            $counter2 = 0;
            while ($row6 = mysqli_fetch_array($result6)) {
                $counter2++;
            }
            echo $counter2;
        }
        echo '</unread_invitations>';
        echo '<purchased_icons>';

        // get all icons purchased by this user
        $query7 = "select icon_name from purchased_icons where user_id = $userid order by datetime asc";
        $result7 = mysqli_query($dbc, $query7);

        // if there were some icons purchased
        if (mysqli_num_rows($result7) != 0) {
            $iconslist = "";
            while ($row7 = mysqli_fetch_array($result7)) {
                $iconslist .= $row7[0] . ',';
            }

            // remove the trailing ',' from the variable $iconslist
            $iconslisttoprint = substr($iconslist, 0, strlen($iconslist) - 1);
            echo $iconslisttoprint;
        }
        echo '</purchased_icons>';

        // get no of chips for the user // get gold availble
        $query8 = "select chips, gold from user_cash where user_id = $userid";
        $result8 = mysqli_query($dbc, $query8);
        if (mysqli_num_rows($result8) == 0) {

            // set gold and chips both to zero
            echo '<gold>0</gold>';
            echo '<chips>0</chips>';
        } else {

            // fetch no of gold and chips from database
            $chips = 0;
            $gold = 0;
            while ($row8 = mysqli_fetch_array($result8)) {
                $chips = number_format($row8['chips']);
                $gold = number_format($row8['gold']);
            }
            echo '<gold>' . $gold . '</gold>';
            echo '<chips>' . $chips . '</chips>';
        }

        echo '<feeds>';

        // ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

        echo '<friends_chips_toppers>';

        $queryfct = "select user_details.user_id, user_details.name, user_cash.chips, user_icon.icon_name from user_details left join user_cash on user_details.user_id = user_cash.user_id
            left join user_icon on user_details.user_id = user_icon.user_id where user_details.user_id in ( select request_from from friend_requests 
                    where request_to = $userid and status = 1 
              union select request_to from friend_requests 
                where request_from = $userid and status = 1 ) and user_cash.chips > 0 order by chips desc limit 3";
        $resultfct = mysqli_query($dbc, $queryfct);

        $highest_chips_fct = 0;
        $highest_place_fct = 1;

        if (mysqli_num_rows($resultfct) > 0) {
            while ($rowfct = mysqli_fetch_array($resultfct)) {
                echo '<friend_chip_topper>';

                echo '<friendsUserId>';
                echo $rowfct[0];
                echo '</friendsUserId>';

                echo '<amount>';
                echo number_format($rowfct[2]);
                echo '</amount>';

                if ($rowfct[2] >= $highest_chips_fct) {
                    echo '<rank>';
                    echo $highest_place_fct;
                    echo '</rank>';

                    $highest_chips_fct = $rowfct[2];
                } else {
                    echo '<rank>';
                    echo $highest_place_fct + 1;
                    echo '</rank>';

                    $highest_place_fct +=1;
                }


                echo '<feedUserName>';
                echo $rowfct[1];
                echo '</feedUserName>';

                echo '<feedIconName>';
                echo $rowfct[3];
                echo '</feedIconName>';

                echo '</friend_chip_topper>';
            }
        }

        echo '</friends_chips_toppers>';

        echo '<friends_gold_toppers>';

        $queryfgt = "select user_details.user_id, user_details.name, user_cash.gold, user_icon.icon_name from user_details left join user_cash on user_details.user_id = user_cash.user_id
            left join user_icon on user_details.user_id = user_icon.user_id where user_details.user_id in ( select request_from from friend_requests 
                    where request_to = $userid and status = 1 
              union select request_to from friend_requests 
                where request_from = $userid and status = 1 ) and user_cash.gold >  0 order by gold desc limit 3";
        $resultfgt = mysqli_query($dbc, $queryfgt);

        $highest_gold_fgt = 0;
        $highest_place_fgt = 1;

        if (mysqli_num_rows($resultfgt) > 0) {
            while ($rowfgt = mysqli_fetch_array($resultfgt)) {
                echo '<friend_gold_topper>';

                echo '<friendsUserId>';
                echo $rowfgt[0];
                echo '</friendsUserId>';

                echo '<amount>';
                echo number_format($rowfgt[2]);
                echo '</amount>';

                if ($rowfgt[2] >= $highest_gold_fgt) {
                    echo '<rank>';
                    echo $highest_place_fgt;
                    echo '</rank>';

                    $highest_gold_fgt = $rowfgt[2];
                } else {
                    echo '<rank>';
                    echo $highest_place_fgt + 1;
                    echo '</rank>';

                    $highest_place_fgt += 1;
                }


                echo '<feedUserName>';
                echo $rowfgt[1];
                echo '</feedUserName>';

                echo '<feedIconName>';
                echo $rowfgt[3];
                echo '</feedIconName>';

                echo '</friend_gold_topper>';
            }
        }

        echo '</friends_gold_toppers>';

        //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        echo '<chips_winners>';

        $datetoday = date('Y-m-d', time());

        $querytopchips = "SELECT user_id, chips FROM daily_winnings where chips > 0 and datetime_chips = '$datetoday' order by chips desc, datetime_chips desc limit 3";
        $resulttopchips = mysqli_query($dbc, $querytopchips);

        $highest_chips = 0;
        $highest_place = 1;

        if (mysqli_num_rows($resulttopchips) > 0) {
            while ($rowtopchips = mysqli_fetch_array($resulttopchips)) {
                echo '<chips_topper>';

                echo '<friendsUserId>';
                echo $rowtopchips[0];
                echo '</friendsUserId>';

                echo '<amount>';
                echo number_format($rowtopchips[1]);
                echo '</amount>';

                if ($rowtopchips[1] >= $highest_chips) {
                    echo '<rank>';
                    echo $highest_place;
                    echo '</rank>';

                    $highest_chips = $rowtopchips[1];
                } else {
                    echo '<rank>';
                    echo $highest_place + 1;
                    echo '</rank>';

                    $highest_place +=1;
                }

                // fetch userName & icon of this user
                $querym50 = "select name, icon_name from user_details left join user_icon on user_details.user_id = user_icon.user_id where user_details.user_id = $rowtopchips[0]";
                $resultm50 = mysqli_query($dbc, $querym50);

                if (mysqli_num_rows($resultm50) > 0) {
                    $rowm50 = mysqli_fetch_row($resultm50);

                    echo '<feedUserName>';
                    echo $rowm50[0];
                    echo '</feedUserName>';

                    echo '<feedIconName>';
                    echo $rowm50[1];
                    echo '</feedIconName>';
                }


                echo '</chips_topper>';
            }
        }
        echo '</chips_winners>';

        echo '<gold_winners>';

        $querytopgold = "SELECT user_id, gold FROM daily_winnings where gold > 0 and datetime_gold = '$datetoday' order by gold desc, datetime_gold desc limit 3";
        $resulttopgold = mysqli_query($dbc, $querytopgold);

        $highest_gold = 0;
        $highest_gold_place = 1;

        if (mysqli_num_rows($resulttopgold) > 0) {
            while ($rowtopgold = mysqli_fetch_array($resulttopgold)) {
                echo '<gold_topper>';

                echo '<friendsUserId>';
                echo $rowtopgold[0];
                echo '</friendsUserId>';

                echo '<amount>';
                echo number_format($rowtopgold[1]);
                echo '</amount>';

                if ($rowtopgold[1] >= $highest_gold) {
                    echo '<rank>';
                    echo $highest_gold_place;
                    echo '</rank>';

                    $highest_gold = $rowtopgold[1];
                } else {
                    echo '<rank>';
                    echo $highest_gold_place + 1;
                    echo '</rank>';

                    $highest_gold_place += 1;
                }

                // fetch userName & icon of this user
                $querym50 = "select name, icon_name from user_details left join user_icon on user_details.user_id = user_icon.user_id where user_details.user_id = $rowtopgold[0]";
                $resultm50 = mysqli_query($dbc, $querym50);

                if (mysqli_num_rows($resultm50) > 0) {
                    $rowm50 = mysqli_fetch_row($resultm50);

                    echo '<feedUserName>';
                    echo $rowm50[0];
                    echo '</feedUserName>';

                    echo '<feedIconName>';
                    echo $rowm50[1];
                    echo '</feedIconName>';
                }


                echo '</gold_topper>';
            }
        }

        echo '</gold_winners>';
        echo '<regular_feed>';

        $querym19 = "select * from casino_feeds 
              where 
                user_id in(
                select request_from from friend_requests 
                    where request_to = $userid and status = 1 
              union select request_to from friend_requests 
                where request_from = $userid and status = 1) order by datetime desc limit 50";

        $resultm19 = mysqli_query($dbc, $querym19);
        if (mysqli_num_rows($resultm19) > 0) {
            while ($row19 = mysqli_fetch_array($resultm19)) {
                echo '<feed>';

                echo '<friendsUserId>';
                echo $row19[1];
                echo '</friendsUserId>';

                echo '<feedMessage>';
                echo $row19[2];
                echo '</feedMessage>';

                echo '<feedTime>';
                echo getTimeAgo($row19[3]);
                echo '</feedTime>';

                $query20 = "select name, icon_name from user_details, user_icon where user_details.user_id = user_icon.user_id and user_details.user_id = $row19[1]";
                $result20 = mysqli_query($dbc, $query20);

                if (mysqli_num_rows($result20) > 0) {

                    $row20 = mysqli_fetch_row($result20);

                    echo '<feedUserName>';
                    echo $row20[0];
                    echo '</feedUserName>';

                    echo '<feedIconName>';
                    echo $row20[1];
                    echo '</feedIconName>';
                }

                echo '</feed>';
            }
        }

        echo '</regular_feed>';
        echo '</feeds>';

        echo '</user>';

        $query3 = "update current_login_status set status=1 where user_id = $userid";
        mysqli_query($dbc, $query3);
    } else {
        echo '0';
        echo '</user>';
    }
    mysqli_close($dbc);
}
?>
