<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

// check if required fields were passed in url
  if ( is_null($_GET['session_id']) || is_null($_GET['user_id']) )
  {
      echo 'session_id or user_id was not passed';
  }
  else
  {

// grab the variable
      $sessionid = $_GET['session_id'];
      $userid = $_GET['user_id'];

      //sanity check the variable
      if ( !is_numeric($sessionid) )
      {
          echo 'There is unexpected error';
      }
      else
      {
          // check no of active players of this game
          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");

          $query = "select * from roulette_game_data where session_id = $sessionid";

          $result = mysqli_query($dbc, $query);

          // if there was no row
          if ( mysqli_num_rows($result) == 0 )
          {

              // make an entry
              $timezone = "Asia/Calcutta";
              if ( function_exists('date_default_timezone_set') )
                  date_default_timezone_set($timezone);


              // also make an entry in roulette_game_data table
              $query3 = "insert into roulette_game_data(session_id, datetime) values($sessionid, '0000-00-00 00:00:00')";

              mysqli_query($dbc, $query3);
          }



          // there was no active players of this game
          // so set its status as active, and return seonds remaining
          $query2 = "update games_players set status = 1 where session_id = $sessionid and user_id = $userid";
          if ( mysqli_query($dbc, $query2) )
              echo '<status>1</status>';
          else
              echo '<status>0</status>';


          // also make an entry in bets table
          $query5 = "insert into roulette_bets( user_id, session_id) values( $userid, $sessionid)";
          mysqli_query($dbc, $query5);

          mysqli_close($dbc);
      }
  }
?>
