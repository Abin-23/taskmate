<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "taskmate";

if (isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $domain = substr(strrchr($email, "@"), 1); 

    if (!checkdnsrr($domain, "MX")) {
        echo 'invalid_domain';  
        exit();
    }

    $conn = mysqli_connect($servername, $username, $password, $database);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $query = "SELECT id FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        echo 'taken'; 
    } else {
        echo 'available';  
    }
}

mysqli_close($conn);
?>
