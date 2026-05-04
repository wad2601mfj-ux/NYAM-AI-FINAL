<?php
$c = new mysqli('localhost', 'root', '', 'nyam_ai');
if ($c->connect_error) die("Connection failed: " . $c->connect_error);

function safeAlter($c, $table, $column) {
    $res = $c->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($res && $res->num_rows > 0) {
        $c->query("ALTER TABLE `$table` MODIFY `$column` MEDIUMTEXT");
        echo "Expanded $table.$column\n";
    }
}

safeAlter($c, 'products', 'imageUrl');
safeAlter($c, 'products', 'image');
safeAlter($c, 'buyer_offers', 'product_image');
safeAlter($c, 'buyer_offers', 'imageUrl');

echo "Success: DB Ready for Base64.\n";
?>
