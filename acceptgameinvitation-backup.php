<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

// grab user passed variables
//$handlerid = $_GET['handler_id'];
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
              $query6 = "select * from table_gamesessions where session_id = $gotsessionid";
              $result6 = mysqli_query($dbc, $query6);
              if ( mysqli_num_rows($result6) == 1 )
              {
                  // this game exists
                  // write query to update status of invitation
                  // also check if this user is part of another same type of game
                  $thisgamename = "";
                  while ( $row6 = mysqli_fetch_array($result6) )
                  {
                      $thisgamename = $row6['game_type'];
                  }

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
                          $query8 = "select * from games_players where user_id = $userid and status = 1 and session_id = $thissessionid";
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
                              $query4 = "insert into games_players (session_id, user_id, status) values ($sessionid, $userid, 1)";
                              mysqli_query($dbc, $query4);

                              // delete the row from invitations table
                              $query5 = "delete from invitations where id = $handlerid";
                              mysqli_query($dbc, $query5);

                              // if one or more rows were affected, say invitation is acceepted.
                              echo '<result><status>1</status><session_id>' . $sessionid . '</session_id></result>';

                              // when everything id done successfully, invoke a push, and also insert it into messages 
                              // table
                              //fetch name of person accepted the invitation
                              $query6 = "select name from user_details where user_id = $userid";
                              $result6 = mysqli_query($dbc, $query6);
                              $name = "A friend";
                              if ( mysqli_num_rows($result6) != 0 )
                              {
                                  while ( $row6 = mysqli_fetch_array($result6) )
                                  {
                                      $name = $row6[0];
                                  }
                              }

                              // fetch active devicetoken of the user who invited this person
                              // grab devicetoken of this user
                              $query7 = "select status, device_token from current_login_status where user_id = $invitedby";
                              $result7 = mysqli_query($dbc, $query7);

                              $receiverisonline = FALSE;
                              $devicetokenofreceiver = NULL;

                              if ( mysqli_num_rows($result7) != 0 )
                              {
                                  while ( $row7 = mysqli_fetch_array($result7) )
                                  {
                                      if ( $row7[0] == 1 )
                                      {
                                          $receiverisonline = TRUE;
                                          $devicetokenofreceiver = $row7[1];
                                          break;
                                      }
                                  }
                              }

                              // if receiveer is online, get current device token, otherwise main device token

                              if ( is_null($devicetokenofreceiver) )
                              {
                                  $query8 = "select devicetoken from user_details where user_id = $invitedby";
                                  $result8 = mysqli_query($dbc, $query8);
                                  if ( mysqli_num_rows($result8) != 0 )
                                  {
                                      while ( $row8 = mysqli_fetch_array($result8) )
                                      {
                                          $devicetokenofreceiver = $row8[0];
                                      }
                                  }
                                  else
                                  {
                                      exit("An unexpected error occured. Please retry");
                                  }
                              }

                              // make an entry in messages table
                              $message = $name . " accepted your game invitation";
                              $query9 = "insert into messages(message, message_type, user_id) values ('$message', 0, $invitedby)";
                              mysqli_query($dbc, $query9);

                              // also check if the invitor was the game_creator
                              $query10 = "select creator_userid from table_gamesessions where session_id = $gotsessionid";
                              $result10 = mysqli_query($dbc, $query10);
                              if ( mysqli_num_rows($result10) == 0 )
                              {
                                  // this case can never happen
                                  echo '<result><status>0</status></result>';
                              }
                              else
                              {
                                  $creatorid = 0;
                                  while ( $row10 = mysqli_fetch_array($result10) )
                                  {
                                      $creatorid = $row10[0];
                                  }
                                  if ( $creatorid != $invitedby )
                                  {
                                      // if invitor was different, alert him too
                                      // fetch active devicetoken of the user who invited this person
                                      // grab devicetoken of this user
                                      $query11 = "select status, device_token from current_login_status where user_id = $creatorid";
                                      $result11 = mysqli_query($dbc, $query11);

                                      $receiverisonline = FALSE;
                                      $devicetoken = NULL;

                                      if ( mysqli_num_rows($result11) != 0 )
                                      {
                                          while ( $row11 = mysqli_fetch_array($result11) )
                                          {
                                              if ( $row11[0] == 1 )
                                              {
                                                  $receiverisonline = TRUE;
                                                  $devicetoken = $row11[1];
                                                  break;
                                              }
                                          }
                                      }

                                      // if receiveer is online, get current device token, otherwise main device token

                                      if ( is_null($devicetoken) )
                                      {
                                          $query12 = "select devicetoken from user_details where user_id = $creatorid";
                                          $result12 = mysqli_query($dbc, $query12);
                                          if ( mysqli_num_rows($result12) != 0 )
                                          {
                                              while ( $row12 = mysqli_fetch_array($result12) )
                                              {
                                                  $devicetoken = $row12[0];
                                              }
                                          }
                                          else
                                          {
                                              exit("An unexpected error occured. Please retry");
                                          }
                                      }
                                      // got devie token
                                      // make an entry in messagebox and invoke push
                                      // make an entry in messages table
                                      $message = $name . " accepted your game invitation";
                                      $query13 = "insert into messages(message, message_type, user_id) values ('$message', 0, $creatorid)";
                                      mysqli_query($dbc, $query13);

                                      // send him push
                                      sendpush($devicetoken, $name);
                                  }
                              }

                              // got device token of receiver and name
                              // invoke the push
                              sendpush($devicetokenofreceiver, $name);
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
              // delete the game and invitation
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
  }

// function sendpush here 
  function sendpush($devicetoken, $name)
  {
// Put your device token here (without spaces):
      $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

// Actual $deviceToken = $devicetokenofthereceiver;
// Put your private key's passphrase here:
      $passphrase = 'abcd';

// Put your alert message here:
      // $message = $nameofinvitor . ' has invited you for ' . $thisgametype;
////////////////////////////////////////////////////////////////////////////////

      $ctx = stream_context_create();
      stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
      stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
      $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

      if ( !$fp )
          exit("Failed to connect: $err $errstr" . PHP_EOL);


// Create the payload body
      $message = $name . ' accepted your game invitation';

      $body['aps'] = array(
          'alert' => $message,
          'sound' => '1'
      );

// Encode the payload as JSON
      $payload = json_encode($body);

// Build the binary notification
      $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
      $result = fwrite($fp, $msg, strlen($msg));

// Close the connection to the server
      fclose($fp);
  }

?>
