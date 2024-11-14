<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Connection to the database
require_once('../connection/connection.php');

if (!$connect) {
    http_response_code(500);
    echo json_encode([
        "StatusCode" => 500,
        "Status" => "Error",
        "Message" => "Database connection failed"
    ]);
    exit();
}

// Helper function to generate policy number based on date and sequence
function generatePolicyNumber($connect) {
    $datePrefix = date("Ymd"); // Format: YYYYMMDD
    $query = "SELECT COUNT(*) AS count FROM Insurance WHERE policy_number LIKE '$datePrefix%'";
    $result = mysqli_query($connect, $query);
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'] + 1; // Increment count to get the next number in sequence
    return $datePrefix . str_pad($count, 4, "0", STR_PAD_LEFT); // Pad with zeros to 4 digits
}

// Check if the request is a GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Generate a new policy number
    $newPolicyNumber = generatePolicyNumber($connect);

    // Return the new policy number
    http_response_code(200);
    echo json_encode([
        "StatusCode" => 200,
        "Status" => "Success",
        "Data" => ["policy_number" => $newPolicyNumber]
    ]);
} else {
    // Handle unsupported methods
    http_response_code(405);
    echo json_encode([
        "StatusCode" => 405,
        "Status" => "Error",
        "Message" => "Method not allowed"
    ]);
}
?>