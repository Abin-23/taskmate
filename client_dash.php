<?php
session_start();
$host = 'localhost';
$db = 'taskmate';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$sql_client_profile = "SELECT * FROM client_profile WHERE id=?";
$stmt_client_profile = $conn->prepare($sql_client_profile);
$stmt_client_profile->bind_param("i", $_SESSION['user_id']);
$stmt_client_profile->execute();
$result_client_profile = $stmt_client_profile->get_result();

if ($result_client_profile->num_rows < 1) {
    header('Location: client_profile.php');    
    exit();
}
$client_profile = $result_client_profile->fetch_assoc();

$sql_user = "SELECT * FROM users WHERE id=?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

$sql_active_projects = "SELECT * FROM jobs WHERE client_id=? AND job_status IN ('Open', 'In Progress')";
$stmt_active_projects = $conn->prepare($sql_active_projects);
$stmt_active_projects->bind_param("i", $_SESSION['user_id']);
$stmt_active_projects->execute();
$result_active_projects = $stmt_active_projects->get_result();
$active_projects_count = $result_active_projects->num_rows;

$sql_completed_projects = "SELECT * FROM jobs WHERE client_id=? AND job_status = 'Completed'";
$stmt_completed_projects = $conn->prepare($sql_completed_projects);
$stmt_completed_projects->bind_param("i", $_SESSION['user_id']);
$stmt_completed_projects->execute();
$result_completed_projects = $stmt_completed_projects->get_result();
$completed_projects_count = $result_completed_projects->num_rows;

// Fetch total spent
$sql_total_spent = "SELECT SUM(amount) as total_spent FROM jobs_payments WHERE client_id=?";
$stmt_total_spent = $conn->prepare($sql_total_spent);
$stmt_total_spent->bind_param("i", $_SESSION['user_id']);
$stmt_total_spent->execute();
$result_total_spent = $stmt_total_spent->get_result();
$total_spent = $result_total_spent->fetch_assoc()['total_spent'] ?? 0;

$sql_recent_projects = "SELECT * FROM jobs WHERE client_id=? ORDER BY date_posted DESC LIMIT 2";
$stmt_recent_projects = $conn->prepare($sql_recent_projects);
$stmt_recent_projects->bind_param("i", $_SESSION['user_id']);
$stmt_recent_projects->execute();
$result_recent_projects = $stmt_recent_projects->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Client Dashboard</title>
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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            background: #f0f9ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--primary-color);
            font-size: 24px;
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

        /* Project Cards */
        .project-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
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
        }

        .project-header {
            padding: 20px;
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            color: white;
        }

        .project-body {
            padding: 20px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            margin: 15px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            border-radius: 4px;
            transition: width 0.3s ease;
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

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        /* Budget Section */
        .budget-section {
            background: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .budget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .budget-chart {
            height: 300px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
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

            .project-grid {
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
            <li><a href="client_dash.php" class="active"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="client_project.php"><i class="fas fa-list"></i>My Projects</a></li>
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
                <input type="text" placeholder="Search projects or freelancers...">
            </div>
            <div class="user-info">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="notification-dot"></div>
                </div>  
                <img src="<?php echo htmlspecialchars($client_profile['profile_picture'] ?? '/api/placeholder/40/40'); ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
        <div style="margin-bottom: 30px;">
            <h1 style="color: #1e293b; font-size: 2rem; font-weight: 700;">Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h1>
            <p style="color: #64748b; margin-top: 8px;">Here's what's happening with your jobs today.</p>
        </div>
        <div class="quick-actions">
            <a href="add_job.php" class="action-card" style="text-decoration: none; color: inherit;">
                <div class="action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h3>Post New Project</h3>
            </a>
            
            <a href="payments.php" class="action-card" style="text-decoration: none; color: inherit;">
                <div class="action-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h3>View Invoices</h3>
            </a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-briefcase"></i>Active Projects</h3>
                <div class="stat-value"><?php echo $active_projects_count; ?></div>
                <div class="stat-trend">
                    <?php 
                    // You might want to implement a more sophisticated method to track new projects
                    echo $active_projects_count > 0 ? "↑ New projects this week" : "No active projects"; 
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-check-circle"></i>Completed</h3>
                <div class="stat-value"><?php echo $completed_projects_count; ?></div>
                <div class="stat-trend">
                    <?php 
                    // Similar to above, you might want a more precise way to track monthly completions
                    echo $completed_projects_count > 0 ? "↑ Completed this month" : "No completed projects"; 
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-dollar-sign"></i>Total Spent</h3>
                <div class="stat-value">₹<?php echo number_format($total_spent, 2); ?></div>
                <div class="stat-trend">Budget tracking</div>
            </div>
        </div>

        <div class="project-grid">
            <?php while($project = $result_recent_projects->fetch_assoc()): ?>
            <div class="project-card">
                <div class="project-header">
                    <h3><?php echo htmlspecialchars($project['job_title']); ?></h3>
                    <p><?php echo htmlspecialchars($project['task_category']); ?></p>
                </div>
                <div class="project-body">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php 
                            echo $project['job_status'] == 'Completed' ? '100' : 
                                 ($project['job_status'] == 'In Progress' ? '50' : '25'); 
                        ?>%"></div>
                    </div>
                    <p>Status: <?php echo htmlspecialchars($project['job_status']); ?></p>
                    <div style="margin-top: 15px;">
                        <span class="status-badge <?php 
                            echo $project['job_status'] == 'Open' ? 'status-active' : 
                                 ($project['job_status'] == 'Completed' ? 'status-completed' : 'status-pending'); 
                        ?>">
                            <?php echo htmlspecialchars($project['job_status']); ?>
                        </span>
                    </div>
                    <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span>Budget: ₹<?php echo number_format($project['budget'], 2); ?></span>
                        <span>Deadline: <?php echo date('d M Y', strtotime($project['deadline'])); ?></span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script>
          // Menu Toggle
          const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        menuToggle.addEventListener('click', () => {
            body.classList.toggle('show-sidebar');
        });

        // Budget Chart
        const ctx = document.getElementById('budgetChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Budget Spent',
                    data: [2000, 3500, 4200, 6000, 7200, 8450],
                    borderColor: var(--primary-color),
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Add hover effects
        document.querySelectorAll('.project-card, .action-card').forEach(card => {
            card.addEventListener('mouseover', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.transition = 'all 0.3s ease';
                card.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
            });

            card.addEventListener('mouseout', () => {
                card.style.transform = 'none';
                card.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
            });
        });
    </script>
</body>
</html>
<?php
// Close all prepared statements and database connection
$stmt_client_profile->close();
$stmt_user->close();
$stmt_active_projects->close();
$stmt_completed_projects->close();
$stmt_total_spent->close();
$stmt_recent_projects->close();
$conn->close();
?>