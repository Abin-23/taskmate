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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['job_id']) && isset($_POST['redirect_to'])) {
    $_SESSION['current_job_id'] = $_POST['job_id'];
    $redirect_to = $_POST['redirect_to'];
    header("Location: $redirect_to");
    exit();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$sql = "SELECT * FROM client_profile WHERE id='" . $_SESSION['user_id'] . "'";
$result = $conn->query($sql);

if ($result && $result->num_rows < 1) {
    header('Location:client_profile.php');    
}


$user = $result->fetch_assoc();
$sql2 = "SELECT * FROM users WHERE id='" . $_SESSION['user_id'] . "'";
$result2 = $conn->query($sql2);
$user2 = $result2->fetch_assoc();

$jobsQuery = "SELECT * FROM jobs WHERE client_id = '" . $_SESSION['user_id'] . "' ORDER BY date_posted DESC";
$jobsResult = $conn->query($jobsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - My Projects</title>
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* Project List Styles */
        .project-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .project-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .project-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .project-flex {
            display: flex;
            flex-wrap: wrap;
        }

        .project-info {
            flex: 2;
            padding: 25px;
            border-right: 1px solid #f1f5f9;
        }

        .project-stats {
            flex: 1;
            padding: 25px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .project-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .project-desc {
            color: #64748b;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .tag {
            display: inline-block;
            padding: 5px 10px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-right: 8px;
            margin-bottom: 8px;
            color: #64748b;
        }

        .tag.dev {
            background: #e0f2fe;
            color: #0369a1;
        }

        .tag.design {
            background: #fce7f3;
            color: #be185d;
        }

        .tag.editing {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .tag.data {
            background: #ecfccb;
            color: #3f6212;
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

        .status-open {
            background: #dcfce7;
            color: #166534;
        }

        .status-progress {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background: #e0f2fe;
            color: #0369a1;
        }

        .action-buttons {
            display: flex;
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
        }

        .btn-primary {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--gradient-start));
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #64748b;
        }

        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
        }

        .page-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: white;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .page-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .page-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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

            .project-flex {
                flex-direction: column;
            }

            .project-info {
                border-right: none;
                border-bottom: 1px solid #f1f5f9;
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

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        .skills-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        .skill-tag {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #64748b;
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
            <div class="search-bar">
                <input type="text" placeholder="Search in your projects...">
            </div>
            <div class="user-info">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="notification-dot"></div>
                </div>  
                <img src="<?php echo $user['profile_picture']?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="color: #1e293b; font-size: 2rem; font-weight: 700;">My Projects</h1>
                <p style="color: #64748b; margin-top: 8px;">Manage all your posted projects in one place</p>
            </div>
            <a href="add_job.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Post New Project
            </a>
        </div>

        <div class="project-filters">
    <button class="filter-btn active" data-status="all">All Projects</button>
    <button class="filter-btn" data-status="open">Open</button>
    <button class="filter-btn" data-status="inprogress">In Progress</button>
    <button class="filter-btn" data-status="completed">Completed</button>
</div>


        <div class="project-list">
            <?php if ($jobsResult && $jobsResult->num_rows > 0) : ?>
                <?php while ($job = $jobsResult->fetch_assoc()) : ?>
                    <div class="project-card" data-status="<?php echo strtolower(str_replace(' ', '', $job['job_status'])); ?>">
                        <div class="project-flex">
                            <div class="project-info">
                                <h3 class="project-title"><?php echo htmlspecialchars($job['job_title']); ?></h3>
                                <p class="project-desc"><?php echo htmlspecialchars(substr($job['job_description'], 0, 200)) . (strlen($job['job_description']) > 200 ? '...' : ''); ?></p>
                                
                                <div style="margin: 15px 0;">
                                    <span class="tag <?php 
                                        if ($job['task_category'] == 'Development') echo 'dev';
                                        elseif ($job['task_category'] == 'Design') echo 'design';
                                        elseif ($job['task_category'] == 'Editing') echo 'editing';
                                        elseif ($job['task_category'] == 'Data Entry') echo 'data';
                                    ?>">
                                        <i class="fas fa-<?php 
                                            if ($job['task_category'] == 'Development') echo 'code';
                                            elseif ($job['task_category'] == 'Design') echo 'palette';
                                            elseif ($job['task_category'] == 'Editing') echo 'edit';
                                            elseif ($job['task_category'] == 'Data Entry') echo 'database';
                                        ?>"></i>
                                        <?php echo htmlspecialchars($job['task_category']); ?>
                                    </span>
                                </div>
                                
                                <div class="skills-tags">
                          <?php 
                      $skill_ids = explode(',', $job['required_skills']);
                      foreach ($skill_ids as $skill_id) :
                     $skill_id = trim($skill_id);
                    if (!empty($skill_id)) :
                     $sql3 = "SELECT skill_name FROM skills WHERE id='$skill_id'";
                      $result3 = mysqli_query($conn, $sql3);
                      if ($result3 && mysqli_num_rows($result3) > 0) {
                      $row3 = mysqli_fetch_assoc($result3);
                       $skill_name = $row3['skill_name'];
                     } else {
                     $skill_name = '';
                     }
                   ?>
            <span class="skill-tag"><?php echo htmlspecialchars($skill_name); ?></span>
          <?php 
           endif;
           endforeach; 
            ?>
           </div>

                                
           <div class="action-buttons">
           <?php if ($job['job_status'] === 'In Progress') : ?>
    <a href="chat.php?job_id=<?php echo htmlspecialchars($job['job_id']); ?>" class="btn btn-primary">Message</a>
<?php elseif ($job['job_status'] === 'Open') : ?> 
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" style="display:inline;">
        <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job['job_id']); ?>">
        <input type="hidden" name="redirect_to" value="view_application.php">
        <button type="submit" class="btn btn-primary">View Applications</button>
    </form>
<?php endif; ?>

<?php if ($job['job_status'] === 'In Progress') : ?>
    <form action="update_status.php" method="POST" style="display:inline;">
        <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
        <select name="job_status" onchange="this.form.submit()" class="btn btn-outline">
            <option value="In Progress" selected>In Progress</option>
            <option value="Completed">Completed</option>
        </select>
    </form>
<?php elseif ($job['job_status'] === 'Open') : ?>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" style="display:inline;">
        <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
        <input type="hidden" name="redirect_to" value="edit_project.php">
        <button type="submit" class="btn btn-outline">Edit Project</button>
    </form>
<?php elseif($job['job_status']=='Completed') : ?>
    <form action="feedback.php" method="GET" style="display:inline;">
        <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
        <button type="submit" class="btn btn-outline">Give Feedback</button>
        </select>
    </form>
<?php endif; ?>
</div>


                            </div>
                            <div class="project-stats">
                                <div class="stat-row">
                                    <span class="stat-label">Status</span>
                                    <span class="status-badge status-<?php 
                                        echo strtolower(str_replace(' ', '', $job['job_status']));
                                    ?>">
                                        <?php echo htmlspecialchars($job['job_status']); ?>
                                    </span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Budget</span>
                                    <span class="stat-value">₹<?php echo number_format($job['budget'], 2); ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Posted On</span>
                                    <span class="stat-value"><?php echo date('M d, Y', strtotime($job['date_posted'])); ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Deadline</span>
                                    <span class="stat-value"><?php 
                                        $deadline = new DateTime($job['deadline']);
                                        $today = new DateTime();
                                        $diff = $today->diff($deadline);
                                        
                                        if ($deadline < $today) {
                                            echo '<span style="color: #dc2626;">Overdue</span>';
                                        } else if ($diff->days <= 3) {
                                            echo '<span style="color: #f59e0b;">' . date('M d, Y', strtotime($job['deadline'])) . '</span>';
                                        } else {
                                            echo date('M d, Y', strtotime($job['deadline']));
                                        }
                                    ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                

            <?php else : ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>You haven't posted any projects yet</h3>
                    <p>Get started by posting your first project and connect with talented freelancers to bring your ideas to life.</p>
                    <a href="add_job.php" class="btn btn-primary">Post Your First Project</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        menuToggle.addEventListener('click', () => {
            body.classList.toggle('show-sidebar');
        });

        // Filter functionality
        const filterButtons = document.querySelectorAll('.filter-btn');
        const jobCards = document.querySelectorAll('.project-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
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
                const title = card.querySelector('.project-title').textContent.toLowerCase();
                const description = card.querySelector('.project-desc').textContent.toLowerCase();

                if (title.includes(searchTerm) || description.includes(searchTerm)) {
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
    });
</script>

</body>
</html>
