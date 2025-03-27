<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "taskmate";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;

if ($client_id === 0) {
    header("Location: freelancer_dash.php");
    exit();
}

// Get client details
$query = "SELECT 
    u.id,
    u.name,
    u.email,
    u.created_at,
    cp.company_name,
    cp.website,
    cp.address,
    cp.profile_picture
FROM users u
INNER JOIN client_profile cp ON u.id = cp.id
WHERE u.id = ? AND u.role = 'client' AND u.active = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

if (!$client) {
    header("Location: freelancer_dash.php");
    exit();
}

// Get all feedback for this client across all jobs
$feedback_query = "SELECT f.rating, f.comment, f.created_at, u.name as reviewer_name
                  FROM feedback f
                  INNER JOIN users u ON f.given_by = u.id
                  WHERE f.received_by = ?
                  ORDER BY f.created_at DESC";
$feedback_stmt = $conn->prepare($feedback_query);
$feedback_stmt->bind_param("i", $client_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();

$feedbacks = [];
$total_rating = 0;
$rating_count = 0;
while ($row = $feedback_result->fetch_assoc()) {
    $feedbacks[] = $row;
    $total_rating += $row['rating'];
    $rating_count++;
}
$avg_rating = $rating_count > 0 ? round($total_rating / $rating_count, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - View Client Profile</title>
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

        .company-name {
            color: #64748b;
            font-size: 18px;
            margin-bottom: 15px;
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

        .contact-item a {
            color: var(--primary-color);
            text-decoration: none;
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
            margin-top: 30px;
        }

        .rating-summary {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .star-rating i {
            color: #facc15;
            font-size: 18px;
        }

        .star-rating .far {
            color: #e2e8f0;
        }

        .feedback-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: 500;
            color: #1e293b;
        }

        .feedback-date {
            color: #64748b;
            font-size: 14px;
        }

        .feedback-comment {
            color: #475569;
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
    <div class="sidebar">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        <ul class="nav-links">
            <li><a href="freelancer_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="freelancer_job.php" class="active"><i class="fas fa-briefcase"></i>My Jobs</a></li>
            <li><a href="profile_freelancer.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="earnings.php"><i class="fas fa-wallet"></i>Earnings</a></li>
            <li><a href="freelancer_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <a href="job_details.php?id=<?php echo $job_id; ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>

        <div class="profile-container">
            <div class="profile-header">
                <img src="<?php echo !empty($client['profile_picture']) ? htmlspecialchars($client['profile_picture']) : '/api/placeholder/120/120'; ?>" 
                     alt="<?php echo htmlspecialchars($client['name']); ?>" 
                     class="profile-image">
                
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($client['name']); ?></h1>
                    <div class="company-name">
                        <i class="fas fa-building"></i>
                        <?php echo htmlspecialchars($client['company_name']); ?>
                    </div>
                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Member since <?php echo date('M Y', strtotime($client['created_at'])); ?></span>
                        </div>
                    </div>
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
                        <p><?php echo htmlspecialchars($client['email']); ?></p>
                    </div>
                    <?php if (!empty($client['website'])): ?>
                    <div class="contact-item">
                        <h3><i class="fas fa-globe Endocrinology</span>"></i>Website</h3>
                        <p><a href="<?php echo htmlspecialchars($client['website']); ?>" 
                              target="_blank">
                            <?php echo htmlspecialchars($client['website']); ?>
                            <i class="fas fa-external-link-alt"></i>
                        </a></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Address
                </h2>
                <div class="contact-item">
                    <p><?php echo nl2br(htmlspecialchars($client['address'])); ?></p>
                </div>
            </div>

            <div class="section rating-section">
                <h2 class="section-title">
                    <i class="fas fa-star"></i>
                    Feedback & Ratings
                </h2>
                
                <?php if ($rating_count > 0): ?>
                    <div class="rating-summary">
                        <div class="star-rating">
                            <?php
                            $full_stars = floor($avg_rating);
                            $half_star = ($avg_rating - $full_stars) >= 0.5;
                            
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
                        <span><?php echo number_format($avg_rating, 1); ?> / 5 (<?php echo $rating_count; ?> reviews)</span>
                    </div>

                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <span class="reviewer-name"><?php echo htmlspecialchars($feedback['reviewer_name']); ?></span>
                                <span class="feedback-date"><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></span>
                            </div>
                            <div class="star-rating">
                                <?php
                                $feedback_full_stars = floor($feedback['rating']);
                                $feedback_half_star = ($feedback['rating'] - $feedback_full_stars) >= 0.5;
                                
                                // Full stars
                                for ($i = 0; $i < $feedback_full_stars; $i++) {
                                    echo '<i class="fas fa-star"></i>';
                                }
                                // Half star
                                if ($feedback_half_star) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                }
                                // Empty stars
                                for ($i = $feedback_full_stars + ($feedback_half_star ? 1 : 0); $i < 5; $i++) {
                                    echo '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <?php if (!empty($feedback['comment'])): ?>
                                <p class="feedback-comment"><?php echo nl2br(htmlspecialchars($feedback['comment'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="feedback-item">
                        <p>No reviews yet for this client.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
$feedback_stmt->close();
$conn->close();
?>