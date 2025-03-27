<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Database connection
require_once 'config.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT role FROM users WHERE id='$user_id'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $user_type = $row['role']; 
} else {
    $user_type = null; 
}

$error_message = '';
$success_message = '';

if (!isset($_GET['job_id'])) {
    header("Location: " . ($user_type === 'freelancer' ? 'freelancer_job.php' : 'client_project.php'));
    exit();
}
$job_id = $_GET['job_id'];

// Get job details and verify access
if ($user_type === 'freelancer') {
    $job_query = "SELECT j.*, c.name AS client_name, c.id AS client_user_id
                  FROM jobs j 
                  INNER JOIN users c ON j.client_id = c.id 
                  WHERE j.job_id = ? AND j.freelancer_id = ? AND j.job_status = 'Completed'";
    $stmt = $conn->prepare($job_query);
    $stmt->bind_param("ii", $job_id, $user_id);
} else {
    $job_query = "SELECT j.*, f.name AS freelancer_name, f.id AS freelancer_user_id
                  FROM jobs j 
                  INNER JOIN users f ON j.freelancer_id = f.id 
                  WHERE j.job_id = ? AND j.client_id = ? AND j.job_status = 'Completed'";
    $stmt = $conn->prepare($job_query);
    $stmt->bind_param("ii", $job_id, $user_id);
}

$stmt->execute();
$job_result = $stmt->get_result();

if ($job_result->num_rows === 0) {
    header("Location: " . ($user_type === 'freelancer' ? 'freelancer_job.php' : 'client_project.php'));
    exit();
}

$job = $job_result->fetch_assoc();

// Set received_by based on user type
if ($user_type === 'freelancer') {
    $received_by = $job['client_user_id'];
    $receiver_name = $job['client_name'];
} else {
    $received_by = $job['freelancer_user_id'];
    $receiver_name = $job['freelancer_name'];
}

// Check if feedback already exists
$feedback_check_query = "SELECT * FROM feedback WHERE job_id = ? AND given_by = ?";
$stmt = $conn->prepare($feedback_check_query);
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$feedback_result = $stmt->get_result();
$feedback_exists = $feedback_result->num_rows > 0;
$feedback = $feedback_exists ? $feedback_result->fetch_assoc() : null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$feedback_exists) {
    $rating = $_POST['rating'] ?? 0;
    $comment = $_POST['comment'] ?? '';
    
    if ($rating < 1 || $rating > 5) {
        $error_message = "Please provide a rating between 1 and 5.";
    } else {
        // Insert feedback
        $insert_query = "INSERT INTO feedback (job_id, given_by, received_by, rating, comment, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiiis", $job_id, $user_id, $received_by, $rating, $comment);
        
        if ($stmt->execute()) {
            $success_message = "Your feedback has been submitted successfully!";
            header("Location: " . ($user_type === 'freelancer' ? 'freelancer_job.php' : 'client_project.php'));
            exit();
        } else {
            $error_message = "Error submitting feedback. Please try again.";
        }
    }
}

if ($user_type === 'freelancer') {
    $profile_query = "SELECT profile_picture FROM freelancer_profile WHERE user_id = ?";
} else {
    $profile_query = "SELECT profile_picture FROM client_profile WHERE id = ?";
}
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile_result = $stmt->get_result();
$user = $profile_result->fetch_assoc();

