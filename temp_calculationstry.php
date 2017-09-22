<?php

$dbc = mysqli_connect('localhost', 'root', 'mittal', 'casinoapp2')
        or die("Error connecting database");

require_once 'functions.php';
$sessionid = 127;

$tablecards = '';
$tablecardscombinations = array();
$query1 = "select cards from omaha_game_data where session_id = $sessionid";
$result1 = mysqli_query($dbc, $query1);
if (mysqli_num_rows($result1) > 0)
{
    $row1 = mysqli_fetch_row($result1);
    $tablecards = $row1[0];
}

//prepare combinations for this player
$tablecardsexploded = explode(',', $tablecards);

array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[1] . ',' . $tablecardsexploded[2]);
array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[1] . ',' . $tablecardsexploded[3]);
array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[1] . ',' . $tablecardsexploded[4]);
array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[2] . ',' . $tablecardsexploded[3]);
array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[2] . ',' . $tablecardsexploded[4]);
array_push($tablecardscombinations, $tablecardsexploded[0] . ',' . $tablecardsexploded[3] . ',' . $tablecardsexploded[4]);
array_push($tablecardscombinations, $tablecardsexploded[1] . ',' . $tablecardsexploded[2] . ',' . $tablecardsexploded[3]);
array_push($tablecardscombinations, $tablecardsexploded[1] . ',' . $tablecardsexploded[2] . ',' . $tablecardsexploded[4]);
array_push($tablecardscombinations, $tablecardsexploded[2] . ',' . $tablecardsexploded[3] . ',' . $tablecardsexploded[4]);
array_push($tablecardscombinations, $tablecardsexploded[1] . ',' . $tablecardsexploded[3] . ',' . $tablecardsexploded[4]);

