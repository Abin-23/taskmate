<?php
session_start();
$host = 'localhost';
$db = 'taskmate';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["job_id"]) && isset($_POST["job_status"])) {
    $job_id = intval($_POST["job_id"]);
    $new_status = $_POST["job_status"];

    $updateQuery = "UPDATE jobs SET job_status = ? WHERE job_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $new_status, $job_id);

    if ($stmt->execute()) {
        header("Location: client_project.php"); 
        exit();
    } else {
        echo "Error updating status: " . $conn->error;
    }
}
?>
