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

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Fetch user data from both tables
$sql = "SELECT u.*, c.* FROM users u 
        LEFT JOIN client_profile c ON u.id = c.id 
        WHERE u.id = '" . $_SESSION['user_id'] . "'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update users table
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $mobile = $conn->real_escape_string($_POST['mobile']);
    
    $sql = "UPDATE users SET 
            name = '$name',
            email = '$email',
            mobile = '$mobile'
            WHERE id = " . $_SESSION['user_id'];
    
    $conn->query($sql);
    
    // Update client_profile table
    $company_name = $conn->real_escape_string($_POST['company_name']);
    $website = $conn->real_escape_string($_POST['website']);
    $address = $conn->real_escape_string($_POST['address']);
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/";
        $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
        $file_name = uniqid() . "." . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $target_file;
            $sql = "UPDATE client_profile SET 
                    company_name = '$company_name',
                    website = '$website',
                    address = '$address',
                    profile_picture = '$profile_picture'
                    WHERE id = " . $_SESSION['user_id'];
        }
    } else {
        $sql = "UPDATE client_profile SET 
                company_name = '$company_name',
                website = '$website',
                address = '$address'
                WHERE id = " . $_SESSION['user_id'];
    }
    
    if ($conn->query($sql)) {
        header("Location: profile_client.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - TaskMate</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
            padding: 30px;
        }

        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid white;
            margin-bottom: 20px;
            object-fit: cover;
        }

        .profile-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .profile-role {
            font-size: 16px;
            opacity: 0.9;
        }

        .profile-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .save-btn {
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: var(--primary-color);
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <a href="client_dash.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
    </a>

    <div class="profile-container">
        <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            Profile updated successfully!
        </div>
        <?php endif; ?>

        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/api/placeholder/150/150'); ?>" 
                 alt="Profile Picture" 
                 class="profile-picture">
            <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
            <div class="profile-role">Client</div>
        </div>

        <div class="profile-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Profile Picture</label>
                    <input type="file" 
                           name="profile_picture" 
                           accept="image/*" 
                           class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" 
                           name="name" 
                           value="<?php echo htmlspecialchars($user['name']); ?>" 
                           class="form-input" 
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           class="form-input" 
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mobile</label>
                    <input type="tel" 
                           name="mobile" 
                           value="<?php echo htmlspecialchars($user['mobile']); ?>" 
                           class="form-input" 
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" 
                           name="company_name" 
                           value="<?php echo htmlspecialchars($user['company_name']); ?>" 
                           class="form-input" 
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" 
                           name="website" 
                           value="<?php echo htmlspecialchars($user['website']); ?>" 
                           class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" 
                              class="form-input" 
                              rows="3" 
                              required><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>

                <button type="submit" class="save-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</body>
</html>