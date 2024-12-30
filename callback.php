<?php

// Get the raw POST data
$json = file_get_contents('php://input');

// Decode it to make sure it's valid JSON
$data = json_decode($json);

if ($data !== null) {
    // Save the JSON data to data.json file
    file_put_contents('data.json', $json);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Callback data saved']);
} else {
    // Return error response if invalid JSON
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
}