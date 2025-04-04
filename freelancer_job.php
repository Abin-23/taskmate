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

// Fetch freelancer profile
$sql = "SELECT * FROM freelancer_profile WHERE user_id='" . $_SESSION['user_id'] . "'";
$result = $conn->query($sql);

if ($result && $result->num_rows < 1) {
    header('Location:freelancer_profile.php');    
}

$user = $result->fetch_assoc();
$sql2 = "SELECT * FROM users WHERE id='" . $_SESSION['user_id'] . "'";
$result2 = $conn->query($sql2);
$user2 = $result2->fetch_assoc();

// Fetch jobs assigned to the freelancer
$jobs_sql = "SELECT j.*, u.name as client_name
             FROM jobs j 
             INNER JOIN users u ON j.client_id = u.id 
             WHERE j.freelancer_id = ? 
             ORDER BY 
                CASE 
                    WHEN j.job_status = 'In Progress' THEN 1
                    WHEN j.job_status = 'Open' THEN 2
                    WHEN j.job_status = 'Completed' THEN 3
                END,
                j.deadline ASC";
                
$stmt = $conn->prepare($jobs_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$jobs_result = $stmt->get_result();

// For debugging
if (!$jobs_result) {
    die("Query failed: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - My Jobs</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .job-filter {
            display: flex;
            margin-bottom: 20px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .filter-btn:hover:not(.active) {
            background: #f1f5f9;
        }

        .search-bar {
            flex: 1;
            max-width: 500px;
            margin: 0 20px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 20px 10px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
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

        /* Job List Styles */
        .job-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .job-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .job-header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #f1f5f9;
        }

        .job-info {
            flex: 1;
        }

        .job-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .client-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .client-picture {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .client-name {
            color: #64748b;
            font-size: 14px;
        }

        .job-status {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            text-align: center;
            width: fit-content;
        }

        .status-open {
            background: #e0f2fe;
            color: #0284c7;
        }

        .status-in-progress {
            background: #fef3c7;
            color: #d97706;
        }

        .status-completed {
            background: #dcfce7;
            color: #16a34a;
        }

        .job-details {
            padding: 20px;
        }

        .job-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            color: #64748b;
            font-size: 14px;
            flex-wrap: wrap;
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
            margin-bottom: 15px;
        }

        .job-description {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .job-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .tag {
            background: #f1f5f9;
            color: #475569;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
        }

        .job-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }

        .btn-message {
            background: #f1f5f9;
            color: #3b82f6;
        }

        .btn-message:hover {
            background: #e2e8f0;
        }

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-view:hover {
            background: #2563eb;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .empty-icon {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-text {
            color: #64748b;
            font-size: 16px;
            max-width: 300px;
            margin: 0 auto;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1001;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 50px auto;
            padding: 25px;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

        .message-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message-form textarea {
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            resize: vertical;
            min-height: 150px;
            font-size: 15px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
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

            .search-bar {
                width: 100%;
                max-width: none;
                margin: 15px 0;
            }

            .job-actions {
                flex-direction: column;
                width: 100%;
            }

            .action-btn {
                width: 100%;
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
        <div class="header">
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search my jobs...">
            </div>
            <div class="user-info">
                <div class="notification">
                <a href="notifications.php" class="notification">
        <i class="fas fa-bell"></i>
    </a>
                </div>
                <img src="<?php echo $user['profile_picture']; ?>" alt="Profile" style="width: 35px; height: 35px; border-radius: 50%;">
                <a href="logout.php" style="text-decoration: none; color: #64748b;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <h1 class="page-title">My Jobs</h1>
        
        <div class="job-filter">
            <button class="filter-btn active" data-status="all">All Jobs</button>
            <button class="filter-btn" data-status="In Progress">In Progress</button>
            <button class="filter-btn" data-status="Open">Pending</button>
            <button class="filter-btn" data-status="Completed">Completed</button>
        </div>

        <div class="job-list">
            <?php if ($jobs_result->num_rows > 0): ?>
                <?php while($job = $jobs_result->fetch_assoc()): ?>
                    <div class="job-card" data-status="<?php echo $job['job_status']; ?>">
                        <div class="job-header">
                            <div class="job-info">
                                <div class="job-title"><?php echo htmlspecialchars($job['job_title']); ?></div>
                                <div class="client-info">
                                    <span class="client-name"><?php echo htmlspecialchars($job['client_name']); ?></span>
                                </div>
                                <div class="job-status <?php 
                                    echo 'status-' . strtolower(str_replace(' ', '-', $job['job_status'])); 
                                ?>">
                                    <?php echo $job['job_status']; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-details">
                            <div class="job-meta">
                                <div><i class="fas fa-folder"></i> <?php echo htmlspecialchars($job['task_category']); ?></div>
                                <div><i class="fas fa-calendar"></i> Due: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></div>
                                <div><i class="fas fa-clock"></i> Posted: <?php echo date('M d, Y', strtotime($job['date_posted'])); ?></div>
                            </div>
                            
                            <div class="job-budget">₹<?php echo number_format($job['budget'], 2); ?></div>
                            
                            <div class="job-description">
                                <?php echo nl2br(htmlspecialchars(substr($job['job_description'], 0, 150))); ?>...
                            </div>
                            
                            <?php if (!empty($job['tools_software'])): ?>
                                <div class="job-tags">
                                    <?php 
                                    $tools = explode(',', $job['tools_software']);
                                    foreach(array_slice($tools, 0, 3) as $tool): 
                                        if(trim($tool)):
                                    ?>
                                        <span class="tag"><?php echo htmlspecialchars(trim($tool)); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="job-actions">
    <?php if ($job['job_status'] === 'Completed') : ?>
        <a href="feedback.php?job_id=<?php echo htmlspecialchars($job['job_id']); ?>" class="action-btn btn-message">
             Give Feedback
        </a>
    <?php else : ?>
        <a href="chat.php?job_id=<?php echo htmlspecialchars($job['job_id']); ?>" class="action-btn btn-message">
            <i class="fas fa-comment"></i> Message
        </a>
    <?php endif; ?>

    <a href="job_details.php?id=<?php echo htmlspecialchars($job['job_id']); ?>" class="action-btn btn-view">
        <i class="fas fa-eye"></i> View Job
    </a>
</div>

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-briefcase empty-icon"></i>
                    <p class="empty-text">You don't have any jobs assigned yet. Browse available jobs on your dashboard!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Message Modal -->
        <div id="messageModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <h2 style="margin-bottom: 20px;">Message Client</h2>
                <p id="messageJobTitle" style="margin-bottom: 20px; color: #64748b;"></p>
                
                <form class="message-form" id="messageForm" action="send_message.php" method="post">
                    <input type="hidden" id="jobId" name="job_id">
                    <textarea name="message" placeholder="Type your message here..." required></textarea>
                    
                    <div class="form-actions">
                        <button type="button" class="action-btn btn-message" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="action-btn btn-view">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Menu toggle functionality
        const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        menuToggle.addEventListener('click', () => {
            body.classList.toggle('show-sidebar');
        });

        // Filter functionality
        const filterButtons = document.querySelectorAll('.filter-btn');
        const jobCards = document.querySelectorAll('.job-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                button.classList.add('active');
                
                const filterStatus = button.getAttribute('data-status');
                
                jobCards.forEach(card => {
                    if (filterStatus === 'all' || card.getAttribute('data-status') === filterStatus) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Search functionality
        const searchInput = document.querySelector('.search-bar input');

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            jobCards.forEach(card => {
                const title = card.querySelector('.job-title').textContent.toLowerCase();
                const description = card.querySelector('.job-description').textContent.toLowerCase();
                const client = card.querySelector('.client-name').textContent.toLowerCase();
                const category = card.querySelector('.job-meta').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || 
                    description.includes(searchTerm) || 
                    client.includes(searchTerm) ||
                    category.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Message modal functionality
        function showMessageModal(jobId, jobTitle) {
            const modal = document.getElementById('messageModal');
            const jobIdField = document.getElementById('jobId');
            const messageJobTitle = document.getElementById('messageJobTitle');
            
            jobIdField.value = jobId;
            messageJobTitle.textContent = 'Job: ' + jobTitle;
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('messageModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('messageModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>