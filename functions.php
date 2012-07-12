<?php

  require_once 'variables/dbconnectionvariables.php';

//****************************************************************************************

  function setmessage($message, $receiversid, $handlerid, $messagetype = 3, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      $query = "insert into messages(message, message_type, user_id, handler_id) values ('$message', $messagetype, $receiversid, $handlerid)";
      mysqli_query($dbc, $query);
      if ( mysqli_affected_rows($dbc) == 1 )
      {
          if ( $toclose )
              mysqli_close($dbc);
          return TRUE;
      } else
      {
          if ( $toclose )
              mysqli_close($dbc);
          return FALSE;
      }
  }

//****************************************************************************************
//
//****************************************************************************************
  function setchips($userid, $chips, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      $query = "update user_cash set chips = $chips where user_id = $userid";
      mysqli_query($dbc, $query);

      if ( mysqli_affected_rows($dbc) == 1 )
      {
          if ( $toclose )
              mysqli_close($dbc);
          return TRUE;
      } else
      {
          if ( $toclose )
              mysqli_close($dbc);
          return FALSE;
      }
  }

// **************************************************************************************
// 
// ***********************************************************************************
  function getchips($userid, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      $noofchips = NULL;

      $query = "select chips from user_cash where user_id = $userid";
      $result = mysqli_query($dbc, $query);
      if ( mysqli_num_rows($result) != 0 )
      {
          while ( $row = mysqli_fetch_array($result) )
          {
              $noofchips = $row[0];
          }
      }
      if ( $toclose )
          mysqli_close($dbc);
      return $noofchips;
  }

// ****************************************************************************************
// 
//
//****************************************************************************************
  function fetchdevicetoken($messageto, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      $query6 = "select status, device_token from current_login_status where user_id = $messageto";
      $result6 = mysqli_query($dbc, $query6);

      $receiverisonline = FALSE;
      $devicetokenofreceiver = NULL;

      if ( mysqli_num_rows($result6) != 0 )
      {
          while ( $row6 = mysqli_fetch_array($result6) )
          {
              if ( $row6[0] == 1 )
              {
                  $receiverisonline = TRUE;
                  $devicetokenofreceiver = $row6[1];
                  break;
              }
          }
      }

      // if receiveer is online, get current device token, otherwise main device token

      if ( is_null($devicetokenofreceiver) )
      {

          $query7 = "select devicetoken from user_details where user_id = $messageto";
          $result7 = mysqli_query($dbc, $query7);
          if ( mysqli_num_rows($result7) != 0 )
          {
              while ( $row7 = mysqli_fetch_array($result7) )
              {
                  $devicetokenofreceiver = $row7[0];
              }
          }
      }
      if ( $toclose )
          mysqli_close($dbc);

      return $devicetokenofreceiver;
  }

////****************************************************************************************
//
//
//--------------------------------------------------------------------------------------
//
//****************************************************************************************
  function fetchgametype($sessionid, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      $query = "select game_type from table_gamesessions where session_id = " . $sessionid;

      $result = mysqli_query($dbc, $query);

      $gametype = "";
      if ( mysqli_num_rows($result) == 1 )
      {
          while ( $row = mysqli_fetch_array($result) )
          {
              $gametype = $row[0];
          }
      }
      if ( $toclose )
          mysqli_close($dbc);

      return $gametype;
  }

//********************************************************************************
//
//****************************************************************************************
  function fetchname($messagefrom, $dbc = FALSE)
  {

      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");

          $toclose = TRUE;
      }

      $query5 = "select name from user_details where user_id = $messagefrom";
      $result5 = mysqli_query($dbc, $query5);
      $nameinpush = NULL;
      if ( mysqli_num_rows($result5) == 1 )
      {
          while ( $row5 = mysqli_fetch_array($result5) )
          {
              $nameinpush = $row5[0];
          }
      }
      else
      {
          $nameinpush = "A friend : ";
      }
      if ( $toclose )
          mysqli_close($dbc);
      return $nameinpush;
  }

