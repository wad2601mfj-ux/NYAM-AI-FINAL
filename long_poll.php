<?php
/**
 * long_poll.php — High-Performance Timestamp Cursor
 * Reliable realtime without URL length limits.
 */
header("Content-Type: application/json");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");

require 'db.php';

$table     = $_GET['table']     ?? 'chats';
$filterKey = $_GET['filterKey'] ?? '';
$filterVal = $_GET['filterVal'] ?? '';
$since     = $_GET['since']     ?? ''; // Cursor timestamp

$allowed = ['chats', 'buyer_activities', 'orders', 'buyer_offers'];
if (!in_array($table, $allowed)) exit;

$tsCol = ($table === 'orders') ? 'time' : 'timestamp';

// ── GET NEW DATA ──
// We fetch everything GREATER THAN OR EQUAL to since to ensure nothing is missed
// deduplication happens on the client side.
$sql = "SELECT * FROM `$table`";
$where = [];

if ($since) {
    $where[] = "`$tsCol` >= '" . $conn->real_escape_string($since) . "'";
}

if ($filterKey && $filterVal) {
    $fk = $conn->real_escape_string($filterKey);
    $fv = $conn->real_escape_string($filterVal);
    $where[] = "`$fk` = '$fv'";
}

if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY `$tsCol` ASC LIMIT 100";

$res = $conn->query($sql);
$rows = [];
$maxTs = $since;

if ($res) {
    while ($row = $res->fetch_assoc()) {
        // Normalization
        if ($table === 'buyer_activities') {
            $row['sellerName'] = $row['sellername'];
            $row['buyerId']    = $row['buyerid'];
            $row['activity_type'] = $row['action'];
        }
        if ($table === 'orders') {
            $row['sellerName'] = $row['seller'];
            $row['timestamp']  = $row['time'];
            $row['buyerId']    = $row['buyerName'];
        }
        
        $rows[] = $row;
        // Track the latest timestamp
        if ($row[$tsCol] > $maxTs) $maxTs = $row[$tsCol];
    }
}

echo json_encode([
    'data' => $rows,
    'since' => $maxTs ?: date('Y-m-d H:i:s')
]);
?>
