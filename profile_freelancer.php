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

// Fetch user data
$user_id = $_SESSION['user_id'];
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
    // Update user table
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    
    $sql_user = "UPDATE users SET name=?, email=?, mobile=? WHERE id=?";
    $stmt = $conn->prepare($sql_user);
    $stmt->bind_param("sssi", $name, $email, $mobile, $user_id);
    $stmt->execute();

    // Update freelancer_profile table
    $bio = $_POST['bio'];
    $experience = $_POST['experience'];
    $portfolio = $_POST['portfolio_link'];

    // Handle profile picture upload
    $profile_picture = $user['profile_picture']; // Keep existing by default
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $target_dir = "uploads/";
        $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid('profile_') . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $target_file;
        }
    }

    $sql_profile = "UPDATE freelancer_profile 
                    SET bio=?, experience=?, portfolio_link=?, profile_picture=? 
                    WHERE user_id=?";
    $stmt = $conn->prepare($sql_profile);
    $stmt->bind_param("ssssi", $bio, $experience, $portfolio, $profile_picture, $user_id);
    $stmt->execute();

    // Update skills
    if (isset($_POST['skills'])) {
        // First, remove existing skills
        $sql_delete_skills = "DELETE fs FROM freelancer_skills fs 
                             JOIN freelancer_profile fp ON fs.profile_id = fp.profile_id 
                             WHERE fp.user_id = ?";
        $stmt = $conn->prepare($sql_delete_skills);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Then add new skills
        $sql_get_profile = "SELECT profile_id FROM freelancer_profile WHERE user_id = ?";
        $stmt = $conn->prepare($sql_get_profile);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $profile_result = $stmt->get_result();
        $profile = $profile_result->fetch_assoc();

        foreach ($_POST['skills'] as $skill_id) {
            $sql_insert_skill = "INSERT INTO freelancer_skills (profile_id, skill_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql_insert_skill);
            $stmt->bind_param("ii", $profile['profile_id'], $skill_id);
            $stmt->execute();
        }
    }

    // Redirect to refresh the page
    header("Location: freelancer_profile.php?updated=1");
    exit();
}

// Fetch all available skills
$sql_skills = "SELECT * FROM skills ORDER BY category, skill_name";
$skills_result = $conn->query($sql_skills);
$all_skills = [];
while ($skill = $skills_result->fetch_assoc()) {
    $all_skills[$skill['category']][] = $skill;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Profile</title>
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
            --header-height: 70px;
            --gradient-start: #3b82f6;
            --gradient-end: #60a5fa;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
        }

        /* Animations */
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            animation: slideIn 0.5s ease;
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
            color: #1e293b;
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
            animation: fadeIn 0.5s ease;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: white;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .search-bar {
            flex: 1;
            margin: 0 30px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.2em;
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
            transition: all 0.3s ease;
        }

        .notification:hover {
            background: #f1f5f9;
        }

        .notification i {
            font-size: 1.2em;
            color: #64748b;
        }

        .notification-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid white;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-card h3 i {
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .stat-trend {
            font-size: 0.875rem;
            color: #22c55e;
        }

        /* Recent Jobs */
        .recent-jobs {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .recent-jobs h2 {
            margin-bottom: 20px;
            color: #1e293b;
            font-size: 1.5rem;
        }

        .jobs-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .jobs-table th {
            padding: 15px;
            text-align: left;
            color: #64748b;
            font-weight: 600;
            border-bottom: 2px solid #f1f5f9;
        }

        .jobs-table td {
            padding: 15px;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
        }

        .jobs-table tr td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            border-left: 1px solid #f1f5f9;
        }

        .jobs-table tr td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            border-right: 1px solid #f1f5f9;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-review {
            background: #fef3c7;
            color: #92400e;
        }

        .project-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .project-icon {
            width: 40px;
            height: 40px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .main-content {
                margin-left: 0;
            }

            .show-sidebar .sidebar {
                transform: translateX(0);
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .search-bar {
                margin: 15px 0;
                width: 100%;
            }

            .jobs-table {
                display: block;
                overflow-x: auto;
            }
        }

        .profile-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
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
            border: 3px solid white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .profile-picture .edit-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        .form-group input[type="tel"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
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
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }

        .skill-checkbox:checked + .skill-label {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .submit-btn {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
    <div class="sidebar">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        <ul class="nav-links">
            <li><a href="freelancer_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="#"><i class="fas fa-briefcase"></i>My Jobs</a></li>
            <li><a href="#" class="active"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="#"><i class="fas fa-wallet"></i>Earnings</a></li>
            <li><a href="freelancer_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="user-info">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="notification-dot"></div>
                </div>
                <img src="<?php echo $user['profile_picture'] ?? '/api/placeholder/40/40'; ?>" alt="Profile" 
                     style="width: 40px; height: 40px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            Profile updated successfully!
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="profile-section">
                <div class="profile-header">
                    <div class="profile-picture">
                        <img src="<?php echo $user['profile_picture'] ?? '/api/placeholder/120/120'; ?>" alt="Profile Picture">
                        <label for="profile_picture" class="edit-overlay">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" style="display: none;" accept="image/*">
                    </div>
                    <div>
                        <h1 style="color: #1e293b; margin-bottom: 5px;"><?php echo htmlspecialchars($user['name']); ?></h1>
                        <p style="color: #64748b;"><?php echo htmlspecialchars($user['email']); ?></p>
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
            </div>

            <div class="profile-section">
                <h2 style="margin-bottom: 20px; color: #1e293b;">Skills</h2>
                <?php foreach ($all_skills as $category => $skills): ?>
                    <h3 style="color: #64748b; margin-bottom: 10px;"><?php echo $category; ?></h3>
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
        // Menu toggle functionality
        const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        menuToggle.addEventListener('click', () => {
            body.classList.toggle('show-sidebar');
        });

        // Profile picture preview
        const profilePicInput = document.getElementById('profile_picture');
        const profilePicPreview = document.querySelector('.profile-picture img');

        profilePicInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePicPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const mobile = document.getElementById('mobile').value;
            const portfolio = document.getElementById('portfolio_link').value;

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }

            // Mobile validation
            const mobileRegex = /^\+?[\d\s-]{10,15}$/;
            if (!mobileRegex.test(mobile)) {
                e.preventDefault();
                alert('Please enter a valid mobile number');
                return;
            }

            // Portfolio link validation (optional)
            if (portfolio && !portfolio.startsWith('http://') && !portfolio.startsWith('https://')) {
                e.preventDefault();
                alert('Portfolio link must start with http:// or https://');
                return;
            }
        });

        // Skills selection limit
        const skillCheckboxes = document.querySelectorAll('.skill-checkbox');
        const MAX_SKILLS = 10;

        skillCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedSkills = document.querySelectorAll('.skill-checkbox:checked');
                if (checkedSkills.length > MAX_SKILLS) {
                    this.checked = false;
                    alert(`You can select a maximum of ${MAX_SKILLS} skills`);
                }
            });
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add unsaved changes warning
        let formChanged = false;
        const formInputs = form.querySelectorAll('input, textarea, select');

        formInputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });

        // Reset warning when form is submitted
        form.addEventListener('submit', () => {
            formChanged = false;
        });

        // Add logout button functionality
        const logoutBtn = document.createElement('a');
        logoutBtn.href = 'logout.php';
        logoutBtn.className = 'logout-btn';
        logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Logout';
        logoutBtn.style.cssText = `
            padding: 8px 16px;
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            margin-left: 20px;
        `;

        document.querySelector('.user-info').appendChild(logoutBtn);
    </script>
</body>
</html>