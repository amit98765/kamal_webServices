<?php

  header('Content-Type:text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>';
  require_once 'variables/dbconnectionvariables.php';

  $emailid = strtolower($_GET['email_id']);
  if ( !is_numeric($emailid) )
  {
      $dbc = mysqli_connect(host, user, password, database)
              or die("Error connecting dataabase");
   
      $query = "select name, password from user_details where email_id= '$emailid'";
      
      $result = mysqli_query($dbc, $query);
      
      if ( mysqli_num_rows($result) == 1 )
      {
          $name = "";
          $password = "";
      
          while ( $row = mysqli_fetch_array($result) )
          {
              $name = $row['name'];
              $password = $row['password'];
          }

          //we have got emailid and password.
          $headers = "MIME-Version: 1.0" . "\r\n";
          $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
          $headers .= 'FROM:feedback@hotelnewoffers.com';


          $message = "You have requested to recover your forgotten password.";
          $message .= "<br/> Your account details are :- <br />";
          $message .= "Name : " . $name;
          $message .= "<br /> Password : " . $password . "<br />";
          $message .= "<br/> Please delete this email as soon as possible to avoid misuse.";


          $mailsent = mail($emailid, "Recover Your Password", $message, $headers);
          if ( $mailsent )
          {
              echo '<status>';
              echo '1';
              echo '</status>';
          }
          else
          {
              echo '<status>';
              echo '0';
              echo '</status>';
          }
      }
      else
      {
          echo '<status>';
          echo '0';
          echo '</status>';
      }
  }
  else
  {

      echo '<status>';
      echo '0';
      echo '</status>';
  }
?>
