<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  $userid = $_GET['user_id'];
  $devicetoken = $_GET['devicetoken'];

  if ( is_numeric($userid) && !is_null($devicetoken) )
  {
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error conneccting database");

      $query = "select device_token from current_login_status where user_id = $userid";
      $result = mysqli_query($dbc, $query);
     
      if ( mysqli_num_rows($result) == 0 )
      {
          echo '<status>0</status>';
          mysqli_close($dbc);
      }
      else
      {
          $currentdevtoken = "";
          while ( $row = mysqli_fetch_array($result) )
          {
              $currentdevtoken = $row[0];
          }

          if ( $currentdevtoken == $devicetoken )
          {
              $query2 = "update user_details set devicetoken = '$devicetoken' where user_id = $userid";
              mysqli_query($dbc, $query2);
              if ( mysqli_affected_rows($dbc) == 1 )
              {
                  echo '<status>1</status>';
                  mysqli_close($dbc);
              }
              else
              {
                  echo '<status>0</status>';
                  mysqli_close($dbc);
              }
          }
          else
          {
              echo '<status>0</status>';
              mysqli_close($dbc);
          }
      }
  }
?>
