<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  /* set status as logged in when this page loads */

  $userid = $_GET['user_id'];

  echo '<user>';
  if ( !is_numeric($userid) )
  {
      echo '0';
      echo '</user>';
      exit();
  }
  else
  {
      $dbc = mysqli_connect(host, user, password, database)
              or die('Error connecting database');
      $query = "select name, email_id, icon_name from user_details left join user_icon on user_details.user_id = user_icon.user_id where user_details.user_id = $userid";
      $result = mysqli_query($dbc, $query);
      if ( mysqli_num_rows($result) != 0 )
      {
          $username = "";
          $useremailid = "";
          $iconname = "";
          $jobname = "";
          $roomname = "";
          while ( $row = mysqli_fetch_array($result) )
          {
              $username = $row['name'];
              $useremailid = $row['email_id'];
              $iconname = $row['icon_name'];
          }


          $query2 = "select room_name, job_name from user_job left join user_room on user_room.user_id = user_job.user_id where user_room.user_id = $userid";
          $result2 = mysqli_query($dbc, $query2);
          if ( mysqli_num_rows($result2) == 0 )
          {
              
          }
          else
          {
              while ( $row2 = mysqli_fetch_array($result2) )
              {
                  $jobname = $row2['job_name'];
                  $roomname = $row2['room_name'];
              }
          }
          echo '<user-name>';
          echo $username;
          echo '</user-name>';
          
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
          if ( mysqli_num_rows($result5) == 0 )
          {
              echo '0';
          }
          else
          {
              $counter = 0;
              while ( $row5 = mysqli_fetch_array($result5) )
              {
                  $counter++;
              }
              echo $counter;
          }
          echo '</unread_friend_requests>';
          echo '<paycheck>';

          $query9 = "select * from user_cash where user_id = $userid";
          $result9 = mysqli_query($dbc, $query9);
          if ( mysqli_num_rows($result9) == 1 )
          {

              // if there is a row, check whether it has been 24 hours to when last time, chips were given to user
              // it has gone well, so proceed 
              while ( $row10 = mysqli_fetch_array($result9) )
              {
                  $timezone = "Asia/Calcutta";
                  if ( function_exists('date_default_timezone_set') )
                      date_default_timezone_set($timezone);

                  $prevtime = strtotime($row10['datetime']);
                  if ( (time() - $prevtime) > 60 * 60 * 24)
                  {
                      echo '1';
                  }
                  else
                  {
                      echo '0';
                  }
              }
          }
          elseif ( mysqli_num_rows($result9) == 0 )
          {
              echo '1';

              // make an entry in table user_cash setting field 'status' value to 1
              $query11 = "insert into user_cash(user_id) values($userid)";
              mysqli_query($dbc, $query11);
          }
          else
          {
              echo "There was more than one entry of the same user in the user_cash table";
          }

          echo '</paycheck>';
          echo '<unread_invitations>';

          $query6 = "select * from messages where user_id = $userid and status = 0";
          $result6 = mysqli_query($dbc, $query6);
          if ( mysqli_num_rows($result6) == 0 )
          {
              echo '0';
          }
          else
          {
              $counter2 = 0;
              while ( $row6 = mysqli_fetch_array($result6) )
              {
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
          if ( mysqli_num_rows($result7) != 0 )
          {
              $iconslist = "";
              while ( $row7 = mysqli_fetch_array($result7) )
              {
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
          if ( mysqli_num_rows($result8) == 0 )
          {

              // set gold and chips both to zero
              echo '<gold>0</gold>';
              echo '<chips>0</chips>';
          }
          else
          {

              // fetch no of gold and chips from database
              $chips = 0;
              $gold = 0;
              while ( $row8 = mysqli_fetch_array($result8) )
              {
                  $chips = number_format($row8['chips']);
                  $gold = number_format($row8['gold']);
              }
              echo '<gold>' . $gold . '</gold>';
              echo '<chips>' . $chips . '</chips>';
          }

          echo '</user>';

          $query3 = "update current_login_status set status=1 where user_id = $userid";
          mysqli_query($dbc, $query3);
      }
      else
      {
          echo '0';
          echo '</user>';
      }
      mysqli_close($dbc);
  }
?>
