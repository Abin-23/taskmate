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

if (!isset($_SESSION['current_job_id'])) {
    header("Location: client_project.php");
    exit();
}

$job_id = $_SESSION['current_job_id'];
$user_id = $_SESSION['user_id'];

// Fetch job details
$sql = "SELECT * FROM jobs WHERE job_id = ? AND client_id = ? AND job_status = 'Open'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows < 1) {
    header("Location: client_project.php");
    exit();
}

$job = $result->fetch_assoc();

// Fetch all skills
$sql_skills = "SELECT * FROM skills ORDER BY category, skill_name";
$skills_result = $conn->query($sql_skills);
$all_skills = [];
while ($skill = $skills_result->fetch_assoc()) {
    $all_skills[$skill['category']][] = $skill;
}

// Fetch current job skills
$current_skills = explode(',', $job['required_skills']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_title = $conn->real_escape_string($_POST['job_title']);
    $job_description = $conn->real_escape_string($_POST['job_description']);
    $task_category = $conn->real_escape_string($_POST['task_category']);
    $budget = floatval($_POST['budget']);
    $deadline = $conn->real_escape_string($_POST['deadline']);
    $required_skills = implode(',', array_map('intval', $_POST['skills'] ?? []));

    $sql_update = "UPDATE jobs SET job_title=?, job_description=?, task_category=?, budget=?, deadline=?, required_skills=? WHERE job_id=? AND client_id=?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("sssdsisi", $job_title, $job_description, $task_category, $budget, $deadline, $required_skills, $job_id, $user_id);
    
    if ($stmt->execute()) {
        unset($_SESSION['current_job_id']);
        header("Location: client_project.php?updated=1");
        exit();
    } else {
        $error_message = "Failed to update project. Please try again.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - Edit Project</title>
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
            --color-bg: #f8fafc;
            --color-white: #ffffff;
            --color-text: #1e293b;
            --color-text-light: #64748b;
            --color-border: #e2e8f0;
            --color-success: #22c55e;
            --color-danger: #ef4444;
        }

        body {
            background: var(--color-bg);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--color-white);
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
            color: var(--color-text);
            font-weight: 700;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--color-text-light);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .nav-links a:hover {
            background: var(--color-bg);
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .nav-links a.active {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            color: var(--color-white);
        }

        .nav-links a i {
            margin-right: 12px;
            width: 20px;
            font-size: 1.2em;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            animation: fadeIn 0.5s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: var(--color-white);
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .form-container {
            background: var(--color-white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--color-text);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--color-border);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .skill-checkbox {
            display: none;
        }

        .skill-label {
            display: block;
            padding: 10px;
            background: var(--color-bg);
            border: 2px solid var(--color-border);
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }

        .skill-checkbox:checked + .skill-label {
            background: var(--primary-color);
            color: var(--color-white);
            border-color: var(--primary-color);
        }

        .submit-btn {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            color: var(--color-white);
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .submit-btn:hover:not(:disabled) {
            background: linear-gradient(to right, var(--primary-dark), var(--gradient-start));
            transform: translateY(-2px);
        }

        .error-message {
            color: var(--color-danger);
            margin-bottom: 20px;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            <li><a href="client_project.php" class="active"><i class="fas fa-list"></i>My Projects</a></li>
            <li><a href="profile_client.php"><i class="fas fa-user"></i>Profile</a></li>
            <li><a href="payments.php"><i class="fas fa-wallet"></i>Payments</a></li>
            <li><a href="client_settings.php"><i class="fas fa-gear"></i>Settings</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1 style="color: #1e293b; font-size: 1.5rem; font-weight: 600;">Edit Project</h1>
        </div>

        <div class="form-container">
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="job_title">Project Title</label>
                    <input type="text" id="job_title" name="job_title" value="<?php echo htmlspecialchars($job['job_title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="job_description">Project Description</label>
                    <textarea id="job_description" name="job_description" rows="5" required><?php echo htmlspecialchars($job['job_description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="task_category">Task Category</label>
                    <select id="task_category" name="task_category" required>
                        <option value="Development" <?php echo $job['task_category'] === 'Development' ? 'selected' : ''; ?>>Development</option>
                        <option value="Design" <?php echo $job['task_category'] === 'Design' ? 'selected' : ''; ?>>Design</option>
                        <option value="Editing" <?php echo $job['task_category'] === 'Editing' ? 'selected' : ''; ?>>Editing</option>
                        <option value="Data Entry" <?php echo $job['task_category'] === 'Data Entry' ? 'selected' : ''; ?>>Data Entry</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="budget">Budget (₹)</label>
                    <input type="number" id="budget" name="budget" step="0.01" min="0" value="<?php echo htmlspecialchars($job['budget']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input type="date" id="deadline" name="deadline" value="<?php echo htmlspecialchars($job['deadline']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Required Skills</label>
                    <div class="skills-grid">
                        <?php foreach ($all_skills as $category => $skills): ?>
                            <h3 style="grid-column: 1 / -1; color: var(--color-text-light);"><?php echo htmlspecialchars($category); ?></h3>
                            <?php foreach ($skills as $skill): ?>
                                <div>
                                    <input type="checkbox" id="skill_<?php echo $skill['id']; ?>" 
                                           name="skills[]" value="<?php echo $skill['id']; ?>" 
                                           class="skill-checkbox"
                                           <?php echo in_array($skill['id'], $current_skills) ? 'checked' : ''; ?>>
                                    <label for="skill_<?php echo $skill['id']; ?>" class="skill-label">
                                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const jobTitleInput = document.getElementById('job_title');
        const jobDescInput = document.getElementById('job_description');
        const taskCategoryInput = document.getElementById('task_category');
        const budgetInput = document.getElementById('budget');
        const deadlineInput = document.getElementById('deadline');
        const skillCheckboxes = document.querySelectorAll('.skill-checkbox');
        const submitBtn = document.querySelector('.submit-btn');

        // Original values
        const originalValues = {
            job_title: '<?php echo addslashes($job['job_title']); ?>',
            job_description: '<?php echo addslashes($job['job_description']); ?>',
            task_category: '<?php echo addslashes($job['task_category']); ?>',
            budget: '<?php echo addslashes($job['budget']); ?>',
            deadline: '<?php echo addslashes($job['deadline']); ?>'
        };
        const originalSkills = [<?php echo implode(',', array_map('intval', $current_skills)); ?>];

        // Validation messages
        const validationMessages = {};
        [jobTitleInput, jobDescInput, taskCategoryInput, budgetInput, deadlineInput].forEach(input => {
            validationMessages[input.id] = createValidationMessage(input);
        });

        function createValidationMessage(input) {
            const msg = document.createElement('div');
            msg.className = 'validation-message';
            msg.style.cssText = `
                color: #ef4444;
                font-size: 0.875rem;
                margin-top: 5px;
                display: none;
            `;
            input.parentNode.appendChild(msg);
            return msg;
        }

        // Validation functions
        function validateJobTitle() {
            const value = jobTitleInput.value.trim();
            if (!value) return showError(jobTitleInput, 'Project title is required');
            if (value.length < 5) return showError(jobTitleInput, 'Title must be at least 5 characters');
            return showSuccess(jobTitleInput);
        }

        function validateJobDesc() {
            const value = jobDescInput.value.trim();
            if (!value) return showError(jobDescInput, 'Description is required');
            if (value.length < 5) return showError(jobDescInput, 'Description must be at least 5 characters');
            return showSuccess(jobDescInput);
        }

        function validateTaskCategory() {
            const value = taskCategoryInput.value;
            const validOptions = ['Development', 'Design', 'Editing', 'Data Entry'];
            if (!value || !validOptions.includes(value)) {
                return showError(taskCategoryInput, 'Please select a valid category');
            }
            return showSuccess(taskCategoryInput);
        }

        function validateBudget() {
            const value = parseFloat(budgetInput.value);
            if (!value || value <= 0) return showError(budgetInput, 'Budget must be greater than 0');
            return showSuccess(budgetInput);
        }

        function validateDeadline() {
            const value = deadlineInput.value;
            const today = new Date().toISOString().split('T')[0];
            if (!value) return showError(deadlineInput, 'Deadline is required');
            if (value < today) return showError(deadlineInput, 'Deadline cannot be in the past');
            return showSuccess(deadlineInput);
        }

        function validateSkills() {
            return Array.from(skillCheckboxes).filter(cb => cb.checked).length >= 1;
        }

        function showError(input, message) {
            const msg = validationMessages[input.id];
            msg.textContent = message;
            msg.style.display = 'block';
            input.style.borderColor = '#ef4444';
            return false;
        }

        function showSuccess(input) {
            const msg = validationMessages[input.id];
            msg.style.display = 'none';
            input.style.borderColor = '#22c55e';
            return true;
        }

        function hasChanges() {
            const currentValues = {
                job_title: jobTitleInput.value,
                job_description: jobDescInput.value,
                task_category: taskCategoryInput.value,
                budget: budgetInput.value,
                deadline: deadlineInput.value
            };
            const currentSkills = Array.from(skillCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => parseInt(cb.value));

            const valuesChanged = Object.keys(currentValues).some(key => 
                currentValues[key] !== originalValues[key]
            );
            const skillsChanged = JSON.stringify(currentSkills.sort()) !== JSON.stringify(originalSkills.sort());

            return valuesChanged || skillsChanged;
        }

        function updateSubmitButton() {
            const isValid = validateJobTitle() && validateJobDesc() && validateTaskCategory() && 
                           validateBudget() && validateDeadline() && validateSkills();
            const hasChangesFlag = hasChanges();

            submitBtn.disabled = !(isValid && hasChangesFlag);
            submitBtn.style.opacity = isValid && hasChangesFlag ? '1' : '0.5';
        }

        // Event listeners
        jobTitleInput.addEventListener('input', () => { validateJobTitle(); updateSubmitButton(); });
        jobDescInput.addEventListener('input', () => { validateJobDesc(); updateSubmitButton(); });
        taskCategoryInput.addEventListener('change', () => { validateTaskCategory(); updateSubmitButton(); });
        budgetInput.addEventListener('input', () => { validateBudget(); updateSubmitButton(); });
        deadlineInput.addEventListener('change', () => { validateDeadline(); updateSubmitButton(); });
        skillCheckboxes.forEach(cb => cb.addEventListener('change', updateSubmitButton));

        form.addEventListener('submit', (e) => {
            const validations = [
                validateJobTitle(),
                validateJobDesc(),
                validateTaskCategory(),
                validateBudget(),
                validateDeadline(),
                validateSkills()
            ];

            if (!validations.every(v => v) || !hasChanges()) {
                e.preventDefault();
                const firstInvalid = form.querySelector('.form-group input[style*="border-color: rgb(239, 68, 68)"]') ||
                                   form.querySelector('.form-group textarea[style*="border-color: rgb(239, 68, 68)"]') ||
                                   form.querySelector('.form-group select[style*="border-color: rgb(239, 68, 68)"]');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
                if (!hasChanges()) alert('No changes detected.');
                if (!validateSkills()) alert('Please select at least one skill.');
            }
        });

        updateSubmitButton();
    });
    </script>
</body>
</html>