// first of all find winner
$query = "select * from omaha_bets where session_id = $sessionid and is_folded = 0 ";
$result = mysqli_query($dbc, $query);
if (mysqli_num_rows($result) > 0)
{
    while ($row = mysqli_fetch_array($result))
    {
        $thisplayerid = $row['user_id'];
        $thisplayercards = $row['cards'];

        $thisplayercardsexploded = explode(',', $thisplayercards);
        $thisplayercardsarray = array();

        array_push($thisplayercardsarray, $thisplayercardsexploded[0] . ',' . $thisplayercardsexploded[1]);
        array_push($thisplayercardsarray, $thisplayercardsexploded[0] . ',' . $thisplayercardsexploded[2]);
        array_push($thisplayercardsarray, $thisplayercardsexploded[0] . ',' . $thisplayercardsexploded[3]);
        array_push($thisplayercardsarray, $thisplayercardsexploded[1] . ',' . $thisplayercardsexploded[2]);
        array_push($thisplayercardsarray, $thisplayercardsexploded[1] . ',' . $thisplayercardsexploded[3]);
        array_push($thisplayercardsarray, $thisplayercardsexploded[2] . ',' . $thisplayercardsexploded[3]);

        $allpossiblecombinationsforthisplayer = array();
        for ($i = 0; $i < count($tablecardscombinations); $i++)
        {
            $tablecardsbroken = explode(',', $tablecardscombinations[$i]);
            $tablecard1 = $tablecardsbroken[0];
            $tablecard2 = $tablecardsbroken[1];
            $tablecard3 = $tablecardsbroken[2];

            for ($j = 0; $j < count($thisplayercardsarray); $j++)
            {
                $thisplayercardsbroken = explode(',', $thisplayercardsarray[$j]);
                $thisplayercard1 = $thisplayercardsbroken[0];
                $thisplayercard2 = $thisplayercardsbroken[1];

                $allpossiblecombinationsforthisplayer[] = array($tablecard1, $tablecard2, $tablecard3, $thisplayercard1, $thisplayercard2);
            }
        }

        // now i have all the combinations for this plAYer, and i have to choose the best one
        for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
        {

            usort($allpossiblecombinationsforthisplayer[$i], function($a, $b)
                    {
                        if ($b % 13 == $a % 13)
                            return 0;

                        if (( $a % 13 == 0) && ($b % 13 == 1))
                            return -1;
                        if (( $b % 13 == 0) && ($a % 13 == 1))
                            return 1;
                        if ($a % 13 == 0 || $a % 13 == 1)
                            return 1;
                        if ($b % 13 == 0 || $b % 13 == 1)
                            return -1;
                        return ($a % 13 < $b % 13) ? -1 : 1;
                    });
        }



        $found = FALSE;

        // check for royal flush
        for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
        {
            $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

            // for every combination, check if it forms an royal flush
            if ((($allpossiblecombinationsforthisplayer[$i][0] == 1)
                    || ($allpossiblecombinationsforthisplayer[$i][0] == 14)
                    || ($allpossiblecombinationsforthisplayer[$i][0] == 27)
                    || ($allpossiblecombinationsforthisplayer[$i][0] == 40))
                    &&
                    ((($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 9)
                    && ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 1)
                    && ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 1)
                    && ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 1))))
            {
                $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 1," . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                mysqli_query($dbc, $query21);
                $found = TRUE;
                break;
            }
        }

        if (!$found)
        {
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                // check for straight flush
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);
                if (
                        ($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 1)
                        && ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 1)
                        && ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 1)
                        && ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 1)
                        && ($allpossiblecombinationsforthisplayer[$i][1] != 1)
                        && ($allpossiblecombinationsforthisplayer[$i][1] != 14)
                        && ($allpossiblecombinationsforthisplayer[$i][1] != 27)
                        && ($allpossiblecombinationsforthisplayer[$i][1] != 40)
                        && ($allpossiblecombinationsforthisplayer[$i][2] != 1)
                        && ($allpossiblecombinationsforthisplayer[$i][2] != 14)
                        && ($allpossiblecombinationsforthisplayer[$i][2] != 27)
                        && ($allpossiblecombinationsforthisplayer[$i][2] != 40)
                        && ($allpossiblecombinationsforthisplayer[$i][3] != 1)
                        && ($allpossiblecombinationsforthisplayer[$i][3] != 14)
                        && ($allpossiblecombinationsforthisplayer[$i][3] != 27)
                        && ($allpossiblecombinationsforthisplayer[$i][3] != 40))
                {

                    // try to select older result, if was available
                    $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                    $result22 = mysqli_query($dbc, $query22);

                    if (mysqli_num_rows($result22) == 0)
                    {
                        $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 2, " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                        mysqli_query($dbc, $query21);
                        $found = TRUE;
                    }
                    else
                    {
                        $row22 = mysqli_fetch_row($result22);
                        $earlierhighercard = $row22[0];
                        $combinationexplode = explode(',', $earlierhighercard);

                        for ($p = count($combinationexplode) - 1; $p < -1; $p--)
                        {
                            if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                mysqli_query($dbc, $query23);
                                break;
                            }
                            elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!$found)
        {
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                $uniquevalues1 = array_count_values($arraytocountvalues);

                $uniquevalues = array_flip($uniquevalues1);


                // check for 4 of a kind
                if (array_key_exists(4, $uniquevalues))
                {

                    // player has 4 of a kind
                    $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                    $result22 = mysqli_query($dbc, $query22);

                    if (mysqli_num_rows($result22) == 0)
                    {
                        $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 3, " . getcardvalueOmaha($uniquevalues[4]) . " )";
                        mysqli_query($dbc, $query21);
                        $found = TRUE;
                    }
                    else
                    {
                        $row22 = mysqli_fetch_row($result22);
                        $earlierhighercard = $row22[0];
                        $combinationexplode = explode(',', $earlierhighercard);

                        for ($p = count($combinationexplode) - 1; $p < -1; $p--)
                        {
                            if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($uniquevalues[4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                mysqli_query($dbc, $query23);
                                break;
                            }
                            elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!$found)
        {
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                $uniquevalues1 = array_count_values($arraytocountvalues);

                $uniquevalues = array_flip($uniquevalues1);



                // check for full house
                if (array_key_exists(3, $uniquevalues) && array_key_exists(2, $uniquevalues))
                {

                    // player has a full house
                    $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                    $result22 = mysqli_query($dbc, $query22);

                    if (mysqli_num_rows($result22) == 0)
                    {
                        $toinsert1 = 0;
                        $toinsert2 = 0;
                        if (getcardvalueOmaha($uniquevalues[3]) > getcardvalueOmaha($uniquevalues[2]))
                        {
                            $toinsert1 = getcardvalueOmaha($uniquevalues[3]);
                            $toinsert2 = getcardvalueOmaha($uniquevalues[2]);
                        }
                        else
                        {
                            $toinsert2 = getcardvalueOmaha($uniquevalues[3]);
                            $toinsert1 = getcardvalueOmaha($uniquevalues[2]);
                        }
                        $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards, highest_cards2) values($sessionid, $thisplayerid, '$dbcombination', 4, $toinsert1, $toinsert2)";
                        mysqli_query($dbc, $query21);
                        $found = TRUE;
                    }
                    else
                    {
                        $row22 = mysqli_fetch_row($result22);
                        $earlierhighercard = $row22[0];
                        $combinationexplode = explode(',', $earlierhighercard);

                        for ($p = count($combinationexplode) - 1; $p < -1; $p--)
                        {
                            if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($uniquevalues[4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                mysqli_query($dbc, $query23);
                                break;
                            }
                            elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!$found)
        {
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                // check for flush
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);
                if (
                        (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][0]) < 13)
                        && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][0]) > 0)
                        && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][2]) < 13)
                        && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][2]) > 0)
                        && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3]) < 13)
                        && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3]) > 0)
                        && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][1]) < 13)
                        && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][1]) > 0)
                )
                {
                    // player has a flush
                    $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                    $result22 = mysqli_query($dbc, $query22);

                    if (mysqli_num_rows($result22) == 0)
                    {
                        $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 5, " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                        mysqli_query($dbc, $query21);
                        $found = TRUE;
                    }
                    else
                    {
                        $row22 = mysqli_fetch_row($result22);
                        $earlierhighercard = $row22[0];
                        $combinationexplode = explode(',', $earlierhighercard);

                        for ($p = count($combinationexplode) - 1; $p < -1; $p--)
                        {
                            if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                mysqli_query($dbc, $query23);
                                break;
                            }
                            elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!$found)
        {
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);
                // check for a straight
                if (
                        (($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 1)
                        || ($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 14)
                        || ($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 27)
                        || ($allpossiblecombinationsforthisplayer[$i][1] - $allpossiblecombinationsforthisplayer[$i][0] == 40))
                        && (($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 1)
                        || ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 14)
                        || ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 27)
                        || ($allpossiblecombinationsforthisplayer[$i][2] - $allpossiblecombinationsforthisplayer[$i][1] == 40))
                        && (($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 1)
                        || ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 14)
                        || ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 27)
                        || ($allpossiblecombinationsforthisplayer[$i][3] - $allpossiblecombinationsforthisplayer[$i][2] == 40))
                        && (($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 1)
                        || ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 14)
                        || ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 27)
                        || ($allpossiblecombinationsforthisplayer[$i][4] - $allpossiblecombinationsforthisplayer[$i][3] == 40))
                )
                {
                    // player fas  striaght
                    $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                    $result22 = mysqli_query($dbc, $query22);

                    if (mysqli_num_rows($result22) == 0)
                    {
                        $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 6, " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                        mysqli_query($dbc, $query21);
                        $found = TRUE;
                    }
                    else
                    {
                        $row22 = mysqli_fetch_row($result22);
                        $earlierhighercard = $row22[0];
                        $combinationexplode = explode(',', $earlierhighercard);

                        for ($p = count($combinationexplode) - 1; $p < -1; $p--)
                        {
                            if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                mysqli_query($dbc, $query23);
                                break;
                            }
                            elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!$found)
        {

            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                // before doing this, we must create a new array, conting the vales in the array
                $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                $uniquevalues1 = array_count_values($arraytocountvalues);

                $uniquevalues = array_flip($uniquevalues1);


                // check for 3 of a kind
                if (array_key_exists(3, $uniquevalues))
                {
                    if (!is_null($uniquevalues[3]))
                    {
                        // player has 3 of a kind
                        // player has a full house
                        $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                        $result22 = mysqli_query($dbc, $query22);

                        if (mysqli_num_rows($result22) == 0)
                        {
                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 7, " . getcardvalueOmaha($uniquevalues[3]) . " )";
                            mysqli_query($dbc, $query21);
                            $found = TRUE;
                        }
                        else
                        {
                            $row22 = mysqli_fetch_row($result22);
                            $earlierhighercard = $row22[0];
                            $combinationexplode = explode(',', $earlierhighercard);

                            for ($p = count($combinationexplode) - 1; $p < -1; $p--)
                            {
                                if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                                {
                                    $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                    mysqli_query($dbc, $query23);
                                    break;
                                }
                                elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                                {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$found)
        {
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                $uniquevalues1 = array_count_values($arraytocountvalues);

                $uniquevalues = array_flip($uniquevalues1);
                // check for a 2 pair

                if (count($uniquevalues1) == 3 && array_key_exists(2, $uniquevalues))
                {

                    // player has a pair
                    $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                    $result22 = mysqli_query($dbc, $query22);

                    if (mysqli_num_rows($result22) == 0)
                    {
                        // FIND both highest cards
                        $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards, highest_cards2) values($sessionid, $thisplayerid, '$dbcombination', 8, 1, 2)";
                        mysqli_query($dbc, $query21);
                        $found = TRUE;
                    }
                    else
                    {
                        $row22 = mysqli_fetch_row($result22);
                        $earlierhighercard = $row22[0];
                        $combinationexplode = explode(',', $earlierhighercard);

                        for ($p = count($combinationexplode) - 1; $p < -1; $p--)
                        {
                            if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                mysqli_query($dbc, $query23);
                                break;
                            }
                            elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                            {
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!$found)
        {

            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);

                $arraytocountvalues = convertToCardValues($allpossiblecombinationsforthisplayer[$i]);
                $uniquevalues1 = array_count_values($arraytocountvalues);

                $uniquevalues = array_flip($uniquevalues1);

                // check for a 1
                if (array_key_exists(2, $uniquevalues))
                {
                    if (!is_null($uniquevalues[2]))
                    {
                        // player has a pair
                        $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                        $result22 = mysqli_query($dbc, $query22);

                        if (mysqli_num_rows($result22) == 0)
                        {
                            $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 9, " . getcardvalueOmaha($uniquevalues[2]) . ")";
                            mysqli_query($dbc, $query21);
                            $found = TRUE;
                        }
                        else
                        {
                            $row22 = mysqli_fetch_row($result22);
                            $earlierhighercard = $row22[0];
                            $combinationexplode = explode(',', $earlierhighercard);

                            for ($p = count($combinationexplode) - 1; $p < -1; $p--)
                            {
                                if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                                {
                                    $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                                    mysqli_query($dbc, $query23);
                                    break;
                                }
                                elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                                {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!$found)
        {
            for ($i = 0; $i < count($allpossiblecombinationsforthisplayer); $i++)
            {
                // last case
                $dbcombination = join(',', $allpossiblecombinationsforthisplayer[$i]);
                $query22 = "select combination from omaha_results where session_id = $sessionid and user_id = $thisplayerid";
                $result22 = mysqli_query($dbc, $query22);

                if (mysqli_num_rows($result22) == 0)
                {
                    $query21 = "insert into omaha_results(session_id, user_id, combination, rank, highest_cards) values($sessionid, $thisplayerid, '$dbcombination', 10,  " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ")";
                    mysqli_query($dbc, $query21);
                    $found = TRUE;
                }
                else
                {
                    $row22 = mysqli_fetch_row($result22);
                    $earlierhighercard = $row22[0];
                    $combinationexplode = explode(',', $earlierhighercard);

                    for ($p = count($combinationexplode) - 1; $p < -1; $p--)
                    {
                        if (getcardvalueOmaha($combinationexplode[$p]) < getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                        {
                            $query23 = "update omaha_results set highest_cards = " . getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][4]) . ", combination='" . $dbcombination . "' where session_id = $sessionid and user_id = $thisplayerid";
                            mysqli_query($dbc, $query23);
                            break;
                        }
                        elseif (getcardvalueOmaha($combinationexplode[$p]) > getcardvalueOmaha($allpossiblecombinationsforthisplayer[$i][$p]))
                        {
                            break;
                        }
                    }
                }
            }
        }
    }


    // loop breaks here
    $query23 = "select min(rank) from omaha_results where session_id = $sessionid";
    $result23 = mysqli_query($dbc, $query23);
    if (mysqli_num_rows($result23) > 0)
    {
        $row23 = mysqli_fetch_row($result23);
        $maxrank = $row23[0];

        $query24 = "select user_id, combination from omaha_results where rank = $maxrank and session_id = $sessionid";
        $result24 = mysqli_query($dbc, $query24);
        $noofwinners = mysqli_num_rows($result24);

        $winners = array();
        // put all winners in an array
        if ($noofwinners > 0)
        {
            while ($row24 = mysqli_fetch_array($result24))
            {
                $winners[] = array($row24[0], $row24[1]);
            }
        }

      
        usort($winners, function($a, $b)
                {
                    $a1exploded = explode(',', $a[1]);
                    $b1exploded = explode(',', $b[1]);
                    for ($i = count($a1exploded) - 1; $i > -1; $i--)
                    {
                        if ($a1exploded[$i] % 13 == $b1exploded[$i] % 13)
                            continue;
                        elseif ($a1exploded[$i] % 13 < $b1exploded[$i] % 13)
                        {
                            return -1;
                        }
                        elseif ($a1exploded[$i] % 13 > $b1exploded[$i] % 13)
                        {
                            return 1;
                        }
                    }
                });

        
        $finalwinners = array_reverse($winners);
        $total = count($finalwinners);
        
        print_r($finalwinners);
        echo '<<<<';
        // now i have to keep only those values where card values are same
        for ($i = 1; $i < $total; $i++)
        {
            $winnercardsexploded = explode(',', $finalwinners[0][1]);
            $thiscardsexploded = explode(',', $finalwinners[$i][1]);

            for ($j = 0; $j < count($winnercardsexploded); $j++)
            {
                if ($winnercardsexploded[$j] % 13 != $thiscardsexploded[$j] % 13)
                {
                    unset($finalwinners[$i]);
                    break;
                }
            }
        }
        
        print_r($finalwinners);
        echo '<<<<';
        // now i have to form strategy for giving back the pots
        $winneruserids = array();
        for ($i = 0; $i < count($finalwinners); $i++)
        {
            array_push($winneruserids, $finalwinners[$i][0]);
        }
        
        print_r($winneruserids);
        
        $potplayers = array();
        $query25 = "select pot_players, pot_amount from omaha_pots where session_id = $sessionid and pot_type=1";
        $result25 = mysqli_query($dbc, $query25);

        $mainpotamount = 0;
        if (mysqli_num_rows($result25) > 0)
        {
            $row25 = mysqli_fetch_row($result25);
            $potplayers = explode(',', $row25[0]);
            $mainpotamount = $row25[1];
        }

        // now intersect both array to find common winners
        $eligiblewinners = array_merge(array_intersect($potplayers, $winneruserids));
        print_r($eligiblewinners);
        echo '<<<<';
        
        if (array_key_exists(0, $eligiblewinners))
        {
            // there was/were person or persons winning main pot

            $noofwinners = count($eligiblewinners);
            $eachplayerwon = round($mainpotamount / $noofwinners, 0);

            for ($i = 0; $i < count($eligiblewinners); $i++)
            {
                $query27 = "update omaha_bets set chips_available = chips_available + $eachplayerwon where session_id = $sessionid and user_id = $eligiblewinners[$i]";
                mysqli_query($dbc, $query27);

                setlatestmessage($sessionid, $eligiblewinners[$i], "Won " . $eachplayerwon . ' chips from main pot', 1, $dbc);
            }
            // delete the main pot
            $query28 = "delete from omaha_pots where session_id = $sessionid and pot_type =1";
            mysqli_query($dbc, $query28);
        }
        else
        {
            // no one won main pot. so just return it to contributors
            $eachplayerwon = round($mainpotamount / count($potplayers), 0);
            for ($i = 0; $i < count($potplayers); $i++)
            {
                $query27 = "update omaha_bets set chips_available = chips_available + $eachplayerwon where session_id = $sessionid and user_id = $potplayers[$i]";
                mysqli_query($dbc, $query27);

                setlatestmessage($sessionid, $potplayers[$i], "Returned " . $eachplayerwon . ' chips from main pot', 1, $dbc);
            }
            // delete the main pot
            $query28 = "delete from omaha_pots where session_id = $sessionid and pot_type =1";
            mysqli_query($dbc, $query28);
        }

        // check for other pots also
        $query29 = "select * from omaha_pots where session_id = $sessionid and pot_type = 0";
        $result29 = mysqli_query($dbc, $query29);
        if (mysqli_num_rows($result29) > 0)
        {
            while ($row29 = mysqli_fetch_array($result29))
            {
                $thispotid = $row29[0];
                $thispotamount = $row29[1];
                $thispotplayers = $row29[2];

                $thispotplayersexploded = explode(',', $thispotplayers);
                $thispotwinners = array_merge(array_intersect($thispotplayersexploded, $winneruserids));

                if (array_key_exists(0, $thispotwinners))
                {

                    // there was/were person or persons winning main pot
                    $noofwinners = count($thispotwinners);
                    $eachplayerwon = round($thispotamount / $noofwinners, 0);

                    for ($i = 0; $i < count($thispotwinners); $i++)
                    {
                        $query27 = "update omaha_bets set chips_available = chips_available + $eachplayerwon where session_id = $sessionid and user_id = $thispotwinners[$i]";
                        mysqli_query($dbc, $query27);

                        setlatestmessage($sessionid, $thispotwinners[$i], "Won " . $eachplayerwon . ' chips from side pot', 1, $dbc);
                    }
                    // delete the main pot
                    $query28 = "delete from omaha_pots where session_id = $sessionid and id = $thispotid";
                    mysqli_query($dbc, $query28);
                }
                else
                {
                    // no one won main pot. so just return it to contributors
                    $eachplayerwon = round($thispotamount / count($thispotplayersexploded), 0);
                    for ($i = 0; $i < count($thispotplayersexploded); $i++)
                    {
                        $query27 = "update omaha_bets set chips_available = chips_available + $eachplayerwon where session_id = $sessionid and user_id = $thispotplayersexploded[$i]";
                        mysqli_query($dbc, $query27);

                        setlatestmessage($sessionid, $thispotplayersexploded[$i], "Returned " . $eachplayerwon . ' chips from side pot', 1, $dbc);
                    }
                    // delete the main pot
                    $query28 = "delete from omaha_pots where session_id = $sessionid and id = $thispotid";
                    mysqli_query($dbc, $query28);
                }
            }
        }

        //reward the people who have 0 chips left now
        $query30 = "select * from omaha_bets where is_rewarded = 1 and session_id = $sessionid";
        $result30 = mysqli_query($dbc, $query30);
        $alreadyrewarded = mysqli_num_rows($result30);

        $query31 = "select * from omaha_bets where session_id = $sessionid and is_rewarded = 0 and chips_available = 0 order by datetime";
        $result31 = mysqli_query($dbc, $query31);

        if (mysqli_num_rows($result31) > 0)
        {
            while ($row31 = mysqli_fetch_array($result31))
            {
                $thisrowid = $row31[0];
                $thisuserid = $row31['user_id'];

                if ($alreadyrewarded < 3)
                {
                    $query32 = "update omaha_bets set is_rewarded = 1 where id = $thisrowid";
                    mysqli_query($dbc, $query32);
                    $alreadyrewarded++;
                }
                elseif ($alreadyrewarded == 3)
                {
                    $chipstogive = convertBackChips(fetchgametype($sessionid, $dbc));
                    increasedecreasechips($thisuserid, $chipstogive, 1, $dbc);
                    setlatestmessage($sessionid, $thisuserid, "Rewarded " . $chipstogive . ' chips : 3rd pos', 1, $dbc);

                    $query32 = "update omaha_bets set is_rewarded = 1 where session_id = $sessionid and user_id = $thisuserid";
                    mysqli_query($dbc, $query32);
                    $alreadyrewarded++;
                }
                elseif ($alreadyrewarded == 4)
                {
                    $chipstogive = 2 * convertBackChips(fetchgametype($sessionid, $dbc));
                    increasedecreasechips($thisuserid, $chipstogive, 1, $dbc);
                    setlatestmessage($sessionid, $thisuserid, "Rewarded " . $chipstogive . ' chips : 2nd pos', 1, $dbc);

                    $query32 = "update omaha_bets set is_rewarded = 1 where session_id = $sessionid and user_id = $thisuserid";
                    mysqli_query($dbc, $query32);
                    $alreadyrewarded++;
                }
                elseif ($alreadyrewarded == 5)
                {
                    $chipstogive = 3 * convertBackChips(fetchgametype($sessionid, $dbc));
                    increasedecreasechips($thisuserid, $chipstogive, 1, $dbc);
                    setlatestmessage($sessionid, $thisuserid, "Rewarded " . $chipstogive . ' chips : 1st pos', 1, $dbc);

                    $query32 = "update omaha_bets set is_rewarded = 1 where session_id = $sessionid and user_id = $thisuserid";
                    mysqli_query($dbc, $query32);
                    $alreadyrewarded++;
                }
            }
        }

        //empty result table
        //  $query33 = "delete from omaha_results where session_id = $sessionid";
        // mysqli_query($dbc, $query33);

        $query34 = "update omaha_bets set amount_bet = 0, player_status = 0, cards = '', is_folded = 0 where session_id = $sessionid";
        mysqli_query($dbc, $query34);

        $query35 = "update omaha_game_data set round = 1, status = 2, cards = '' where session_id = $sessionid";
        mysqli_query($dbc, $query35);

        $query35 = "select id from omaha_bets where is_dealer = 1 and session_id = $sessionid";
        $result35 = mysqli_query($dbc, $query35);


        if (mysqli_num_rows($result35) > 0)
        {
            $row35 = mysqli_fetch_row($result35);
            $olddealer = $row35[0];
            $query36 = "update omaha_bets set is_dealer = 2 where id = $row35[0] and session_id = $sessionid";
            mysqli_query($dbc, $query36);

            // create new dealer, set small and big blind
            $newdealer = 0;
            if ($olddealer < 6)
                $newdealer = $olddealer + 1;
            else
                $newdealer = 1;

            $query10 = "update omaha_bets set datetime = '" . date('Y-m-d H:i:s', time()) . "', is_dealer=1 where id = $newdealer";
            mysqli_query($dbc, $query10);

            $bigblind = 0;
            $smallblind = 0;

            if ($newdealer < 5)
            {
                $smallblind = $newdealer + 1;
                $bigblind = $newdealer + 2;
            }
            elseif ($newdealer == 5)
            {
                $smallblind = 6;
                $bigblind = 1;
            }
            elseif ($newdealer == 6)
            {
                $smallblind = 1;
                $bigblind = 2;
            }

            $amountbet = 25;
            $newtime = date('Y-m-d H:i:s', time() + $amountbet);
            $query113 = "update omaha_bets set amount_bet = $amountbet, datetime = '$newtime', chips_available = chips_available-$amountbet where id = " . $smallblind;
            $result113 = mysqli_query($dbc, $query113);

            $amountbet2 = 50;
            $newtime2 = date('Y-m-d H:i:s', time() + $amountbet2);
            $query114 = "update omaha_bets set amount_bet = $amountbet2, datetime = '$newtime2', chips_available=chips_available-$amountbet2  where id = " . $bigblind;
            $result114 = mysqli_query($dbc, $query114);

            $alreadygivencards = array();

            $query2 = "select user_id from omaha_bets where session_id = $sessionid and chips_available != 0";
            $result2 = mysqli_query($dbc, $query2);

            if (mysqli_num_rows($result2) > 0)
            {
                while ($row2 = mysqli_fetch_array($result2))
                {
                    $thisuserid = $row2[0];
                    $card1 = getnewcard($alreadygivencards);
                    array_push($alreadygivencards, $card1);

                    $card2 = getnewcard($alreadygivencards);
                    array_push($alreadygivencards, $card2);

                    $card3 = getnewcard($alreadygivencards);
                    array_push($alreadygivencards, $card3);

                    $card4 = getnewcard($alreadygivencards);
                    array_push($alreadygivencards, $card4);

                    $allcards = $card1 . ',' . $card2 . ',' . $card3 . ',' . $card4;

                    $query3 = "update omaha_bets set cards='$allcards' where session_id = $sessionid and user_id = $thisuserid";
                    mysqli_query($dbc, $query3);

                    $query4 = "update omaha_game_data set round = 1 where session_id = $sessionid";
                    mysqli_query($dbc, $query4);
                }
            }

            // activate the next player
            $query4 = "select * from omaha_bets where player_status = 0 and session_id = $sessionid and chips_available != 0 order by datetime limit 1";
            $result4 = mysqli_query($dbc, $query4);
            if (mysqli_num_rows($result4) > 0)
            {
                $row4 = mysqli_fetch_row($result4);
                $playeruserid = $row4[2];

                $query5 = "update omaha_bets set player_status = 1 where user_id = $playeruserid and session_id = $sessionid";
                mysqli_query($dbc, $query5);

                // also update game status
                $newgametime = date('Y-m-d H:i:s', time() + 20);
                $query7 = "update omaha_game_data set datetime = '$newgametime', status = 2, round=1 where session_id = $sessionid";
                mysqli_query($dbc, $query7);


                echo '<seconds>';
                echo 20;
                echo '</seconds>';

                $pushids = array();
                $query33 = "select user_id from omaha_bets where session_id = $sessionid";
                $result33 = mysqli_query($dbc, $query33);
                if (mysqli_num_rows($result33) > 0)
                {
                    while ($row33 = mysqli_fetch_array($result33))
                    {
                        array_push($pushids, $row33['user_id']);
                    }
                }

                $pushmessage = "Omaha starts again";
                sendpushtoplayers($pushids, $pushmessage, $dbc);
            }
        }
    }
}
mysqli_close($dbc);
?>
