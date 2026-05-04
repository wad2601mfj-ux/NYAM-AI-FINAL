<?php
$c = new mysqli('localhost', 'root', '', 'nyam_ai');
$tables = ['buyer_activities', 'buyer_offers', 'chats', 'orders', 'products'];
foreach ($tables as $t) {
    $res = $c->query("SHOW CREATE TABLE $t");
    $row = $res->fetch_row();
    echo $row[1] . "\n\n";
}
