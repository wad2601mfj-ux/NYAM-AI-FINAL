<?php
header("Content-Type: application/json");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

require 'db.php';

// Helper: generate UUID v4 style IDs (matches existing orders table format)
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
function generateId($length = 10) {
    return generateUUID();
}

$action = $_GET['action'] ?? '';
$filterKey = $_GET['filterKey'] ?? '';
$filterVal = $_GET['filterVal'] ?? '';
$sellerName = $_GET['sellerName'] ?? $_GET['seller'] ?? '';

if (strtolower($filterKey) === 'seller' || strtolower($filterKey) === 'sellername') {
    $sellerName = $filterVal;
}

function getJsonInput() { return json_decode(file_get_contents('php://input'), true); }

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    echo json_encode(["error" => "PHP Error: $errstr", "file" => $errfile, "line" => $errline]);
    exit;
});
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_products') {
            $sql = "SELECT * FROM products";
            if ($sellerName) $sql .= " WHERE sellerName = '" . $conn->real_escape_string($sellerName) . "'";
            $res = $conn->query($sql);
            $data = [];
            while($row = $res->fetch_assoc()) {
                $r = array_change_key_case($row, CASE_LOWER);
                $row['sellerName'] = $r['sellername'] ?? '';
                $row['item'] = $r['items'] ?? $r['title'] ?? '';
                $row['items'] = [$row['item']];
                $row['basePrice'] = (float)($r['baseprice'] ?? $r['price'] ?? 0);
                $row['imageUrl'] = $r['imageurl'] ?? $r['image'] ?? '';
                $data[] = $row;
            }
            echo json_encode(["data" => $data]);
            
        } elseif ($action === 'get_offers' || $action === 'get_buyer_offers') {
            $bid = $_GET['buyerId'] ?? $filterVal ?? '';
            $sql = "SELECT * FROM buyer_offers";
            if ($bid) $sql .= " WHERE buyerid = '" . $conn->real_escape_string($bid) . "'";
            $sql .= " ORDER BY id DESC";
            $res = $conn->query($sql);
            $data = [];
            while($row = $res->fetch_assoc()) {
                $r = array_change_key_case($row, CASE_LOWER);
                $row['sellerName'] = $r['sellername'] ?? '';
                $row['buyerId'] = $r['buyerid'] ?? '';
                $row['item'] = $r['item'] ?? $r['product_title'] ?? '';
                $row['basePrice'] = (float)($r['baseprice'] ?? $r['product_price'] ?? 0);
                $row['imageUrl'] = $r['imageurl'] ?? $r['product_image'] ?? '';
                $data[] = $row;
            }
            echo json_encode(["data" => $data]);

        } elseif ($action === 'get_chats') {
            $bid = $_GET['buyerId'] ?? (in_array(strtolower($filterKey), ['buyerid', 'buyerid']) ? $filterVal : '');
            $sql = "SELECT * FROM chats";
            if ($bid) {
                $sql .= " WHERE buyerid = '" . $conn->real_escape_string($bid) . "'";
            } elseif ($sellerName) {
                // Include Global chats so sellers can see messages from new buyers
                $esc = $conn->real_escape_string($sellerName);
                $sql .= " WHERE (sellerName = '$esc' OR sellerName = 'Global')";
            }
            $sql .= " ORDER BY timestamp ASC";
            $res = $conn->query($sql);
            $data = [];
            while($row = $res->fetch_assoc()) {
                $r = array_change_key_case($row, CASE_LOWER);
                $row['buyerId'] = $r['buyerid'] ?? '';
                $row['sellerName'] = $r['sellername'] ?? '';
                $data[] = $row;
            }
            echo json_encode(["data" => $data]);
            
        } elseif ($action === 'get_buyers') {
            $sql = "SELECT DISTINCT buyerid FROM chats";
            if ($sellerName) {
                $esc = $conn->real_escape_string($sellerName);
                $sql .= " WHERE (sellerName = '$esc' OR sellerName = 'Global')";
            }
            $res = $conn->query($sql);
            $data = [];
            while($row = $res->fetch_assoc()) {
                $r = array_change_key_case($row, CASE_LOWER);
                $data[] = ["buyerId" => $r['buyerid'], "buyerid" => $r['buyerid']];
            }
            echo json_encode(["data" => $data]);

        } elseif ($action === 'get_activities' || $action === 'get_buyer_activities') {
            $sql = "SELECT * FROM buyer_activities";
            if ($sellerName) $sql .= " WHERE sellername = '" . $conn->real_escape_string($sellerName) . "'";
            $sql .= " ORDER BY timestamp DESC LIMIT 50";
            $res = $conn->query($sql);
            $data = [];
            while($row = $res->fetch_assoc()) {
                $r = array_change_key_case($row, CASE_LOWER);
                $row['sellerName'] = $r['sellername'] ?? '';
                $row['buyerId'] = $r['buyerid'] ?? '';
                $row['activity_type'] = $r['action'] ?? '';
                $data[] = $row;
            }
            echo json_encode(["data" => $data]);

        } elseif ($action === 'get_orders') {
            $sql = "SELECT * FROM orders";
            if ($sellerName) $sql .= " WHERE seller = '" . $conn->real_escape_string($sellerName) . "'";
            $sql .= " ORDER BY id DESC LIMIT 50";
            $res = $conn->query($sql);
            $data = [];
            while($row = $res->fetch_assoc()) {
                $r = array_change_key_case($row, CASE_LOWER);
                $row['sellerName'] = $r['seller'] ?? $r['sellername'] ?? '';
                $row['buyerId'] = $r['buyername'] ?? $r['buyerid'] ?? '';
                $row['timestamp'] = $r['time'] ?? $r['timestamp'] ?? '';
                if(isset($row['items']) && is_string($row['items']) && strpos($row['items'], '[') === 0) {
                    $row['items'] = json_decode($row['items'], true);
                } else {
                    $row['items'] = [ ["item" => $r['item'] ?? 'Item', "price" => $r['itemprice'] ?? 0, "qty" => $r['quantity'] ?? 1] ];
                }
                $data[] = $row;
            }
            echo json_encode(["data" => $data]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inputRaw = getJsonInput() ?: [];
        // Supabase polyfill sends arrays for inserts: [{...}, {...}]
        $inputs = (isset($inputRaw[0]) && is_array($inputRaw[0])) ? $inputRaw : [$inputRaw];
        $ts = date('Y-m-d H:i:s');
        $insertedData = [];
        
        if ($action === 'insert_order' || $action === 'insert_orders') {
            $stmt = $conn->prepare("INSERT INTO orders (id, seller, buyerName, buyerPhone, address, paymentMethod, item, quantity, itemPrice, deliveryFee, total, status, time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            foreach ($inputs as $input) {
                $items = $input['items'] ?? [];
                if (is_string($items)) $items = json_decode($items, true);
                $itemObj = is_array($items) && isset($items[0]) ? $items[0] : [];
                $itemStr = $itemObj['item'] ?? 'Order';
                $qty = (int)($itemObj['qty'] ?? 1);
                $prc = (float)($itemObj['price'] ?? 0);
                $status = 'Paid';
                
                $orderId = generateUUID();
                $seller = $input['sellerName'] ?? '';
                $buyerName = $input['buyerId'] ?? '';
                $buyerPhone = $input['buyerPhone'] ?? '';
                $address = $input['address'] ?? '';
                $paymentMethod = $input['paymentMethod'] ?? '';
                $deliveryFee = (float)($input['deliveryFee'] ?? 3000);
                $total = (float)($input['total'] ?? 0);

                $stmt->bind_param("sssssssidddss", $orderId, $seller, $buyerName, $buyerPhone, $address, $paymentMethod, $itemStr, $qty, $prc, $deliveryFee, $total, $status, $ts);
                $stmt->execute();
                $insertedData[] = ["id" => $orderId];
            }
            echo json_encode(["data" => $insertedData, "success" => true]);

        } elseif ($action === 'insert_buyer_activities' || $action === 'insert_activity') {
            // Get the next available ID (table uses integer IDs without auto-increment)
            $maxRes = $conn->query("SELECT COALESCE(MAX(id), -1) + 1 as nextId FROM buyer_activities");
            $nextId = (int)$maxRes->fetch_assoc()['nextId'];
            
            $stmt = $conn->prepare("INSERT INTO buyer_activities (id, buyerid, sellername, action, timestamp) VALUES (?,?,?,?,?)");
            foreach ($inputs as $input) {
                $bid = $input['buyerId'] ?? '';
                $sn = $input['sellerName'] ?? '';
                $act = $input['activity_type'] ?? $input['action'] ?? '';
                $stmt->bind_param("issss", $nextId, $bid, $sn, $act, $ts);
                $stmt->execute();
                $insertedData[] = ["id" => $nextId];
                $nextId++;
            }
            echo json_encode(["data" => $insertedData, "success" => true]);

        } elseif ($action === 'insert_chat' || $action === 'insert_chats') {
            $stmt = $conn->prepare("INSERT INTO chats (id, buyerid, sender, sellerName, message, address, rawText, timestamp) VALUES (?,?,?,?,?,?,?,?)");
            foreach ($inputs as $input) {
                $id = $input['id'] ?? generateId();
                $bid = $input['buyerId'] ?? '';
                $snd = $input['sender'] ?? '';
                $sn = $input['sellerName'] ?? '';
                $msg = $input['message'] ?? '';
                $addr = $input['address'] ?? '';
                $txt = $input['rawText'] ?? $msg;
                $stmt->bind_param("ssssssss", $id, $bid, $snd, $sn, $msg, $addr, $txt, $ts);
                $stmt->execute();
                $insertedData[] = ["id" => $id];
            }
            echo json_encode(["data" => $insertedData, "success" => true]);
            
        } elseif ($action === 'insert_offer' || $action === 'insert_buyer_offers') {
            $stmt = $conn->prepare("INSERT INTO buyer_offers (id, buyerid, sellerName, item, category, basePrice, discount, imageUrl, videoUrl, timestamp) VALUES (?,?,?,?,?,?,?,?,?,?)");
            foreach ($inputs as $input) {
                $id = $input['id'] ?? generateId();
                $bid = $input['buyerId'] ?? '';
                $sn = $input['sellerName'] ?? '';
                $itm = $input['item'] ?? '';
                $cat = $input['category'] ?? '';
                $prc = $input['basePrice'] ?? 0;
                $dsc = $input['discount'] ?? 0;
                $img = $input['imageUrl'] ?? '';
                $vid = $input['videoUrl'] ?? '';
                $stmt->bind_param("sssssddsss", $id, $bid, $sn, $itm, $cat, $prc, $dsc, $img, $vid, $ts);
                $stmt->execute();
                $insertedData[] = ["id" => $id];
            }
            echo json_encode(["data" => $insertedData, "success" => true]);
        
        } elseif ($action === 'update_order_rating') {
            // Rating update: polyfill sends { rating: N, id: "uuid" }
            $input = $inputRaw;
            $id = $input['id'] ?? '';
            $rating = (int)($input['rating'] ?? 0);
            if ($id && $rating > 0) {
                $stmt = $conn->prepare("UPDATE orders SET rating = ? WHERE id = ?");
                $stmt->bind_param("is", $rating, $id);
                $stmt->execute();
                echo json_encode(["success" => true, "data" => [["id" => $id, "rating" => $rating]]]);
            } else {
                echo json_encode(["error" => "Missing id or rating", "success" => false]);
            }

        } elseif ($action === 'update_products') {
            $input = $inputRaw;
            $id = $input['id'] ?? '';
            unset($input['id']);
            if ($id && count($input) > 0) {
                $sets = [];
                $vals = [];
                $types = '';
                foreach ($input as $col => $val) {
                    $sets[] = "$col = ?";
                    $vals[] = $val;
                    $types .= is_int($val) ? 'i' : (is_float($val) ? 'd' : 's');
                }
                $vals[] = $id;
                $types .= 's';
                $sql = "UPDATE products SET " . implode(', ', $sets) . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$vals);
                $stmt->execute();
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["error" => "Missing id or update data"]);
            }

        } elseif ($action === 'delete_chats') {
            $input = $inputRaw;
            $bid = $input['buyerId'] ?? '';
            if ($bid) {
                $stmt = $conn->prepare("DELETE FROM chats WHERE buyerid = ?");
                $stmt->bind_param("s", $bid);
                $stmt->execute();
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["error" => "Missing buyerId"]);
            }

        } elseif ($action === 'delete_offer') {
            $input = $inputRaw;
            $id = $input['id'] ?? '';
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM buyer_offers WHERE id = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["error" => "Missing id"]);
            }

        } elseif ($action === 'delete_offers_by_buyer') {
            $input = $inputRaw;
            $bid = $input['buyerId'] ?? '';
            if ($bid) {
                $stmt = $conn->prepare("DELETE FROM buyer_offers WHERE buyerid = ?");
                $stmt->bind_param("s", $bid);
                $stmt->execute();
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["error" => "Missing buyerId"]);
            }
        }
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage(), "action" => $action]);
}
