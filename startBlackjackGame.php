<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

// check if required fields were passed in url
  if ( is_null($_GET['session_id']) || is_null($_GET['user_id']) )
  {
      echo 'session_id or user_id was not passed';
  }
  else
  {

// grab the variable
      $sessionid = $_GET['session_id'];
      $userid = $_GET['user_id'];

      $pushids = array();

      //sanity check the variable
      if ( !is_numeric($sessionid) )
      {
          echo 'There is unexpected error';
      }
      else
      {
          // check no of active players of this game
          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");

          //check if the game already exists
          $query = "select * from blackjack_game_data where session_id = $sessionid";
          $result = mysqli_query($dbc, $query);

          if ( mysqli_num_rows($result) == 0 )
          {
              // this is first player of this game
              $query2 = "insert into blackjack_game_data(session_id) values ($sessionid)";
              mysqli_query($dbc, $query2);

              $query4 = "insert into blackjack_bets( user_id, session_id) values( $userid, $sessionid)";
              mysqli_query($dbc, $query4);

              // create a dealer account
              $query5 = "insert into blackjack_bets(user_id, session_id) values (0, $sessionid)";
              mysqli_query($dbc, $query5);

              setlatestmessage($sessionid, 0, 'Welcome', 0, $dbc);
          }
          else
          {
              //check if this player is already a part of the game
              $query9 = "select * from blackjack_bets where session_id = $sessionid and user_id = $userid";
              $result9 = mysqli_query($dbc, $query9);

              if ( mysqli_num_rows($result9) == 0 )
              {

                  // also make an entry in bets table
                  $query5 = "insert into blackjack_bets( user_id, session_id) values( $userid, $sessionid)";
                  mysqli_query($dbc, $query5);

                  $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                  $query6 = "update blackjack_bets set datetime = '$date2' where session_id = $sessionid and user_id = 0";
                  mysqli_query($dbc, $query6);
              }
          }

          setlatestmessage($sessionid, $userid, 'Start Playing Game', 1, $dbc);

          // update this player status as online
          $query3 = "update games_players set status = '1' where session_id = $sessionid and user_id = $userid";
          if ( mysqli_query($dbc, $query3) )
              echo '<status>1</status>';
          else
              echo '<status>0</status>';


          //send push to others 
          $queryn1 = "select user_id from blackjack_bets where session_id =  $sessionid and user_id not in ($userid,0)";
          $result1 = mysqli_query($dbc, $queryn1);
          if ( mysqli_num_rows($result1) > 0 )
          {
              while ( $row1 = mysqli_fetch_array($result1) )
              {
                  array_push($pushids, $row1[0]);
              }
          }

          if ( $pushids > 0 )
          {
              $passphrase = 'abcd';
              $ctx = stream_context_create();
              stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
              stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

              $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

              if ( !$fp )
                  exit("Failed to connect: $err $errstr" . PHP_EOL);
              for ( $i = 0; $i < count($pushids); $i++ )
              {

                  $body['aps'] = array(
                      'alert' => fetchname($userid, $dbc) . ' Starts playing BlackJack',
                      'sound' => '3'
                  );

                  $payload = json_encode($body);

                  $devicetoken = fetchdevicetoken($pushids[$i], $dbc);

                  $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                  fwrite($fp, $msg, strlen($msg));
              }
              fclose($fp);
          }


          mysqli_close($dbc);
      }
  }
?>
