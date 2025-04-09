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


$sql = "SELECT * FROM freelancer_profile WHERE user_id='" . $_SESSION['user_id'] . "'";
$result = $conn->query($sql);

if ($result && $result->num_rows < 1) {
    header('Location:freelancer_profile.php');    
}

$user = $result->fetch_assoc();
$sql2 = "SELECT * FROM users WHERE id='" . $_SESSION['user_id'] . "'";
$result2 = $conn->query($sql2);
$user2 = $result2->fetch_assoc();

// Get freelancer's skills
$skills_sql = "SELECT s.skill_name, s.id 
               FROM skills s 
               INNER JOIN freelancer_skills fs ON s.id = fs.skill_id 
               WHERE fs.profile_id = ?";
$stmt = $conn->prepare($skills_sql);
$stmt->bind_param("i", $user['profile_id']);
$stmt->execute();
$skills_result = $stmt->get_result();

// Create array of freelancer's skill IDs
$freelancer_skills = array();
while ($skill = $skills_result->fetch_assoc()) {
    $freelancer_skills[] = $skill['id'];
}

// Build the SQL conditions for matching skills
$skill_conditions = array();
foreach ($freelancer_skills as $skill_id) {
    $skill_conditions[] = "FIND_IN_SET('$skill_id', required_skills) > 0";
}

$jobs_sql = "SELECT DISTINCT j.*, u.name as client_name 
             FROM jobs j 
             INNER JOIN users u ON j.client_id = u.id 
             WHERE j.job_status = 'Open' 
             AND (" . implode(' OR ', $skill_conditions) . ")
             ORDER BY j.date_posted DESC";
$jobs_result = $conn->query($jobs_sql);