//****************************************************************************************
//
//
//****************************************************************************************
  function setlatestmessage($sessionid, $userid, $message, $status = 0, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      // there was no row, so just add a row
      $query2 = "insert into game_messages(session_id, user_id, message, status) values ( $sessionid, $userid, '$message', $status)";
      mysqli_query($dbc, $query2);

      // if a row was affected, close connection and send true
      if ( mysqli_affected_rows($dbc) == 1 )
      {
          if ( $toclose )
              mysqli_close($dbc);
          return TRUE;
      }
      if ( $toclose )
          mysqli_close($dbc);
      return FALSE;
  }

//
//****************************************************************************************
//
//****************************************************************************************

  function increasedecreasechips($userid, $chips, $status, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      // fetch no of chips of this user
      $alreadyhavechips = getchips($userid);

      // check if we have got the chips
      if ( is_null($alreadyhavechips) )
      {
          if ( $toclose )
              mysqli_close($dbc);
          return FALSE;
      } else
      {
          // get new chips count
          if ( $status == 1 )
          {
              $newchipscount = $alreadyhavechips + $chips;
          }
          else
          {
              $newchipscount = $alreadyhavechips - $chips;
          }
          // set this as the no of chips of this user
          $done = setchips($userid, $newchipscount);

          // check if it is done successfully
          if ( $done )
          {

              if ( $toclose )
                  mysqli_close($dbc);

              return TRUE;
          }
          else
          {

              if ( $toclose )
                  mysqli_close($dbc);

              return FALSE;
          }
      }
  }

//
//****************************************************************************************
//
//****************************************************************************************

  function format_cash($cash)
  {

      // strip any commas 
      $cash = (0 + STR_REPLACE(',', '', $cash));

      // make sure it's a number...
      IF ( !IS_NUMERIC($cash) )
      {
          RETURN FALSE;
      }

      // filter and format it 
      IF ( $cash > 1000000000000 )
      {
          RETURN ROUND(($cash / 1000000000000), 1) . ' T';
      }
      ELSEIF ( $cash > 1000000000 )
      {
          RETURN ROUND(($cash / 1000000000), 1) . ' B';
      }
      ELSEIF ( $cash > 1000000 )
      {
          RETURN ROUND(($cash / 1000000), 1) . ' M';
      }
      ELSEIF ( $cash > 1000 )
      {
          RETURN ROUND(($cash / 1000), 1) . ' K';
      }

      RETURN NUMBER_FORMAT($cash);
  }

//****************************************************************************************
  function setlatestmessageid($sessionid, $userid, $message, $status = 0, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      // there was no row, so just add a row
      $query2 = "insert into game_messages(session_id, user_id, message, status) values ( $sessionid, $userid, '$message', $status)";
      mysqli_query($dbc, $query2);

      // if a row was affected, close connection and send true
      if ( mysqli_affected_rows($dbc) == 1 )
      {
          $id = mysqli_insert_id($dbc);

          if ( $toclose )
              mysqli_close($dbc);

          return $id;
      }

      if ( $toclose )
          mysqli_close($dbc);

      return FALSE;
  }

//
//****************************************************************************************
//
//****************************************************************************************

  function checkrouletteresult($selectedcases, $winningnumber)
  {

// check if the winning number exists in selected cases
      if ( in_array($winningnumber, $selectedcases, TRUE) )
      {

          return TRUE;
      }
      else
      {

          return FALSE;
      }
  }

