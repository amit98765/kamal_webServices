<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

if (isset($_GET['snuid']) && isset($_GET['currency']))
{

    $dbc = mysqli_connect(host, user, password, database)
            or die("Error connecting database");

    $userid = $_GET['snuid'];
    $amount = $_GET['currency'];

    if ($amount > 0)
    {
        $query = "update user_cash set chips = chips + $amount where user_id = $userid";
        mysqli_query($dbc, $query);

        insertIntoFeed($userid, 'have been rewarded ' . $amount . ' chips by Tapjoy', $dbc);

        $passphrase = 'abcd';
        $ctx        = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);



        $body['aps'] = array(
            'alert' => 'You have been rewarded ' . $amount . ' chips by Tapjoy',
            'sound' => '90'
        );

        $payload = json_encode($body);

        $devicetoken = fetchdevicetoken($userid, $dbc);

        $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;

        fwrite($fp, $msg, strlen($msg));

        fclose($fp);
    }
}
?>
