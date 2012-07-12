<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';

  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

//check if required variables were passed
  if ( !isset($_GET['requested_by']) || !isset($_GET['requested_about']) )
  {
      echo 'not all variables were passed';
  }
  else
  {

// grab the variables    
      $datarequestedby = $_GET['requested_by'];
      $datarequestedabout = $_GET['requested_about'];

      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");

// data required is name, chips, gold, icon, whether friend or not
      $query = "select name, gold, chips, icon_name, user_details.user_id
            from user_details, user_cash, user_icon where user_details.user_id=$datarequestedabout 
            and user_cash.user_id=$datarequestedabout and user_icon.user_id=$datarequestedabout limit 1";

      $result = mysqli_query($dbc, $query);

      echo '<player>';

      if ( mysqli_num_rows($result) > 0 )
      {

          $row = mysqli_fetch_row($result);

          echo '<name>';
          echo $row[0];
          echo '</name>';

          echo '<chips>';
          echo number_format($row[2]);
          echo '</chips>';

          echo '<gold>';
          echo number_format($row[1]);
          echo '</gold>';

          echo '<icon_name>';
          echo $row[3];
          echo '</icon_name>';

          echo '<user_id>';
          echo $row[4];
          echo '</user_id>';
      }

//check if this user is the friend of the person that has asked details 
      $query2 = "select * from friend_requests where request_from = $datarequestedby and request_to = $datarequestedabout";
      $result2 = mysqli_query($dbc, $query2);

      if ( mysqli_num_rows($result2) > 0 )
      {

// i had sent friend request to other person, so he is friend for me
          echo '<friendship_status>';
          echo '1';
          echo '</friendship_status>';
      }
      else
      {

// check if he has sent me request
          $query3 = "select status from friend_requests where request_to = $datarequestedby and request_from = $datarequestedabout";
          $result3 = mysqli_query($dbc, $query3);

          if ( mysqli_num_rows($result3) > 0 )
          {
              $row3 = mysqli_fetch_row($result3);
              $status = $row3[0];

              if ( $status == 0 )
              {

// request is pending
                  echo '<friendship_status>';
                  echo '2';
                  echo '</friendship_status>';
              }
              else
              {

// he is friend
                  echo '<friendship_status>';
                  echo '1';
                  echo '</friendship_status>';
              }
          }
          else
          {

              echo '<friendship_status>';
              echo '0';
              echo '</friendship_status>';
          }
      }

      echo '<purchased_icons>';

      $query4 = "select icon_name from purchased_icons where user_id= $datarequestedabout";
      $result4 = mysqli_query($dbc, $query4);

      $purchasediconarray = array();

      if ( mysqli_num_rows($result4) > 0 )
      {
          while ( $row4 = mysqli_fetch_array($result4) )
          {
              array_push($purchasediconarray, $row4[0]);
          }
      }

      echo join(',', $purchasediconarray);

      echo '</purchased_icons>';
      echo '</player>';
      mysqli_close($dbc);
  }
?>
