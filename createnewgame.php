<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

// grab all variables passed through GET
  $userid = $_GET['user_id'];
  $gametype = $_GET['game_type'];
  $tabletype = $_GET['table_type'];

// Check for latest session Id
  $dbc = mysqli_connect(host, user, password, database)
          or die("Error Connecting database");

// sanity check userpassed data

  if ( !is_numeric($userid) || is_null($gametype) || is_null($tabletype) )
  {
      echo '<result><status>0</status></result>';
  }
  else
  {

      // put the data in the database
      $query2 = "insert into table_gamesessions(game_type, table_type, creator_userid) values('$gametype', '$tabletype', $userid)";
      mysqli_query($dbc, $query2);
      if ( mysqli_affected_rows($dbc) == 1 )
      {
          echo '<result>';
          echo '<status>1</status>';
          echo '<session_id>';

          $query4 = "select max(session_id) from table_gamesessions";
          $result4 = mysqli_query($dbc, $query4);

          $latestsessionid = 0;
          if ( mysqli_num_rows($result4) == 0 )
          {
              // this case can never be assumed because we have just inserted an row
          }
          else
          {
              while ( $row4 = mysqli_fetch_array($result4) )
              {
                  $latestsessionid = $row4[0];
              }
          }
          echo $latestsessionid;
          echo '</session_id>';
          echo '</result>';

          // also make an entry of this user in the players table
          $query3 = "insert into games_players(session_id, user_id) values ($latestsessionid, $userid)";
          mysqli_query($dbc, $query3);
      }
      else
      {
          echo '<result><status>0</status></result>';
      }
  }
  mysqli_close($dbc);
?>
