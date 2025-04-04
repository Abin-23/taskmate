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

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$job_sql = "SELECT j.*, u.name as client_name, cp.company_name,u.id, cp.profile_picture as company_logo 
            FROM jobs j 
            INNER JOIN users u ON j.client_id = u.id 
            LEFT JOIN client_profile cp ON u.id = cp.id 
            WHERE j.job_id = ?";
$stmt = $conn->prepare($job_sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job_result = $stmt->get_result();

if ($job_result->num_rows === 0) {
    header("Location: freelancer_dash.php");
    exit();
}

$job = $job_result->fetch_assoc();

// Check if user has already applied
$check_application_sql = "SELECT * FROM applications WHERE job_id = ? AND freelancer_id = ?";
$check_stmt = $conn->prepare($check_application_sql);
$check_stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
$check_stmt->execute();
$existing_application = $check_stmt->get_result()->num_rows > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    if (!$existing_application) {
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // Insert the application
            $apply_sql = "INSERT INTO applications (job_id, freelancer_id) VALUES (?, ?)";
            $apply_stmt = $conn->prepare($apply_sql);
            $apply_stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
            $apply_stmt->execute();
            
            // Get freelancer name from users table
            $freelancer_sql = "SELECT name FROM users WHERE id = ?";
            $freelancer_stmt = $conn->prepare($freelancer_sql);
            $freelancer_stmt->bind_param("i", $_SESSION['user_id']);
            $freelancer_stmt->execute();
            $freelancer_result = $freelancer_stmt->get_result();
            $freelancer_data = $freelancer_result->fetch_assoc();
            $freelancerName = $freelancer_data['name'];
            
            // Get job title and client ID (already available in $job array)
            $jobTitle = $job['job_title'];
            $client_id = $job['client_id'];
            
            // Create notification message
            $notificationMessage = "A new freelancer, $freelancerName, has applied to your job '$jobTitle'!";
            
            // Insert notification
            $notification_sql = "INSERT INTO notifications (user_id, type, message, related_id) 
                                VALUES (?, 'new_application', ?, ?)";
            $notification_stmt = $conn->prepare($notification_sql);
            $notification_stmt->bind_param("isi", $client_id, $notificationMessage, $job_id);
            $notification_stmt->execute();
            
            // Commit the transaction
            $conn->commit();
            
            $success_message = "Application submitted successfully!";
            $existing_application = true;
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Error submitting application. Please try again.";
        }
    }
}

// Get required skills
$skills_sql = "SELECT s.skill_name 
               FROM skills s 
               WHERE FIND_IN_SET(s.id, ?)";
$skills_stmt = $conn->prepare($skills_sql);
$skills_stmt->bind_param("s", $job['required_skills']);
$skills_stmt->execute();
$skills_result = $skills_stmt->get_result();

$required_skills = array();
while ($skill = $skills_result->fetch_assoc()) {
    $required_skills[] = $skill['skill_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - <?php echo htmlspecialchars($job['job_title']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, sans-serif;
        }

        body {
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .job-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .job-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1e293b;
        }


    .profile-form {
    margin: 0;
    padding: 0;
    display: inline;
}

.view-profile-button {
    background: #3b82f6;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    border: none; 
    cursor: pointer; 
}

.view-profile-button:hover {
    background: #2563eb;
    transform: translateY(-1px);
}
        .company-logo {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .client-details h3 {
            font-size: 16px;
            color: #1e293b;
        }

        .client-details p {
            color: #64748b;
            font-size: 14px;
        }

        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .meta-item i {
            color: #3b82f6;
            font-size: 18px;
        }

        .job-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1e293b;
        }

        .job-description {
            color: #475569;
            margin-bottom: 30px;
        }

        .skills-list, .tools-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
        }

        .skill-tag, .tool-tag {
            background: #f1f5f9;
            color: #475569;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
        }

        .apply-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .apply-button {
            background: #22c55e;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .apply-button:hover {
            background: #16a34a;
            transform: translateY(-1px);
        }

        .apply-button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }

        .success-message {
            color: #16a34a;
            background: #dcfce7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .error-message {
            color: #dc2626;
            background: #fee2e2;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .back-button:hover {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="freelancer_dash.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="job-header">
            <h1 class="job-title"><?php echo htmlspecialchars($job['job_title']); ?></h1>
            <!-- In job_details.php, update the client-info section -->
<div class="client-info">
    <img src="<?php echo htmlspecialchars($job['company_logo'] ?? 'default-company-logo.png'); ?>" 
         alt="Company Logo" 
         class="company-logo">
    <div class="client-details">
        <h3><?php echo htmlspecialchars($job['company_name'] ?? $job['client_name']); ?></h3>
        <p>Posted by <?php echo htmlspecialchars($job['client_name']); ?></p>
    </div>
    <form action="view_client_profile.php" method="POST" class="profile-form">
        <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($job['client_id']); ?>">
        <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">
        <button type="submit" class="view-profile-button">
            <i class="fas fa-user"></i> View Profile
        </button>
    </form>
</div>
            <div class="job-meta">
                <div class="meta-item">
                    <i class="fas fa-money-bill"></i>
                    <div>
                        <strong>Budget</strong>
                        <p>₹<?php echo number_format($job['budget'], 2); ?></p>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-folder"></i>
                    <div>
                        <strong>Category</strong>
                        <p><?php echo htmlspecialchars($job['task_category']); ?></p>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <strong>Deadline</strong>
                        <p><?php echo date('M d, Y', strtotime($job['deadline'])); ?></p>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong>Posted</strong>
                        <p><?php echo date('M d, Y', strtotime($job['date_posted'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="job-content">
            <h2 class="section-title">Job Description</h2>
            <div class="job-description">
                <?php echo nl2br(htmlspecialchars($job['job_description'])); ?>
            </div>

            <h2 class="section-title">Required Skills</h2>
            <div class="skills-list">
                <?php foreach ($required_skills as $skill): ?>
                    <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($job['tools_software'])): ?>
                <h2 class="section-title">Required Tools & Software</h2>
                <div class="tools-list">
                    <?php foreach (explode(',', $job['tools_software']) as $tool): ?>
                        <span class="tool-tag"><?php echo htmlspecialchars(trim($tool)); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="apply-section">
            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php elseif (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <button type="submit" 
                        name="apply" 
                        class="apply-button"
                        <?php echo $existing_application ? 'disabled' : ''; ?>>
                    <i class="fas fa-paper-plane"></i>
                    <?php echo $existing_application ? 'Application Submitted' : 'Apply for this Job'; ?>
                </button>
            </form>
        </div>
    </div>
</body>
</html>