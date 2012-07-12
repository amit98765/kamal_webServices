<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  $dbc = mysqli_connect(host, user, password, database)
          or die("Error Connecting Database");

  $userid = $_GET['user_id'];

  echo '<jobs>';
  $jobnames = array('Average Joe', 'Business Executive', 'Poker Pro', 'Rock Star');

  for ( $i = 0; $i < count($jobnames); $i++ )
  {
      $query2 = "select * from purchased_jobs where user_id = $userid and job_name = '$jobnames[$i]'";

      $result2 = mysqli_query($dbc, $query2);
      if ( mysqli_num_rows($result2) == 0 )
      {
          echo '<job>';

          echo '<name>';
          echo $jobnames[$i];
          echo '</name>';
          
          echo '<status>';
          echo '0';
          echo '</status>';
          
          echo '</job>';
      }
      else
      {
          echo '<job>';
          
          echo '<name>';
          echo $jobnames[$i];
          echo '</name>';
          
          echo '<status>';
          echo '1';
          echo '</status>';
          
          echo '</job>';
      }
  }


  echo '</jobs>';
  mysqli_close($dbc);
?>
