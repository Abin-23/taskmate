<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "taskmate";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get freelancer ID from URL
$freelancer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch freelancer details with skills
$query ="SELECT 
u.id,
u.name,
u.email,
u.mobile,
u.created_at,
fp.bio,
fp.experience,
fp.portfolio_link,
fp.profile_picture,
GROUP_CONCAT(DISTINCT s.skill_name) as skills,
GROUP_CONCAT(DISTINCT s.category) as categories,
AVG(f.rating) as avg_rating,
COUNT(DISTINCT f.feedback_id) as feedback_count
FROM users u
INNER JOIN freelancer_profile fp ON u.id = fp.user_id
LEFT JOIN (
SELECT DISTINCT fs.profile_id, s.skill_name, s.category
FROM freelancer_skills fs
LEFT JOIN skills s ON fs.skill_id = s.id
) s ON fp.profile_id = s.profile_id
LEFT JOIN feedback f ON u.id = f.received_by
WHERE u.id = ? AND u.role = 'freelancer' AND u.active = 1
GROUP BY u.id";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $freelancer_id);
$stmt->execute();
$result = $stmt->get_result();
$freelancer = $result->fetch_assoc();

// If freelancer not found, redirect to freelancer list
if (!$freelancer) {
    header("Location: admin_freelancer.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - View Freelancer Profile</title>
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

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }

        .profile-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e2e8f0;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f1f5f9;
        }

        .profile-info h1 {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .profile-meta {
            display: flex;
            gap: 20px;
            color: #64748b;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-item i {
            color: var(--primary-color);
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .bio {
            color: #64748b;
            line-height: 1.6;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .skill-tag {
            background: #e0f2fe;
            color: #0369a1;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .skill-tag i {
            font-size: 12px;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .contact-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            transition: transform 0.3s ease;
        }

        .contact-item:hover {
            transform: translateY(-2px);
        }

        .contact-item h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .contact-item h3 i {
            color: var(--primary-color);
        }

        .contact-item p {
            color: #1e293b;
            font-weight: 500;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .rating-section {
    margin-bottom: 30px;
}

.rating-summary {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 20px;
}

.star-rating {
    color: #fbbf24;
    font-size: 20px;
}

.feedback-list {
    display: grid;
    gap: 15px;
}

.feedback-item {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    transition: transform 0.3s ease;
}

.feedback-item:hover {
    transform: translateY(-2px);
}

.feedback-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.feedback-rating {
    display: flex;
    gap: 5px;
    color: #fbbf24;
}

.feedback-comment {
    color: #64748b;
    line-height: 1.6;
}

.feedback-date {
    color: #94a3b8;
    font-size: 14px;
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

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-meta {
                justify-content: center;
            }

            .contact-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        <ul class="nav-links">
            <li><a href="client_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="client_project.php" class="active"><i class="fas fa-list"></i>My Projects</a></li>
            <li><a href="profile_client.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="payments.php"><i class="fas fa-wallet"></i>Payments</a></li>
            <li><a href="client_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <div class="profile-container">
            <div class="profile-header">
                <img src="<?php echo !empty($freelancer['profile_picture']) ? htmlspecialchars($freelancer['profile_picture']) : '/api/placeholder/120/120'; ?>" 
                     alt="<?php echo htmlspecialchars($freelancer['name']); ?>" 
                     class="profile-image">
                
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($freelancer['name']); ?></h1>
                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="fas fa-chart-line"></i>
                            <span><?php echo htmlspecialchars($freelancer['experience']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Member since <?php echo date('M Y', strtotime($freelancer['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-user"></i>
                    About
                </h2>
                <p class="bio"><?php echo nl2br(htmlspecialchars($freelancer['bio'])); ?></p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-code"></i>
                    Skills
                </h2>
                <div class="skills-list">
                    <?php
                    if (!empty($freelancer['skills'])) {
                        $skills = explode(',', $freelancer['skills']);
                        foreach ($skills as $skill) {
                            echo '<span class="skill-tag"><i class="fas fa-check-circle"></i>' . 
                                 htmlspecialchars(trim($skill)) . '</span>';
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-address-card"></i>
                    Contact Information
                </h2>
                <div class="contact-info">
                    <div class="contact-item">
                        <h3><i class="fas fa-envelope"></i>Email</h3>
                        <p><?php echo htmlspecialchars($freelancer['email']); ?></p>
                    </div>
                    <div class="contact-item">
                        <h3><i class="fas fa-phone"></i>Phone</h3>
                        <p><?php echo htmlspecialchars($freelancer['mobile']); ?></p>
                    </div>
                    <?php if (!empty($freelancer['portfolio_link'])): ?>
                    <div class="contact-item">
                        <h3><i class="fas fa-globe"></i>Portfolio</h3>
                        <p><a href="<?php echo htmlspecialchars($freelancer['portfolio_link']); ?>" 
                              target="_blank"
                              style="color: var(--primary-color); text-decoration: none;">
                            View Portfolio <i class="fas fa-external-link-alt"></i>
                        </a></p>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Add this after the Contact Information section -->
<div class="section rating-section">
    <h2 class="section-title">
        <i class="fas fa-star"></i>
        Feedback & Ratings
    </h2>
    
    <div class="rating-summary">
        <div class="star-rating">
            <?php
            $rating = round($freelancer['avg_rating'], 1);
            $full_stars = floor($rating);
            $half_star = ($rating - $full_stars) >= 0.5;
            
            // Full stars
            for ($i = 0; $i < $full_stars; $i++) {
                echo '<i class="fas fa-star"></i>';
            }
            // Half star
            if ($half_star) {
                echo '<i class="fas fa-star-half-alt"></i>';
            }
            // Empty stars
            for ($i = $full_stars + ($half_star ? 1 : 0); $i < 5; $i++) {
                echo '<i class="far fa-star"></i>';
            }
            ?>
        </div>
        <span><?php echo number_format($freelancer['avg_rating'], 1); ?> / 5 (<?php echo $freelancer['feedback_count']; ?> reviews)</span>
    </div>

    <div class="feedback-list">
        <?php
        // Fetch individual feedback
        $feedback_query = "SELECT f.rating, f.comment, f.created_at, u.name as reviewer_name
                          FROM feedback f
                          INNER JOIN users u ON f.given_by = u.id
                          WHERE f.received_by = ?
                          ORDER BY f.created_at DESC
                          LIMIT 5";
        
        $feedback_stmt = $conn->prepare($feedback_query);
        $feedback_stmt->bind_param("i", $freelancer_id);
        $feedback_stmt->execute();
        $feedback_result = $feedback_stmt->get_result();

        while ($feedback = $feedback_result->fetch_assoc()) {
        ?>
            <div class="feedback-item">
                <div class="feedback-header">
                    <div>
                        <strong><?php echo htmlspecialchars($feedback['reviewer_name']); ?></strong>
                        <div class="feedback-rating">
                            <?php
                            for ($i = 0; $i < 5; $i++) {
                                if ($i < $feedback['rating']) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <span class="feedback-date">
                        <?php echo date('M d, Y', strtotime($feedback['created_at'])); ?>
                    </span>
                </div>
                <p class="feedback-comment">
                    <?php echo nl2br(htmlspecialchars($feedback['comment'])); ?>
                </p>
            </div>
        <?php
        }
        $feedback_stmt->close();
        ?>
    </div>
</div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                body.classList.toggle('show-sidebar');
            });
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>