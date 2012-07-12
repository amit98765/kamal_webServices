<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

//check if required variables were passed
  if ( is_null($_GET['user_id']) || is_null($_GET['userids']) || is_null($_GET['gift']) || is_null($_GET['session_id']) || is_null($_GET['gift_price']) )
  {
      echo 'not all variables were passed';
  }
  else
  {
      // grab the variables
      $userid = $_GET['user_id'];
      $userids = $_GET['userids'];
      $giftname = $_GET['gift'];
      $sessionid = $_GET['session_id'];
      $giftprice = $_GET['gift_price'];

      $useridsexploded = explode(',', $userids);

      $giftsentto = array();

      // insert the data into the gift box table
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");

      // reduce the price of the gift from the user's cash
      $done = increasedecreasechips($userid, $giftprice, 0, $dbc);

      if ( !$done )
      {
          echo '<status>0</status>';
      }
      else
      {
          $handlerid = NULL;

          for ( $i = 0; $i < count($useridsexploded); $i++ )
          {

              $query = "insert into gift_box(sent_by, sent_to, session_id, gift_name) values ($userid, $useridsexploded[$i], $sessionid, '$giftname')";

              mysqli_query($dbc, $query);

              $handlerid = mysqli_insert_id($dbc);

              // set a normal message for all these people in the message box
              $name = fetchname($userid, $dbc);

              $gametype = fetchgametype($sessionid, $dbc);

              $message = "You Received " . $giftname . ' as a gift from ' . $name . ' in ' . $gametype . ' game.';

              setmessage($message, $useridsexploded[$i], $handlerid, 4, $dbc);

              array_push($giftsentto, $useridsexploded[$i]);
          }

// check if handler id is formed
          if ( is_null($handlerid) )
          {

//return unsuccessful
              echo '<status>0</status>';
          }
          else
          {

// return unsuccessful
              echo '<status>1</status>';

// also send a push to every person
              if ( count($giftsentto) > 0 )
              {

                  // form a message and send the push

                  $name = fetchname($userid, $dbc);

                  $gametype = fetchgametype($sessionid, $dbc);

                  $message = "You Received " . $giftname . ' as a gift from ' . $name . ' in ' . $gametype . ' game.';

                  //$deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';
                  // Actual $deviceToken = $devicetokenofthereceiver;
                  // Put your private key's passphrase here:
                  $passphrase = 'abcd';

                  // Put your alert message here:
                  ////////////////////////////////////////////////////////////////////////////////

                  $ctx = stream_context_create();
                  stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                  stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                  // Open a connection to the APNS server
                  $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                  if ( !$fp )
                      exit("Failed to connect: $err $errstr" . PHP_EOL);

                  for ( $i = 0; $i < count($giftsentto); $i++ )
                  {
                      // Create the payload body
                      $body['aps'] = array(
                          'alert' => $message,
                          'sound' => '3'
                      );

                      // Encode the payload as JSON
                      $payload = json_encode($body);

                      // fetch device token of all players
                      $devicetoken = fetchdevicetoken($giftsentto[$i]);

                      // Build the binary notification
                      $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                      // Send it to the server
                      fwrite($fp, $msg, strlen($msg));
                  }
                  // Close the connection to the server
                  fclose($fp);
              }
          }
      }
      mysqli_close($dbc);
  }
?>
