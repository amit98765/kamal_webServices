<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

// check if variables are passed

  if ( !isset($_GET['session_id']) || !isset($_GET['userids']) || !isset($_GET['user_id']) )
  {
      echo 'All Variables were not passed ';
  }
  else
  {

// grab all varables
      $sessionid = $_GET['session_id'];
      $useridslist = $_GET['userids'];
      $userid = $_GET['user_id'];

//fetch gametype corresponding to session id
      $gametype = fetchgametype($sessionid);


//check if got correct value
      if ( is_null($gametype) )
      {
          echo '<status>0</status>';
      }
      else
      {

          $pusharray1 = array();
          $pusharray2 = array();

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
// if the gametype is slots, check how many players are invited or are part of game
          if ( $gametype == 'slots' )
          {

// break apart all userids passed
              $useridsexploded = explode(',', $useridslist);

              $noofplayers = count($useridsexploded);

// check enteries in players table


              $query = "select * from games_players where session_id = $sessionid";
              $result = mysqli_query($dbc, $query);

              $noofplayers += (int) mysqli_num_rows($result);

// now check how many players are invited
              $query2 = "select * from invitations where session_id = $sessionid";
              $result2 = mysqli_query($dbc, $query2);

              $noofplayers += (int) mysqli_num_rows($result2);

              if ( $noofplayers > 4 )
              {
// there cannot be this much of users for this game
                  echo '<status>2</status>';
                  exit();
              }
          }

// check if this player has alreaady been part of game or invited
// break apart all userids passed
          $useridsexploded = explode(',', $useridslist);

          for ( $i = 0; $i < count($useridsexploded); $i++ )
          {
              $thisuserid = $useridsexploded[$i];

// check if this user is part of the game
              $userpartofthegame = FALSE;
              $query3 = "select * from games_players where session_id = $sessionid and user_id = $thisuserid";
              $result3 = mysqli_query($dbc, $query3);

              if ( mysqli_num_rows($result3) != 0 )
              {
                  $userpartofthegame = TRUE;
              }
              else
              {
// check if an invitation is sent to this user already
                  $query4 = "select * from invitations where invitation_to = $thisuserid and session_id = $sessionid";
                  $result4 = mysqli_query($dbc, $query4);

                  if ( mysqli_num_rows($result4) != 0 )
                  {
                      $userpartofthegame = TRUE;
                  }
              }

// if this user was not a part of the game, sent the invitation
              if ( !$userpartofthegame )
              {

// no invitation is sent to this user
                  $query = "insert into invitations (session_id, invitation_to, invitation_from) values($sessionid, $thisuserid, $userid)";
                  mysqli_query($dbc, $query);
              }
              else
              {
                  // user is already part of game
                  // return status 3 and exit
                  echo '<status>3</status>';
                  exit();
              }
          }


          // // work was successful
          // send the push(fetch all devicetokens, name of game, name of game creator)
          //fetch name of request sender
          $nameofrequestsender = fetchname($userid, $dbc);

          // fetch name of the game
          $gametype = fetchgametype($sessionid, $dbc);

          if ( is_null($gametype) )
          {
              echo '<status>0</status>';
          }
          else
          {
              // get how many invitations were sent

              $query4 = "select invitation_to from invitations where session_id = $sessionid and invitation_to in ($useridslist)";
              $result4 = mysqli_query($dbc, $query4);

              if ( mysqli_num_rows($result4) == 0 )
              {
                  //no invitation was sent
                  echo '<status>0</status>';
              }
              else
              {

                  // fetch device tokens of these users one by one
                  while ( $row4 = mysqli_fetch_array($result4) )
                  {
                      $inviteduserid = $row4[0];
                      $inviteddevicetoken = fetchdevicetoken($inviteduserid, $dbc);

                      if ( !is_null($inviteddevicetoken) )
                      {

                          //fetch handler id
                          $query8 = "select id from invitations where session_id = $sessionid and invitation_to = $inviteduserid ";

                          $result8 = mysqli_query($dbc, $query8);
                          
                          $numrows8 = mysqli_num_rows($result8);
                          
                          $handlerid = 0;
                          if ( $numrows8 > 0 )
                          {
                              while ( $row8 = mysqli_fetch_array($result8) )
                              {
                                  $handlerid = $row8['id'];
                              }
                          }
                          else
                          {
                              echo 'There is some error in the logic';
                          }

                          // form a message now 
                          $messageformed = $nameofrequestsender . ' has Invited you for ' . $gametype . ' game.';

                          // insert the row
                          $query7 = "insert into messages( message, message_type, user_id, handler_id) values ( '$messageformed', 1, $inviteduserid, $handlerid) ";
                          mysqli_query($dbc, $query7);

                          array_push($pusharray1, $inviteduserid);
                      }
                  }
              }
          }
          mysqli_close($dbc);
      }
  }