// For debugging
if (!$jobs_result) {
    die("Query failed: " . $conn->error);
}
$unreadCountQuery = "SELECT COUNT(*) FROM notifications WHERE user_id = '" . $_SESSION['user_id'] . "' AND is_read = 0";
$unreadCount = $conn->query($unreadCountQuery)->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Job Feed</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, sans-serif;
        }

        :root {
            --primary: #3b82f6;
            --sidebar-width: 250px;
            --header-height: 70px;
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
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .logo span {
            font-size: 24px;
            font-weight: 700;
        }

        .logo span:first-child {
            color: var(--primary);
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-links a:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        .nav-links a.active {
            background: var(--primary);
            color: white;
        }

        .nav-links a i {
            margin-right: 12px;
            width: 20px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .header {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .search-bar {
            flex: 1;
            max-width: 500px;
            margin: 0 20px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 20px 10px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification {
            position: relative;
            padding: 8px;
            cursor: pointer;
        }

        .notification-dot {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }

        /* Grid Layout Styles */
        .job-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        @media (min-width: 768px) {
            .job-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .job-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .job-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .job-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .client-name {
            color: #64748b;
            font-size: 14px;
        }

        .job-budget {
            background: #f0f9ff;
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
        }

        .job-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            color: #64748b;
            font-size: 14px;
            flex-wrap: wrap;
        }

        .job-meta div {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .job-description {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .job-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .tag {
            background: #f1f5f9;
            color: #475569;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
        }

        .job-actions {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .apply-btn {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .apply-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .view-details-btn {
            background: #f1f5f9;
            color: #3b82f6;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .view-details-btn:hover {
            background: #e2e8f0;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1001;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 50px auto;
            padding: 25px;
            width: 90%;
            max-width: 800px;
            border-radius: 12px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

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
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .search-bar {
                width: 100%;
                max-width: none;
                margin: 15px 0;
            }

            .job-meta {
                gap: 10px;
            }
        }
        .user-info {
    display: flex;
    align-items: center;
    gap: 20px; /* Matches your original spacing */
}

.notification {
    position: relative;
    padding: 8px;
    display: inline-block;
    text-decoration: none; /* Removes underline from the link */
    color: #64748b; /* Matches your logout color */
    transition: all 0.3s ease;
}

.notification:hover {
    transform: scale(1.1); /* Slight zoom on hover */
}

.notification i {
    font-size: 1.2rem; /* Slightly larger bell */
}

.notification-dot {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 10px;
    height: 10px;
    background: #ef4444; /* Red dot, can be changed to var(--primary-color) */
    border-radius: 50%;
    border: 2px solid white;
}

.notification-dot::after {
    content: attr(data-count);
    position: absolute;
    top: -8px;
    right: -8px;
    width: 16px;
    height: 16px;
    background: #ef4444;
    border-radius: 50%;
    color: white;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: scale(0);
    transition: transform 0.2s ease;
}

.notification-dot[data-count="1"]::after {
    display: none; /* Show only dot for 1 notification */
}

.notification-dot[data-count]:not([data-count="1"])::after {
    transform: scale(1); /* Show count for 2+ notifications */
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
            <li><a href="freelancer_job.php"><i class="fas fa-briefcase"></i>My Jobs</a></li>
            <li><a href="profile_freelancer.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="earnings.php"><i class="fas fa-wallet"></i>Earnings</a></li>
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
    <a href="notifications.php" class="notification">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <div class="notification-dot" data-count="<?php echo $unreadCount; ?>"></div>
        <?php endif; ?>
    </a>
    <img src="<?php echo $user['profile_picture']; ?>" alt="Profile" style="width: 35px; height: 35px; border-radius: 50%;">
    <a href="logout.php" style="text-decoration: none; color: #64748b;">
        <i class="fas fa-sign-out-alt"></i>
    </a>
</div>
        </div>

        <div class="job-grid">
    <?php if ($jobs_result->num_rows > 0): ?>
        <?php while($job = $jobs_result->fetch_assoc()): ?>
            <div class="job-card">
                <div class="job-header">
                    <div>
                        <div class="job-title"><?php echo htmlspecialchars($job['job_title']); ?></div>
                        <div class="client-name"><?php echo htmlspecialchars($job['client_name']); ?></div>
                    </div>
                    <div class="job-budget">₹<?php echo number_format($job['budget'], 2); ?></div>
                </div>
                
                <div class="job-meta">
                    <div><i class="fas fa-folder"></i> <?php echo htmlspecialchars($job['task_category']); ?></div>
                    <div><i class="fas fa-calendar"></i> Due: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></div>
                </div>
                
                <div class="job-description">
                    <?php echo nl2br(htmlspecialchars(substr($job['job_description'], 0, 100))); ?>...
                </div>
                
                <?php if (!empty($job['tools_software'])): ?>
                    <div class="job-tags">
                        <?php 
                        $tools = explode(',', $job['tools_software']);
                        foreach(array_slice($tools, 0, 3) as $tool): 
                            if(trim($tool)):
                        ?>
                            <span class="tag"><?php echo htmlspecialchars(trim($tool)); ?></span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="job-actions">
                    <button class="view-details-btn" onclick="showJobDetails(<?php echo htmlspecialchars(json_encode($job)); ?>)">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    <a href="job_details.php?id=<?php echo $job['job_id']; ?>" class="apply-btn">
                        <i class="fas fa-paper-plane"></i> Apply Now
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; background: white; border-radius: 12px; grid-column: 1 / -1;">
            <i class="fas fa-briefcase" style="font-size: 48px; color: #cbd5e1; margin-bottom: 20px;"></i>
            <p style="color: #64748b;">No jobs available at the moment. Check back later!</p>
        </div>
    <?php endif; ?>
</div>

        <!-- Modal for job details -->
        <div id="jobModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <div id="modalContent"></div>
            </div>
        </div>
    </div>

    <script>
        // Menu toggle functionality
        const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        menuToggle.addEventListener('click', () => {
            body.classList.toggle('show-sidebar');
        });

        // Search functionality
        const searchInput = document.querySelector('.search-bar input');
        const jobCards = document.querySelectorAll('.job-card');

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            jobCards.forEach(card => {
                const title = card.querySelector('.job-title').textContent.toLowerCase();
                const description = card.querySelector('.job-description').textContent.toLowerCase();
                const category = card.querySelector('.job-meta').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || 
                    description.includes(searchTerm) || 
                    category.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Job details modal functionality
        function showJobDetails(job) {
            const modal = document.getElementById('jobModal');
            const modalContent = document.getElementById('modalContent');
            
            const content = `
                <h2 class="job-title" style="font-size: 24px; margin-bottom: 10px;">${job.job_title}</h2>
                <div class="client-name" style="margin-bottom: 20px; color: #64748b;">Posted by ${job.client_name}</div>
                
                <div class="job-budget" style="margin-bottom: 20px; font-size: 20px; display: inline-block;">
                    Budget: $${parseFloat(job.budget).toFixed(2)}
                </div>
                
                <div class="job-meta" style="margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div><i class="fas fa-folder"></i> ${job.task_category}</div>
                    <div><i class="fas fa-calendar"></i> Due: ${new Date(job.deadline).toLocaleDateString()}</div>
                    <div><i class="fas fa-clock"></i> Posted: ${new Date(job.date_posted).toLocaleDateString()}</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h3 style="margin-bottom: 10px; color: #1e293b;">Job Description</h3>
                    <div class="job-description" style="line-height: 1.6; color: #475569;">
                        ${job.job_description}
                    </div>
                </div>
                
                ${job.tools_software ? `
                    <div style="margin-bottom: 30px;">
                        <h3 style="margin-bottom: 10px; color: #1e293b;">Required Tools & Software</h3>
                        <div class="job-tags" style="display: flex; flex-wrap: wrap; gap: 8px;">
                            ${job.tools_software.split(',').map(tool => `
                                <span class="tag">${tool.trim()}</span>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                <div style="display: flex; justify-content: flex-end; gap: 15px;">
                    <button onclick="closeModal()" style="padding: 10px 20px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; cursor: pointer;">
                        Close
                    </button>
                    <a href="job_details.php?id=${job.job_id}" class="apply-btn" style="padding: 10px 25px;">
                        <i class="fas fa-paper-plane"></i> Apply Now
                    </a>
                </div>
            `;
            
            modalContent.innerHTML = content;
            modal.style.display = 'block';
            
            // Prevent body scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('jobModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('jobModal');
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
    </script>
</body>
</html>