//
//****************************************************************************************
//
//****************************************************************************************


  function getroulettemultiplier($selectedcasesarray, $winningnumber)
  {

      // we already know that the person is winner
      $multiple = 1;

      // if a single number was bet
      if ( count($selectedcasesarray) == 1 )
      {

          // he has bet single number, put all possible cases in an array,then check for special cases
          $specialcasesarray = array("00", "0", "1", "2", "3");

          // check if the number that has been bet, is a special number
          if ( in_array($winningnumber, $specialcasesarray, TRUE) )
          {

              // pay him six times
              $multiple = 6;
          }
          else
          {

              $multiple = 35;
          }
      }
      elseif ( count($selectedcasesarray) == 2 )
      {

          $multiple = 17;
      }
      elseif ( count($specialcasesarray) == 3 )
      {

          $multiple = 11;
      }
      elseif ( count($specialcasesarray) == 4 )
      {

          $multiple = 8;
      }
      elseif ( count($specialcasesarray) == 6 )
      {

          $multiple = 5;
      }
      elseif ( count($specialcasesarray) == 12 )
      {

          $multiple = 2;
      }
      elseif ( count($specialcasesarray) == 18 )
      {

          $multiple = 1;
      }
      return $multiple;
  }

//
//****************************************************************************************
//
//****************************************************************************************


  function rouletteGameSeconds($sessionid, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      // set time one first
      setTimeZone();

      $query3 = "select datetime from roulette_game_data where session_id = $sessionid";
      $result3 = mysqli_query($dbc, $query3);

      if ( mysqli_num_rows($result3) != 0 )
      {
          $row3 = mysqli_fetch_row($result3);
          $time = strtotime($row3[0]) - time();

          // this is the time remaining for roulette 
          if ( $time > 0 )
          {
              if ( $toclose )
                  mysqli_close($dbc);
              return $time;
          }
          else
          {
              // initialte the session again

              $date3 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+47 seconds'));
              $query7 = "update roulette_game_data set datetime = '$date3' where session_id = $sessionid";
              mysqli_query($dbc, $query7);

              if ( mysqli_affected_rows($dbc) == 1 )
              {
                  if ( $toclose )
                      mysqli_close($dbc);
                  return 45;
              } else
              {
                  if ( $toclose )
                      mysqli_close($dbc);
                  return FALSE;
              }
          }
      } else
      {
          if ( $toclose )
              mysqli_close($dbc);
          return FALSE;
      }
  }

//
//****************************************************************************************
//
//****************************************************************************************


  function setTimeZone()
  {

      $timezone = "Asia/Calcutta";
      if ( function_exists('date_default_timezone_set') )
          date_default_timezone_set($timezone);
  }

//
//****************************************************************************************
//
//****************************************************************************************

  function currentRouletteGameStatus($sessionid, $dbc = FALSE)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      $query = "select status from roulette_game_data where session_id = $sessionid";
      $result = mysqli_query($dbc, $query);

      if ( mysqli_num_rows($result) > 0 )
      {
          $row = mysqli_fetch_row($result);
          if ( $toclose )
              mysqli_close($dbc);
          return $row[0];
      }
      else
      {
          if ( $toclose )
              mysqli_close($dbc);
          return FALSE;
      }
  }

//
//****************************************************************************************
//
//****************************************************************************************


  function checksomeonebetrouletteornot($sessionid, $dbc)
  {
      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      $query = "select * from roulette_bets where session_id = $sessionid and cases is not null and amount != 0";
      $result = mysqli_query($dbc, $query);

      if ( mysqli_num_rows($result) == 0 )
      {
          // no one has bet, retuen false
          if ( $toclose )
              mysqli_close($dbc);
          return FALSE;
      }
      else
      {
          if ( $toclose )
              mysqli_close($dbc);
          return TRUE;
      }
  }

//****************************************************************************************
//
//****************************************************************************************

  function convertChips($chips)
  {
      // check if M exists
      $result = strpos($chips, 'M');

      if ( $result === FALSE )
      {
          // check if it is a K
          $result2 = strpos($chips, 'K');
          if ( $result2 === FALSE )
          {
              // check if it is a B
              $result3 = strpos($chips, 'B');

              if ( $result3 === FALSE )
              {
                  return $chips;
              }
              else
              {
                  // create a substring, and return
                  $returnable = (substr($chips, 0, strlen($chips) - 1) * 1000000000);
                  return $returnable;
              }
          }
          else
          {
              // create a substring, and return
              $returnable = (substr($chips, 0, strlen($chips) - 1) * 1000);
              return $returnable;
          }
      }
      else
      {
          // create a substring, and return
          $returnable = (substr($chips, 0, strlen($chips) - 1) * 1000000);
          return $returnable;
      }
  }

