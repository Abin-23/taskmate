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

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT u.*, fp.*, GROUP_CONCAT(s.skill_name) as skills 
        FROM users u 
        LEFT JOIN freelancer_profile fp ON u.id = fp.user_id 
        LEFT JOIN freelancer_skills fs ON fp.profile_id = fs.profile_id 
        LEFT JOIN skills s ON fs.skill_id = s.id 
        WHERE u.id = ? 
        GROUP BY u.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $mobile = $conn->real_escape_string($_POST['mobile']);
    $bio = $conn->real_escape_string($_POST['bio']);
    $experience = $conn->real_escape_string($_POST['experience']);
    $portfolio = $conn->real_escape_string($_POST['portfolio_link'] ?? '');
    $upi_id = $conn->real_escape_string($_POST['upi_id']);

    // Profile picture handling
    $profile_picture = $user['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid('profile_') . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $target_file;
        }
    }

    // Update users table
    $sql_user = "UPDATE users SET name=?, email=?, mobile=? WHERE id=?";
    $stmt = $conn->prepare($sql_user);
    $stmt->bind_param("sssi", $name, $email, $mobile, $user_id);
    $stmt->execute();

    // Update or insert freelancer_profile including upi_id
    $sql_profile = "INSERT INTO freelancer_profile (user_id, bio, experience, portfolio_link, profile_picture, upi_id) 
                    VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE bio=?, experience=?, portfolio_link=?, profile_picture=?, upi_id=?";
    $stmt = $conn->prepare($sql_profile);
    $stmt->bind_param("issssssssss", $user_id, $bio, $experience, $portfolio, $profile_picture, $upi_id, 
                     $bio, $experience, $portfolio, $profile_picture, $upi_id);
    $stmt->execute();

    // Update skills
    if (isset($_POST['skills'])) {
        $sql_profile_id = "SELECT profile_id FROM freelancer_profile WHERE user_id=?";
        $stmt = $conn->prepare($sql_profile_id);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $profile_id = $stmt->get_result()->fetch_assoc()['profile_id'];

        $sql_delete = "DELETE FROM freelancer_skills WHERE profile_id=?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $profile_id);
        $stmt->execute();

        foreach ($_POST['skills'] as $skill_id) {
            $sql_insert = "INSERT INTO freelancer_skills (profile_id, skill_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql_insert);
            $stmt->bind_param("ii", $profile_id, $skill_id);
            $stmt->execute();
        }
    }

    header("Location: freelancer_profile.php?updated=1");
    exit();
}

