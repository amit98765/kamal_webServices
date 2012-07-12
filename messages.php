<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

// chech whether all required parameters were passed.
  if ( !isset($_GET['user_id']) )
  {
      echo 'User_id not passed.';
      exit(0);
  }

  $userid = (int) $_GET['user_id'];
  echo '<messages>';
  $dbc = mysqli_connect(host, user, password, database)
          or die("Error connecting to database");

  $query = "select * from messages where user_id = $userid order by datetime desc";
  $result = mysqli_query($dbc, $query);

// check if number of rows was greater than 0
  if ( mysqli_num_rows($result) > 0 )
  {
      while ( $row = mysqli_fetch_array($result) )
      {
          echo '<message>';

          echo '<message_id>';
          echo $row['id'];
          echo '</message_id>';

          echo '<body>';
          echo $row['message'];
          echo '</body>';

          echo '<message_type>';
          echo $row['message_type'];
          echo '</message_type>';

          echo '<handler_id>';
          echo $row['handler_id'];
          echo '</handler_id>';
          
          echo '</message>';
      }
      $query2 = "update messages set status = 1 where user_id = $userid";
      mysqli_query($dbc, $query2);
  }
  mysqli_close($dbc);
  echo '</messages>';
?>