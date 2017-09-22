<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

// check if required fields were passed in url
  if ( is_null($_GET['session_id']) || is_null($_GET['user_id']) || is_null($_GET['amount']) || is_null($_GET['selected_cases']) )
  {
      echo 'session_id or user_id or amount was not passed';
  }
  else
  {

// grab the variable
      $sessionid = $_GET['session_id'];
      $userid = $_GET['user_id'];
      $amount = $_GET['amount'];
      $selectedcases = $_GET['selected_cases'];


      // CHECK IF A ROW ALREADY EXISTS    
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");

      $query = "select amount,cases from roulette_bets where user_id = $userid and session_id = $sessionid";
      $result = mysqli_query($dbc, $query);

      if ( mysqli_num_rows($result) == 0 )
      {
          // insert a new row
          $query2 = "insert into roulette_bets( user_id, session_id, amount, cases) values( $userid, $sessionid, '$amount', '$selectedcases')";
          mysqli_query($dbc, $query2);

          if ( mysqli_affected_rows($dbc) > 0 )
              echo '<status>1</status>';
          else
              echo '<status>0</status>';
      }
      else
      {
          // update the existing bet, by first fetching existing bet
          $row = mysqli_fetch_row($result);

          $previousamount = $row[0];
          $previouscases = $row[1];

          if ( $previousamount != 0 )
          {
              $newamount = $previousamount . ':' . $amount;
              $newcases = $previouscases . ':' . $selectedcases;
          }
          else
          {
              $newamount = $amount;
              $newcases = $selectedcases;
          }
          
          // update the new data
          $query2 = "update roulette_bets set amount= '$newamount', cases = '$newcases' where user_id = $userid and session_id = $sessionid";
          if ( mysqli_query($dbc, $query2) )
              echo '<status>1</status>';
          else
              echo '<status>0</status>';
      }

      mysqli_close($dbc);
  }
?>