//****************************************************************************************
//
//****************************************************************************************

  function convertBackChips($chips)
  {
      if ( $chips < 0 )
      {
          $chips = - $chips;
      }
      if ( $chips >= 1000000000 )
      {
          RETURN ROUND(($chips / 1000000000), 0) . 'B';
      }
      elseif ( $chips >= 1000000 )
      {
          RETURN ROUND(($chips / 1000000), 0) . 'M';
      }
      elseif ( $chips >= 1000 )
      {
          RETURN ROUND(($chips / 1000), 0) . 'K';
      }
      else
      {
          return $chips;
      }
  }

//****************************************************************************************
//
//****************************************************************************************

  function getnewcard($alreadygivencards)
  {
      //generate  a random number
      $card = (string) rand(1, 52);
      if ( in_array($card, $alreadygivencards, TRUE) )
      {
          return getnewcard($alreadygivencards);
      }
      elseif ( is_null($card) )
      {
          return getnewcard($alreadygivencards);
      }
      else
          return $card;
  }

  function givecardstoall($dbc, $sessionid)
  {

      $toclose = FALSE;

      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }
      $alreadygivencards = array();

      // check how many active users are there 
      $query12 = "select * from blackjack_bets where session_id = $sessionid ";
      $result12 = mysqli_query($dbc, $query12);

      if ( mysqli_num_rows($result12) >= 0 )
      {
          // for each user generate two cards
          while ( $row12 = mysqli_fetch_array($result12) )
          {
              $thisuserid = $row12['user_id'];

              // generate a random number between 1 and 52
              $card1 = getnewcard($alreadygivencards);
              array_push($alreadygivencards, $card1);

              $card2 = getnewcard($alreadygivencards);
              array_push($alreadygivencards, $card2);

              //set these as cards for this user
              $timenow = date('Y-m-d  H:i:s');
              $query13 = "update blackjack_bets set cards = '$card1,$card2', datetime = '$timenow', player_status = '0', amount='1000' where session_id = $sessionid and user_id = $thisuserid";
              mysqli_query($dbc, $query13);
          }

          //also set the game status as running, and time +30 seconds
          $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));
          $querym2 = "update blackjack_game_data set status = 1, datetime = '$date2' where session_id = $sessionid";
          if ( mysqli_query($dbc, $querym2) )
          {
              if ( $toclose )
                  mysqli_close($dbc);
              return TRUE;
          }
          else
          {
              if ( $toclose )
                  mysqli_close($dbc);
              return FALSE;
          }
      }
  }

//****************************************************************************************
//
//****************************************************************************************

  function getcardvalue($card)
  {
      if ( $card < 11 )
          return $card;
      elseif ( $card < 14 )
          return 10;
      elseif ( $card < 24 )
          return $card - 13;
      elseif ( $card < 27 )
          return 10;
      elseif ( $card < 37 )
          return $card - 26;
      elseif ( $card < 40 )
          return 10;
      elseif ( $card < 50 )
          return $card - 39;
      elseif ( $card < 53 )
          return 10;
      {
          
      }
  }

