<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  $userid = $_GET['userid'];
  $dbc = mysqli_connect(host, user, password, database)
          or die("Error connecting database");

  $query = "update current_login_status set status = 0 where user_id = $userid";
  mysqli_query($dbc, $query);
  if ( mysqli_affected_rows($dbc) == 1 )
  {
      echo '<status>1</status>';
  }
  else
  {
      echo '<status>0</status>';
  }
  mysqli_query($dbc, $query);
?>
