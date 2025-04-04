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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $updateQuery = "UPDATE notifications SET is_read = 1 WHERE user_id = '" . $_SESSION['user_id'] . "'";
    $conn->query($updateQuery);
    header("Location: notifications.php");
    exit();
}

$notificationsQuery = "SELECT * FROM notifications WHERE user_id = '" . $_SESSION['user_id'] . "' ORDER BY created_at DESC";
$notificationsResult = $conn->query($notificationsQuery);

$userQuery = "SELECT role FROM users WHERE id = '" . $_SESSION['user_id'] . "'";
$userResult = $conn->query($userQuery);
$user = $userResult->fetch_assoc();

// Determine back button URL based on user role
$backUrl = ($user['role'] === 'client') ? 'client_dash.php' : 'freelancer_dash.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Notifications</title>
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
            --gradient-start: #3b82f6;
            --gradient-end: #60a5fa;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Main Content */
        .main-content {
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
            text-decoration: none;
        }

        .logout-btn:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--gradient-start));
            transform: translateY(-2px);
        }

        .logout-btn i {
            font-size: 0.9em;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .notification-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .notification-card.unread {
            background: #f8fafc;
            border-left: 4px solid var(--primary-color);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .notification-type {
            font-weight: 600;
            color: #1e293b;
        }

        .notification-time {
            color: #64748b;
            font-size: 0.875rem;
        }

        .notification-message {
            color: #64748b;
            line-height: 1.6;
        }

        .mark-read-btn {
            background: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mark-read-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="header">
            <button class="menu-toggle"><i class="fas fa-bars"></i></button>
            <div class="user-info">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <a href="<?php echo $backUrl; ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <h3 class="section-title">Notifications <?php echo $notificationsResult->num_rows > 0 ? '(' . $notificationsResult->num_rows . ')' : ''; ?></h3>

        <?php if ($notificationsResult->num_rows > 0): ?>
            <form method="post" style="margin-bottom: 20px;">
                <button type="submit" name="mark_read" class="mark-read-btn">
                    <i class="fas fa-check"></i> Mark All as Read
                </button>
            </form>

            <?php while ($notification = $notificationsResult->fetch_assoc()): ?>
                <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                    <div class="notification-header">
                        <span class="notification-type"><?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?></span>
                        <span class="notification-time"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></span>
                    </div>
                    <div class="notification-message">
                        <?php echo htmlspecialchars($notification['message']); ?>
                        <?php if ($notification['related_id'] && $notification['type'] === 'application_accepted'): ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <h3>No notifications</h3>
                <p>You don't have any notifications at the moment.</p>
            </div>
        <?php endif; ?>
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