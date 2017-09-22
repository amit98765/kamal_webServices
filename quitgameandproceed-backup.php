<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

// chech whether all required parameters were passed.
  if ( !isset($_GET['session_id']) || !isset($_GET['user_id']) || !isset($_GET['message_id']) || !isset($_GET['game_type']) )
  {
      echo 'message_id or user_id was not passed.';
      exit(0);
  }

// grab the variables
  $sessionid = $_GET['session_id'];
  $userid = (int) $_GET['user_id'];
  $messageid = $_GET['message_id'];
  $gametype = trim($_GET['game_type']);

  $dbc = mysqli_connect(host, user, password, database)
          or die("Errior connecting database");


// we have found the game type
// check if the user is a part of this type of game

  $query2 = "select session_id from table_gamesessions where game_type = '$gametype' and session_id != $sessionid";
  $result2 = mysqli_query($dbc, $query2);
  if ( mysqli_num_rows($result2) > 0 )
  {
      while ( $row2 = mysqli_fetch_array($result2) )
      {
          $thissessionid = $row2[0];

          // for each session id, check how many users are playing this game
          $query3 = "select * from games_players where status = 1 and session_id = $thissessionid and user_id = $userid";
          $result3 = mysqli_query($dbc, $query3);


          if ( mysqli_num_rows($result3) == 1 )
          {
              // check other active players
              $querymy = "select * from games_players where status = 1 and session_id = $thissessionid";
              $resultmy = mysqli_query($dbc, $querymy);

              if ( mysqli_num_rows($resultmy) > 1 )
              {

                  // more than 1 active player
                  $query5 = "update games_players set status = 0 where session_id = $thissessionid and user_id = $userid";
                  mysqli_query($dbc, $query5);
              }
              else
              {
                  // delete all the players entries, as there may still be some inactive users
                  $query4 = "delete from games_players where session_id = $thissessionid";
                  mysqli_query($dbc, $query4);

                  if ( mysqli_affected_rows($dbc) > 0 )
                  {
                      $query5 = "delete from table_gamesessions where session_id = $thissessionid";
                      mysqli_query($dbc, $query5);

                      if ( mysqli_affected_rows($dbc) == 1 )
                      {
                          break;
                      }
                  }
              }
          }
      }

      // now make an entry o this user in the active players list in new game
      $query6 = "insert into games_players(session_id, user_id, status) values ($sessionid, $userid, 1)";
      mysqli_query($dbc, $query6);

      if ( mysqli_affected_rows($dbc) == 1 )
      {

          // fetch handlerid according to the given message id and delete that row
          $query7 = "select handler_id from messages where id = $messageid";
          $result7 = mysqli_query($dbc, $query7);
          if ( mysqli_num_rows($result7) > 0 )
          {
              $handlerid = "";
              while ( $row7 = mysqli_fetch_array($result7) )
              {
                  $handlerid = $row7[0];
              }
              // delete this row

              $query8 = "delete from messages where id = $messageid";
              mysqli_query($dbc, $query8);

              // delete the row from handler id
              $query9 = "select * from invitations where id = $handlerid";
              $result9 = mysqli_query($dbc, $query9);
              if ( mysqli_num_rows($result9) > 0 )
              {
                  $query10 = "delete from invitations where id = $handlerid";
                  mysqli_query($dbc, $query10);
              }
          }

          echo '<status>1</status>';
      }
  }
  else
  {
      // this case was never assumed.
      echo '<status>0</status>';
  }

  mysqli_close($dbc);
?>