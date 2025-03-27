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
$sql_freelancer_profile = "SELECT * FROM freelancer_profile WHERE user_id=?";
$stmt_freelancer_profile = $conn->prepare($sql_freelancer_profile);
$stmt_freelancer_profile->bind_param("i", $_SESSION['user_id']);
$stmt_freelancer_profile->execute();
$result_freelancer_profile = $stmt_freelancer_profile->get_result();
$freelancer_profile = $result_freelancer_profile->fetch_assoc();

// Fetch user details
$sql_user = "SELECT * FROM users WHERE id=?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

// Fetch earnings history from jobs_payments
$sql_earnings = "
    SELECT jp.*, j.job_title, j.task_category 
    FROM jobs_payments jp 
    JOIN jobs j ON jp.job_id = j.job_id 
    JOIN applications ja ON j.job_id = ja.job_id
    WHERE ja.freelancer_id = ? AND ja.application_status = 'Accepted'
    ORDER BY jp.payment_date DESC
";
$stmt_earnings = $conn->prepare($sql_earnings);
$stmt_earnings->bind_param("i", $_SESSION['user_id']);
$stmt_earnings->execute();
$result_earnings = $stmt_earnings->get_result();

// Calculate total earnings and statistics
$sql_earning_stats = "
    SELECT 
        SUM(jp.amount) as total_earned, 
        AVG(jp.amount) as avg_earning,
        COUNT(*) as total_transactions,
        SUM(CASE WHEN jp.payment_status = 'Completed' THEN jp.amount ELSE 0 END) as completed_earnings,
        SUM(CASE WHEN jp.payment_status = 'Pending' THEN jp.amount ELSE 0 END) as pending_earnings
    FROM jobs_payments jp
    JOIN applications ja ON jp.job_id = ja.job_id
    WHERE ja.freelancer_id = ? AND ja.application_status = 'Accepted'
";
$stmt_earning_stats = $conn->prepare($sql_earning_stats);
$stmt_earning_stats->bind_param("i", $_SESSION['user_id']);
$stmt_earning_stats->execute();
$earning_stats = $stmt_earning_stats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Earnings</title>
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

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }

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
            text-decoration: none;
        }

        .logout-btn:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--gradient-start));
            transform: translateY(-2px);
        }

        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .payment-stat-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .payment-stat-card:hover {
            transform: translateY(-5px);
        }

        .payment-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 15px;
        }

        .payment-table thead {
            background: #f8fafc;
        }

        .payment-table th, .payment-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .payment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

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
            <li><a href="freelancer_job.php"><i class="fas fa-briefcase"></i>My Jobs</a></li>
            <li><a href="profile_freelancer.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="earnings.php" class="active"><i class="fas fa-wallet"></i>Earnings</a></li>
            <li><a href="freelancer_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <input type="text" placeholder="Search earnings...">
            </div>
            <div class="user-info">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="notification-dot"></div>
                </div>  
                <img src="<?php echo htmlspecialchars($freelancer_profile['profile_picture'] ?? '/api/placeholder/40/40'); ?>" 
                     alt="Profile" 
                     style="width: 40px; height: 40px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div style="margin-bottom: 30px;">
            <h1 style="color: #1e293b; font-size: 2rem; font-weight: 700;">Earnings History</h1>
            <p style="color: #64748b; margin-top: 8px;">Overview of your earnings and transactions</p>
        </div>

        <div class="payment-stats">
            <div class="payment-stat-card">
                <h3><i class="fas fa-money-bill-wave"></i> Total Earned</h3>
                <div class="stat-value">₹<?php echo number_format($earning_stats['total_earned'] ?? 0, 2); ?></div>
            </div>
            <div class="payment-stat-card">
                <h3><i class="fas fa-chart-line"></i> Avg Earning</h3>
                <div class="stat-value">₹<?php echo number_format($earning_stats['avg_earning'] ?? 0, 2); ?></div>
            </div>
            <div class="payment-stat-card">
                <h3><i class="fas fa-receipt"></i> Total Transactions</h3>
                <div class="stat-value"><?php echo $earning_stats['total_transactions'] ?? 0; ?></div>
            </div>
        </div>

        <div style="background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <h2 style="margin-bottom: 20px;">Earnings History</h2>
            <div style="overflow-x: auto;">
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($earning = $result_earnings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($earning['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($earning['job_title']); ?></td>
                            <td>₹<?php echo number_format($earning['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($earning['payment_method']); ?></td>
                            <td>
                                <span class="payment-status status-<?php 
                                    echo strtolower($earning['payment_status']); 
                                ?>">
                                    <?php echo htmlspecialchars($earning['payment_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($result_earnings->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No earnings recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
<?php
$stmt_freelancer_profile->close();
$stmt_user->close();
$stmt_earnings->close();
$stmt_earning_stats->close();
$conn->close();
?>