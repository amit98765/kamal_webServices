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

  $dbc = mysqli_connect(host, user, password, database)
          or die("Error connecting to database");

  $query = "select chips, gold, reward from user_cash where user_id =  $userid";

  $result = mysqli_query($dbc, $query);

  echo '<cash>';

  if ( mysqli_num_rows($result) == 1 )
  {
      while ( $row = mysqli_fetch_array($result) )
      {

          echo '<gold>';
          echo $row['gold'];
          echo '</gold>';

          echo '<chips>';
          echo $row['chips'];
          echo '</chips>';

          echo '<reward>';
          echo $row['reward'];
          echo '</reward>';
      }
  }
  echo '</cash>';
  mysqli_close($dbc);

  