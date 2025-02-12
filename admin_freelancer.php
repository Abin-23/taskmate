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

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = " AND (u.name LIKE '%$search%' OR s.skill_name LIKE '%$search%')";
}


$query = "SELECT DISTINCT 
             u.id,
             u.name,
             u.email,
             fp.bio,
             fp.experience,
             fp.profile_picture,
             GROUP_CONCAT(DISTINCT s.skill_name) as skills,
             GROUP_CONCAT(DISTINCT s.category) as categories
           FROM users u
           INNER JOIN freelancer_profile fp ON u.id = fp.user_id
           LEFT JOIN freelancer_skills fs ON fp.profile_id = fs.profile_id
           LEFT JOIN skills s ON fs.skill_id = s.id
           WHERE u.role = 'freelancer'
             AND u.active = 1" . $searchCondition . "
           GROUP BY u.id";
$result = $conn->query($query);

// Delete freelancer functionality
if (isset($_POST['delete_freelancer'])) {
    $freelancer_id = (int)$_POST['freelancer_id'];
    $delete_query = "UPDATE users SET active = 0 WHERE id = $freelancer_id AND role = 'freelancer'";
    $conn->query($delete_query);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Freelancers</title>
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

        /* Reusing existing styles from dashboard */
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

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.2em;
        }

        /* New styles for freelancers list */
        .freelancers-list {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .freelancer-header {
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr 120px;
            padding: 20px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            color: #1e293b;
        }

        .freelancer-item {
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr 120px;
            padding: 20px;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.3s ease;
        }

        .freelancer-item:hover {
            background-color: #f8fafc;
        }

        .freelancer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .freelancer-info h3 {
            color: #1e293b;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .freelancer-info p {
            color: #64748b;
            font-size: 0.875rem;
        }

        .freelancer-skills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .skill-tag {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
        }

        .freelancer-rating {
            color: #f59e0b;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .freelancer-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .view-btn {
            background: #e0f2fe;
            color: #0369a1;
        }

        .view-btn:hover {
            background: #bae6fd;
        }

        .delete-btn {
            background: #fee2e2;
            color: #b91c1c;
        }

        .delete-btn:hover {
            background: #fecaca;
        }

        @media (max-width: 1024px) {
            .freelancer-header, .freelancer-item {
                grid-template-columns: 80px 2fr 1fr 120px;
            }
            .freelancer-rating {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .freelancer-header, .freelancer-item {
                grid-template-columns: 80px 2fr 120px;
            }
            .freelancer-skills {
                display: none;
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
            <li><a href="admindash.php"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="admin_freelancer.php" class="active"><i class="fas fa-users"></i>Freelancers</a></li>
            <li><a href="admin_client.php"><i class="fas fa-user-tie"></i>Clients</a></li>
            <li><a href="#"><i class="fas fa-briefcase"></i>Jobs</a></li>
            <li><a href="admin_settings.php"><i class="fas fa-cog"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1 style="color: #1e293b; font-size: 1.5rem;">Freelancers</h1>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <form method="GET" action="">
                    <input type="text" name="search" 
                           placeholder="Search freelancers by name, skills..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
        </div>

        <div class="freelancers-list">
            <div class="freelancer-header">
                <div>Profile</div>
                <div>Info</div>
                <div>Skills</div>
                <div>Experience</div>
                <div>Actions</div>
            </div>

            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $skills_array = explode(',', $row['skills']);
                    $profile_pic = !empty($row['profile_picture']) ? 
                        htmlspecialchars($row['profile_picture']) : 
                        '/api/placeholder/50/50';
            ?>
                <div class="freelancer-item">
                    <img src="<?php echo $profile_pic; ?>" 
                         alt="<?php echo htmlspecialchars($row['name']); ?>" 
                         class="freelancer-avatar">
                    <div class="freelancer-info">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p><?php echo htmlspecialchars($row['email']); ?></p>
                    </div>
                    <div class="freelancer-skills">
                        <?php foreach($skills_array as $skill): ?>
                            <span class="skill-tag">
                                <?php echo htmlspecialchars(trim($skill)); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="freelancer-experience">
                        <?php echo htmlspecialchars($row['experience']); ?>
                    </div>
                    <div class="freelancer-actions">
                        <a href="view_profile.php?id=<?php echo $row['id']; ?>" 
                           class="action-btn view-btn">View</a>
                        <form method="POST" 
                              action="" 
                              style="display: inline;" 
                              onsubmit="return confirm('Are you sure you want to delete this freelancer?');">
                            <input type="hidden" 
                                   name="freelancer_id" 
                                   value="<?php echo $row['id']; ?>">
                            <button type="submit" 
                                    name="delete_freelancer" 
                                    class="action-btn delete-btn">Delete</button>
                        </form>
                    </div>
                </div>
            <?php
                }
            } else {
                echo '<div class="no-results">No freelancers found</div>';
            }
            ?>
        </div>
    </div>

    <script>
    // Search form submission
    document.querySelector('.search-bar input').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            this.closest('form').submit();
        }
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>