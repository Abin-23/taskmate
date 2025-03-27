<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "taskmate";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    die("Invalid request");
}

$payment_id = $data['payment_id'];
$payer_email = $data['payer_email'];
$amount = $data['amount'];
$job_id = intval($data['job_id']);
$freelancer_email = $data['freelancer_email'];

// Insert into payments table
$sql = "INSERT INTO payments (payment_id, payer_email, amount, job_id, freelancer_email, status) 
        VALUES (?, ?, ?, ?, ?, 'Completed')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdss", $payment_id, $payer_email, $amount, $job_id, $freelancer_email);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Payment saved successfully!";
} else {
    echo "Error saving payment.";
}

$stmt->close();
$conn->close();
?>
