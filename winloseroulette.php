<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

// check if required fields were passed in url
  if ( is_null($_GET['session_id']) )
  {
      echo 'session_id was not passed';
  }
  else
  {
      $sessionid = $_GET['session_id'];
      $pushids = array();

      // check if a number has been set as winning number
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");

      $query = "select cases, amount, user_id 
                from roulette_bets 
                where 
                roulette_bets.session_id = $sessionid 
                and 
                roulette_bets.amount != 0 
                and 
                roulette_bets.cases !='' ";

      $result = mysqli_query($dbc, $query);

      $winningnumber = "";

      if ( mysqli_num_rows($result) > 0 )
      {

          // create a random number and insert it in database
          $randomnumber = (string) rand(0, 37);
          if ( $randomnumber == 37 )
              $randomnumber == "00";

          // set this as winning number
          $winningnumber = $randomnumber;

          // insert it into database
          $query2 = "update roulette_game_data set winning_number = '$randomnumber' where session_id = $sessionid";
          mysqli_query($dbc, $query2);


          while ( $row = mysqli_fetch_array($result) )
          {

              $selectedcases = $row[0];
              $amountbet = $row[1];
              $userid = $row[2];
              $playerwinsorloses = 0;

              //break down the multiple bets
              $casessplitted = explode(':', $selectedcases);
              $amountsplitted = explode(':', $amountbet);

              for ( $i = 0; $i < count($casessplitted); $i++ )
              {
                  $individualselectedcase = $casessplitted[$i];

                  // check how many numbers are bet
                  $selectedcasesarray = explode(',', $individualselectedcase);

                  // pass parameters to a function to check winnings
                  $wonornot = checkrouletteresult($selectedcasesarray, $winningnumber);

                  if ( $wonornot )
                  {

                      // the person has won, so check how much to increment to the player
                      $multiple = getroulettemultiplier($selectedcasesarray, $winningnumber);

                      $amounttoincrease = $multiple * convertChips($amountsplitted[$i]);

                      increasedecreasechips($userid, $amounttoincrease, 1, $dbc);

                      $playerwinsorloses += $amounttoincrease;
                  }
                  else
                  {

                      $amounttodecrease = - convertChips($amountsplitted[$i]);

                      increasedecreasechips($userid, $amounttodecrease, 1, $dbc);

                      $playerwinsorloses += $amounttodecrease;
                  }
              }

              // here we have individual users wins or loses
              if ( $playerwinsorloses == 0 )
              {
                  $message = "No Win/Lose";
              }
              elseif ( $playerwinsorloses > 0 )
              {
                  $message = '+ ' . convertBackChips($playerwinsorloses);
              }
              else
              {
                  $message = '- ' . convertBackChips(-$playerwinsorloses);
              }

              // set latest message 
              setlatestmessage($sessionid, $userid, $message, 1, $dbc);

              $pushids[] = array($userid, $playerwinsorloses);
          }

          // also set the time of session to new time
          // initialte the session again
          $timezone = "Asia/Calcutta";

          if ( function_exists('date_default_timezone_set') )
              date_default_timezone_set($timezone);

          $date3 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));

          $query7 = "update roulette_game_data set datetime = '$date3', status=0 where session_id = $sessionid";

          mysqli_query($dbc, $query7);

          $query6 = "update roulette_bets set amount=0, cases = '' where session_id = $sessionid";
          mysqli_query($dbc, $query6);

          // send the pushes
          // check if there are items in array
          if ( count($pushids) > 0 )
          {

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

              for ( $i = 0; $i < count($pushids); $i++ )
              {
                  if ( $pushids[$i][1] == 0 )
                  {
                      $body['aps'] = array(
                          'alert' => 'You Won and Loses were equal in Roulette.',
                          'sound' => '3'
                      );
                  }
                  elseif ( $pushids[$i][1] > 0 )
                  {
                      $body['aps'] = array(
                          'alert' => 'You Won ' . convertBackChips($pushids[$i][1]) . ' in Roulette.',
                          'sound' => '3'
                      );
                  }
                  else
                  {
                      $body['aps'] = array(
                          'alert' => 'You Lost ' . convertBackChips(- $pushids[$i][1]) . ' in Roulette.',
                          'sound' => '3'
                      );
                  }
                  // Encode the payload as JSON
                  $payload = json_encode($body);

                  // fetch device token of all players
                  $devicetoken = fetchdevicetoken($pushids[$i][0], $dbc);

                  // Build the binary notification
                  $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                  // Send it to the server
                  fwrite($fp, $msg, strlen($msg));
              }
              // Close the connection to the server
              fclose($fp);
          }
      }
      else
      {
          // game has ended , so dont know what to do
      }

      mysqli_close($dbc);
  }
?>
