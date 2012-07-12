<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

// check if variBLES were passed
  if ( !isset($_GET['message_id']) )
  {
      echo 'message_id were not set';
  }
  else
  {
//$userid = (int) $_GET['user_id'];
      $messageid = $_GET['message_id'];

      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting to database");

// $query = "select * from messages where id = $messageid and user_id = $userid";
// $result = mysqli_query($dbc, $query);
// if (mysqli_num_rows($result) == 1) {
// all is fine. Just delete this row.
      $query2 = "delete from messages where id = $messageid";
      mysqli_query($dbc, $query2);

// check how many rows were affected
      if ( mysqli_affected_rows($dbc) == 1 )
      {
          echo '<status>1</status>';
      }
      else
      {
          echo '<status>0</status>';
      }

      mysqli_close($dbc);
  }
?>
