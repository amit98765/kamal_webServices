<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  /* adds/ updates the name of current user & also sets the no. of chips for the user */

  $jobname = $_GET['job_name'];
  $userid = $_GET['user_id'];
  echo '<result>';

  $dbc = mysqli_connect(host, user, password, database)
          or die("Error connecting database");

  $query = "select job_name from user_job where user_id = $userid";
  $result = mysqli_query($dbc, $query);
  $numrows = mysqli_num_rows($result);
  if ( $numrows == 1 )
  {
      $timezone = "Asia/Calcutta";
      if ( function_exists('date_default_timezone_set') )
          date_default_timezone_set($timezone);
      $datemy = date('Y-m-d H:i:s');
      $query2 = "update user_job set job_name = '$jobname', datetime='$datemy' where user_id=$userid";
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
      $query2 = "insert into user_job(user_id, job_name, datetime) values($userid,'$jobname','$datemy')";
      $result2 = mysqli_query($dbc, $query2);

      echo '1';

      /* also update the chips of the user */
      $chips = 0;
      if ( $jobname == "Average Joe" )
      {
          $chips = 500;
      }
      else if ( $jobname == "Business Executive" )
      {
          $chips = 2500;
      }
      else if ( $jobname == "Poker Pro" )
      {
          $chips = 20000;
      }
      else if ( $jobname == "Rockstar" )
      {
          $chips = 100000;
      }

      //$query3 = "insert into user_cash (user_id, chips) values ($userid, $chips)";
      // mysqli_query($dbc, $query3);
      /* ends here */
  }
  mysqli_close($dbc);
  echo '</result>';
?>
