<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  $user_id = $_GET['userid'];
  $gamename = $_GET['job_name'];

  if ( !is_null($gamename) && !is_null($user_id) )
  {
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");

      $query = "insert into purchased_jobs(user_id, job_name) values($user_id, '$gamename')";
      mysqli_query($dbc, $query);
      if ( mysqli_affected_rows($dbc) == 1 )
      {
          echo '<status>';
          echo '1';
          echo '</status>';
      }
      else
      {
          echo '<status>';
          echo '0';
          echo '</status>';
      }
      mysqli_close($dbc);
  }
?>
