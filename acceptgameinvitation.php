<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

  $userid = $_GET['user_id'];
  $messageid = $_GET['message_id'];

//sanity check user passed variables
  if ( !is_numeric($userid) || !is_numeric($messageid) )
  {

      // both expected to be numeric. If they were not, say Unsuccessful
      echo '<status>0</status>';
  }
  else
  {

      $pushids = array();

      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");

      // fetch handler id of this message id
      $querypre = "select handler_id from messages where id = $messageid";
      $resultpre = mysqli_query($dbc, $querypre);
      if ( mysqli_num_rows($resultpre) > 0 )
      {

          $handlerid = NULL;
          while ( $rowpre = mysqli_fetch_array($resultpre) )
          {
              $handlerid = $rowpre[0];
          }
          // check if the game exists
          $query5 = "select session_id, invitation_from from invitations where id = $handlerid";
          $result5 = mysqli_query($dbc, $query5);
          if ( mysqli_num_rows($result5) == 1 )
          {
              // check if the session id exists

              $gotsessionid = "";
              $invitedby = "";
              while ( $row5 = mysqli_fetch_array($result5) )
              {
                  $gotsessionid = $row5[0];
                  $invitedby = $row5[1];
              }

              $thisgamename = fetchgametype($gotsessionid, $dbc);

              if ( $thisgamename != "" )
              {

                  // this game exists
                  // write query to update status of invitation
                  // also check if this user is part of another same type of game
                  // fetch all session ids where same type of game was created.
                  $query7 = "select session_id from table_gamesessions where game_type='$thisgamename'";
                  $result7 = mysqli_query($dbc, $query7);

                  $thissessionid = "";
                  $thisusernotpartofsamegame = TRUE;
                  if ( mysqli_num_rows($result7) == 0 )
                  {
                      echo 'this case should never be there. must be error in my query';
                  }
                  else
                  {
                      while ( $row7 = mysqli_fetch_array($result7) )
                      {
                          $thissessionid = $row7[0];

                          $query8 = "select * from games_players where user_id = $userid and session_id = $thissessionid";
                          $result8 = mysqli_query($dbc, $query8);
                          if ( mysqli_num_rows($result8) != 0 )
                          {
                              $thisusernotpartofsamegame = FALSE;
                              break;
                          }
                      }
                  }
                  if ( !$thisusernotpartofsamegame )
                  {
                      echo '<result><status>2</status><session_id>' . $gotsessionid . '</session_id><game_type>' . $thisgamename . '</game_type></result>';
                  }
                  else
                  {
                      // this user is not part of any game of this type
                      $query = "update invitations set status = 1 where id = $handlerid and invitation_to = $userid";
                      mysqli_query($dbc, $query);
                      if ( mysqli_affected_rows($dbc) != 0 )
                      {


                          //also delete that row from the notifications
                          $query2 = "delete from messages where user_id = $userid and id = $messageid";
                          mysqli_query($dbc, $query2);

                          // also make an entry in the game_players table
                          // fetch session id first
                          $query3 = "select session_id from invitations where id = $handlerid";
                          $result3 = mysqli_query($dbc, $query3);
                          if ( mysqli_num_rows($result3) > 0 )
                          {
                              $sessionid = NULL;
                              while ( $row3 = mysqli_fetch_array($result3) )
                              {
                                  $sessionid = $row3[0];
                              }

                              // check what type of game this is 
                              $playerstatus = 0;
                              $gametype = fetchgametype($sessionid, $dbc);

                              if ( $gametype == "slots" )
                                  $playerstatus = 1;

                              $query4 = "insert into games_players (session_id, user_id, status) values ($sessionid, $userid, $playerstatus)";
                              mysqli_query($dbc, $query4);

                              // delete the row from invitations table
                              $query5 = "delete from invitations where id = $handlerid";
                              mysqli_query($dbc, $query5);

                              // if one or more rows were affected, say invitation is acceepted.
                              echo '<result><status>1</status><session_id>' . $sessionid . '</session_id></result>';

                              //set a message and send push to all players of this game
                              $name = fetchname($userid, $dbc);

                              $message = $name . ' has joined ' . $gametype . ' game.';

                              // set this message now for all the players
                              $query6 = "select user_id from games_players where session_id = $sessionid and user_id not in ($userid)";
                              $result6 = mysqli_query($dbc, $query6);

                              if ( mysqli_num_rows($result6) > 0 )
                              {

                                  // set this message as latest massage of player
                                  $message2 = 'Joined the Game';

                                  $latesthandlerid = setlatestmessageid($sessionid, $userid, $message2, 1);

                                  while ( $row6 = mysqli_fetch_array($result6) )
                                  {
                                      $thisuserid = $row6[0];
                                      $query7 = "insert into messages(message, message_type, handler_id, user_id) values('$message', 3, $latesthandlerid, $thisuserid)";
                                      $result7 = mysqli_query($dbc, $query7);

                                      // fetch device token of this user and push it in an array
                                      $pushdevicetoken = fetchdevicetoken($thisuserid, $dbc);
                                      array_push($pushids, $pushdevicetoken);
                                  }
                              }
                          }
                      }
                      else
                      {
                          // says unsuccessful, if no row was affected.
                          echo '<result><status>0</status></result>';
                      }
                  }
              }
              else
              {
                  echo "<result><status>3</status></result>";
                  // delete the message and invitation
                  $query9 = "delete from invitations where id = $handlerid";
                  mysqli_query($dbc, $query9);

                  $query10 = "delete from messages where id = $messageid";
                  mysqli_query($dbc, $query10);
              }
          }
          else
          {
              echo "<result><status>3</status></result>";

              // delete the message and invitation
              $query9 = "delete from invitations where id = $handlerid";
              mysqli_query($dbc, $query9);

              $query10 = "delete from messages where id = $messageid";
              mysqli_query($dbc, $query10);
          }
      }
      else
      {
          // says unsuccessful, if no row was affected.
          echo '<result><status>0</status></result>';
      }
      mysqli_close($dbc);

      // check if there are some items in the pusharray
      if ( count($pushids) > 0 )
      {
          
          // Put your private key's passphrase here:
          $passphrase = 'abcd';

          //set a message and send push to all players of this game
          $name = fetchname($userid);

          $gametype = fetchgametype($sessionid);

          $message = $name . ' has joined ' . $gametype . ' game.';

          $ctx = stream_context_create();
          stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
          stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

          // Open a connection to the APNS server
          $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

          if ( !$fp )
              exit("Failed to connect: $err $errstr" . PHP_EOL);

          for ( $i = 0; $i < count($pushids); $i++ )
          {
              // Create the payload body
              $body['aps'] = array(
                  'alert' => $message,
                  'sound' => '3'
              );

              // Encode the payload as JSON
              $payload = json_encode($body);

              // Build the binary notification
              $msg = chr(0) . pack('n', 32) . pack('H*', $pushids[$i]) . pack('n', strlen($payload)) . $payload;

              // Send it to the server
              fwrite($fp, $msg, strlen($msg));
          }
          // Close the connection to the server
          fclose($fp);
      }
  }
?>