//****************************************************************************************
//
//****************************************************************************************


  function doblackjackcalculations($sessionid, $dbc, $delay = FALSE)
  {

      $toclose = FALSE;
      if ( !$dbc )
      {

          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");
          $toclose = TRUE;
      }

      $alreadygivencards = array();

      $query10 = "select cards from blackjack_bets where session_id = $sessionid";
      $result10 = mysqli_query($dbc, $query10);

      if ( mysqli_num_rows($result10) > 0 )
      {
          while ( $row10 = mysqli_fetch_array($result10) )
          {
              if ( !is_null($row10[0]) )
              {
                  $cardsexploded = (string) explode(',', $row10[0]);
                  $alreadygivencards = array_merge(array_unique(array_merge($alreadygivencards, $cardsexploded)));
              }
          }
      }

      //now give two cards to the dealer
      $querym1 = "select cards from blackjack_bets where session_id = $sessionid and user_id = 0";
      $resultm1 = mysqli_query($dbc, $querym1);

      if ( mysqli_num_rows($resultm1) > 0 )
      {
          $rowm1 = mysqli_fetch_row($resultm1);
          $dealercards = $rowm1[0];
          $dealercardexploded = explode(',', $dealercards);

          $dealercard1 = $dealercardexploded[0];
          $dealercard2 = $dealercardexploded[1];

          $valdealercard1 = getcardvalue($dealercard1);
          $valdealercard2 = getcardvalue($dealercard2);

          if ( ($valdealercard1 == 1 ) )
              $valdealercard1 = 11;
          elseif ( $valdealercard2 == 1 )
              $valdealercard2 = 11;

          $dealertotal = $valdealercard1 + $valdealercard2;

          while ( $dealertotal > 17 )
          {

              $dealercard3 = getnewcard($alreadygivencards);
              array_push($alreadygivencards, $dealercard3);

              $valdealercard3 = getcardvalue($dealercard3);

              setlatestmessage($sessionid, 0, 'Hit ', 0, $dbc);
              $newdealercards .= ',' . $dealercard3;

              $queryu = "update blackjack_bets set cards = '$newdealercards' where session_id = $sessionid and user_id = 0";
              mysqli_query($dbc, $queryu);

              // send push to all players 
              $queryn1 = "select user_id from blackjack_bets where user_id !=0 and session_id = $sessionid";
              $resultn1 = mysqli_query($dbc, $queryn1);

              $allpushids = array();
              if ( mysqli_num_rows($resultn1) > 0 )
              {
                  while ( $rown1 = mysqli_fetch_array($resultn1) )
                  {
                      array_push($allpushids, $rown1[0]);
                  }
              }

              if ( $allpushids > 0 )
              {

                  $passphrase = 'abcd';
                  $ctx = stream_context_create();
                  stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                  stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

                  $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                  if ( !$fp )
                      exit("Failed to connect: $err $errstr" . PHP_EOL);

                  for ( $i = 0; $i < count($allpushids); $i++ )
                  {

                      $body['aps'] = array(
                          'alert' => 'Dealer hit a card in Blackjack',
                          'sound' => '3'
                      );

                      $payload = json_encode($body);

                      $devicetoken = fetchdevicetoken($allpushids[$i], $dbc);

                      $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                      fwrite($fp, $msg, strlen($msg));
                  }

                  fclose($fp);
              }
              $dealertotal += $valdealercard3;

              if ( $delay )
                  sleep(2);
          }

          // check if dealer has already lost
          if ( $dealertotal > 21 )
          {
              //all players that stood have won
              $dealertotal = 0;
          }
          // fetch details of all other players 

          $query1 = "select user_id, amount, cards from blackjack_bets 
                    where session_id = $sessionid 
                    and 
                    amount != '0' and amount != '0:0' and cards !='' and cards != ':'";

          $result1 = mysqli_query($dbc, $query1);

          $pushids = array();

          if ( mysqli_num_rows($result1) > 0 )
          {
              while ( $row1 = mysqli_fetch_array($result1) )
              {
                  $playeruserid = $row1[0];
                  $playeramount = $row1[1];
                  $playercards = $row1[2];

                  // check if some person has splitted
                  $playercardssplitted = explode(':', $playercards);
                  $playeramountsplitted = explode(':', $playeramount);

                  $playerwonorlose = 0;
                  for ( $i = 0; $i < count($playeramountsplitted); $i++ )
                  {

                      if ( $playeramountsplitted[$i] != 0 )
                      {
                          $totalofcards = 0;
                          $cardsofthisuser = explode(',', $playercardssplitted[$i]);
                          for ( $j = 0; $j < count($cardsofthisuser); $j++ )
                          {
                              $cardvalue11set = FALSE;
                              if ( getcardvalue($cardsofthisuser[$j]) == 1 )
                              {
                                  if ( !$cardvalue11set )
                                  {
                                      $totalofcards += 11;
                                      $cardvalue11set = TRUE;
                                  }
                                  else
                                      $totalofcards +=1;
                              }
                              else
                              {
                                  $totalofcards += getcardvalue($cardsofthisuser[$j]);
                              }
                          }
                          if ( $totalofcards > $dealertotal )
                          {
                              $playerwonorlose += convertChips($playeramountsplitted[$i]);
                          }
                          elseif ( $totalofcards < $dealertotal )
                          {
                              $playerwonorlose -= convertChips($playeramountsplitted[$i]);
                          }
                      }
                  }
                  //check how much this player has won or lose 
                  if ( $playerwonorlose > 0 )
                  {
                      $message = "+ " . convertBackChips($playerwonorlose);
                      setlatestmessage($sessionid, $playeruserid, $message, 1, $dbc);

                      $pushids[] = array($playerwonorlose, $playeruserid);
                  }
                  if ( $playerwonorlose < 0 )
                  {
                      $message = "- " . convertBackChips(- $playerwonorlose);
                      setlatestmessage($sessionid, $playeruserid, $message, 1, $dbc);

                      $pushids[] = array($playerwonorlose, $playeruserid);
                  }
                  if ( $playerwonorlose == 0 )
                  {
                      $message = "Tie";
                      setlatestmessage($sessionid, $playeruserid, $message, 1, $dbc);

                      $pushids[] = array($playerwonorlose, $playeruserid);
                  }
              }
          }

          // check if there are items in array
          if ( count($pushids) > 0 )
          {
              $passphrase = 'abcd';
              $ctx = stream_context_create();
              stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
              stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

              $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

              if ( !$fp )
                  exit("Failed to connect: $err $errstr" . PHP_EOL);

              for ( $i = 0; $i < count($pushids); $i++ )
              {

                  if ( $pushids[$i][0] > 0 )
                  {
                      $body['aps'] = array(
                          'alert' => 'You won ' . convertBackChips($pushids[$i][0]) . ' chips in BlackJack',
                          'sound' => '3'
                      );
                  }
                  else if ( $pushids[$i][0] < 0 )
                  {

                      $body['aps'] = array(
                          'alert' => 'You lose ' . convertBackChips(- $pushids[$i][0]) . ' chips in BlackJack',
                          'sound' => '3'
                      );
                  }
                  else
                  {

                      $body['aps'] = array(
                          'alert' => 'You won/lose nothing in BlackJack',
                          'sound' => '3'
                      );
                  }

                  $payload = json_encode($body);

                  $devicetoken = fetchdevicetoken($pushids[$i][1], $dbc);

                  $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

                  fwrite($fp, $msg, strlen($msg));
              }

              fclose($fp);

              if ( $toclose )
                  mysqli_close($dbc);
              return TRUE;
          }
          else
          {
              return FALSE;
          }
      }
  }

//****************************************************************************************
//
//****************************************************************************************

  function sendthepush($message, $userid, $dbc)
  {

      $passphrase = 'abcd';
      $ctx = stream_context_create();
      stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
      stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

      $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

      if ( !$fp )
          exit("Failed to connect: $err $errstr" . PHP_EOL);


      $body['aps'] = array(
          'alert' => fetchname($userid) . ' ' . $message . 's in Blackjack Game',
          'sound' => '3'
      );

      $payload = json_encode($body);
   
      $devicetoken = fetchdevicetoken($userid, $dbc);

      $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

      fwrite($fp, $msg, strlen($msg));

      fclose($fp);
      
      $name = "amit";
  }

?>