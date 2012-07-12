<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

// check if requiredd fields were passed in url
  if ( is_null($_GET['session_id']) || is_null($_GET['user_id']) || is_null($_GET['amount']) )
  {
      echo 'session_id, or user_id or action was not passed';
  }
  else
  {
      // grab the variable
      $sessionid = $_GET['session_id'];
      $userid = $_GET['user_id'];
      $amount = $_GET['amount'];

      //sanity check the variable
      if ( !is_numeric($sessionid) )
      {
          echo 'There is unexpected error';
      }
      else
      {
          // fetch all session ids playing gme in this session id
          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");

          $query1 = "update blackjack_bets set amount = '$amount' where user_id = $userid and session_id = $sessionid";
          if(mysqli_query($dbc, $query1))
          {
              echo '<status>1</status>';
              setlatestmessage($sessionid, $userid, 'Bet ' . $amount . ' chips', 1, $dbc);
          }
          else
          {
              echo '<status>0</status>';
          }
          mysqli_close($dbc);
      }
  }
?>
