<?php
$text = "\*/1 \* \* \* \* wget -q http://198.101.207.181/casino_game/croncalled.php 2\>\/dev\/null 1\>\&2";
exec("echo  $text > /var/www/html/casino_game/cronjobs.txt");
exec('crontab /var/www/html/casino_game/cronjobs.txt')
?>
