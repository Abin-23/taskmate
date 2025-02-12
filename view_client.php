<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "taskmate";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = "SELECT 
    u.id,
    u.name,
    u.email,
    u.mobile,
    u.created_at,
    cp.company_name,
    cp.website,
    cp.address,
    cp.profile_picture
FROM users u
INNER JOIN client_profile cp ON u.id = cp.id
WHERE u.id = ? AND u.role = 'client' AND u.active = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

// If client not found, redirect to client list
if (!$client) {
    header("Location: admin_client.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - View Client Profile</title>
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

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }

        .profile-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e2e8f0;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f1f5f9;
        }

        .profile-info h1 {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .company-name {
            color: #64748b;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .profile-meta {
            display: flex;
            gap: 20px;
            color: #64748b;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-item i {
            color: var(--primary-color);
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .contact-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            transition: transform 0.3s ease;
        }

        .contact-item:hover {
            transform: translateY(-2px);
        }

        .contact-item h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .contact-item h3 i {
            color: var(--primary-color);
        }

        .contact-item p {
            color: #1e293b;
            font-weight: 500;
        }

        .contact-item a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .show-sidebar .sidebar {
                transform: translateX(0);
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-meta {
                justify-content: center;
            }

            .contact-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        <ul class="nav-links">
            <li><a href="admindash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="admin_freelancer.php"><i class="fas fa-users"></i>Freelancers</a></li>
            <li><a href="admin_client.php" class="active"><i class="fas fa-user-tie"></i>Clients</a></li>
            <li><a href="jobs.php"><i class="fas fa-briefcase"></i>Jobs</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <a href="admin_client.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Clients
        </a>

        <div class="profile-container">
            <div class="profile-header">
                <img src="<?php echo !empty($client['profile_picture']) ? htmlspecialchars($client['profile_picture']) : '/api/placeholder/120/120'; ?>" 
                     alt="<?php echo htmlspecialchars($client['name']); ?>" 
                     class="profile-image">
                
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($client['name']); ?></h1>
                    <div class="company-name">
                        <i class="fas fa-building"></i>
                        <?php echo htmlspecialchars($client['company_name']); ?>
                    </div>
                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Member since <?php echo date('M Y', strtotime($client['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-address-card"></i>
                    Contact Information
                </h2>
                <div class="contact-info">
                    <div class="contact-item">
                        <h3><i class="fas fa-envelope"></i>Email</h3>
                        <p><?php echo htmlspecialchars($client['email']); ?></p>
                    </div>
                    <div class="contact-item">
                        <h3><i class="fas fa-phone"></i>Phone</h3>
                        <p><?php echo htmlspecialchars($client['mobile']); ?></p>
                    </div>
                    <?php if (!empty($client['website'])): ?>
                    <div class="contact-item">
                        <h3><i class="fas fa-globe"></i>Website</h3>
                        <p><a href="<?php echo htmlspecialchars($client['website']); ?>" 
                              target="_blank">
                            <?php echo htmlspecialchars($client['website']); ?>
                            <i class="fas fa-external-link-alt"></i>
                        </a></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Address
                </h2>
                <div class="contact-item">
                    <p><?php echo nl2br(htmlspecialchars($client['address'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                body.classList.toggle('show-sidebar');
            });
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>