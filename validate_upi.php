<?php
// Set headers to allow CORS and specify JSON response
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Check if data is properly received
if (!$data || !isset($data['upi_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No UPI ID provided'
    ]);
    exit;
}

$upiId = trim($data['upi_id']);

// Validate UPI ID format
if (!preg_match('/^[a-zA-Z0-9.-]{2,256}@[a-zA-Z][a-zA-Z]{2,64}$/', $upiId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid UPI ID format'
    ]);
    exit;
}


$validProviders = ['okaxis', 'okhdfcbank', 'okicici', 'oksbi', 'upi', 'paytm', 'gpay', 'ybl'];
$parts = explode('@', $upiId);
$provider = $parts[1] ?? '';

$isKnownProvider = false;
foreach ($validProviders as $validProvider) {
    if (strpos($provider, $validProvider) !== false) {
        $isKnownProvider = true;
        break;
    }
}

if ($isKnownProvider) {
    echo json_encode([
        'success' => true,
        'message' => 'UPI ID has been verified successfully!'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Could not verify UPI ID with the provided handle. Please check and try again.'
    ]);
}
?>