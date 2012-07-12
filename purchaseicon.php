<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

// chech whether all required parameters were passed.
  if ( !isset($_GET['user_id']) || !isset($_GET['icon_name']) || !isset($_GET['price']) || !isset($_GET['price_unit']) )
  {
      echo 'Some required parameters were not passed.';
      exit(0);
  }

// if they were passed, proceed 
  $userid = $_GET['user_id'];
  $iconname = $_GET['icon_name'];
  $price = $_GET['price'];
  $priceunit = $_GET['price_unit'];


// sanity check all these variables
  if ( !is_numeric($price) || is_null($iconname) || !is_numeric($userid) || ($priceunit != "g" && $priceunit != "c" && $priceunit != "r") )
  {
      echo 'Improper variables were passed. CHeck variable names in URL';
  }
  else
  {
      // check the price unit and put it in a variable
      if ( $priceunit == "g" )
      {
          $todeduct = "gold";
      }
      elseif ( $priceunit == "c" )
      {
          $todeduct = "chips";
      }
      elseif ( $priceunit == "r" )
      {
          $todeduct = "reward";
      }
      else
      {
          exit("price_unit variable not passed correctly");
      }

      $dbc = mysqli_connect(host, user, password, database)
              or die("Error Connecting to database");

      // enter new purchased icon in the table
      $query = "insert into purchased_icons(user_id, icon_name) values ($userid, '$iconname')";
      mysqli_query($dbc, $query);

      if ( mysqli_affected_rows($dbc) == 1 )
      {

          // one step was successful, now proceed to second step TO DECREASE NO OF CHIPS
          // fetch earlier no of chips of this user
          $query2 = "select $todeduct from user_cash where user_id = $userid";
          $result2 = mysqli_query($dbc, $query2);

          // if chips/gold were successfully fetched 
          if ( mysqli_num_rows($result2) == 1 )
          {
              $prevchipsorgold = NULL;
              while ( $row2 = mysqli_fetch_array($result2) )
              {
                  $prevchipsorgold = $row2[0];
              }

              // if chips amount was not numeric due to some reason
              if ( !is_numeric($prevchipsorgold) )
              {
                  echo 'Previous chips amount to user were not numeric';
              }
              // if user was not having sufficient chips, delete the entry made
              elseif ( $prevchipsorgold < $price )
              {
                  // You do not have sufficient chips count.';
                  echo '<status>2</status>';

                  // delete added row from database 
                  $query3 = "delete from purchased_icons where user_id = $userid and icon_name = '$iconname'";
                  mysqli_query($dbc, $query3);
              }
              else
              {

                  //deduct chips and make entry iin database
                  $newchipsorgoldcount = $prevchipsorgold - $price;

                  $query4 = "update user_cash set $todeduct = $newchipsorgoldcount where user_id= $userid";
                  if ( mysqli_query($dbc, $query4) )
                  {
                      echo '<user>';
                      echo '<status>1</status>';

                      // when status is 1, also return chips and gold available to the user
                      $query5 = "select gold, chips, reward from user_cash where user_id = $userid";
                      $result5 = mysqli_query($dbc, $query5);
                      if ( mysqli_num_rows($result5) == 1 )
                      {
                          // everything gone well
                          while ( $row5 = mysqli_fetch_array($result5) )
                          {
                              echo '<gold>';
                              echo $row5['gold'];
                              echo '</gold>';
      
                              echo '<chips>';
                              echo $row5['chips'];
                              echo '</chips>';
                              
                              echo '<reward>';
                              echo $row5['reward'];
                              echo '</reward>';
                          }
                          echo '</user>';
                      }
                      elseif ( mysqli_num_rows($result5) == 0 )
                      {
                          // chips/ gold was not avaulable for the user
                          echo 'this case wont ever happen unless there is error in query5';
                      }
                      else
                      {
                          // more than 1 row was returned
                          echo 'this case is possible only if there was error in forming user_cash table';
                      }
                  }
                  else
                      echo '<status>0</status>';
              }
          } else
          {
              //we were unable to fetch earlier chips/gold available to user
              $query3 = "delete from purchased_icons where user_id = $userid and icon_name = '$iconname'";
              mysqli_query($dbc, $query3);
              echo '<status>0</status>';
          }
      }
      else
      {
          echo '<status>0</status>';
      }
      mysqli_close($dbc);
  }
?>