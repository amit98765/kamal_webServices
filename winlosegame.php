<?php

header('Content-Type:text/xml');
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'variables/dbconnectionvariables.php';
require_once 'functions.php';

// check if required variables are passed or not
if (!isset($_GET['session_id']) || !isset($_GET['players_ids']) || !isset($_GET['senders_userid']) || !isset($_GET['message']) || !isset($_GET['game_type']))
{
    echo 'you did not pass session_id or players_ids or senders_userid or message';
}
else
{

    // sanity check userpassed data
    $canproceed = TRUE;
    if (!is_numeric($_GET['session_id']) || is_null($_GET['players_ids']) || !is_numeric($_GET['senders_userid']) || is_null($_GET['message']) || is_null($_GET['game_type']))
    {
        $canproceed = FALSE;
    }


    if ($canproceed)
    {

        // create an array to store devide tokens to send the push
        $pushids = array();

        // grab all the variables
        $sessionid     = $_GET['session_id'];
        $playersids    = $_GET['players_ids'];
        $sendersuserid = $_GET['senders_userid'];
        $message1      = $_GET['message'];
        $gametype      = $_GET['game_type'];

        $playersidsexploded = explode(',', $playersids);

        // first of all update the chips of the player 
        $olderchips = getchips($sendersuserid);

        // check if olderchips were fetched or not
        if (is_null($olderchips))
        {
            echo '<status>0</status>';
        }
        else
        {

            // find new chips count
            $newchipscount = $olderchips + $message1;

            //update the no of new chips in the database
            $result = setchips($sendersuserid, $newchipscount);

            // check if chips were updated
            if (!$result)
            {
                echo '<status>0<status>';
            }
            else
            {

                // chips were updated successfully
                // set a message now in the latest messages table
// get the id of the row inserted

                $dbc = mysqli_connect(host, user, password, database)
                        or die("Error connecting database");

                $done = setlatestmessageid($sessionid, $sendersuserid, $message1, 0, $dbc);
                if ($done)
                {

                    $handlerid = $done;

                    if (is_null($handlerid))
                    {
                        echo '<status>0</status>';
                    }
                    else
                    {

                        // for each player make an entry in messages table
                        for ($i = 0; $i < count($playersidsexploded); $i++)
                        {

                            $thisplayersid = $playersidsexploded[$i];
                            if (setmessage($message1, $thisplayersid, $sendersuserid, 3, $dbc))
                            {

                                // get device token of this player
                                $devicetoken = fetchdevicetoken($thisplayersid);

                                // push it in an array
                                if (!is_null($devicetoken))
                                    array_push($pushids, $devicetoken);
                            }
                        }


                        // invoke push here, if there were some items in the array 
                        if (count($pushids) > 0)
                        {

                            // return status 1
                            echo '<status>1</status>';

                            // fetch name of person who sent the message
                            $name = fetchname($sendersuserid);

                            // if a valid name is found
                            if ($name != "")
                            {

                                // check status whether user lost or won the game
                                $status = "";
                                if ($message1 > 0)
                                {
                                    $status  = "won";
                                    $message = "Won " . $amount . ' in ' . $gametype;
                                    insertIntoFeed($sendersuserid, $message, $dbc);
                                }
                                else
                                {
                                    $status = "lost";
                                }

                                // strip out first letter from message now
                                $amount = substr($message1, 1);

                                // construct a message to be sent
                                $pushmessage = strtoupper($name) . ' ' . $status . ' ' . $amount . ' in ' . $gametype;

                                //send the push now
                                // Put your device token here (without spaces):
// Actual $deviceToken = $devicetokenofthereceiver;
// Put your private key's passphrase here:
                                $passphrase = 'abcd';

// Put your alert message here:
////////////////////////////////////////////////////////////////////////////////

                                $ctx = stream_context_create();
                                stream_context_set_option($ctx, 'ssl', 'local_cert', 'hotel.pem');
                                stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
                                $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

                                if (!$fp)
                                    exit("Failed to connect: $err $errstr" . PHP_EOL);

                                for ($i = 0; $i < count($pushids); $i++)
                                {
// Create the payload body
                                    $body['aps'] = array(
                                        'alert' => $pushmessage,
                                        'sound' => '1'
                                    );

// Encode the payload as JSON
                                    $payload = json_encode($body);

// Build the binary notification
                                    $msg = chr(0) . pack('n', 32) . pack('H*', $pushids[$i]) . pack('n', strlen($payload)) . $payload;

// Send it to the server
                                    fwrite($fp, $msg, strlen($msg));
                                }
// Close the connection to the server
                                fclose($fp);
                            }
                        }
                    }
                }
                mysqli_close($dbc);
            }
        }
    }
}
?>
