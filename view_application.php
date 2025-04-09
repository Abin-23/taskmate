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

$id = $_SESSION['current_job_id'];
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}
$checkUserRole = "SELECT role FROM users WHERE id = '" . $_SESSION['user_id'] . "'";
$roleResult = $conn->query($checkUserRole);
$userRole = $roleResult->fetch_assoc();

if ($userRole['role'] !== 'client') {
    header("Location: index.php");
    exit();
}

if (!isset($id)) {
    header("Location: client_project.php");
    exit();
}

$job_id = $id; 

$jobCheckQuery = "SELECT * FROM jobs WHERE job_id = '$job_id' AND client_id = '" . $_SESSION['user_id'] . "'";
$jobCheckResult = $conn->query($jobCheckQuery);

if ($jobCheckResult->num_rows == 0) {
    header("Location: my_projects.php");
    exit();
}

$job = $jobCheckResult->fetch_assoc();

if (isset($_POST['update_status'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['status'];
    $freelancer_id = $_POST['freelancer_id'];
    
    $updateQuery = "UPDATE applications SET application_status = '$new_status' WHERE application_id = '$application_id'";
    
    if ($conn->query($updateQuery)) {
        if ($new_status == 'Accepted') {
            // Update job status
            $updateJobQuery = "UPDATE jobs 
                SET job_status = 'In Progress', 
                    freelancer_id = '$freelancer_id' 
                WHERE job_id = '$job_id'";
            $conn->query($updateJobQuery);
            
            // Reject other applications
            $rejectOthersQuery = "UPDATE applications SET application_status = 'Rejected' WHERE job_id = '$job_id' AND application_id != '$application_id'";
            $conn->query($rejectOthersQuery);
            
            // Fetch job title
            $jobTitleQuery = "SELECT job_title FROM jobs WHERE job_id = '$job_id'";
            $jobTitleResult = $conn->query($jobTitleQuery);
            $jobTitle = $jobTitleResult->fetch_assoc()['job_title'];
            
            // Prepare notification message
            $notificationMessage = "Your application for '$jobTitle' has been accepted!";
            
            // Use prepared statement for notification insertion
            $notificationQuery = "INSERT INTO notifications (user_id, type, message, related_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($notificationQuery);
            $stmt->bind_param("issi", $freelancer_id, $type, $notificationMessage, $job_id);
            $type = 'application_accepted'; // Define the type variable
            $stmt->execute();
            $stmt->close();
        }

        header("Location: view_application.php?success=1"); 
        exit();
    } else {
        $error = "Failed to update application status: " . $conn->error;
    }
}

$applicationsQuery = "SELECT a.*, u.name, u.email,u.id, fp.experience, fp.bio, fp.profile_picture, fp.portfolio_link 
                      FROM applications a 
                      JOIN users u ON a.freelancer_id = u.id 
                      JOIN freelancer_profile fp ON u.id = fp.user_id 
                      WHERE a.job_id = '$job_id' 
                      ORDER BY a.date_applied DESC";
$applicationsResult = $conn->query($applicationsQuery);

$clientQuery = "SELECT * FROM client_profile WHERE id = '" . $_SESSION['user_id'] . "'";
$clientResult = $conn->query($clientQuery);
$client = $clientResult->fetch_assoc();
$userQuery = "SELECT * FROM users WHERE id = '" . $_SESSION['user_id'] . "'";
$userResult = $conn->query($userQuery);
$user = $userResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - View Applications</title>
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

        /* Animation Keyframes */
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Sidebar */
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

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #64748b;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            margin-bottom: 8px;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logout-btn {
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
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--gradient-start));
            transform: translateY(-2px);
        }

        .logout-btn i {
            font-size: 0.9em;
        }

        

        /* Application List */
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .applications {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .application-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .application-flex {
            display: flex;
            flex-wrap: wrap;
        }

        .freelancer-info {
            flex: 2;
            padding: 25px;
            border-right: 1px solid #f1f5f9;
        }

        .application-stats {
            flex: 1;
            padding: 25px;
            background: #f8fafc;
        }

        .freelancer-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .freelancer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f1f5f9;
        }

        .freelancer-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
        }

        .freelancer-email {
            color: #64748b;
            font-size: 0.9rem;
        }

        .experience-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 10px;
        }

        .beginner {
            background: #e0f2fe;
            color: #0369a1;
        }

        .intermediate {
            background: #fef3c7;
            color: #92400e;
        }

        .expert {
            background: #dcfce7;
            color: #166534;
        }

        .freelancer-bio {
            color: #64748b;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .portfolio-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .portfolio-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
        }

        .stat-value {
            font-weight: 600;
            color: #1e293b;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            width: fit-content;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-accepted {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            border: none;
        }

        .btn-accept {
            background: #dcfce7;
            color: #166534;
        }

        .btn-accept:hover {
            background: #bbf7d0;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #fee2e2;
            color: #b91c1c;
        }

        .btn-reject:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        .btn-view {
            background: #e0f2fe;
            color: #0369a1;
        }

        .btn-view:hover {
            background: #bae6fd;
            transform: translateY(-2px);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            color: #64748b;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #16a34a;
        }

        .alert-info {
            background: #e0f2fe;
            color: #0369a1;
            border-left: 4px solid #0284c7;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: #e2e8f0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .show-sidebar .sidebar {
                transform: translateX(0);
            }

            .application-flex {
                flex-direction: column;
            }

            .freelancer-info {
                border-right: none;
                border-bottom: 1px solid #f1f5f9;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .project-info {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo">
        <span>Task</span><span>Mate</span>
    </div>
    <ul class="nav-links"> <!-- FIX: Wrap list items inside a UL -->
        <li><a href="client_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
        <li><a href="client_project.php" class="active"><i class="fas fa-list"></i>My Projects</a></li>
        <li><a href="profile_client.php"><i class="fas fa-user"></i>Profile</a></li>
        <li><a href="payments.php"><i class="fas fa-wallet"></i>Payments</a></li>
        <li><a href="client_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
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
                </div>  
                <img src="<?php echo $client['profile_picture']; ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <a href="client_project.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Projects
        </a>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Application status updated successfully!
        </div>
        <?php endif; ?>


        <h3 class="section-title">Applications <?php if ($applicationsResult->num_rows > 0) echo '(' . $applicationsResult->num_rows . ')'; ?></h3>

        <div class="applications">
            <?php if ($applicationsResult->num_rows > 0): ?>
                <?php while ($application = $applicationsResult->fetch_assoc()): ?>
                    <div class="application-card">
                        <div class="application-flex">
                            <div class="freelancer-info">
                                <div class="freelancer-header">
                                    <img src="<?php echo htmlspecialchars($application['profile_picture'] ?: 'assets/default-avatar.png'); ?>" 
                                         alt="Freelancer" class="freelancer-avatar">
                                    <div>
                                        <div class="freelancer-name">
                                            <?php echo htmlspecialchars($application['name']); ?>
                                            <span class="experience-badge <?php echo strtolower($application['experience']); ?>">
                                                <?php echo htmlspecialchars($application['experience']); ?>
                                            </span>
                                        </div>
                                        <div class="freelancer-email"><?php echo htmlspecialchars($application['email']); ?></div>
                                    </div>
                                </div>

                                <div class="freelancer-bio">
                                    <?php echo htmlspecialchars(substr($application['bio'], 0, 300)) . (strlen($application['bio']) > 300 ? '...' : ''); ?>
                                </div>

                                <?php if (!empty($application['portfolio_link'])): ?>
                                <a href="<?php echo htmlspecialchars($application['portfolio_link']); ?>" target="_blank" class="portfolio-link">
                                    <i class="fas fa-external-link-alt"></i>
                                    View Portfolio
                                </a>
                                <?php endif; ?>
                            </div>

                            <div class="application-stats">
                                <div class="stat-row">
                                    <span class="stat-label">Status</span>
                                    <span class="status-badge status-<?php echo strtolower($application['application_status']); ?>">
                                        <?php echo htmlspecialchars($application['application_status']); ?>
                                    </span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Applied On</span>
                                    <span class="stat-value"><?php echo date('M d, Y', strtotime($application['date_applied'])); ?></span>
                                </div>

                                <?php if ($job['job_status'] !== 'In Progress' && $job['job_status'] !== 'Completed' && $application['application_status'] === 'Pending'): ?>
                                <div class="action-buttons">
                                    <form method="post">
                                    <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>"> 
                                    <input type="hidden" name="status" value="Accepted">
                                    <input type="hidden" name="freelancer_id" value="<?php echo $application['freelancer_id'];?>">
                                        <button type="submit" name="update_status" class="btn btn-accept">
                                            <i class="fas fa-check"></i>
                                            Accept Application
                                        </button>
                                    </form>
                                    
                                    <form method="post">
                                        <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                        <input type="hidden" name="status" value="Rejected">
                                        <button type="submit" name="update_status" class="btn btn-reject">
                                            <i class="fas fa-times"></i>
                                            Reject Application
                                        </button>
                                    </form>
                                    
                                    <form action="view_freelancer.php" method="post" style="display: inline;">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($application['id']); ?>">
    <button type="submit" class="btn btn-view">
        <i class="fas fa-eye"></i>
        View Full Profile
    </button>
</form>
                                </div>
                                <?php elseif ($application['application_status'] === 'Accepted'): ?>
                                <div class="alert alert-success" style="margin-top: 15px;">
                                    <i class="fas fa-check-circle"></i>
                                    You've accepted this freelancer for this project
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-clock"></i>
                    <h3>No applications yet</h3>
                    <p>Your project hasn't received any applications yet. Check back later or consider updating your project details to attract more freelancers.</p>
                    <a href="edit_project.php?id=<?php echo $job_id; ?>" class="btn btn-primary">Update Project</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        
        const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        menuToggle.addEventListener('click', () => {
            body.classList.toggle('show-sidebar');
        });
    </script>
</body>
</html>