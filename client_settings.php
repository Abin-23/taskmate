<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "taskmate";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        if (empty($name) || empty($email)) {
            $error = "Name and email are required fields.";
        } else {
            $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != $user_id");
            if (mysqli_num_rows($check_email) > 0) {
                $error = "Email already exists!";
            } else {
                $update_query = "UPDATE users SET name='$name', email='$email' WHERE id=$user_id";
                if (mysqli_query($conn, $update_query)) {
                    $success_message = "Profile updated successfully!";
                } else {
                    $error = "Error updating profile: " . mysqli_error($conn);
                }
            }
        }
    }
    
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password != $confirm_password) {
            $error = "New passwords do not match!";
        } else if (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long!";
        } else {
            $password_query = "SELECT password FROM users WHERE id=$user_id";
            $result = mysqli_query($conn, $password_query);
            $user = mysqli_fetch_array($result);
            
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET password='$hashed_password' WHERE id=$user_id";
                if (mysqli_query($conn, $update_query)) {
                    $success_message = "Password updated successfully!";
                } else {
                    $error = "Error updating password: " . mysqli_error($conn);
                }
            } else {
                $error = "Current password is incorrect!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Settings</title>
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
            --sidebar-width: 280px;
            --gradient-start: #3b82f6;
            --gradient-end: #60a5fa;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: white;
            padding: 25px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            padding-bottom: 25px;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 25px;
        }

        .logo span:first-child {
            font-size: 24px;
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            color: transparent;
            font-weight: 700;
        }

        .logo span:last-child {
            font-size: 24px;
            color: #2563eb;
            font-weight: 700;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 8px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #64748b;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-links a:hover {
            background: #f8fafc;
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .nav-links a.active {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            color: white;
        }

        .nav-links a i {
            margin-right: 12px;
            width: 20px;
            font-size: 1.2em;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }

        .settings-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-header i {
            color: var(--primary-color);
            font-size: 1.2em;
        }

        .section-header h2 {
            color: #1e293b;
            font-size: 1.2em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1e293b;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .toggle-switch input[type="checkbox"] {
            display: none;
        }

        .toggle-switch label {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
            background: #e2e8f0;
            border-radius: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-switch label:after {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: white;
            top: 2px;
            left: 2px;
            transition: all 0.3s ease;
        }

        .toggle-switch input[type="checkbox"]:checked + label {
            background: var(--primary-color);
        }

        .toggle-switch input[type="checkbox"]:checked + label:after {
            left: 26px;
        }

        .toggle-switch span {
            color: #64748b;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
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

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .show-sidebar .sidebar {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        <ul class="nav-links">
        <li><a href="client_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="#"><i class="fas fa-briefcase"></i>My Jobs</a></li>
            <li><a href="profile_client.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="#"><i class="fas fa-wallet"></i>Earnings</a></li>
            <li><a href="client_settings.php"class="active"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="settings-container">
            <?php if (isset($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-header">
                    <i class="fas fa-user-circle"></i>
                    <h2>Profile Settings</h2>
                </div>
                <form method="POST" action="#">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $user_data['name'];?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $user_data['email'];?>">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <div class="section">
                <div class="section-header">
                    <i class="fas fa-lock"></i>
                    <h2>Security Settings</h2>
                </div>
                <form method="POST" action="#">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                    <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                </form>
            </div>

                

    <script>
        // Mobile menu toggle
        const menuToggle = document.createElement('button');
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.querySelector('.main-content').prepend(menuToggle);

        menuToggle.addEventListener('click', () => {
            document.body.classList.toggle('show-sidebar');
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>