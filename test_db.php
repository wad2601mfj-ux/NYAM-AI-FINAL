<?php
$c = new mysqli('localhost', 'root', '', 'nyam_ai');
$tables = ['buyer_activities', 'buyer_offers', 'chats', 'orders', 'products'];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $res = $c->query("DESCRIBE $t");
    while ($r = $res->fetch_assoc()) {
        echo " - " . $r['Field'] . "\n";
    }
}
