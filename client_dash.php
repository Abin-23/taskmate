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

        /* Animation Keyframes */
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
            <li><a href="#" class="active"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="#"><i class="fas fa-list"></i>My Projects</a></li>
            <li><a href="#"><i class="fas fa-users"></i>Freelancers</a></li>
            <li><a href="#"><i class="fas fa-message"></i>Messages</a></li>
            <li><a href="#"><i class="fas fa-wallet"></i>Payments</a></li>
            <li><a href="#"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <button class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search projects or freelancers...">
            </div>
            <div class="user-info">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <div class="notification-dot"></div>
                </div>
                <img src="/api/placeholder/40/40" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            </div>
        </div>

        <div class="quick-actions">
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h3>Post New Project</h3>
            </div>
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Hire Freelancer</h3>
            </div>
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h3>View Invoices</h3>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-briefcase"></i>Active Projects</h3>
                <div class="stat-value">5</div>
                <div class="stat-trend">↑ 2 new this week</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-check-circle"></i>Completed</h3>
                <div class="stat-value">12</div>
                <div class="stat-trend">↑ 3 this month</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-dollar-sign"></i>Total Spent</h3>
                <div class="stat-value">$8,450</div>
                <div class="stat-trend">Within budget</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-users"></i>Active Freelancers</h3>
                <div class="stat-value">7</div>
                <div class="stat-trend">Top rated</div>
            </div>
        </div>

        <div class="project-grid">
            <div class="project-card">
                <div class="project-header">
                    <h3>Website Redesign</h3>
                    <p>Tech Stack: HTML, CSS, JavaScript</p>
                </div>
                <div class="project-body">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 75%"></div>
                    </div>
                    <p>Progress: 75%</p>
                    <div style="margin-top: 15px;">
                        <span class="status-badge status-active">Active</span>
                    </div>
                    <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span>Budget: $3,000</span>
                        <span>Due: 7 days</span>
                    </div>
                </div>
            </div>

            <div class="project-card">
                <div class="project-header">
                    <h3>Mobile App Development</h3>
                    <p>Platform: iOS & Android</p>
                </div>
                <div class="project-body">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 30%"></div>
                    </div>
                    <p>Progress: 30%</p>
                    <div style="margin-top: 15px;">
                        <span class="status-badge status-pending">In Review</span>
                    </div>
                    <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span>Budget: $5,000</span>
                        <span>Due: 14 days</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="budget-section">
            <div class="budget-header">
                <h2>Budget Overview</h2>
                <select style="padding: 8px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <option>This Month</option>
                    <option>Last Month</option>
                    <option>Last 3 Months</option>
                </select>
            </div>
            <div class="budget-chart">
                <canvas id="budgetChart"></canvas>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <h4>Total Budget</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">$15,000</p>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <h4>Spent</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #16a34a;">$8,450</p>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <h4>Remaining</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #dc2626;">$6,550</p>
                </div>
            </div>
        </div>

        <!-- Freelancer Section -->
        <div style="background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <h2 style="margin-bottom: 20px;">Active Freelancers</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8fafc; border-radius: 12px;">
                    <img src="/api/placeholder/50/50" alt="Freelancer" style="width: 50px; height: 50px; border-radius: 50%;">
                    <div>
                        <h4>John Doe</h4>
                        <p style="color: #64748b;">Web Developer</p>
                        <div style="display: flex; gap: 5px; color: #f59e0b;">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8fafc; border-radius: 12px;">
                    <img src="/api/placeholder/50/50" alt="Freelancer" style="width: 50px; height: 50px; border-radius: 50%;">
                    <div>
                        <h4>Jane Smith</h4>
                        <p style="color: #64748b;">UI Designer</p>
                        <div style="display: flex; gap: 5px; color: #f59e0b;">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
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