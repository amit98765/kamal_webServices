<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  echo '<result>';
// grab parameters passed with post

  $emailid = strtolower($_GET['email_id']);
  $name = $_GET['name'];
  $password = $_GET['password'];
  $tokenid = $_GET['devicetoken'];

  if ( !filter_var($emailid, FILTER_VALIDATE_EMAIL) )
  {
      echo '2-0';
  }
  else
  {

      $dbc = mysqli_connect(host, user, password, database)
              or die('ERROR CONNECTING DATABASE');

      $query2 = "select email_id from user_details";

      $result2 = mysqli_query($dbc, $query2);

      $numrows2 = mysqli_num_rows($result2);

      if ( $numrows2 == 0 )
      {

          $query = "insert into user_details(name,email_id, password, devicetoken)values('$name', '$emailid', '$password', '$tokenid')";
          $result = mysqli_query($dbc, $query);

          // select latest userid for this user, and then insert a default icon in the database
          $query3 = "select user_id from user_details where email_id = '$emailid'";

          $result3 = mysqli_query($dbc, $query3);

          $gotuserid = "";
          if ( mysqli_num_rows($result3) == 1 )
          {

              $row3 = mysqli_fetch_row($result3);
              $gotuserid = $row3[0];

              $query4 = "insert into user_icon(user_id, icon_name) values ($gotuserid, 'Dice')";

              mysqli_query($dbc, $query4);
          }
          if ( mysqli_affected_rows($dbc) == 1 )
          {

              echo '1-' . $gotuserid;

              // sign up was successful
              // so make a entry for this user in the login table
              $querylast = "insert into current_login_status(user_id, status, device_token) values($gotuserid, '0', '$tokenid')";

              mysqli_query($dbc, $querylast);

              // give user a Dice Icon as His Purchased Icon.
              $querylast1 = "insert into purchased_icons(user_id, icon_name) values ($gotuserid, 'Dice') ";

              mysqli_query($dbc, $querylast1);
          }
          else
              echo '0';
      }
      else
      {
          $emailexists = FALSE;
          while ( $row2 = mysqli_fetch_array($result2) )
          {
              if ( strtolower($row2['email_id']) == strtolower($emailid) )
              {
                  echo '0-0';
                  $emailexists = TRUE;
                  break;
              }
          }
          if ( !$emailexists )
          {
              $query = "insert into user_details(name,email_id, password, devicetoken)values('$name', '$emailid', '$password', '$tokenid')";
              $result = mysqli_query($dbc, $query);
              if ( mysqli_affected_rows($dbc) == 1 )
              {
                  $gotuserid = "";
                  $query3 = "select user_id from user_details where email_id='$emailid'";
                  $result3 = mysqli_query($dbc, $query3);
                  $numrows3 = mysqli_num_rows($result3);
                  if ( $numrows3 > 0 )
                  {

                      while ( $row3 = mysqli_fetch_array($result3) )
                      {
                          $gotuserid = $row3['user_id'];
                      }
                  }
                  $query4 = "insert into user_icon(user_id, icon_name) values ($gotuserid, 'Dice')";
                  mysqli_query($dbc, $query4);

                  echo '1-' . $gotuserid;


                  $querylast = "insert into current_login_status(user_id, status, device_token) values($gotuserid, '0', '$tokenid')";
                  mysqli_query($dbc, $querylast);

                  // give user a Dice Icon as His Purchased Icon.
                  $querylast1 = "insert into purchased_icons(user_id, icon_name) values ($gotuserid, 'Dice') ";
                  mysqli_query($dbc, $querylast1);
              }
              else
                  echo '0-0';
          }
      }
      mysqli_close($dbc);
  }


  echo '</result>';
?>