// Fetch all skills
$sql_skills = "SELECT * FROM skills ORDER BY category, skill_name";
$skills_result = $conn->query($sql_skills);
$all_skills = [];
while ($skill = $skills_result->fetch_assoc()) {
    $all_skills[$skill['category']][] = $skill;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Freelancer Profile</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        :root {
            --color-primary: #3b82f6;
            --color-primary-dark: #2563eb;
            --color-primary-light: #60a5fa;
            --color-bg: #f8fafc;
            --color-white: #ffffff;
            --color-text: #1e293b;
            --color-text-light: #64748b;
            --color-border: #e2e8f0;
            --color-border-light: #f1f5f9;
            --color-success: #22c55e;
            --color-success-bg: #dcfce7;
            --color-success-text: #166534;
            --color-warning-bg: #fef3c7;
            --color-warning-text: #92400e;
            --color-danger: #ef4444;
            --sidebar-width: 280px;
            --header-height: 70px;
            --border-radius-sm: 8px;
            --border-radius-md: 12px;
            --border-radius-lg: 16px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background: var(--color-bg);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--color-white);
            padding: 25px;
            box-shadow: var(--box-shadow);
            animation: slideIn 0.5s ease;
            z-index: 1000;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            animation: fadeIn 0.5s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: var(--color-white);
            border-radius: var(--border-radius-lg);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }

        .logo {
            display: flex;
            align-items: center;
            padding-bottom: 25px;
            border-bottom: 2px solid var(--color-border-light);
            margin-bottom: 25px;
        }

        .logo span:first-child {
            font-size: 24px;
            background: linear-gradient(to right, var(--color-primary), var(--color-primary-light));
            -webkit-background-clip: text;
            color: transparent;
            font-weight: 700;
        }

        .logo span:last-child {
            font-size: 24px;
            color: var(--color-text);
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
            color: var(--color-text-light);
            text-decoration: none;
            border-radius: var(--border-radius-md);
            transition: var(--transition);
            font-weight: 500;
        }

        .nav-links a i {
            margin-right: 12px;
            width: 20px;
            font-size: 1.2em;
        }

        .nav-links a:hover {
            background: var(--color-bg);
            color: var(--color-primary);
            transform: translateX(5px);
        }

        .nav-links a.active {
            background: linear-gradient(to right, var(--color-primary), var(--color-primary-light));
            color: var(--color-white);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification {
            position: relative;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: var(--transition);
        }

        .notification:hover {
            background: var(--color-border-light);
        }

        .notification i {
            font-size: 1.2em;
            color: var(--color-text-light);
        }

        .notification-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            background: var(--color-danger);
            border-radius: 50%;
            border: 2px solid var(--color-white);
        }

        .profile-section {
            background: var(--color-white);
            padding: 25px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--color-border-light);
        }

        .profile-picture {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--color-white);
            box-shadow: var(--box-shadow);
        }

        .profile-picture .edit-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--color-primary);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-white);
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .profile-header h1 {
            color: var(--color-text);
            margin-bottom: 5px;
        }

        .profile-header p {
            color: var(--color-text-light);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--color-text);
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--color-border);
            border-radius: var(--border-radius-sm);
            font-size: 15px;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--color-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .skill-checkbox {
            display: none;
        }

        .skill-label {
            display: block;
            padding: 10px;
            background: var(--color-bg);
            border: 2px solid var(--color-border);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            text-align: center;
            transition: var(--transition);
        }

        .skill-checkbox:checked + .skill-label {
            background: var(--color-primary);
            color: var(--color-white);
            border-color: var(--color-primary);
        }

        .profile-section h2 {
            margin-bottom: 20px;
            color: var(--color-text);
        }

        .profile-section h3 {
            color: var(--color-text-light);
            margin-bottom: 10px;
        }

        .submit-btn {
            background: linear-gradient(to right, var(--color-primary), var(--color-primary-light));
            color: var(--color-white);
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow);
        }

        .menu-toggle {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2em;
            color: var(--color-text-light);
            display: none;
        }

        .success-message {
            background: var(--color-success-bg);
            color: var(--color-success-text);
            padding: 15px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        input[type="file"] {
            display: none;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
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
            <li><a href="freelancer_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="freelancer_job.php"><i class="fas fa-briefcase"></i>My Jobs</a></li>
            <li><a href="profile_freelancer.php" class="active"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="earnings.php"><i class="fas fa-wallet"></i>Earnings</a></li>
            <li><a href="freelancer_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <button class="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="user-info">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="notification-dot"></div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Profile updated successfully!
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="profile-section">
                <div class="profile-header">
                    <div class="profile-picture">
                        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/api/placeholder/120/120'); ?>" 
                             alt="Profile Picture" id="profile-preview">
                        <label for="profile_picture" class="edit-overlay">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                    </div>
                    <div>
                        <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile</label>
                    <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="experience">Experience Level</label>
                    <select id="experience" name="experience" required>
                        <option value="Beginner" <?php echo ($user['experience'] ?? '') === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                        <option value="Intermediate" <?php echo ($user['experience'] ?? '') === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="Expert" <?php echo ($user['experience'] ?? '') === 'Expert' ? 'selected' : ''; ?>>Expert</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="portfolio_link">Portfolio Link</label>
                    <input type="text" id="portfolio_link" name="portfolio_link" 
                           value="<?php echo htmlspecialchars($user['portfolio_link'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="upi_id">UPI ID</label>
                    <input type="text" id="upi_id" name="upi_id" 
                           value="<?php echo htmlspecialchars($user['upi_id'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="profile-section">
                <h2>Skills</h2>
                <?php foreach ($all_skills as $category => $skills): ?>
                    <h3><?php echo htmlspecialchars($category); ?></h3>
                    <div class="skills-grid">
                        <?php foreach ($skills as $skill): ?>
                            <div>
                                <input type="checkbox" id="skill_<?php echo $skill['id']; ?>" 
                                       name="skills[]" value="<?php echo $skill['id']; ?>" 
                                       class="skill-checkbox"
                                       <?php echo strpos($user['skills'] ?? '', $skill['skill_name']) !== false ? 'checked' : ''; ?>>
                                <label for="skill_<?php echo $skill['id']; ?>" class="skill-label">
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const mobileInput = document.getElementById('mobile');
    const bioInput = document.getElementById('bio');
    const experienceInput = document.getElementById('experience');
    const portfolioInput = document.getElementById('portfolio_link');
    const profilePicInput = document.getElementById('profile_picture');
    const upiInput = document.getElementById('upi_id');
    const submitBtn = document.querySelector('.submit-btn');
    const skillCheckboxes = document.querySelectorAll('.skill-checkbox');

    // Original values
    const originalValues = {
        name: '<?php echo addslashes($user['name']); ?>',
        email: '<?php echo addslashes($user['email']); ?>',
        mobile: '<?php echo addslashes($user['mobile']); ?>',
        bio: '<?php echo addslashes($user['bio'] ?? ''); ?>',
        experience: '<?php echo addslashes($user['experience'] ?? ''); ?>',
        portfolio: '<?php echo addslashes($user['portfolio_link'] ?? ''); ?>',
        profile_picture: '',
        upi_id: '<?php echo addslashes($user['upi_id'] ?? ''); ?>'
    };
    const originalSkills = [<?php 
        $skills_array = explode(',', $user['skills'] ?? '');
        echo implode(',', array_map(function($skill) { return "'".addslashes(trim($skill))."'"; }, $skills_array));
    ?>].filter(Boolean);

    // Validation messages
    const validationMessages = {};
    [nameInput, emailInput, mobileInput, bioInput, experienceInput, portfolioInput, profilePicInput, upiInput]
        .forEach(input => {
            validationMessages[input.id] = createValidationMessage(input);
        });

    let emailCheckTimeout = null;
    let isEmailChecking = false;
    let emailAvailable = true;
    let profilePicValid = true;

    function createValidationMessage(input) {
        const msg = document.createElement('div');
        msg.className = 'validation-message';
        msg.style.cssText = `
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 5px;
            display: none;
        `;
        input.parentNode.appendChild(msg);
        return msg;
    }

    // Validation functions
    function validateName() {
        const value = nameInput.value.trim();
        if (!value) return showError(nameInput, 'Name is required');
        if (value.length < 3) return showError(nameInput, 'Name must be at least 3 characters');
        return showSuccess(nameInput);
    }

    function validateEmail() {
        const value = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!value) return showError(emailInput, 'Email is required');
        if (!emailRegex.test(value)) return showError(emailInput, 'Please enter a valid email');
        if (!emailAvailable && !isEmailChecking && value !== originalValues.email) {
            return showError(emailInput, 'Email is already taken');
        }
        return showSuccess(emailInput);
    }

    function validateMobile() {
        const value = mobileInput.value.trim();
        const mobileRegex = /^(\+91[\s-]?)?[6-9]\d{9}$/;
        if (!value) return showError(mobileInput, 'Mobile number is required');
        if (!mobileRegex.test(value)) return showError(mobileInput, 'Enter a valid Indian mobile number');
        return showSuccess(mobileInput);
    }

    function validateBio() {
        const value = bioInput.value.trim();
        if (!value) return showError(bioInput, 'Bio is required');
        if (value.length < 10) return showError(bioInput, 'Bio must be at least 10 characters');
        if (value.length > 500) return showError(bioInput, 'Bio cannot exceed 500 characters');
        return showSuccess(bioInput);
    }

    function validateExperience() {
        const value = experienceInput.value;
        const validOptions = ['Beginner', 'Intermediate', 'Expert'];
        if (!value || !validOptions.includes(value)) {
            return showError(experienceInput, 'Please select a valid experience level');
        }
        return showSuccess(experienceInput);
    }

    function validatePortfolio() {
        const value = portfolioInput.value.trim();
        if (!value) return showSuccess(portfolioInput);
        const urlRegex = /^(https?:\/\/)?([\w\-]+\.)+[a-z]{2,6}(:\d{1,5})?(\/.*)?$/i;
        if (!urlRegex.test(value)) {
            return showError(portfolioInput, 'Enter a valid URL');
        }
        return showSuccess(portfolioInput);
    }

    function validateProfilePic() {
        if (profilePicInput.files.length > 0) {
            const file = profilePicInput.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                profilePicValid = false;
                return showError(profilePicInput, 'Only JPG, PNG, or GIF allowed');
            }
            if (file.size > 2 * 1024 * 1024) {
                profilePicValid = false;
                return showError(profilePicInput, 'Image must be less than 2MB');
            }
            profilePicValid = true;
            previewImage(file);
        }
        return showSuccess(profilePicInput);
    }

    function validateUpi() {
        const value = upiInput.value.trim();
        const upiRegex = /^[a-zA-Z0-9.-]{2,256}@[a-zA-Z][a-zA-Z]{2,64}$/;
        if (!value) return showError(upiInput, 'UPI ID is required');
        if (!upiRegex.test(value)) return showError(upiInput, 'Enter a valid UPI ID (e.g., name@bank)');
        return showSuccess(upiInput);
    }

    function validateSkills() {
        return Array.from(skillCheckboxes).filter(cb => cb.checked).length >= 1;
    }

    function showError(input, message) {
        const msg = validationMessages[input.id];
        msg.textContent = message;
        msg.style.display = 'block';
        msg.style.color = '#ef4444';
        input.style.borderColor = '#ef4444';
        return false;
    }

    function showSuccess(input) {
        const msg = validationMessages[input.id];
        msg.style.display = 'none';
        input.style.borderColor = '#22c55e';
        return true;
    }

    function checkEmailAvailability() {
        const email = emailInput.value.trim();
        if (!validateEmail() || email === originalValues.email) return;

        isEmailChecking = true;
        showLoading(emailInput, 'Checking email...');

        const formData = new FormData();
        formData.append('email', email);

        fetch('check_email.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(data => {
            isEmailChecking = false;
            switch (data.trim()) {
                case 'taken':
                    emailAvailable = false;
                    showError(emailInput, 'This email is already registered');
                    break;
                case 'available':
                    emailAvailable = true;
                    showSuccess(emailInput);
                    break;
                case 'invalid_domain':
                    emailAvailable = false;
                    showError(emailInput, 'Invalid email domain');
                    break;
                default:
                    // Handle unexpected responses (e.g., connection errors)
                    emailAvailable = true; // Default to available to not block user
                    showError(emailInput, 'Error checking email availability');
                    console.error('Unexpected response:', data);
                    break;
            }
            updateSubmitButton();
        })
        .catch(error => {
            isEmailChecking = false;
            emailAvailable = true; // Default to available on network error
            showError(emailInput, 'Network error checking email');
            console.error('Email check error:', error);
            updateSubmitButton();
        });
    }

    function showLoading(input, message) {
        const msg = validationMessages[input.id];
        msg.textContent = message;
        msg.style.display = 'block';
        msg.style.color = '#6b7280';
        input.style.borderColor = '#e2e8f0';
    }

    function previewImage(file) {
        const preview = document.getElementById('profile-preview');
        const reader = new FileReader();
        reader.onload = (e) => preview.src = e.target.result;
        reader.readAsDataURL(file);
    }

    function hasChangesInTrackedFields() {
        const currentValues = {
            name: nameInput.value,
            email: emailInput.value,
            mobile: mobileInput.value,
            bio: bioInput.value,
            experience: experienceInput.value,
            portfolio: portfolioInput.value,
            profile_picture: profilePicInput.files.length > 0 ? 'changed' : '',
            upi_id: upiInput.value
        };
        const currentSkills = Array.from(skillCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.labels[0].textContent.trim());

        const valuesChanged = Object.keys(currentValues).some(key => 
            currentValues[key] !== originalValues[key]
        );
        const skillsChanged = JSON.stringify(currentSkills.sort()) !== 
                            JSON.stringify(originalSkills.sort());

        return valuesChanged || skillsChanged;
    }

    function updateSubmitButton() {
        const isValid = validateName() && validateEmail() && validateMobile() && 
                       validateBio() && validateExperience() && validatePortfolio() && 
                       validateProfilePic() && validateUpi() && validateSkills() && 
                       !isEmailChecking && emailAvailable;
        const hasChanges = hasChangesInTrackedFields();

        submitBtn.disabled = !(isValid && hasChanges);
        submitBtn.style.opacity = isValid && hasChanges ? '1' : '0.5';
        submitBtn.style.cursor = isValid && hasChanges ? 'pointer' : 'not-allowed';
    }

    // Event listeners
    nameInput.addEventListener('input', () => { validateName(); updateSubmitButton(); });
    emailInput.addEventListener('input', () => {
        clearTimeout(emailCheckTimeout);
        validateEmail();
        emailCheckTimeout = setTimeout(checkEmailAvailability, 600);
        updateSubmitButton();
    });
    mobileInput.addEventListener('input', () => { validateMobile(); updateSubmitButton(); });
    bioInput.addEventListener('input', () => { validateBio(); updateSubmitButton(); });
    experienceInput.addEventListener('change', () => { validateExperience(); updateSubmitButton(); });
    portfolioInput.addEventListener('input', () => { validatePortfolio(); updateSubmitButton(); });
    profilePicInput.addEventListener('change', () => { validateProfilePic(); updateSubmitButton(); });
    upiInput.addEventListener('input', () => { validateUpi(); updateSubmitButton(); });
    skillCheckboxes.forEach(cb => cb.addEventListener('change', updateSubmitButton));

    form.addEventListener('submit', (e) => {
        const validations = [
            validateName(),
            validateEmail(),
            validateMobile(),
            validateBio(),
            validateExperience(),
            validatePortfolio(),
            validateProfilePic(),
            validateUpi(),
            validateSkills()
        ];

        if (!validations.every(v => v) || isEmailChecking || !hasChangesInTrackedFields()) {
            e.preventDefault();
            const firstInvalid = form.querySelector('.form-group input[style*="border-color: rgb(239, 68, 68)"]') ||
                               form.querySelector('.form-group textarea[style*="border-color: rgb(239, 68, 68)"]') ||
                               form.querySelector('.form-group select[style*="border-color: rgb(239, 68, 68)"]');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
            if (!hasChangesInTrackedFields()) showNoChangesMessage();
            if (!validateSkills()) showSkillsError();
        }
    });

    function showNoChangesMessage() {
        const existing = document.querySelector('.no-changes-message');
        if (existing) existing.remove();
        const msg = document.createElement('div');
        msg.className = 'no-changes-message';
        msg.style.cssText = `
            background: #eff6ff;
            color: #1e40af;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        `;
        msg.innerHTML = '<i class="fas fa-info-circle"></i> No changes detected.';
        submitBtn.before(msg);
        setTimeout(() => msg.remove(), 5000);
    }

    function showSkillsError() {
        const skillsSection = document.querySelector('.profile-section:nth-child(2)');
        const existing = skillsSection.querySelector('.skills-error');
        if (existing) return;
        const msg = document.createElement('div');
        msg.className = 'skills-error';
        msg.style.cssText = 'color: #ef4444; margin: 10px 0;';
        msg.textContent = 'Please select at least one skill';
        skillsSection.appendChild(msg);
        setTimeout(() => msg.remove(), 5000);
    }

    const MAX_BIO_LENGTH = 500;
    const bioCounter = document.createElement('div');
    bioCounter.style.cssText = `
        font-size: 0.875rem;
        color: #64748b;
        text-align: right;
        margin-top: 5px;
    `;
    bioInput.after(bioCounter);

    function updateBioCounter() {
        const remaining = MAX_BIO_LENGTH - bioInput.value.length;
        bioCounter.textContent = `${remaining} characters remaining`;
        bioCounter.style.color = remaining < 50 ? '#f59e0b' : remaining < 0 ? '#ef4444' : '#64748b';
        if (remaining < 0) bioInput.value = bioInput.value.substring(0, MAX_BIO_LENGTH);
    }
    bioInput.addEventListener('input', updateBioCounter);
    updateBioCounter();

    updateSubmitButton();
});
</script>

</body>
</html>