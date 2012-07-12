<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

// check if requiredd fields were passed in url
  if ( is_null($_GET['session_id']) || is_null($_GET['user_id']) )
  {
      echo 'session_id was not passed';
  }
  else
  {
      setTimeZone();

      // grab the variable
      $sessionid = $_GET['session_id'];
      $userid = $_GET['user_id'];

      //sanity check the variable
      if ( !is_numeric($sessionid) )
      {
          echo 'There is unexpected error';
      }
      else
      {
          $pushids = array();

          // fetch all session ids playing gme in this session id
          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");

          echo '<users>';

          // find first time
          $query12 = "select status from games_players where session_id = $sessionid and user_id = $userid";
          $result12 = mysqli_query($dbc, $query12);

          if ( mysqli_num_rows($result12) != 0 )
          {
              $row12 = mysqli_fetch_row($result12);

              echo '<first_time>';
              echo $row12[0];
              echo '</first_time>';

              // if the user was first time dont provide any information 
              if ( $row12[0] == 0 )
              {
                  echo '</users>';
                  exit();
              }
              else
              {
                  //select rolette status and time first
                  $query10 = "select status, datetime from roulette_game_data where session_id = $sessionid";
                  $result10 = mysqli_query($dbc, $query10);

                  if ( mysqli_num_rows($result10) > 0 )
                  {
                      $row10 = mysqli_fetch_row($result10);
                      $roulettestatus = $row10[0];
                      $roulettetime = $row10[1];

                      $sessionisexpired = FALSE;

                      // check if he session has expired
                      if ( $roulettestatus == 0 )
                      {
                          // check if it is bet time
                          setTimeZone();
                          if ( (strtotime($roulettetime) - time()) > 0 )
                          {
                              // it is the bet time, so return 
                              echo '<rouletteStatus>';
                              echo $roulettestatus;
                              echo '</rouletteStatus>';

                              echo '<seconds>';
                              echo (strtotime($roulettetime) - time());
                              echo '</seconds>';
                          }
                          elseif ( (strtotime($roulettetime) - time() + 15 ) > 0 )
                          {
                              // this is the rotate time
                              // form a new date
                              // only set status as 1, if someone has bet
                              $query99 = "select 
                        *
                        from 
                            roulette_bets 
                            where
                                roulette_bets.session_id = $sessionid 
                              and 
                                roulette_bets.amount != 0 
                              and  
                                roulette_bets.cases !='' ";
                              $result99 = mysqli_query($dbc, $query99);

                              if ( mysqli_num_rows($result99) > 0 )
                              {

                                  $newdate = date('Y-m-d  H:i:s', strtotime($roulettetime . '+15 seconds'));

                                  // update rowlette status and time
                                  $query11 = "update roulette_game_data set status = 1, datetime = '$newdate' where session_id = $sessionid";
                                  mysqli_query($dbc, $query11);

                                  // now return 
                                  // it is the bet time, so return 
                                  echo '<rouletteStatus>';
                                  echo 1;
                                  echo '</rouletteStatus>';

                                  echo '<seconds>';
                                  echo (strtotime($roulettetime) - time() + 15 );
                                  echo '</seconds>';
                              }
                              else
                              {

                                  $newdate = date('Y-m-d  H:i:s', strtotime($roulettetime . '+30 seconds'));

                                  // update rowlette status and time
                                  $query11 = "update roulette_game_data set status = 0, datetime = '$newdate' where session_id = $sessionid";
                                  mysqli_query($dbc, $query11);

                                  // it is the bet time, so return 
                                  echo '<rouletteStatus>';
                                  echo 0;
                                  echo '</rouletteStatus>';

                                  echo '<seconds>';
                                  if ( ( strtotime($roulettetime) - time() ) < 0 )
                                  {
                                      echo 30;
                                  }
                                  else
                                  {
                                      echo (strtotime($roulettetime) - time());
                                  }
                                  echo '</seconds>';
                              }
                          }
                          else
                          {
                              // session is expired, check any pending results
                              $sessionisexpired = TRUE;
                          }
                      }
                      elseif ( $roulettestatus == 1 )
                      {
                          // roulette is rotating now 
                          setTimeZone();
                          if ( (strtotime($roulettetime) - time()) > 0 )
                          {
                              // now return 
                              // it is the bet time, so return 
                              echo '<rouletteStatus>';
                              echo 1;
                              echo '</rouletteStatus>';

                              echo '<seconds>';
                              echo (strtotime($roulettetime) - time());
                              echo '</seconds>';
                          }
                          else
                          {
                              //session has expired, check any pending results
                              $sessionisexpired = TRUE;
                          }
                      }

                      if ( $sessionisexpired )
                      {
                          //****************  if the session was expired, generate new session *********************************************

                          $newdate = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));

                          // update rowlette status and time
                          $query11 = "update roulette_game_data set status = 0, datetime = '$newdate' where session_id = $sessionid";
                          mysqli_query($dbc, $query11);

                          // now return 
                          // it is the bet time, so return 
                          echo '<rouletteStatus>';
                          echo 0;
                          echo '</rouletteStatus>';

                          echo '<seconds>';
                          echo 30;
                          echo '</seconds>';

                          //************************************************************************************************************************
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
                          }

                          //    ================================================================================================================================
                      }
                  }
              }
          }
          //****************************************************************************************************************
          //*******************************************************************************************************************
          //  echo '</randomNumber>';

          $query2 = "select table_type from table_gamesessions where session_id = $sessionid";
          $result2 = mysqli_query($dbc, $query2);

          if ( mysqli_num_rows($result2) != 0 )
          {
              while ( $row2 = mysqli_fetch_array($result2) )
              {
                  echo '<table_type>';
                  echo $row2[0];
                  echo '</table_type>';
              }

              $query = "select user_details.user_id,games_players.status,invitations.status, name, gold, chips, icon_name
                from 
                user_details left join user_cash on user_details.user_id = user_cash.user_id 
                left join user_icon on user_details.user_id = user_icon.user_id 
                left join games_players on user_details.user_id = games_players.user_id 
                    and games_players.session_id = $sessionid
                left join invitations on user_details.user_id = invitations.invitation_to 
                    and invitations.session_id = $sessionid
            
                having user_details.user_id in 
                    (SELECT user_id
                    FROM games_players where session_id = $sessionid
                    union   
                    select invitation_to from invitations 
                    where session_id = $sessionid)
                        order by invitations.datetime, games_players.datetime  
            ";

              $result = mysqli_query($dbc, $query);



              if ( mysqli_num_rows($result) != 0 )
              {
                  while ( $row = mysqli_fetch_array($result) )
                  {
                      echo '<user>';

                      echo '<user_id>';
                      echo $row[0];
                      echo '</user_id>';

                      if ( is_null($row[1]) && !is_null($row[2]) )
                      {

                          // it means just an invitation is sent to this user
                          echo '<status>';
                          echo '1';
                          echo '</status>';
                      }
                      elseif ( !is_null($row[1]) && is_null($row[2]) )
                      {
                          echo '<status>';
                          echo '3';
                          echo '</status>';
                      }

                      echo '<name>';
                      echo $row['name'];
                      echo '</name>';

                      echo '<gift>';

// fetch the oldest gift sent to this user only if this is user who has called the page
                      if ( $row[0] == $userid )
                      {

                          $timezone = "Asia/Calcutta";
                          if ( function_exists('date_default_timezone_set') )
                              date_default_timezone_set($timezone);

                          $currdate = date('Y-m-d  H:i:s');
                          $newdate = date('Y-m-d  H:i:s', strtotime($currdate . '+0 seconds'));

                          $query6 = "select * from gift_box where sent_to = $row[0] and status = 1 and datetime > '$newdate'";
                          $result6 = mysqli_query($dbc, $query6);

                          if ( mysqli_num_rows($result6) > 0 )
                          {
                              
                          }
                          else
                          {

                              // fetch the oldest 1 unread gift
                              $query7 = "select * from gift_box where status = 0 and session_id = $sessionid and sent_to = $userid order by datetime asc limit 1";
                              $result7 = mysqli_query($dbc, $query7);

                              if ( mysqli_num_rows($result7) == 1 )
                              {

                                  // if a row was returned, set its status as read, increase the time to 20 seconds, and set latest message and send push to all
                                  $row7 = mysqli_fetch_row($result7);
                                  $giftid = $row7[0];
                                  $giftname = $row7[4];
                                  $giftsentby = $row7[1];

                                  // now update time and status of this row
                                  $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+70 seconds'));

                                  $query8 = "update gift_box set datetime = '$date2', status = 1 where id = $giftid";
                                  $result8 = mysqli_query($dbc, $query8);

                                  // it the row was updated successfully
                                  if ( $result8 )
                                  {

                                      echo '<gift_name>';
                                      echo $giftname;
                                      echo '</gift_name>';
                                  }

                                  // set latest message of this user
                                  $sendersname = fetchname($giftsentby, $dbc);
                                  $message = "Gift from " . $sendersname;

                                  setlatestmessage($sessionid, $userid, $message, 1, $dbc);

                                  //find all players of this game and set message for all other players of this game
                                  $query9 = "select * from games_players where session_id = $sessionid and user_id not in ($userid, $giftsentby)";
                                  $result9 = mysqli_query($dbc, $query9);

                                  array_push($pushids, $giftsentby);

                                  // form a message
                                  $nameofreceiver = fetchname($userid, $dbc);
                                  $gametype = fetchgametype($sessionid);

                                  $message2 = $nameofreceiver . ' received ' . $giftname . ' as a gift ' . ' from ' . $sendersname . ' in ' . $gametype . ' game.';



                                  if ( mysqli_num_rows($result9) > 0 )
                                  {

                                      while ( $row9 = mysqli_fetch_array($result9) )
                                      {

                                          $otherplayersid = $row9['user_id'];

                                          setmessage($message2, $otherplayersid, $giftid, 3, $dbc);

                                          array_push($pushids, $otherplayersid);
                                      }
                                  }


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

                                          if ( $i == 0 )
                                          {
                                              $body['aps'] = array(
                                                  'alert' => $nameofreceiver . ' received ' . $giftname . ' as a gift from You',
                                                  'sound' => '3'
                                              );
                                          }
                                          else
                                          {
                                              // Create the payload body
                                              $body['aps'] = array(
                                                  'alert' => $message2,
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
                          }
                      }
                      else
                      {

                          // check if this user has any active gift

                          $timezone = "Asia/Calcutta";
                          if ( function_exists('date_default_timezone_set') )
                              date_default_timezone_set($timezone);

                          $currdate = date('Y-m-d  H:i:s');
                          $newdate = date('Y-m-d  H:i:s', strtotime($currdate . '+50 seconds'));

                          $query6 = "select * from gift_box where sent_to = $row[0] and status = 1 and datetime > '$newdate'";
                          $result6 = mysqli_query($dbc, $query6);

                          if ( mysqli_num_rows($result6) > 0 )
                          {

// fetch gift name and remaining time
                              while ( $row6 = mysqli_fetch_array($result6) )
                              {

                                  echo '<gift_name>';
                                  echo $row6['gift_name'];
                                  echo '</gift_name>';
                              }
                          }
                      }

                      echo '</gift>';

                      // fetch latest message of this player
                      $query5 = "select message, status, id from game_messages where session_id = $sessionid and user_id= $row[0] order by datetime desc limit 1";
                      $result5 = mysqli_query($dbc, $query5);

                      $printmessage = NULL;
                      $printmessagetype = NULL;
                      $handlerid = NULL;

                      if ( mysqli_num_rows($result5) > 0 )
                      {
                          while ( $row5 = mysqli_fetch_array($result5) )
                          {
                              $printmessage = $row5['message'];
                              $printmessagetype = $row5[1];
                              $handlerid = $row5['id'];
                          }
                      }
                      echo '<message_type>';
                      echo $printmessagetype;
                      echo '</message_type>';

                      echo '<message>';
                      echo $printmessage;
                      echo '</message>';

                      echo '<gold>';
                      echo $row['gold'];
                      echo '</gold>';

                      echo '<chips>';
                      echo $row['chips'];
                      echo '</chips>';

                      echo '<icon_name>';
                      echo $row['icon_name'];
                      echo '</icon_name>';

                      echo '</user>';

                      // delete a row from message table now
                      $query4 = "delete from messages where handler_id = $handlerid and message_type=3 and user_id = $userid";
                      mysqli_query($dbc, $query4);
                  }
              }
          }
          echo '</users>';
          mysqli_close($dbc);
      }
  }
?>