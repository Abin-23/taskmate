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
    
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Enhanced password validation
        $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
        
        if ($new_password != $confirm_password) {
            $error = "New passwords do not match!";
        } else if (!preg_match($password_pattern, $new_password)) {
            $error = "Password must be at least 8 characters with at least one uppercase letter, one lowercase letter, and one number!";
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

        .validation-criteria {
            font-size: 0.85em;
            margin-top: 5px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .criteria {
            color: #64748b;
        }

        .criteria.valid {
            color: var(--success-color);
        }

        .criteria.invalid {
            color: var(--error-color);
        }

        .criteria i {
            margin-right: 5px;
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

        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            z-index: 2000;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            cursor: pointer;
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

            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
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
            <li><a href="client_project.php" ><i class="fas fa-list"></i>My Projects</a></li>
            <li><a href="profile_client.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="payments.php"><i class="fas fa-wallet"></i>Payments</a></li>
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
                        <div class="validation-criteria" id="passwordCriteria">
                            <div class="criteria" id="lengthCriteria"><i class="fas fa-circle"></i> At least 8 characters</div>
                            <div class="criteria" id="uppercaseCriteria"><i class="fas fa-circle"></i> At least 1 uppercase letter</div>
                            <div class="criteria" id="lowercaseCriteria"><i class="fas fa-circle"></i> At least 1 lowercase letter</div>
                            <div class="criteria" id="numberCriteria"><i class="fas fa-circle"></i> At least 1 number</div>
                        </div>
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
        
        const currentPasswordInput = document.getElementById('current_password');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const currentPasswordValidation = document.getElementById('currentPasswordValidation');
        const confirmPasswordValidation = document.getElementById('confirmPasswordValidation');
        const passwordBtn = document.getElementById('passwordBtn');
        
        // Password criteria elements
        const lengthCriteria = document.getElementById('lengthCriteria');
        const uppercaseCriteria = document.getElementById('uppercaseCriteria');
        const lowercaseCriteria = document.getElementById('lowercaseCriteria');
        const numberCriteria = document.getElementById('numberCriteria');

        // Live password validation
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            
            // Check length
            if (password.length >= 8) {
                lengthCriteria.className = 'criteria valid';
                lengthCriteria.innerHTML = '<i class="fas fa-check-circle"></i> At least 8 characters';
            } else {
                lengthCriteria.className = 'criteria invalid';
                lengthCriteria.innerHTML = '<i class="fas fa-times-circle"></i> At least 8 characters';
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                uppercaseCriteria.className = 'criteria valid';
                uppercaseCriteria.innerHTML = '<i class="fas fa-check-circle"></i> At least 1 uppercase letter';
            } else {
                uppercaseCriteria.className = 'criteria invalid';
                uppercaseCriteria.innerHTML = '<i class="fas fa-times-circle"></i> At least 1 uppercase letter';
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                lowercaseCriteria.className = 'criteria valid';
                lowercaseCriteria.innerHTML = '<i class="fas fa-check-circle"></i> At least 1 lowercase letter';
            } else {
                lowercaseCriteria.className = 'criteria invalid';
                lowercaseCriteria.innerHTML = '<i class="fas fa-times-circle"></i> At least 1 lowercase letter';
            }
            
            // Check number
            if (/[0-9]/.test(password)) {
                numberCriteria.className = 'criteria valid';
                numberCriteria.innerHTML = '<i class="fas fa-check-circle"></i> At least 1 number';
            } else {
                numberCriteria.className = 'criteria invalid';
                numberCriteria.innerHTML = '<i class="fas fa-times-circle"></i> At least 1 number';
            }
            
            // Check if confirmation matches
            if (confirmPasswordInput.value) {
                validatePasswordMatch();
            }
        });
        
        // Validate password match
        function validatePasswordMatch() {
            if (confirmPasswordInput.value !== newPasswordInput.value) {
                confirmPasswordValidation.textContent = 'Passwords do not match';
                confirmPasswordValidation.className = 'validation-message error-message';
                confirmPasswordInput.classList.add('error');
                return false;
            } else {
                confirmPasswordValidation.textContent = '';
                confirmPasswordInput.classList.remove('error');
                return true;
            }
        }
        
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);

        function validatePasswordForm() {
            let isValid = true;
            
            // Validate current password
            if (currentPasswordInput.value.trim() === '') {
                currentPasswordValidation.textContent = 'Current password is required';
                currentPasswordValidation.className = 'validation-message error-message';
                currentPasswordInput.classList.add('error');
                isValid = false;
            } else {
                currentPasswordValidation.textContent = '';
                currentPasswordInput.classList.remove('error');
            }
            
            // Validate new password
            const password = newPasswordInput.value;
            const passwordValid = password.length >= 8 && 
                                  /[A-Z]/.test(password) && 
                                  /[a-z]/.test(password) && 
                                  /[0-9]/.test(password);
            
            if (!passwordValid) {
                isValid = false;
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
        
        currentPasswordInput.addEventListener('input', validatePasswordForm);
        
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            if (!validatePasswordForm()) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>