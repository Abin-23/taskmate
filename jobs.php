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

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: signin.php");
    exit();
}

// Fetch all jobs with total paid amount, client, and freelancer details
$sql_jobs = "
    SELECT 
        j.job_id,
        j.client_id,
        j.freelancer_id,
        j.job_title,
        j.task_category,
        j.budget,
        j.job_status,
        j.deadline,
        j.date_posted,
        c.name AS client_name,
        f.name AS freelancer_name,
        jp.payment_status,
        SUM(CASE WHEN jp.payment_status = 'Completed' THEN jp.amount ELSE 0 END) AS total_paid
    FROM jobs j
    LEFT JOIN users c ON j.client_id = c.id
    LEFT JOIN users f ON j.freelancer_id = f.id
    LEFT JOIN jobs_payments jp ON j.job_id = jp.job_id
    GROUP BY j.job_id, j.client_id, j.freelancer_id, j.job_title, j.task_category, j.budget, 
             j.job_status, j.deadline, j.date_posted, c.name, f.name, jp.payment_status
    ORDER BY j.date_posted DESC
";
$result_jobs = $conn->query($sql_jobs);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Admin Jobs</title>
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

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .jobs-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .jobs-table thead {
            background: #f8fafc;
        }

        .jobs-table th, .jobs-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .jobs-table th {
            color: #1e293b;
            font-weight: 600;
            background: #f8fafc;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .jobs-table td {
            color: #475569;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-open {
            background: #fef3c7;
            color: #92400e;
        }

        .status-in-progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .payment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed-payment {
            background: #dcfce7;
            color: #166534;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-refunded {
            background: #e2e8f0;
            color: #475569;
        }

        .budget-paid {
            display: flex;
            flex-direction: column;
        }

        .budget-paid .budget {
            color: #1e293b;
            font-weight: 600;
        }

        .budget-paid .paid {
            color: #64748b;
            font-size: 0.9em;
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

            .table-container {
                overflow-x: auto;
            }

            .jobs-table {
                min-width: 1000px;
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
            <li><a href="admin_dash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="jobs.php" class="active"><i class="fas fa-briefcase"></i>Jobs</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i>Users</a></li>
            <li><a href="payments.php"><i class="fas fa-wallet"></i>Payments</a></li>
            <li><a href="admin_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <input type="text" placeholder="Search jobs...">
            </div>
            <div class="user-info">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="notification-dot"></div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div style="margin-bottom: 30px;">
            <h1 style="color: #1e293b; font-size: 2rem; font-weight: 700;">Job Management</h1>
            <p style="color: #64748b; margin-top: 8px;">Overview of all jobs in the system</p>
        </div>

        <div class="table-container">
            <table class="jobs-table">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Job Title</th>
                        <th>Client</th>
                        <th>Freelancer</th>
                        <th>Category</th>
                        <th>Budget / Paid</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Date Posted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_jobs->num_rows > 0): ?>
                        <?php while ($job = $result_jobs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $job['job_id']; ?></td>
                                <td><?php echo htmlspecialchars($job['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($job['client_name']); ?></td>
                                <td><?php echo $job['freelancer_name'] ? htmlspecialchars($job['freelancer_name']) : 'Not Assigned'; ?></td>
                                <td><?php echo htmlspecialchars($job['task_category']); ?></td>
                                <td>
                                    <div class="budget-paid">
                                        <span class="budget">₹<?php echo number_format($job['budget'], 2); ?></span>
                                        <span class="paid">Paid: ₹<?php echo number_format($job['total_paid'] ?? 0, 2); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $job['job_status'])); ?>">
                                        <?php echo htmlspecialchars($job['job_status']); ?>
                                    </span>
                                </td>
                               
                                <td><?php echo date('d M Y', strtotime($job['deadline'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($job['date_posted'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">No jobs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
$conn->close();
?>