// If profile picture is not found, use a default image
$profile_picture = ($user && isset($user['profile_picture'])) ? $user['profile_picture'] : 'assets/img/default-avatar.png';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Feedback - TaskMate</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, sans-serif;
        }

        :root {
            --primary: #3b82f6;
            --sidebar-width: 250px;
            --header-height: 70px;
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
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .logo span {
            font-size: 24px;
            font-weight: 700;
        }

        .logo span:first-child {
            color: var(--primary);
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-links a:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        .nav-links a.active {
            background: var(--primary);
            color: white;
        }

        .nav-links a i {
            margin-right: 12px;
            width: 20px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .header {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .page-title {
            font-size: 22px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification {
            position: relative;
            padding: 8px;
            cursor: pointer;
        }

        .notification-dot {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }

        .feedback-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .job-summary {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .job-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .job-meta {
            display: flex;
            gap: 20px;
            color: #64748b;
            font-size: 14px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .job-meta div {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .job-budget {
            background: #f0f9ff;
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
        }

        .rating-container {
            margin-bottom: 25px;
        }

        .rating-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1e293b;
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            font-size: 30px;
            margin-bottom: 20px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            color: #cbd5e1;
            margin-right: 5px;
            transition: all 0.2s ease;
        }

        .star-rating :checked ~ label {
            color: #f59e0b;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #f59e0b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1e293b;
        }

        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            resize: vertical;
            min-height: 150px;
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #2563eb;
        }

        .feedback-submitted {
            background: #dcfce7;
            padding: 20px;
            border-radius: 8px;
            color: #16a34a;
            margin-bottom: 20px;
        }

        .feedback-details {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .feedback-rating {
            display: flex;
            margin-bottom: 15px;
        }

        .feedback-rating i.fas {
            color: #f59e0b;
            margin-right: 3px;
        }

        .feedback-rating i.far {
            color: #cbd5e1;
            margin-right: 3px;
        }

        .feedback-date {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .feedback-comments {
            color: #475569;
            line-height: 1.6;
        }

        .action-btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-back {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-back:hover {
            background: #e2e8f0;
        }

        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success-message {
            background: #dcfce7;
            color: #16a34a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

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
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .job-meta {
                flex-direction: column;
                gap: 10px;
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
            <?php if ($user_type === 'freelancer'): ?>
                <li><a href="freelancer_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
                <li><a href="freelancer_job.php" class="active"><i class="fas fa-briefcase"></i>My Jobs</a></li>
                <li><a href="profile_freelancer.php"><i class="fas fa-user"></i>Profile</a></li>
                <li><a href="#"><i class="fas fa-wallet"></i>Earnings</a></li>
                <li><a href="freelancer_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
            <?php else: ?>
                <li><a href="client_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
                <li><a href="client_jobs.php" class="active"><i class="fas fa-briefcase"></i>My Jobs</a></li>
                <li><a href="post_job.php"><i class="fas fa-plus"></i>Post Job</a></li>
                <li><a href="profile_client.php"><i class="fas fa-user"></i>Profile</a></li>
                <li><a href="client_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
            <?php endif; ?>
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
                <img src="<?php echo $user['profile_picture']; ?>" alt="Profile" style="width: 35px; height: 35px; border-radius: 50%;">
                <a href="logout.php" style="text-decoration: none; color: #64748b;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <h1 class="page-title">Job Feedback</h1>
        
        <div class="feedback-container">
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div class="job-summary">
                <div class="job-title"><?php echo htmlspecialchars($job['job_title']); ?></div>
                <div class="job-meta">
                    <?php if ($user_type === 'freelancer'): ?>
                        <div><i class="fas fa-user"></i> Client: <?php echo htmlspecialchars($job['client_name']); ?></div>
                    <?php else: ?>
                        <div><i class="fas fa-user"></i> Freelancer: <?php echo htmlspecialchars($job['freelancer_name']); ?></div>
                    <?php endif; ?>
                    <div><i class="fas fa-folder"></i> <?php echo htmlspecialchars($job['task_category']); ?></div>
                    <div><i class="fas fa-calendar"></i> Completed: <?php echo date('M d, Y', strtotime($job['completion_date'] ?? $job['deadline'])); ?></div>
                </div>
                <div class="job-budget">₹<?php echo number_format($job['budget'], 2); ?></div>
            </div>

            <?php if ($feedback_exists): ?>
                <div class="feedback-submitted">
                    <i class="fas fa-check-circle"></i> You have already provided feedback for this job.
                </div>
                <div class="feedback-details">
                    <div class="feedback-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= $feedback['rating']): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span style="margin-left: 10px;"><?php echo $feedback['rating']; ?>/5</span>
                    </div>
                    <div class="feedback-date">
                        Submitted on <?php echo date('M d, Y', strtotime($feedback['created_at'])); ?>
                    </div>
                    <div class="feedback-comments">
                        <?php echo nl2br(htmlspecialchars($feedback['comment'])); ?>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="rating-container">
                        <div class="rating-title">
                            <?php if ($user_type === 'freelancer'): ?>
                                Rate your experience working with this client:
                            <?php else: ?>
                                Rate your experience with this freelancer:
                            <?php endif; ?>
                        </div>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" />
                            <label for="star5" class="fas fa-star"></label>
                            <input type="radio" id="star4" name="rating" value="4" />
                            <label for="star4" class="fas fa-star"></label>
                            <input type="radio" id="star3" name="rating" value="3" />
                            <label for="star3" class="fas fa-star"></label>
                            <input type="radio" id="star2" name="rating" value="2" />
                            <label for="star2" class="fas fa-star"></label>
                            <input type="radio" id="star1" name="rating" value="1" />
                            <label for="star1" class="fas fa-star"></label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comment">Comments (Optional):</label>
                        <textarea id="comment" name="comment" placeholder="Share your experience working with <?php echo htmlspecialchars($receiver_name); ?>..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: space-between;">
                        <a href="<?php echo $user_type === 'freelancer' ? 'freelancer_job.php' : 'client_jobs.php'; ?>" class="action-btn btn-back">
                            <i class="fas fa-arrow-left"></i> Back to Jobs
                        </a>
                        <button type="submit" class="submit-btn">Submit Feedback</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($feedback_exists): ?>
                <div style="margin-top: 20px;">
                    <a href="<?php echo $user_type === 'freelancer' ? 'freelancer_job.php' : 'client_project.php'; ?>" class="action-btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Jobs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.body.classList.toggle('show-sidebar');
        });
    </script>
</body>
</html>