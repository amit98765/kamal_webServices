<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

$useridofrequestsender   = $_GET['userid2'];
$useridofrequestreceiver = $_GET['userid1'];

if (!is_numeric($useridofrequestreceiver) || !is_numeric($useridofrequestsender))
{
    echo '<status>0</status>';
}
else
{
    //get the user id of request sender
    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    $query2 = "update friend_requests set status = 1 where request_from = $useridofrequestsender and request_to = $useridofrequestreceiver";
    mysqli_query($dbc, $query2);
    if (mysqli_affected_rows($dbc) == 1)
    {
        echo '<status>1</status>';

        // ------------------------------------------------------------------------------------------------------------------------
        // get current devicetoken of request sender
        $devicetokenofsender = fetchdevicetoken($useridofrequestsender, $dbc);

        // now fetch name of request receiver
        $nameofreceiver = fetchname($useridofrequestreceiver, $dbc);

        //also make an entry of this in the messages table
        $messageformed = 'Your friend request has been approved by ' . $nameofreceiver;

        $query7 = "insert into messages( message, message_type, user_id) values ( '$messageformed', 0, $useridofrequestsender) ";
        mysqli_query($dbc, $query7);

        $message = 'Is now friend with ' . ucfirst(fetchname($useridofrequestreceiver, $dbc));
        insertIntoFeed($useridofrequestsender, $message, $dbc);

        $message2 = 'Is now friend with ' . ucfirst(fetchname($useridofrequestsender, $dbc));
        insertIntoFeed($useridofrequestreceiver, $message2, $dbc);

        sendpushtorequestsender($devicetokenofsender, $nameofreceiver);

        //-------------------------------------------------------------------------------------------------------------------------
    }
    else
    {
        echo '<status>2</status>';
    }

    mysqli_close($dbc);
}

function sendpushtorequestsender($devicetokenofrequestsender, $nameofrequestreceiver)
{


// Put your device token here (without spaces):
    $deviceToken = 'ddc158444fd422ddf04138ca6ada3f6a3eba0f3ac5b9b730a4b21befc7e136e3';

// Actual $deviceToken = $devicetokenofthereceiver;
// Put your private key's passphrase here:
    $passphrase = 'abcd';

// Put your alert message here:
    $message = 'Your friend request has been approved by ' . $nameofrequestreceiver;

////////////////////////////////////////////////////////////////////////////////

    $ctx = stream_context_create();
    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
    $fp = stream_socket_client(
            'ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

    if (!$fp)
        exit("Failed to connect: $err $errstr" . PHP_EOL);


// Create the payload body
    $body['aps'] = array(
        'alert' => $message,
        'sound' => '5'
    );

// Encode the payload as JSON
    $payload = json_encode($body);

// Build the binary notification
    $msg = chr(0) . pack('n', 32) . pack('H*', $devicetokenofrequestsender) . pack('n', strlen($payload)) . $payload;

// Send it to the server
    $result = fwrite($fp, $msg, strlen($msg));

// Close the connection to the server
    fclose($fp);
}

?>
