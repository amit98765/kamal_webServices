<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

// check if requiredd fields were passed in url
  if ( is_null($_GET['session_id']) )
  {
      echo 'session_id was not passed';
  }
  else
  {

      // grab the variable
      $sessionid = $_GET['session_id'];
      $noonebet = FALSE;
      $pushids = array();

      //find all the playerrs of this gamae
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting databse");

      $query3 = "select user_id from roulette_bets where session_id = $sessionid and cases !='' and amount != 0";
      $result3 = mysqli_query($dbc, $query3);

      if ( mysqli_num_rows($result3) > 0 )
      {

          // also set latest messaage as how much chips were bet
          while ( $row3 = mysqli_fetch_array($result3) )
          {
              $thisuserid = $row3[0];

              $query5 = "select amount from roulette_bets where session_id = $sessionid and user_id = $thisuserid";

              $result5 = mysqli_query($dbc, $query5);

              $row5 = mysqli_fetch_row($result5);

              $betssplit = explode(':', $row5[0]);

              $amountbet = 0;

              for ( $i = 0; $i < count($betssplit); $i++ )
              {
                  $amountbet += convertchips($betssplit[$i]);
              }

              $message = "Bet " . convertBackChips($amountbet) . ' Chips';

              setlatestmessage($sessionid, $thisuserid, $message, 1, $dbc);
          }

          setTimeZone();

          $date3 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+15 seconds'));

          //  set the roulette status to 1 
          $query4 = "update roulette_game_data set status = 1, datetime='$date3' where session_id = $sessionid";
          mysqli_query($dbc, $query4);
      }
      else
      {
          // start a new session, if there is no user who has bets
          setTimeZone();

          $date4 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));

          //  set the roulette status to 1 
          $query9 = "update roulette_game_data set status = 0, datetime='$date4' where session_id = $sessionid";
          mysqli_query($dbc, $query9);

          $noonebet = TRUE;
      }

      $query = "select user_id from roulette_bets where session_id = $sessionid and cases ='' and amount = 0";
      $result = mysqli_query($dbc, $query);

      if ( mysqli_num_rows($result) > 0 )
      {
          //fetch all players one by one, and check if they had bet
          while ( $row = mysqli_fetch_array($result) )
          {
              $thisuserid = $row[0];
              $message = "Missed Bet Time";
              setlatestmessage($sessionid, $thisuserid, $message, 1, $dbc);
          }
      }


      // fetch all players and push in an array
      $query2 = "select user_id from games_players where session_id = $sessionid";
      $result2 = mysqli_query($dbc, $query2);

      if ( mysqli_num_rows($result2) > 0 )
      {
          while ( $row2 = mysqli_fetch_array($result2) )
          {
              array_push($pushids, $row2[0]);
          }
      }

      // message for all the players has been set, now send a push to all players 
      if ( count($pushids) > 0 )
      {
          $passphrase = 'abcd';

          $ctx = stream_context_create();
          stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
          stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

          // Open a connection to the APNS server
          $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

          if ( !$fp )
              exit("Failed to connect: $err $errstr" . PHP_EOL);

          for ( $i = 0; $i < count($pushids); $i++ )
          {
              if ( !$noonebet )
              {
                  $body['aps'] = array(
                      'alert' => 'Roulette starts Rolling.',
                      'sound' => '3'
                  );
              }
              else
              {
                  $body['aps'] = array(
                      'alert' => 'You Missed Bet Time.',
                      'sound' => '3'
                  );
              }
              // Encode the payload as JSON
              $payload = json_encode($body);

              // fetch device token of all players
              $devicetoken = fetchdevicetoken($pushids[$i], $dbc);

              // Build the binary notification
              $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

              // Send it to the server
              fwrite($fp, $msg, strlen($msg));
          }
          // Close the connection to the server
          fclose($fp);
      }
  }
?>
