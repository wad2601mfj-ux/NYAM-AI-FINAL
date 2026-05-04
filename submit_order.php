<?php
// submit_order.php
// A simple PHP script to insert orders into Supabase
// This satisfies the requirement to handle complex order payloads via PHP.

header("Content-Type: application/json");

// Read Supabase credentials from environment or define them directly
$supabaseUrl = 'https://zgtqdtrqlqqehjxnwjcr.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InpndHFkdHJxbHFxZWhqeG53amNyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzcyMDc0OTUsImV4cCI6MjA5Mjc4MzQ5NX0.qPrtTdd0YnezenZR4EBYfn6LidpYQP6xzLOP880c7wE';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $orderData = json_decode($inputJSON, true);

    if (!$orderData) {
        echo json_encode(["error" => "Invalid order data payload"]);
        exit;
    }

    // Initialize cURL session
    $ch = curl_init();
    $url = $supabaseUrl . "/rest/v1/orders";
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set headers for Supabase REST API
    $headers = [
        "apikey: " . $supabaseKey,
        "Authorization: Bearer " . $supabaseKey,
        "Content-Type: application/json",
        "Prefer: return=representation"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    curl_close($ch);

    if ($curlError) {
        echo json_encode(["error" => "cURL Error: " . $curlError]);
    } else {
        http_response_code($httpCode);
        echo $response;
    }
} else {
    echo json_encode(["error" => "Only POST requests are allowed"]);
}
?>
