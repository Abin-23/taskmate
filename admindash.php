<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "taskmate";

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql_freelancers = "SELECT COUNT(DISTINCT f.user_id) AS total_freelancers 
                    FROM freelancer_profile f 
                    INNER JOIN users u ON f.user_id = u.id 
                    WHERE u.active = 1";
$result_freelancers = $conn->query($sql_freelancers);
$total_freelancers = ($result_freelancers->num_rows > 0) ? $result_freelancers->fetch_assoc()['total_freelancers'] : 0;

$sql_clients = "SELECT COUNT(DISTINCT c.id) AS total_clients 
                FROM client_profile c 
                INNER JOIN users u ON c.id = u.id 
                WHERE u.active = 1";
$result_clients = $conn->query($sql_clients);
$total_clients = ($result_clients->num_rows > 0) ? $result_clients->fetch_assoc()['total_clients'] : 0;

$sql_recent = "SELECT 
    u.name,
    u.role,
    u.created_at,
    CASE 
        WHEN u.role = 'freelancer' THEN fp.profile_picture
        WHEN u.role = 'client' THEN cp.profile_picture
        ELSE NULL
    END as profile_picture
    FROM users u
    LEFT JOIN freelancer_profile fp ON u.id = fp.user_id
    LEFT JOIN client_profile cp ON u.id = cp.id
    WHERE u.active = 1
    ORDER BY u.created_at DESC
    LIMIT 3";
$result_recent = $conn->query($sql_recent);
$recent_activities = array();
while($row = $result_recent->fetch_assoc()) {
    $recent_activities[] = $row;
}

$formatted_activities = array();
foreach($recent_activities as $activity) {
    $time_ago = time_elapsed_string($activity['created_at']);
    $formatted_activities[] = array(
        'name' => $activity['name'],
        'role' => ucfirst($activity['role']),
        'time_ago' => $time_ago,
        'profile_picture' => $activity['profile_picture'] ?? '/api/placeholder/40/40'
    );
}

function time_elapsed_string($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return 'just now';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Admin Dashboard</title>
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

        /* Recent Activity */
        .recent-activity {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .recent-activity h2 {
            margin-bottom: 20px;
            color: #1e293b;
            font-size: 1.5rem;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-details {
            flex-grow: 1;
        }

        .activity-title {
            font-weight: 500;
            color: #1e293b;
        }

        .activity-time {
            font-size: 0.875rem;
            color: #64748b;
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
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        <ul class="nav-links">
            <li><a href="admindash.php" class="active"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="admin_freelancer.php"><i class="fas fa-users"></i>Freelancers</a></li>
            <li><a href="admin_client.php"><i class="fas fa-user-tie"></i>Clients</a></li>
            <li><a href="jobs.php"><i class="fas fa-briefcase"></i>Jobs</a></li>
            <li><a href="admin_settings.php"><i class="fas fa-cog"></i>Settings</a></li>
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
                <img src="profile.png" alt="Admin Profile" style="width: 40px; height: 40px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-users"></i>Total Freelancers</h3>
                <div class="stat-value"><?php echo $total_freelancers ?></div>
                <div class="stat-trend"></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-user-tie"></i>Total Clients</h3>
                <div class="stat-value"><?php echo $total_clients ?></div>
                <div class="stat-trend"></div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-briefcase"></i>Active Jobs</h3>
                <div class="stat-value">42</div>
                <div class="stat-trend">↑ 5 this week</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-dollar-sign"></i>Total Revenue</h3>
                <div class="stat-value">₹45,670</div>
                <div class="stat-trend">↑ 22% this month</div>
            </div>
        </div>

        <div class="recent-activity">
            <h2>Recent Activity</h2>
            <div class="activity-list">
                <?php foreach($formatted_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon" style="background-color: <?php echo ($activity['role'] === 'Freelancer') ? '#bfdbfe' : '#bbf7d0'; ?>;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="activity-details">
                        <div class="activity-title">New <?php echo $activity['role']; ?> Registered</div>
                        <div class="activity-time"><?php echo htmlspecialchars($activity['name']); ?> joined the platform</div>
                    </div>
                    <div class="activity-time"><?php echo $activity['time_ago']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
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