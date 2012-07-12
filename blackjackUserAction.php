<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';
  require_once 'functions.php';

// check if requiredd fields were passed in url
  if ( is_null($_GET['session_id']) || is_null($_GET['user_id']) || is_null($_GET['action']) )
  {
      echo 'session_id, or user_id or action was not passed';
  }
  else
  {
      // grab the variable
      $sessionid = $_GET['session_id'];
      $userid = $_GET['user_id'];
      $action = $_GET['action'];
      $alreadygivencards = array();

      setTimeZone();

      //sanity check the variable
      if ( !is_numeric($sessionid) )
      {
          echo 'There is unexpected error';
      }
      else
      {
          // fetch all session ids playing gme in this session id
          $dbc = mysqli_connect(host, user, password, database)
                  or die("Error connecting database");

          switch ( $action )
          {
              case 'stand':

                  setlatestmessage($sessionid, $userid, "STAND", 1, $dbc);

                  $query1 = "select player_status from blackjack_bets where session_id = $sessionid and user_id = $userid";
                  $result1 = mysqli_query($dbc, $query1);

                  if ( mysqli_num_rows($result1) > 0 )
                  {
                      $row1 = mysqli_fetch_row($result1);
                      $playerstatus = $row1[0];

                      $playerstatusexploded = explode(':', $playerstatus);
                      for ( $i = 0; $i < count($playerstatusexploded); $i++ )
                      {
                          if ( $playerstatusexploded[$i] == 1 )
                          {
                              $playerstatusexploded[$i] = 2;

                              $newstatus = join(':', $playerstatusexploded);

                              $query2 = "update blackjack_bets set status = '$newstatus' where session_id = $sessionid and user_id = $userid";
                              mysqli_query($dbc, $query2);

                              // are more users available
                              $query3 = "select user_id, player_status from blackjack_bets where player_status like '%0%' and session_id = $sessionid order by datetime limit 1";
                              $result3 = mysqli_query($dbc, $query3);

                              if ( mysqli_num_rows($result3) > 0 )
                              {
                                  $row3 = mysqli_fetch_row($result3);

                                  //renew session
                                  $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                                  $querym2 = "update blackjack_game_data set status = 2, datetime = '$date2' where session_id = $sessionid";
                                  mysqli_query($dbc, $querym2);

                                  $thisplayerstatus = $row3[1];
                                  //set status of this userid as 1, and update time of gamedaa

                                  $thisplayerstatusexploded = explode(':', $thisplayerstatus);

                                  for ( $i = 0; $i < $thisplayerstatusexploded; $i++ )
                                  {
                                      if ( $thisplayerstatusexploded[$i] == 0 )
                                      {
                                          $thisplayerstatusexploded[$i] = 1;

                                          $newstatus = implode(':', $thisplayerstatusexploded);

                                          $query4 = "update blackjack_bets set player_status = '$playerstatus' where user_id = $userid and session_id = $sessionid";
                                          mysqli_query($dbc, $query4);
                                      }
                                  }
                              }
                              else
                              {
                                  // all players had done their job, do calculations here

                                  doblackjackcalculations($sessionid, $dbc);

                                  $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));

                                  $querym2 = "update blackjack_game_data set status = 0, datetime = '$date2' where session_id = $sessionid";
                                  mysqli_query($dbc, $querym2);

                                  $querym6 = "update blackjack_bets set player_status = '0', amount = '0', cards='' where session_id = $sessionid";
                                  mysqli_query($dbc, $querym6);
                              }

                              sendthepush('stand', $userid, $dbc);
                          }
                      }
                  }

                  break;

              case 'split':
                  $query10 = "select cards from blackjack_bets where session_id = $sessionid";
                  $result10 = mysqli_query($dbc, $query10);

                  if ( mysqli_num_rows($result10) > 0 )
                  {
                      while ( $row10 = mysqli_fetch_array($result10) )
                      {
                          if ( !is_null($row10[0]) )
                          {
                              $cardsexploded = explode(',', $row10[0]);
                              $alreadygivencards = array_merge(array_unique(array_merge($alreadygivencards, $cardsexploded)));
                          }
                      }
                  }
                  $query1 = "select * from blackjack_bets where session_id = $sessionid and user_id = $userid";
                  $result1 = mysqli_query($dbc, $query1);

                  if ( mysqli_num_rows($result1) > 0 )
                  {
                      $row1 = mysqli_fetch_row($result1);

                      //check if he has not already splitted
                      $oldstatus = $row1[5];
                      $oldamount = $row1[3];
                      $oldcards = $row1[4];

                      $oldstatusexploded = explode(':', $oldstatus);
                      if ( count($oldstatusexploded) > 1 )
                      {
                          echo '<status>0</status>';
                      }
                      else
                      {
                          $newcard1 = getnewcard($alreadygivencards);
                          array_push($alreadygivencards, $newcard1);

                          $newcard2 = getnewcard($alreadygivencards);
                          array_push($alreadygivencards, $newcard2);

                          // break older comma separated cards
                          $oldcardsexploded = explode(',', $oldcards);

                          $newcards = $oldcardsexploded[0] . ',' . $newcard1 . ':' . $oldcardsexploded[1] . ',' . $newcard2;
                          $newstatus = "1:0";
                          $newamount = $oldamount . ':' . $oldamount;

                          //upload all these things
                          $query2 = "update blackjack_bets set amount = '$newamount', player_status = '$newstatus', cards = '$newcards' where session_id = $sessionid and user_id = $userid";
                          mysqli_query($dbc, $query2);

                          // increase time of game to 20 seconds, 
                          //renew session
                          $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                          $querym2 = "update blackjack_game_data set status = 2, datetime = '$date2' where session_id = $sessionid";
                          mysqli_query($dbc, $querym2);

                          sendthepush('split', $userid, $dbc);
                      }
                  }

                  break;

              case 'hit':
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

                  setlatestmessage($sessionid, $userid, "HIT", 1, $dbc);

                  // give this player a card
                  $newcard = getnewcard($alreadygivencards);



                  $query2 = "select * from blackjack_bets where session_id = $sessionid and user_id = $userid";
                  $result2 = mysqli_query($dbc, $query2);

                  if ( mysqli_num_rows($result2) > 0 )
                  {
                      $row2 = mysqli_fetch_row($result2);
                      $oldcards = $row2[4];
                      $oldstatus = $row2[5];

                      $newcards = $oldcards . ',' . $newcard;
                      $query4 = "update blackjack_bets set cards = '$newcards' where user_id = $userid and session_id = $sessionid";
                      mysqli_query($dbc, $query4);
                  }
                  sendthepush('hit', $userid, $dbc);
                  break;

              case 'double':
                  $query1 = "select amount, player_status from blackjack_bets where user_id = $userid and session_id = $sessionid";
                  $result1 = mysqli_query($dbc, $query1);

                  if ( mysqli_num_rows($result1) > 0 )
                  {
                      $row1 = mysqli_fetch_row($result1);

                      $amount = $row1[0];
                      $status = $row1[1];

                      $statusexploded = explode(':', $status);
                      $amountexploded = explode(':', $amount);

                      for ( $i = 0; $i < count($statusexploded); $i++ )
                      {
                          if ( $statusexploded[$i] == 1 )
                          {
                              $amountexploded[$i]*= 2;
                              $statusexploded[$i] = '2';

                              $newamount = implode(':', $amountexploded);
                              $newstatus = implode(':', $statusexploded);

                              $query2 = "update blackjack_bets set player_status = '$newstatus', amount = '$newamount' where user_id = $userid and session_id = $sessionid";
                              mysqli_query($dbc, $query2);

                              // find next player to activate
                              $query3 = "select user_id, player_status from blackjack_bets where player_status like '%0%' and session_id = $sessionid order by datetime limit 1";
                              $result3 = mysqli_query($dbc, $query3);

                              if ( mysqli_num_rows($result3) > 0 )
                              {
                                  $row3 = mysqli_fetch_row($result3);

                                  echo '<user_id>';
                                  echo $row3[0];
                                  echo '</user_id>';

                                  //renew session
                                  $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                                  $querym2 = "update blackjack_game_data set status = 2, datetime = '$date2' where session_id = $sessionid";
                                  mysqli_query($dbc, $querym2);

                                  $thisplayerstatus = $row3[1];
                                  //set status of this userid as 1, and update time of gamedaa

                                  $thisplayerstatusexploded = explode(':', $thisplayerstatus);

                                  for ( $i = 0; $i < $thisplayerstatusexploded; $i++ )
                                  {
                                      if ( $thisplayerstatusexploded[$i] == 0 )
                                      {
                                          $thisplayerstatusexploded[$i] = 1;

                                          $newstatus = implode(':', $thisplayerstatusexploded);

                                          $query4 = "update blackjack_bets set player_status = '$playerstatus' where user_id = $userid and session_id = $sessionid";
                                          mysqli_query($dbc, $query4);
                                      }
                                  }
                              }
                              else
                              {
                                  // all players had done their job, do calculations here
                                  // also set blackjack status to zero

                                  doblackjackcalculations($sessionid, $dbc);

                                  $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));

                                  $querym2 = "update blackjack_game_data set status = 0, datetime = '$date2' where session_id = $sessionid";
                                  mysqli_query($dbc, $querym2);

                                  $querym6 = "update blackjack_bets set player_status = '0', amount = '0', cards='' where session_id = $sessionid";
                                  mysqli_query($dbc, $querym6);
                              }
                          }
                      }
                      sendthepush('double', $userid, $dbc);
                  }
                  break;

              case 'surrender':

                  // deduct half of the chips he has bet
                  $query1 = "select * from blackjack_bets where user_id = $userid and session_id = $sessionid";
                  $result1 = mysqli_query($dbc, $query1);

                  if ( mysqli_num_rows($result1) > 0 )
                  {
                      $row1 = mysqli_fetch_row($result1);
                      $amount = $row1[3];
                      $status = $row1[5];
                      $cards = $row1[4];

                      $statusexploded = explode(':', $amount);
                      $amountexploded = explode(':', $newcards);
                      $cardsexploded = explode(':', $cards);

                      for ( $i = 0; $i < count($statusexploded); $i++ )
                      {
                          if ( $statusexploded[$i] == 1 )
                          {
                              $oldamount = $amountexploded[$i];
                              $amounttodecrease = $oldamount / 2;

                              increasedecreasechips($userid, $amounttodecrease, 2, $dbc);

                              $amountexploded[$i] = '0';
                              $statusexploded[$i] = '2';
                              $cardsexploded[$i] = '';

                              $newamount = join(":", $amountexploded);
                              $newstatus = join(':', $statusexploded);
                              $newcards = join(':', $cardsexploded);

                              //upload all these things
                              $query2 = "update blackjack_bets set amount = '$newamount', player_status = '$newstatus', cards = '$newcards' where session_id = $sessionid and user_id = $userid";
                              mysqli_query($dbc, $query2);

                              // check if other players exist
                              // find next player to activate
                              $query3 = "select user_id, player_status from blackjack_bets where player_status like '%0%' and session_id = $sessionid order by datetime limit 1";
                              $result3 = mysqli_query($dbc, $query3);

                              if ( mysqli_num_rows($result3) > 0 )
                              {
                                  $row3 = mysqli_fetch_row($result3);

                                  echo '<user_id>';
                                  echo $row3[0];
                                  echo '</user_id>';

                                  //renew session
                                  $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+20 seconds'));
                                  $querym2 = "update blackjack_game_data set status = 2, datetime = '$date2' where session_id = $sessionid";
                                  mysqli_query($dbc, $querym2);

                                  $thisplayerstatus = $row3[1];
                                  //set status of this userid as 1, and update time of gamedaa

                                  $thisplayerstatusexploded = explode(':', $thisplayerstatus);

                                  for ( $i = 0; $i < $thisplayerstatusexploded; $i++ )
                                  {
                                      if ( $thisplayerstatusexploded[$i] == 0 )
                                      {
                                          $thisplayerstatusexploded[$i] = 1;

                                          $newstatus = implode(':', $thisplayerstatusexploded);

                                          $query4 = "update blackjack_bets set player_status = '$playerstatus' where user_id = $userid and session_id = $sessionid";
                                          mysqli_query($dbc, $query4);
                                      }
                                  }
                              }
                              else
                              {
                                  // all players had done their job, do calculations here
                                  // also set blackjack status to zero

                                  doblackjackcalculations($sessionid, $dbc);

                                  $date2 = date('Y-m-d  H:i:s', strtotime(date('Y-m-d  H:i:s') . '+30 seconds'));

                                  $querym2 = "update blackjack_game_data set status = 0, datetime = '$date2' where session_id = $sessionid";
                                  mysqli_query($dbc, $querym2);

                                  $querym6 = "update blackjack_bets set player_status = '0', amount = '0', cards='' where session_id = $sessionid";
                                  mysqli_query($dbc, $querym6);

                                  //return time remaining and game status
                                  echo '<blackjackStatus>';
                                  echo 0;
                                  echo '</blackjackStatus>';

                                  echo '<seconds>';
                                  echo 30;
                                  echo '</seconds>';
                              }
                          }
                      }
                      sendthepush('surrender', $userid, $dbc);
                  }
                  break;

              default :
                  break;
          }
          mysqli_close($dbc);
      }
  }
?>
