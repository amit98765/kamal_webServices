<?php

require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';
//error_reporting(E_ALL);
//
//$alreadygivencards = array('1','2','3','4','5');
//$cardsexploded = array('3','4','5','6','7');
//
//
//print_r(array_merge(array_unique(array_merge($alreadygivencards, $cardsexploded))));
//$date1 = "2013-05-05 05:05:05";
//$date2 = "2013-05-07 15:04:05";
//
//$datett1 = date("Y-m-d", strtotime($date1));
//$datett2 = date("Y-m-d", strtotime($date2));
//
// if($datett1> $datett2)
//     echo 'yes';
// else
//     echo 'no';
//$name = array();
//$data = array("one", "two", "three");
//
//$name[] = array(1,214,100);
//$name[] = array(5,312,200);
//$name[] = array(3,3,$data);
//
//
//print_r($name);
//$array1 = array(1,2);
//$array2 = array(2,1,3);
//
//$array3 = array_diff($array1, $array2);
//
//echo count($array3);
//$data[] = array(a, 1);
//$data[] = array(b, 2);
//$data[] = array(c, 3);
//$data[] = array(d, 4);
//$data[] = array(e, 5);
//
//for ($i = 0; $i < count($data); $i++) {
//    echo $data[$i][0];
//}
//$nae  = array(1,2,3);
//if(in_array(1, $nae))
//{
//    echo 'tes';
//}
//$data = array();
//
//$data[] = array(1, 2);
//$data[] = array(1, 3);
//$data[] = array(1, 4);
//$data[] = array(1, 5);
//
//print_r($data[][1]);
//
//for ($i = 0; $i < count($data); $i++) {
//
//    for ($j = 0; $j < 2; $j++) {
//
//        if ($j == 0)
//            $tosend = 1;
//        else
//            $tosend = 0;
//
//        // Create the payload body
//
//        $text = $data[$i][$j] . ' => ' . ' You and ' . $data[$i][$tosend] . ' are given a reward point';
//        echo $text . "/n";
//    }
//}
//
//$sessionid = 721;
//$message = "hit";
//$userid = 114;
//
//$dbc = mysqli_connect(host, user, password, database);
//
//$pushids = array();
//$queryn1 = "select user_id from blackjack_bets where user_id != 0 and session_id =" . $sessionid;
//$result1 = mysqli_query($dbc, $queryn1);
//if (mysqli_num_rows($result1) > 0) {
//    while ($row1 = mysqli_fetch_array($result1)) {
//        array_push($pushids, $row1[0]);
//    }
//}
//
//echo 'after getting pushids \n';
//echo $message . ',' . $userid . ',' . $sessionid . "\n";
//
//$passphrase = 'abcd';
//$ctx = stream_context_create();
//stream_context_set_option($ctx, 'ssl', 'local_cert', 'testing.pem');
//stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
//
//$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
//
//if (!$fp)
//    exit("Failed to connect: $err $errstr" . PHP_EOL);
//for ($i = 0; $i < count($pushids); $i++) {
//
//    echo 'inside foreach pushid \n';
//    $body['aps'] = array(
//        'alert' => fetchname($userid) . "'" . strtoupper($message) . "' in Blackjack Game",
//        'sound' => '3'
//    );
//
//    $payload = json_encode($body);
//
//    $devicetoken = fetchdevicetoken($pushids[$i], $dbc);
//
//    echo 'after device token';
//
//    echo $devicetoken . "\n";
//
//    $msg = chr(0) . pack('n', 32) . pack('H*', $devicetoken) . pack('n', strlen($payload)) . $payload;
//
//    fwrite($fp, $msg, strlen($msg));
//}
//fclose($fp);
//make gamestatus 3


    $timezone = "Asia/Calcutta";
    if (function_exists('date_default_timezone_set'))
        date_default_timezone_set($timezone);
$date2 = date('Y-m-d  H:i:s', time());

echo $date2;
?>