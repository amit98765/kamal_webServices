<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';

// verify whether user_id variable was set or not.
if (!isset($_GET['user_id'])) {
    echo 'Variable user_id is not set in URL';
} else {

    // check if user_id is numeric or not
    if (!is_numeric($_GET['user_id'])) {
        echo 'Variable user_id is not numeric';
    } else {
        $userid = $_GET['user_id'];

        $dbc = mysqli_connect(host, user, password, database)
                or die("Error connecting database");
        $query = "select icon_name from purchased_icons where user_id = $userid order by datetime asc";
        $result = mysqli_query($dbc, $query);
        echo '<user>';
        echo '<icons>';

        // check if some rows were inserted
        if (mysqli_num_rows($result) != 0) {
            while ($row = mysqli_fetch_array($result)) {
                echo '<icon>';
                echo $row['icon_name'];
                echo '</icon>';
            }
        }
        echo '</icons>';
        // when status is 1, also return chips and gold available to the user
        $query5 = "select gold, chips, reward from user_cash where user_id = $userid";
        $result5 = mysqli_query($dbc, $query5);
        if (mysqli_num_rows($result5) == 1) {
            // everything gone well
            while ($row5 = mysqli_fetch_array($result5)) {
                echo '<gold>';
                echo number_format($row5['gold']);
                echo '</gold>';
                echo '<chips>';
                echo number_format($row5['chips']);
                echo '</chips>';
                echo '<reward>';
                echo $row5['reward'];
                echo '</reward>';
            }
            echo '</user>';
        } elseif (mysqli_num_rows($result5) == 0) {
            // chips/ gold was not avaulable for the user
            echo 'this case wont ever happen unless there is error in query5';
        } else {
            // more than 1 row was returned
            echo 'this case is possible only if there was error in forming user_cash table';
        }
        mysqli_close($dbc);
    }
}
?>