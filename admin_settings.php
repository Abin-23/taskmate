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
        } else if ($name === $user_data['name'] && $email === $user_data['email']) {
            $info_message = "No changes were made to your profile.";
        } else {
            $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != $user_id");
            if (mysqli_num_rows($check_email) > 0) {
                $error = "Email already exists!";
            } else {
                $update_query = "UPDATE users SET name='$name', email='$email' WHERE id=$user_id";
                if (mysqli_query($conn, $update_query)) {
                    $success_message = "Profile updated successfully!";
                    $result = mysqli_query($conn, $user_query);
                    $user_data = mysqli_fetch_assoc($result);
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
            $password_error = "New passwords do not match!";
        } else if (strlen($new_password) < 8) {
            $password_error = "Password must be at least 8 characters long!";
        } else {
            $password_query = "SELECT password FROM users WHERE id=$user_id";
            $result = mysqli_query($conn, $password_query);
            $user = mysqli_fetch_array($result);
            
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET password='$hashed_password' WHERE id=$user_id";
                if (mysqli_query($conn, $update_query)) {
                    $password_success = "Password updated successfully!";
                } else {
                    $password_error = "Error updating password: " . mysqli_error($conn);
                }
            } else {
                $password_error = "Current password is incorrect!";
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
            --error-color: #ef4444;
            --success-color: #10b981;
            --info-color: #0ea5e9;
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
            position: relative;
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

        .form-group input.error {
            border-color: var(--error-color);
        }

        .validation-message {
            font-size: 0.85em;
            margin-top: 5px;
            display: block;
        }

        .error-message {
            color: var(--error-color);
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

        .error-alert {
            background: #fee2e2;
            color: #b91c1c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-message {
            background: #e0f2fe;
            color: #0c4a6e;
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
            <li><a href="admindash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="admin_freelancer.php"><i class="fas fa-users"></i>Freelancers</a></li>
            <li><a href="admin_client.php"><i class="fas fa-user-tie"></i>Clients</a></li>
            <li><a href="jobs.php"><i class="fas fa-briefcase"></i>Jobs</a></li>
            <li><a href="admin_settings.php" class="active"><i class="fas fa-cog"></i>Settings</a></li>
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

            <?php if (isset($error)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($info_message)): ?>
            <div class="info-message">
                <i class="fas fa-info-circle"></i>
                <?php echo $info_message; ?>
            </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-header">
                    <i class="fas fa-user-circle"></i>
                    <h2>Profile Settings</h2>
                </div>
                <form method="POST" action="#" id="profileForm">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $user_data['name'];?>" data-original="<?php echo $user_data['name'];?>">
                        <span class="validation-message" id="nameValidation"></span>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $user_data['email'];?>" data-original="<?php echo $user_data['email'];?>">
                        <span class="validation-message" id="emailValidation"></span>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary" id="profileBtn">Update Profile</button>
                </form>
            </div>

            <div class="section">
                <div class="section-header">
                    <i class="fas fa-lock"></i>
                    <h2>Security Settings</h2>
                </div>
                <?php if (isset($password_success)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $password_success; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($password_error)): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $password_error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="#" id="passwordForm">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                        <span class="validation-message" id="currentPasswordValidation"></span>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                        <span class="validation-message" id="newPasswordValidation"></span>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                        <span class="validation-message" id="confirmPasswordValidation"></span>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-primary" id="passwordBtn">Update Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const menuToggle = document.createElement('button');
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.querySelector('.main-content').prepend(menuToggle);

        menuToggle.addEventListener('click', () => {
            document.body.classList.toggle('show-sidebar');
        });

        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const nameValidation = document.getElementById('nameValidation');
        const emailValidation = document.getElementById('emailValidation');
        const profileBtn = document.getElementById('profileBtn');
        
        const currentPasswordInput = document.getElementById('current_password');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const currentPasswordValidation = document.getElementById('currentPasswordValidation');
        const newPasswordValidation = document.getElementById('newPasswordValidation');
        const confirmPasswordValidation = document.getElementById('confirmPasswordValidation');
        const passwordBtn = document.getElementById('passwordBtn');

        function validateProfileForm() {
            let isValid = true;
            
            if (nameInput.value.trim() === '') {
                nameValidation.textContent = 'Name is required';
                nameValidation.className = 'validation-message error-message';
                nameInput.classList.add('error');
                isValid = false;
            } else {
                nameValidation.textContent = '';
                nameInput.classList.remove('error');
            }
            
            if (emailInput.value.trim() === '') {
                emailValidation.textContent = 'Email is required';
                emailValidation.className = 'validation-message error-message';
                emailInput.classList.add('error');
                isValid = false;
            } else if (!/^\S+@\S+\.\S+$/.test(emailInput.value.trim())) {
                emailValidation.textContent = 'Please enter a valid email address';
                emailValidation.className = 'validation-message error-message';
                emailInput.classList.add('error');
                isValid = false;
            } else {
                emailValidation.textContent = '';
                emailInput.classList.remove('error');
            }
            
            const nameOriginal = nameInput.getAttribute('data-original');
            const emailOriginal = emailInput.getAttribute('data-original');
            
            if (nameInput.value.trim() === nameOriginal && emailInput.value.trim() === emailOriginal) {
                profileBtn.disabled = true;
                profileBtn.style.opacity = '0.7';
                profileBtn.style.cursor = 'not-allowed';
            } else {
                profileBtn.disabled = false;
                profileBtn.style.opacity = '1';
                profileBtn.style.cursor = 'pointer';
            }
            
            return isValid;
        }

        function validatePasswordForm() {
            let isValid = true;
            
            if (currentPasswordInput.value.trim() === '') {
                currentPasswordValidation.textContent = 'Current password is required';
                currentPasswordValidation.className = 'validation-message error-message';
                currentPasswordInput.classList.add('error');
                isValid = false;
            } else {
                currentPasswordValidation.textContent = '';
                currentPasswordInput.classList.remove('error');
            }
            
            if (newPasswordInput.value.trim() === '') {
                newPasswordValidation.textContent = 'New password is required';
                newPasswordValidation.className = 'validation-message error-message';
                newPasswordInput.classList.add('error');
                isValid = false;
            } else if (newPasswordInput.value.length < 8) {
                newPasswordValidation.textContent = 'Password must be at least 8 characters long';
                newPasswordValidation.className = 'validation-message error-message';
                newPasswordInput.classList.add('error');
                isValid = false;
            } else {
                newPasswordValidation.textContent = '';
                newPasswordInput.classList.remove('error');
            }
            
            if (confirmPasswordInput.value.trim() === '') {
                confirmPasswordValidation.textContent = 'Please confirm your new password';
                confirmPasswordValidation.className = 'validation-message error-message';
                confirmPasswordInput.classList.add('error');
                isValid = false;
            } else if (confirmPasswordInput.value !== newPasswordInput.value) {
                confirmPasswordValidation.textContent = 'Passwords do not match';
                confirmPasswordValidation.className = 'validation-message error-message';
                confirmPasswordInput.classList.add('error');
                isValid = false;
            } else {
                confirmPasswordValidation.textContent = '';
                confirmPasswordInput.classList.remove('error');
            }
            
            return isValid;
        }

        nameInput.addEventListener('input', validateProfileForm);
        emailInput.addEventListener('input', validateProfileForm);
        
        currentPasswordInput.addEventListener('input', validatePasswordForm);
        newPasswordInput.addEventListener('input', validatePasswordForm);
        confirmPasswordInput.addEventListener('input', validatePasswordForm);
        
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            if (!validateProfileForm()) {
                e.preventDefault();
            }
        });
        
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            if (!validatePasswordForm()) {
                e.preventDefault();
            }
        });
        
        validateProfileForm();
    </script>
</body>
</html>

<?php
$conn->close();
?>