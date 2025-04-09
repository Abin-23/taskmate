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

$sql = "SELECT * FROM skills ORDER BY category, skill_name";
$result = $conn->query($sql);
$skills = [];
while($row = $result->fetch_assoc()) {
    $skills[$row['category']][] = [
        'id' => $row['id'],
        'name' => $row['skill_name']
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $job_title = $_POST['job_title'];
    $job_description = $_POST['job_description'];
    $task_category = $_POST['task_category'];
    $required_skills = implode(',', $_POST['required_skills'] ?? []);
    $tools_software = $_POST['tools_software'];
    $budget = $_POST['budget'];
    $deadline = $_POST['deadline'];
    $posting_fee = 10.00;

    $sql = "INSERT INTO jobs (client_id, job_title, job_description, task_category, 
            required_skills, tools_software, budget, job_status, deadline, posting_fee) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Open', ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssdsd", $_SESSION['user_id'], $job_title, $job_description, 
                      $task_category, $required_skills, $tools_software, $budget, $deadline, $posting_fee);
    
    if ($stmt->execute()) {
        header("Location: client_dash.php");
        exit();
    } else {
        $error = "Error posting job: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Post New Job</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, sans-serif;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #3b82f6;
            --accent: #0ea5e9;
            --success: #22c55e;
            --background: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
            color: var(--text-primary);
        }

        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border: 1px solid var(--primary);
            color: var(--primary);
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .back-button:hover {
            background: var(--primary);
            color: white;
        }

        .main-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .form-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .form-header h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .form-content {
            padding: 2rem;
        }

        .section {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            transition: all 0.2s;
            font-size: 1rem;
        }

        input:focus, textarea:focus, select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
        }

        .skill-checkbox {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .skill-checkbox:hover {
            border-color: var(--primary);
            background: #eef2ff;
        }

        .submit-button {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.2);
        }

        .fee-notice {
            text-align: center;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1rem auto;
            }

            .form-header {
                padding: 2rem 1rem;
            }

            .form-content {
                padding: 1rem;
            }

            .section {
                padding: 1rem;
            }

            .skills-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="#" class="logo">TaskMate</a>
            <a href="client_dash.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </header>

    <div class="main-container">
        <div class="form-card">
            <div class="form-header">
                <h1>Create a New Job</h1>
                <p>Connect with talented freelancers to bring your project to life</p>
            </div>

            <div class="form-content">
                <form method="POST" action="">
                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-briefcase"></i>
                            Job Details
                        </h2>
                        <div class="form-group">
                            <label for="job_title">Job Title</label>
                            <input type="text" id="job_title" name="job_title" 
                                placeholder="Enter a clear and concise title" required>
                        </div>

                        <div class="form-group">
                            <label for="job_description">Job Description</label>
                            <textarea id="job_description" name="job_description" rows="6" 
                                placeholder="Describe your project requirements and expectations" required></textarea>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-tags"></i>
                            Category & Skills
                        </h2>
                        <div class="form-group">
                            <label for="task_category">Category</label>
                            <select id="task_category" name="task_category" required>
                                <option value="">Select a category</option>
                                <option value="Development">Development</option>
                                <option value="Editing">Editing</option>
                                <option value="Design">Design</option>
                                <option value="Data Entry">Data Entry</option>
                            </select>
                        </div>

                        <label>Required Skills</label>
                        <div class="skills-section">
                            <?php foreach ($skills as $category => $categorySkills): ?>
                                <div class="category-skills" data-category="<?php echo $category; ?>" style="display: none;">
                                    <div class="skills-grid">
                                        <?php foreach ($categorySkills as $skill): ?>
                                            <label class="skill-checkbox">
                                                <input type="checkbox" name="required_skills[]" value="<?php echo $skill['id']; ?>">
                                                <?php echo htmlspecialchars($skill['name']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="no-category-message">
                                <i class="fas fa-list-check" style="font-size: 24px; color: #94a3b8; margin-bottom: 10px;"></i>
                                <p>Please select a category to view relevant skills</p>
                            </div>
                        </div>
                    </div>
                </div>



                    <div class="section">
                        <h2 class="section-title">
                            <i class="fas fa-cog"></i>
                            Project Requirements
                        </h2>
                        <div class="form-group">
                            <label for="tools_software">Required Tools/Software</label>
                            <input type="text" id="tools_software" name="tools_software" 
                                placeholder="e.g., Photoshop, VS Code, Figma">
                        </div>

                        <div class="form-group">
                            <label for="budget">Budget</label>
                            <input type="number" id="budget" name="budget" min="1" step="0.01" 
                                placeholder="Enter your budget" required>
                        </div>

                        <div class="form-group">
                            <label for="deadline">Deadline</label>
                            <input type="date" id="deadline" name="deadline" required>
                        </div>
                    </div>

                    <button type="submit" class="submit-button">
                        <i class="fas fa-paper-plane"></i>
                        Post Job
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('deadline').min = today;

        document.getElementById('task_category').addEventListener('change', function() {
            const selectedCategory = this.value;
            const allCategorySkills = document.querySelectorAll('.category-skills');
            const noTaskMessage = document.querySelector('.no-category-message');
            
            allCategorySkills.forEach(category => {
                category.style.display = 'none';
                category.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
            });

            if (selectedCategory === '') {
                noTaskMessage.style.display = 'block';
            } else {
                noTaskMessage.style.display = 'none';
                const selectedSkills = document.querySelector(`.category-skills[data-category="${selectedCategory}"]`);
                if (selectedSkills) {
                    selectedSkills.style.display = 'block';
                }
            }
        });
    </script>
</body>
</html>