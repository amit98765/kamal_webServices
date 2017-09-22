<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';


if (is_null($_GET['user_id']))
{
    echo '<status>0</status>';
}
else
{
    $userid = $_GET['user_id'];
    
    // insert the data into the gift box table
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");
    
    echo '<jeffrey>';
    $query = "select jeffrey from user_cash where user_id = $userid";
    $result= mysqli_query($dbc, $query);
    if(mysqli_num_rows($result) > 0)
    {
        $row = mysqli_fetch_row($result);
        if($row[0]<0)
					echo '0';
				else 
					echo $row[0];
    }
    echo '</jeffrey>';
    mysqli_close($dbc);
}
?>
