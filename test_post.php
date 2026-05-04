<?php
$data = ['buyerId' => 'test_buyer', 'sender' => 'seller', 'message' => 'hello buyer', 'sellerName' => 'My Restaurant'];
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/WAD%20php/api.php?action=insert_chats', false, $context);
echo "Result: " . $result;
