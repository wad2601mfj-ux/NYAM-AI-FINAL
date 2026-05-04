<?php
// Disable output buffering completely — critical for XAMPP on Windows
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
// Clear ALL output buffer layers
while (ob_get_level() > 0) { ob_end_clean(); }
ob_implicit_flush(true);

// SSE Headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

require 'db.php';

// Parameters
$table    = $_GET['table']    ?? 'chats';
$filterKey = $_GET['filterKey'] ?? '';
$filterVal = $_GET['filterVal'] ?? '';
$lastId   = $_GET['lastId']   ?? '';  // Last known ID (for timestamp tracking)

// Build query
function buildQuery($conn, $table, $filterKey, $filterVal, $lastTs) {
    $allowed = ['chats', 'buyer_activities', 'orders', 'buyer_offers'];
    if (!in_array($table, $allowed)) return null;

    $sql = "SELECT * FROM `$table`";
    $where = [];

    if ($filterKey && $filterVal) {
        $fk = $conn->real_escape_string($filterKey);
        $fv = $conn->real_escape_string($filterVal);
        $where[] = "`$fk` = '$fv'";
    }
    if ($lastTs) {
        $lt = $conn->real_escape_string($lastTs);
        $tsCol = ($table === 'orders') ? 'time' : 'timestamp';
        $where[] = "`$tsCol` > '$lt'";
    }
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $tsCol2 = ($table === 'orders') ? 'time' : 'timestamp';
    $sql .= " ORDER BY `$tsCol2` ASC";
    return $sql;
}

// Get current max timestamp as starting point
function getMaxTimestamp($conn, $table, $filterKey, $filterVal) {
    $allowed = ['chats', 'buyer_activities', 'orders', 'buyer_offers'];
    if (!in_array($table, $allowed)) return '1970-01-01 00:00:00';
    $tsCol = ($table === 'orders') ? 'time' : 'timestamp';
    $sql = "SELECT MAX(`$tsCol`) as maxts FROM `$table`";
    if ($filterKey && $filterVal) {
        $fk = $conn->real_escape_string($filterKey);
        $fv = $conn->real_escape_string($filterVal);
        $sql .= " WHERE `$fk` = '$fv'";
    }
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_assoc();
        return $row['maxts'] ?? '1970-01-01 00:00:00';
    }
    return '1970-01-01 00:00:00';
}

// Send SSE event
function sendEvent($data, $event = 'message') {
    $json = json_encode($data);
    echo "event: $event\n";
    echo "data: $json\n\n";
    if (ob_get_level() > 0) ob_flush();
    flush();
}

// Send a heartbeat so the connection stays alive
function sendHeartbeat() {
    echo ": heartbeat\n\n";
    if (ob_get_level() > 0) ob_flush();
    flush();
}

// Start: send 2KB padding to force Apache to flush the buffer immediately
// (Required for XAMPP/Windows environments)
echo ":" . str_repeat(" ", 2048) . "\n\n";
if (ob_get_level() > 0) ob_flush();
flush();

// Send a connection confirmation
sendEvent(['status' => 'connected', 'table' => $table], 'connected');

// Get baseline timestamp (don't replay old messages)
$lastTimestamp = getMaxTimestamp($conn, $table, $filterKey, $filterVal) ?: '1970-01-01 00:00:00';

$tick = 0;
$maxTime = 55; // Keep connection for ~55 seconds then client reconnects automatically

$startTime = time();

while (true) {
    // Check if client disconnected
    if (connection_aborted()) {
        break;
    }

    // Max runtime check (let browser auto-reconnect)
    if ((time() - $startTime) >= $maxTime) {
        sendEvent(['status' => 'reconnect'], 'reconnect');
        break;
    }

    // Query for new rows since lastTimestamp
    $sql = buildQuery($conn, $table, $filterKey, $filterVal, $lastTimestamp);
    if ($sql) {
        // Reconnect if needed
        if (!$conn->ping()) {
            $conn->close();
            require 'db.php';
        }
        $res = $conn->query($sql);
        if ($res) {
            $newRows = [];
            while ($row = $res->fetch_assoc()) {
                // Normalize data like api.php does
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
                $newRows[] = $row;
            }

            if (!empty($newRows)) {
                // Update lastTimestamp to the newest row
                $tsCol = ($table === 'orders') ? 'time' : 'timestamp';
                $latestTs = end($newRows)[$tsCol];
                if ($latestTs) $lastTimestamp = $latestTs;

                foreach ($newRows as $row) {
                    sendEvent(['eventType' => 'INSERT', 'new' => $row], 'insert');
                }
            }
        }
    }

    // Heartbeat every 5 ticks (~5 seconds)
    $tick++;
    if ($tick % 5 === 0) {
        sendHeartbeat();
    }

    // Poll interval: 1 second
    sleep(1);
}
?>
