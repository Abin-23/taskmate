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

// Fetch client profile
$sql_client_profile = "SELECT * FROM client_profile WHERE id=?";
$stmt_client_profile = $conn->prepare($sql_client_profile);
$stmt_client_profile->bind_param("i", $_SESSION['user_id']);
$stmt_client_profile->execute();
$result_client_profile = $stmt_client_profile->get_result();
$client_profile = $result_client_profile->fetch_assoc();

// Fetch user details
$sql_user = "SELECT * FROM users WHERE id=?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

// Fetch payment history
$sql_payments = "
    SELECT jp.*, j.job_title, j.task_category 
    FROM jobs_payments jp 
    JOIN jobs j ON jp.job_id = j.job_id 
    WHERE jp.client_id = ? 
    ORDER BY jp.payment_date DESC
";
$stmt_payments = $conn->prepare($sql_payments);
$stmt_payments->bind_param("i", $_SESSION['user_id']);
$stmt_payments->execute();
$result_payments = $stmt_payments->get_result();

// Calculate total payments and statistics
$sql_payment_stats = "
    SELECT 
        SUM(amount) as total_paid, 
        AVG(amount) as avg_payment,
        COUNT(*) as total_transactions,
        SUM(CASE WHEN payment_status = 'Completed' THEN amount ELSE 0 END) as completed_payments,
        SUM(CASE WHEN payment_status = 'Pending' THEN amount ELSE 0 END) as pending_payments
    FROM jobs_payments 
    WHERE client_id = ?
";
$stmt_payment_stats = $conn->prepare($sql_payment_stats);
$stmt_payment_stats->bind_param("i", $_SESSION['user_id']);
$stmt_payment_stats->execute();
$payment_stats = $stmt_payment_stats->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Payments</title>
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
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        <ul class="nav-links">
            <li><a href="client_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="client_project.php"><i class="fas fa-list"></i>My Projects</a></li>
            <li><a href="profile_client.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="payments.php" class="active"><i class="fas fa-wallet"></i>Payments</a></li>
            <li><a href="client_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <input type="text" placeholder="Search payments...">
            </div>
            <div class="user-info">
                <div class="notification">
                <a href="notifications.php" class="notification">
        <i class="fas fa-bell"></i>
    </a>
                </div>  
                <img src="<?php echo htmlspecialchars($client_profile['profile_picture'] ?? '/api/placeholder/40/40'); ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div style="margin-bottom: 30px;">
            <h1 style="color: #1e293b; font-size: 2rem; font-weight: 700;">Payment History</h1>
            <p style="color: #64748b; margin-top: 8px;">Overview of your transactions and payments</p>
        </div>

        <div class="payment-stats">
            <div class="payment-stat-card">
                <h3><i class="fas fa-money-bill-wave"></i> Total Paid</h3>
                <div class="stat-value">₹<?php echo number_format($payment_stats['total_paid'], 2); ?></div>
            </div>
            <div class="payment-stat-card">
                <h3><i class="fas fa-chart-line"></i> Avg Payment</h3>
                <div class="stat-value">₹<?php echo number_format($payment_stats['avg_payment'], 2); ?></div>
            </div>
            <div class="payment-stat-card">
                <h3><i class="fas fa-receipt"></i> Total Transactions</h3>
                <div class="stat-value"><?php echo $payment_stats['total_transactions']; ?></div>
            </div>
        </div>

        <div style="background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <h2 style="margin-bottom: 20px;">Payment History</h2>
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
                        <?php while($payment = $result_payments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($payment['job_title']); ?></td>
                            <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                            <td>
                                <span class="payment-status status-<?php 
                                    echo strtolower($payment['payment_status']); 
                                ?>">
                                    <?php echo htmlspecialchars($payment['payment_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
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
$stmt_client_profile->close();
$stmt_user->close();
$stmt_payments->close();
$stmt_payment_stats->close();
$conn->close();
?>