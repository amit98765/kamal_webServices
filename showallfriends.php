<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  $userid = $_GET['userid'];

  if ( is_numeric($userid) )
  {
      $userids = array();

      echo '<friends>';
      // grab a list of friends of this user
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting database");
      $query = "select request_to from friend_requests where request_from = $userid";
      $result = mysqli_query($dbc, $query);
      if ( mysqli_num_rows($result) != 0 )
      {
          while ( $row = mysqli_fetch_array($result) )
          {
              array_push($userids, $row[0]);
          }
      }

      $query2 = "select request_from from friend_requests where request_to = $userid and status=1";
      $result2 = mysqli_query($dbc, $query2);
      if ( mysqli_num_rows($result2) != 0 )
      {
          while ( $row2 = mysqli_fetch_array($result2) )
          {
              array_push($userids, $row2[0]);
          }
      }

      // array is formed now form a query to fetch details of all users
      $finaluseridslist = join(',', $userids);

      $querymy1 = "SELECT user_details.user_id, name, email_id, status from user_details, current_login_status
    where current_login_status.user_id = user_details.user_id and user_details.user_id in ($finaluseridslist) and status = 1 order by name";
      $resultmy1 = mysqli_query($dbc, $querymy1);
      if ( mysqli_num_rows($resultmy1) != 0 )
      {
          echo '<online>';
          while ( $rowmy1 = mysqli_fetch_array($resultmy1) )
          {

              echo '<friend>';

              echo '<userid>';
              echo $rowmy1[0];
              echo '</userid>';

              echo '<name>';
              echo $rowmy1[1];
              echo '</name>';

              echo '<emailid>';
              echo $rowmy1[2];
              echo '</emailid>';

              echo '<iconname>';

              $querymy3 = "select icon_name from user_icon where user_id = $rowmy1[0]";
              $resultmy3 = mysqli_query($dbc, $querymy3);
              if ( mysqli_num_rows($resultmy3) == 0 )
              {
                  echo 'Dice';
              }
              else
              {
                  while ( $rowmy3 = mysqli_fetch_array($resultmy3) )
                  {
                      echo $rowmy3[0];
                  }
              }
              echo '</iconname>';
              echo '</friend>';
          }
          echo '</online>';
      }
      $querymy2 = "SELECT user_details.user_id, name, email_id, status from user_details, current_login_status
    where current_login_status.user_id = user_details.user_id and user_details.user_id in ($finaluseridslist) and status = 0 order by name";
      $resultmy2 = mysqli_query($dbc, $querymy2);
      if ( mysqli_num_rows($resultmy2) != 0 )
      {
          echo '<offline>';
          while ( $rowmy2 = mysqli_fetch_array($resultmy2) )
          {

              echo '<friend>';

              echo '<userid>';
              echo $rowmy2[0];
              echo '</userid>';

              echo '<name>';
              echo $rowmy2[1];
              echo '</name>';

              echo '<emailid>';
              echo $rowmy2[2];
              echo '</emailid>';

              echo '<iconname>';
              $querymy3 = "select icon_name from user_icon where user_id = $rowmy2[0]";
              $resultmy3 = mysqli_query($dbc, $querymy3);
              if ( mysqli_num_rows($resultmy3) == 0 )
              {
                  echo 'Dice';
              }
              else
              {
                  while ( $rowmy3 = mysqli_fetch_array($resultmy3) )
                  {
                      echo $rowmy3[0];
                  }
              }
              echo '</iconname>';
              echo '</friend>';
          }
          echo '</offline>';
      }
      echo '</friends>';
      mysqli_close($dbc);
  }
?>
