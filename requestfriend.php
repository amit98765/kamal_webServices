<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

  $requestfrom = $_GET['user1'];
  $requestto = $_GET['user2'];

  if ( !is_numeric($requestto) || !is_numeric($requestfrom) )
  {
      echo '<status>';
      echo '0';
      echo '</status>';
  }
  elseif ( $requestfrom == $requestto )
  {
      echo '<status>';
      echo '0';
      echo '</status>';
  }
  else
  {


      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");
      $querypre = "select * from friend_requests where request_from = $requestfrom and request_to = $requestto";
      $resultpre = mysqli_query($dbc, $querypre);
      if ( mysqli_num_rows($resultpre) > 0 )
      {
          echo '<status>';
          echo '0';
          echo '</status>';
          mysqli_close($dbc);
      }
      else
      {

          // another check here to make sure that the other person has not 
          // already sent friend request to this user
          $querypre2 = "select * from friend_requests where request_to = $requestfrom and request_from = $requestto";
          $resultpre2 = mysqli_query($dbc, $querypre2);
          if ( mysqli_num_rows($resultpre2) > 0 )
          {
              // this means the other person has already sent him friend request
              echo '<status>';
              echo '2';
              echo '</status>';
              mysqli_close($dbc);
          }
          else
          {
              $query = "insert into friend_requests(request_from, request_to) values ($requestfrom, $requestto)";
              mysqli_query($dbc, $query);

              if ( mysqli_affected_rows($dbc) == 1 )
              {
                  echo '<status>';
                  echo '1';
                  echo '</status>';
                  // check whether requested person is online or not
                  $devicetokenofreceiver = fetchdevicetoken($requestto);

                  // now get name and email of request sender
                  $nameofsender = "";
                  $emailofsender = "";


                  $query3 = "select name, email_id from user_details where user_id = $requestfrom";
                  $result3 = mysqli_query($dbc, $query3);
                  if ( mysqli_num_rows($result3) != 0 )
                  {
                      while ( $row3 = mysqli_fetch_array($result3) )
                      {
                          $nameofsender = $row3[0];
                          $emailofsender = $row3[1];
                      }
                  }

                  sendpush($devicetokenofreceiver, $nameofsender, $emailofsender);
              }
              else
              {
                  echo '<status>';
                  echo '0';
                  echo '</status>';
              }

              mysqli_close($dbc);
          }
      }
  }

  function sendpush($devicetokenofreceiver, $nameofsender, $emailofsender)
  {

// Put your device token here (without spaces):
      //   $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';
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
      $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

      if ( !$fp )
          exit("Failed to connect: $err $errstr" . PHP_EOL);


// Create the payload body
      $body['aps'] = array(
          'alert' => $message,
          'sound' => '2'
      );

// Encode the payload as JSON
      $payload = json_encode($body);

// Build the binary notification
      $msg = chr(0) . pack('n', 32) . pack('H*', $devicetokenofreceiver) . pack('n', strlen($payload)) . $payload;

// Send it to the server
      fwrite($fp, $msg, strlen($msg));

// Close the connection to the server
      fclose($fp);
  }
?>

