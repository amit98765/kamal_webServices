<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  echo '<users>';

  $userid = $_GET['user_id'];

  $dbc = mysqli_connect(host, user, password, database)
          or die("Error Connecting Database");

  $query = "SELECT user_details.user_id, name, email_id, icon_name FROM user_details 
    left join user_icon on user_details.user_id = user_icon.user_id having user_id != $userid";
  $result = mysqli_query($dbc, $query);
  $numrows = mysqli_num_rows($result);
  if ( $numrows == 0 )
  {

      echo '</users>';
      mysqli_close($dbc);
  }
  else
  {

      while ( $row = mysqli_fetch_array($result) )
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
          if ( $row['icon_name'] != "" )
              echo $row['icon_name'];
          else
              echo '-';
          echo '</user-icon>';

          echo '<status>';
          $query4 = "select status from friend_requests where request_from = $userid and request_to = $row[0]";
          $result4 = mysqli_query($dbc, $query4);
          if ( mysqli_num_rows($result4) == 0 )
          {

              $query5 = "select * from friend_requests where request_to = $userid and request_from = $row[0]";
              $result5 = mysqli_query($dbc, $query5);

              if ( mysqli_num_rows($result5) != 0 )
              {
                  while ( $row5 = mysqli_fetch_array($result5) )
                  {
                      if ( $row5['status'] == 1 )
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
      mysqli_close($dbc);
      echo '</users>';
  }
?>
