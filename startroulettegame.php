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

          $query = "select * from roulette_game_data where session_id = $sessionid";

          $result = mysqli_query($dbc, $query);

          // if there was no row
          if ( mysqli_num_rows($result) == 0 )
          {

              // make an entry
              $timezone = "Asia/Calcutta";
              if ( function_exists('date_default_timezone_set') )
                  date_default_timezone_set($timezone);

              $date3 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));

              // also make an entry in roulette_game_data table
              $query3 = "insert into roulette_game_data(session_id, datetime) values($sessionid, '$date3')";

              mysqli_query($dbc, $query3);
          }

          // there was no active players of this game
          // so set its status as active, and return seonds remaining

          $query2 = "update games_players set status = 1 where session_id = $sessionid and user_id = $userid";
          if ( mysqli_query($dbc, $query2) )
              echo '<status>1</status>';
          else
              echo '<status>0</status>';

          // also make an entry in bets table
          $query5 = "insert into roulette_bets( user_id, session_id) values( $userid, $sessionid)";
          mysqli_query($dbc, $query5);

          setlatestmessageid($sessionid, $userid, 'Start Playing Game', 1, $dbc);

          //send push to others 
          $queryn1 = "select user_id from roulette_bets where session_id =  $sessionid and user_id != $userid";
          $result1 = mysqli_query($dbc, $queryn1);
          if ( mysqli_num_rows($result1) > 0 )
          {
              while ( $row1 = mysqli_fetch_array($result1) )
              {
                  array_push($pushids, $row1[0]);
              }
          }

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
                  'alert' => fetchname($userid, $dbc) . ' Starts playing Roulette',
                  'sound' => '3'
              );

              $payload = json_encode($body);

              $devicetoken = fetchdevicetoken($pushids[$i], $dbc);

              $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

              fwrite($fp, $msg, strlen($msg));
          }
          fclose($fp);


          mysqli_close($dbc);
      }
  }
?>
