<?php

  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

  $dbc = mysqli_connect(host, user, password, database)
          or die("Error connecing database");

  /* select all fields from the database */
  $query = "select * from user_cash";

  $result = mysqli_query($dbc, $query);
  $numrows = mysqli_num_rows($result);

  /* if there are some rows */
  if ( $numrows > 0 )
  {


      while ( $row = mysqli_fetch_array($result) )
      {
          $time1 = $row['datetime'];

          $time = strtotime($time1);

          // get userid of this user from earlier query.
          $userid = $row['user_id'];

          // get older chips available to this user
          $olderchips = $row['chips'];

          $status = $row['status'];

          $timezone = "Asia/Calcutta";

          $roomrentdeductiontime = $row['roomrentdeductiontime'];


          if ( function_exists('date_default_timezone_set') )
              date_default_timezone_set($timezone);

          $timenow = time();

          //***********************************************************************************************************
          //deduct room rent if has 24 hours since that is done
          if ( (time() - strtotime($roomrentdeductiontime)) > 60 * 60 * 24 )
          {
              //fetch room name of this user
              $query3 = "select room_name from user_room where user_id = $userid";
              $result3 = mysqli_query($dbc, $query3);

              if ( mysqli_num_rows($result3) > 0 )
              {
                  $row3 = mysqli_fetch_row($result3);
                  $roomname = $row3[0];

                  $chipstodeduct = 0;
                  if ( $roomname == "Sky High Suite" )
                  {
                      $chipstodeduct = 2500;
                  }
                  elseif ( $roomname == "Sky High PentHouse" )
                  {
                      $chipstodeduct = 5000;
                  }
                  elseif ( $roomname == "Resort Room" )
                  {
                      $chipstodeduct = 250;
                  }
                  elseif ( $roomname == "Sky High Room" )
                  {
                      $chipstodeduct = 1000;
                  }
                  else
                  {
                      $chipstodeduct = 1000;
                  }

                  $newchips = $olderchips - $chipstodeduct;

                  setchips($userid, $newchips);

                  // also update new time to current time
                  $newdate = date('Y-m-d  H:i:s', time());

                  $query4 = "update user_cash set roomrentdeductiontime = '$newdate' where user_id = $userid";
                  mysqli_query($dbc, $query4);

                  // also set a message for this user
                  $message = $chipstodeduct . ' chips deducted for room "' . $roomname . '"';

                  setmessage($message, $userid, 0, 0, $dbc);
              }
          }

          //***********************************************************************************************************
          // if the difference is more than 10 mins => later change it to 24 hours 
          if ( ($timenow - $time) > 60 * 60 * 24 )
          {

              $devicetokenofreceiver = fetchdevicetoken($userid, $dbc);

              // send a push to these users if status is 1
              if ( $status == 0 )
              {
                  $query6 = "update user_cash set status = 1 where user_id = $userid";
                  
                  mysqli_query($dbc, $query6);
                  
                  if ( mysqli_affected_rows($dbc) == 1 )
                  {
                      sendpush($devicetokenofreceiver);
                  }
              }
          }
          else
          {
              
          }
      }
  }
  mysqli_close($dbc);

  function sendpush($devicetokenofreceiver)
  {

// Put your device token here (without spaces):
      $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

// Actual $deviceToken = $devicetokenofthereceiver;
// Put your private key's passphrase here:
      $passphrase = 'abcd';

// Put your alert message here:
      $message = 'You have received Daily PayCheck.';

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
          'sound' => '3'
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