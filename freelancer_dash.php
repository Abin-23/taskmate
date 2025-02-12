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

$sql = "SELECT * FROM freelancer_profile WHERE user_id='" . $_SESSION['user_id'] . "'";
$result = $conn->query($sql);

if ($result && $result->num_rows <1) {
    header('Location:freelancer_profile.php');    
}

$user = $result->fetch_assoc();
$sql2= "SELECT * FROM users WHERE id='" . $_SESSION['user_id'] . "'";
$result2 = $conn->query($sql2);
$user2 = $result2->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Freelancer Dashboard</title>
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        <ul class="nav-links">
            <li><a href="freelancer_dash.php" class="active"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="#"><i class="fas fa-briefcase"></i>My Jobs</a></li>
            <li><a href="profile_freelancer.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="#"><i class="fas fa-wallet"></i>Earnings</a></li>
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
                <input type="text" placeholder="Search for jobs...">
            </div>
            <div class="user-info">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="notification-dot"></div>
                </div>
                <img src="<?php echo $user['profile_picture'];?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
        <div style="margin-bottom: 30px;">
    <h1 style="color: #1e293b; font-size: 2rem; font-weight: 700;">Welcome back, <?php echo htmlspecialchars($user2['name']); ?>! 👋</h1>
    <p style="color: #64748b; margin-top: 8px;">Here's what's happening with your projects today.</p>
</div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-briefcase"></i>Active Jobs</h3>
                <div class="stat-value">3</div>
                <div class="stat-trend">↑ 2 this week</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-check-circle"></i>Completed Jobs</h3>
                <div class="stat-value">47</div>
                <div class="stat-trend">↑ 12% this month</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-dollar-sign"></i>Total Earnings</h3>
                <div class="stat-value">$2,845</div>
                <div class="stat-trend">↑ $540 vs last month</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-star"></i>Rating</h3>
                <div class="stat-value">4.9/5</div>
                <div class="stat-trend">Based on 38 reviews</div>
            </div>
        </div>

        <div class="recent-jobs">
            <h2>Recent Jobs</h2>
            <table class="jobs-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="project-cell">
                                <div class="project-icon">
                                    <i class="fas fa-laptop-code"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 500; color: #1e293b;">Website Redesign</div>
                                    <div style="font-size: 0.875rem; color: #64748b;">Due in 5 days</div>
                                </div>
                            </div>
                        </td>
                        <td>Tech Corp</td>
                        <td><span class="status-badge status-active"><i class="fas fa-circle"></i>Active</span></td>
                        <td style="font-weight: 500;">$500</td>
                    </tr>
                    <tr>
                        <td>
                            <div class="project-cell">
                                <div class="project-icon">
                                    <i class="fas fa-paint-brush"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 500; color: #1e293b;">Logo Design</div>
                                    <div style="font-size: 0.875rem; color: #64748b;">Due in 2 days</div>
                                </div>
                            </div>
                        </td>
                        <td>StartUp Inc</td>
                        <td><span class="status-badge status-review"><i class="fas fa-clock"></i>Review</span></td>
                        <td style="font-weight: 500;">$200</td>
                    </tr>
                    <tr>
                        <td>
                            <div class="project-cell">
                                <div class="project-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                    </div>
                                <div>
                                    <div style="font-weight: 500; color: #1e293b;">Mobile App UI</div>
                                    <div style="font-size: 0.875rem; color: #64748b;">Due in 7 days</div>
                                </div>
                            </div>
                        </td>
                        <td>App Labs</td>
                        <td><span class="status-badge status-active"><i class="fas fa-circle"></i>Active</span></td>
                        <td style="font-weight: 500;">$800</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Activity Timeline -->
        <div class="recent-jobs" style="margin-top: 30px;">
            <h2>Recent Activity</h2>
            <div class="timeline" style="position: relative; padding: 20px 0;">
                <div class="timeline-item" style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background: #bfdbfe; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                        <i class="fas fa-comment"></i>
                    </div>
                    <div>
                        <div style="font-weight: 500; color: #1e293b;">New Message from Tech Corp</div>
                        <div style="font-size: 0.875rem; color: #64748b;">2 hours ago</div>
                    </div>
                </div>
                <div class="timeline-item" style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background: #bbf7d0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #166534;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <div style="font-weight: 500; color: #1e293b;">Payment Received</div>
                        <div style="font-size: 0.875rem; color: #64748b;">$350 from StartUp Inc</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        menuToggle.addEventListener('click', () => {
            body.classList.toggle('show-sidebar');
        });

        // Hover effects for table rows
        document.querySelectorAll('.jobs-table tbody tr').forEach(row => {
            row.addEventListener('mouseover', () => {
                row.style.transform = 'translateY(-2px)';
                row.style.transition = 'all 0.3s ease';
                row.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
            });

            row.addEventListener('mouseout', () => {
                row.style.transform = 'none';
                row.style.boxShadow = 'none';
            });
        });

        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>