// send the push now 
  if ( count($pusharray1) > 0 )
  {


      echo '<status>1</status>';

      // Put your device token here (without spaces):
      $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

      // Actual $deviceToken = $devicetokenofthereceiver;
      // Put your private key's passphrase here:
      $passphrase = 'abcd';

      //fetch name of request sender
      $nameofrequestsender = fetchname($userid);

      // fetch name of the game
      $gametype = fetchgametype($sessionid);

      // Put your alert message here:
      $message = $nameofrequestsender . ' has Invited you for ' . $gametype . ' game.';

      $ctx = stream_context_create();
      stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
      stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

      // Open a connection to the APNS server
      $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

      if ( !$fp )
          exit("Failed to connect: $err $errstr" . PHP_EOL);

      for ( $i = 0; $i < count($pusharray1); $i++ )
      {
          // Create the payload body
          $body['aps'] = array(
              'alert' => $message,
              'sound' => '1'
          );

          // Encode the payload as JSON
          $payload = json_encode($body);

          // fetch device token of this user
          $playerdevicetoken = fetchdevicetoken($pusharray1[$i]);

          // Build the binary notification
          $msg = chr(0) . pack('n', 32) . pack('H*', $playerdevicetoken) . pack('n', strlen($payload)) . $payload;

          // Send it to the server
          fwrite($fp, $msg, strlen($msg));
      }
      // Close the connection to the server
      fclose($fp);
  }

// fetch other players of this game
//----------------------------------------------------------------------------------
//
  if ( count($pusharray1) > 0 )
  {
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");

      $query = "select user_id from games_players where session_id = $sessionid and user_id not in ($userid)";
      $result = mysqli_query($dbc, $query);

      if ( mysqli_num_rows($result) > 0 )
      {
          while ( $row = mysqli_fetch_array($result) )
          {
              $playersid = $row[0];

              array_push($pusharray2, $playersid);
          }
      }
      mysqli_close($dbc);
  }

  if ( count($pusharray2) > 0 )
  {

      // insert this message into latest messages table
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");

      //fetch name of request sender
      $nameofrequestsender = fetchname($userid, $dbc);

      // fetch name of the game
      $gametype = fetchgametype($sessionid, $dbc);

      $allinvited1 = "";
      for ( $i = 0; $i < count($pusharray1); $i++ )
      {
          $allinvited1 .= fetchname($pusharray1[$i], $dbc) . ', ';
      }

      // remove trailing ,
      $allinvited = substr($allinvited1, 0, strlen($allinvited1) - 2);

      $message = $nameofrequestsender . ' Invited ' . $allinvited . ' to ' . $gametype . ' game.';



      // insert this AS the user's message, and fetch that id
      $latestmessage = 'Invited ' . $allinvited;
      $handleridlatestmessage = setlatestmessageid($sessionid, $userid, $latestmessage, 1);

      // for wach user in pusharray2
      for ( $i = 0; $i < count($pusharray2); $i++ )
      {
          $thisuserid = $pusharray2[$i];
          $query = "insert into messages(handler_id, user_id, message, message_type) values ($handleridlatestmessage,$thisuserid, '$message', 3)";
          mysqli_query($dbc, $query);
      }
      mysqli_close($dbc);

      // Put your device token here (without spaces):
      $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

      // Actual $deviceToken = $devicetokenofthereceiver;
      // Put your private key's passphrase here:
      $passphrase = 'abcd';

      $ctx = stream_context_create();
      stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
      stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

      // Open a connection to the APNS server
      $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

      if ( !$fp )
          exit("Failed to connect: $err $errstr" . PHP_EOL);

      for ( $i = 0; $i < count($pusharray2); $i++ )
      {
          // Create the payload body
          $body['aps'] = array(
              'alert' => $message,
              'sound' => '4'
          );

          // Encode the payload as JSON
          $payload = json_encode($body);

          // fetch device token of this user
          $playerdevicetoken = fetchdevicetoken($pusharray2[$i]);

          // Build the binary notification
          $msg = chr(0) . pack('n', 32) . pack('H*', $playerdevicetoken) . pack('n', strlen($payload)) . $payload;

          // Send it to the server
          fwrite($fp, $msg, strlen($msg));
      }
      // Close the connection to the server
      fclose($fp);
  }
//
//--------------------------------------------------------------------------------------
?>
