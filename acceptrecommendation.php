<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

// check if required variables were set 
  if ( !isset($_GET['user_id_1']) || !isset($_GET['user_id_2']) )
  {
      echo 'User ids were not set, or set in incorrect variables';
  }
  else
  {
      // sanity check user passed data
      $userid1 = $_GET['user_id_1'];
      $userid2 = $_GET['user_id_2'];
      if ( !is_numeric($userid1) || !is_numeric($userid2) )
      {
          echo 'user Ids were not numeric';
      }
      else
      {
          // make an entry in recommendations table
          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connectind database");

          $query1 = "insert into recommendations (recommendors_user_id, recommended_user_id) values ( $userid2, $userid1)";
          mysqli_query($dbc, $query1);

          // check if row was successfully inserted or not
          if ( mysqli_affected_rows($dbc) == 1 )
          {
              // successful
              // now send a freiend request from this user to the other
              $query2 = "insert into friend_requests(request_from, request_to) values ($userid1, $userid2)";
              if ( mysqli_query($dbc, $query2) )
              {
                  $query3 = "select status, device_token from current_login_status where user_id = $userid2";
                  $result3 = mysqli_query($dbc, $query3);

                  $receiverisonline = FALSE;
                  $devicetokenofreceiver = NULL;

                  if ( mysqli_num_rows($result3) != 0 )
                  {
                      while ( $row3 = mysqli_fetch_array($result3) )
                      {
                          if ( $row3[0] == 1 )
                          {
                              $receiverisonline = TRUE;
                              $devicetokenofreceiver = $row3[1];
                              break;
                          }
                      }
                  }

                  // if receiveer is online, get current device token, otherwise main device token

                  if ( is_null($devicetokenofreceiver) )
                  {
                      $query4 = "select devicetoken from user_details where user_id = $userid2";
                      $result4 = mysqli_query($dbc, $query4);
                      if ( mysqli_num_rows($result4) != 0 )
                      {
                          while ( $row4 = mysqli_fetch_array($result4) )
                          {
                              $devicetokenofreceiver = $row4[0];
                          }
                      }
                  }

                  // now get name and email of request sender
                  $nameofsender = "";
                  $emailofsender = "";
                  $devicetokenofsender = "";

                  $query5 = "select name, email_id, devicetoken from user_details where user_id = $userid1";
                  $result5 = mysqli_query($dbc, $query5);
                  if ( mysqli_num_rows($result5) != 0 )
                  {
                      while ( $row5 = mysqli_fetch_array($result5) )
                      {
                          $nameofsender = $row5[0];
                          $emailofsender = $row5[1];
                          $devicetokenofsender = $row5[2];
                      }
                  }
                  echo '<status>1</status>';
                  sendpush($devicetokenofreceiver, $nameofsender, $emailofsender);
              }
              else
              {

                  // recommendation was added, but friend request was not sent.
                  echo '<status>2</status>';
              }
          }
          elseif ( mysqli_affected_rows($dbc) > 1 )
          {
              // panic
              echo '<status>2</status>';
          }
          else
          {
              //failed
              echo '<status>0</status>';
          }
      }
  }
?>
<?php

  function sendpush($devicetokenofreceiver, $nameofsender, $emailofsender)
  {

// Put your device token here (without spaces):
      $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

// Actual $deviceToken = $devicetokenofthereceiver;
// Put your private key's passphrase here:
      $passphrase = 'abcd';

// Put your alert message here:
      $message = 'You received a friend request from ' . $nameofsender . ' ( ' . $emailofsender . ' ) ';

////////////////////////////////////////////////////////////////////////////////

      $ctx = stream_context_create();
      stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
      stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
      $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

      if ( !$fp )
          exit("Failed to connect: $err $errstr" . PHP_EOL);


// Create the payload body
      $body['aps'] = array(
          'alert' => $message,
          'sound' => 'default'
      );

// Encode the payload as JSON
      $payload = json_encode($body);

// Build the binary notification
      $msg = chr(0) . pack('n', 32) . pack('H*', $devicetokenofreceiver) . pack('n', strlen($payload)) . $payload;

// Send it to the server
      $result = fwrite($fp, $msg, strlen($msg));

// Close the connection to the server
      fclose($fp);
  }

?>
