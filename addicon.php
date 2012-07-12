<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  $iconname = $_GET['icon_name'];
  $userid = $_GET['user_id'];
  echo '<result>';

  $dbc = mysqli_connect(host, user, password, database)
          or die("Error connecting database");

  $query = "select icon_name from user_icon where user_id = $userid";
  $result = mysqli_query($dbc, $query);
  $numrows = mysqli_num_rows($result);
  if ( $numrows == 1 )
  {
      $timezone = "Asia/Calcutta";
      if ( function_exists('date_default_timezone_set') )
          date_default_timezone_set($timezone);
      $datemy = date('Y-m-d H:i:s');
      $query2 = "update user_icon set icon_name = '$iconname', datetime='$datemy' where user_id=$userid";
      $result2 = mysqli_query($dbc, $query2);
      if ( mysqli_affected_rows($dbc) == 1 )
      {
          echo '1';
      }
      else
      {
          echo '0';
      }
  }
  else
  {
      $timezone = "Asia/Calcutta";
      if ( function_exists('date_default_timezone_set') )
          date_default_timezone_set($timezone);
      $datemy = date('Y-m-d H:i:s');
      $query2 = "insert into user_icon(user_id, icon_name, datetime) values($userid,'$iconname','$datemy')";
      $result2 = mysqli_query($dbc, $query2);
      if ( mysqli_affected_rows($dbc) == 1 )
      {
          echo '1';
      }
      else
      {
          echo '0';
      }
  }
  mysqli_close($dbc);
  echo '</result>';
?>
