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

$freelancer_query = "SELECT profile_id FROM freelancer_profile WHERE user_id = " . $_SESSION['user_id']." ";
$freelancer_result = $conn->query($freelancer_query);
if (!$freelancer_result->num_rows) {
    die("Please create your profile first.");
}
$freelancer_id = $freelancer_result->fetch_assoc()['profile_id'];

$skills_query = "SELECT id, category, skill_name FROM skills ORDER BY category, skill_name";
$skills_result = $conn->query($skills_query);
$skills_by_category = [];
while ($skill = $skills_result->fetch_assoc()) {
    $skills_by_category[$skill['category']][] = $skill;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['skills'])) {
        $delete_sql = "DELETE FROM freelancer_skills WHERE profile_id = $freelancer_id";
        $conn->query($delete_sql);

        $insert_sql = "INSERT INTO freelancer_skills (profile_id, skill_id) VALUES ";
        $values = [];
        foreach ($_POST['skills'] as $skill_id) {
            $skill_id = (int)$skill_id;
            $values[] = "($freelancer_id, $skill_id)";
        }
        $insert_sql .= implode(',', $values);
        
        if ($conn->query($insert_sql)) {
            header('Location: freelancer_dash.php');
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Please select at least one skill.";
    }
}

$selected_skills = [];
$current_skills_query = "SELECT skill_id FROM freelancer_skills WHERE profile_id = $freelancer_id";
$current_skills_result = $conn->query($current_skills_query);
while ($skill = $current_skills_result->fetch_assoc()) {
    $selected_skills[] = $skill['skill_id'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TaskMate - Select Your Skills</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --gradient-start: #3b82f6;
            --gradient-end: #60a5fa;
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }

        .form-container {
            background: white;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 700px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .logo {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .logo::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            border-radius: 2px;
        }

        .logo span:first-child {
            font-size: 2.5rem;
            color: #2563eb;
            font-weight: 800;
        }

        .logo span:last-child {
            font-size: 2.5rem;
            color: #3b82f6;
            font-weight: 800;
        }

        .category-section {
            background: #f8fafc;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
        }

        .category-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .skill-item {
            position: relative;
            padding: 0.5rem;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-checkbox:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .custom-checkbox input {
            display: none;
        }

        .custom-checkbox input:checked + span {
            color: var(--primary-color);
            font-weight: 600;
        }

        .custom-checkbox input:checked + span::before {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .submit-button {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            width: 100%;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(59, 130, 246, 0.2);
        }

        .error-message {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 4px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="skillsForm">
            <?php foreach ($skills_by_category as $category => $skills): ?>
            <div class="category-section">
                <div class="category-title">
                    <i class="fas <?php 
                        echo match($category) {
                            'Development' => 'fa-code',
                            'Editing' => 'fa-edit',
                            'Design' => 'fa-palette',
                            'Data Entry' => 'fa-database',
                            default => 'fa-star'
                        };
                    ?>"></i>
                    <?php echo $category; ?>
                </div>
                <div class="skills-grid">
                    <?php foreach ($skills as $skill): ?>
                    <div class="skill-item">
                        <label class="custom-checkbox">
                            <input type="checkbox" name="skills[]" value="<?php echo $skill['id']; ?>"
                                <?php echo in_array($skill['id'], $selected_skills) ? 'checked' : ''; ?>>
                            <span><?php echo $skill['skill_name']; ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="error-message" id="skillsError"></div>
            <button type="submit" class="submit-button">Save Skills</button>
        </form>
    </div>

    <script>
        document.getElementById('skillsForm').addEventListener('submit', function(e) {
            const checkedSkills = document.querySelectorAll('input[name="skills[]"]:checked');
            const errorElement = document.getElementById('skillsError');
            
            if (checkedSkills.length === 0) {
                e.preventDefault();
                errorElement.textContent = 'Please select at least one skill';
                errorElement.style.display = 'block';
            } else {
                errorElement.style.display = 'none';
            }
        });
    </script>
</body>
</html>