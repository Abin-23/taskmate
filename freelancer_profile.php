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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $experience = $conn->real_escape_string($_POST['experience']);
    $bio = trim($conn->real_escape_string($_POST['bio']));
    $portfolio = isset($_POST['portfolio']) ? trim($conn->real_escape_string($_POST['portfolio'])) : '';

    // Server-side validation
    if (strlen($bio) < 10) {
        die("Invalid input: Bio must be at least 10 characters.");
    }

    if (!in_array($experience, ['beginner', 'intermediate', 'expert'])) {
        die("Invalid experience level selected.");
    }

    if ($portfolio && !filter_var($portfolio, FILTER_VALIDATE_URL)) {
        die("Invalid portfolio URL.");
    }

 // Profile Picture Upload
$profilePic = '';
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileTmp = $_FILES['profile_picture']['tmp_name'];
    $fileSize = $_FILES['profile_picture']['size'];
    $fileName = basename($_FILES['profile_picture']['name']);
    $uploadDir = 'uploads/';

    // Ensure uploads directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileType = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);

    if (!in_array($fileType, $allowedTypes)) {
        die("Invalid file type. Only JPG, PNG, and GIF are allowed.");
    }

    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $uniqueFileName = uniqid('profile_', true) . '.' . $fileExt;

    if (move_uploaded_file($fileTmp, $uploadDir . $uniqueFileName)) {
        $profilePic = $uploadDir . $uniqueFileName;
    } else {
        die("Error uploading profile picture. Check folder permissions.");
    }
}

    $upi_id = trim($conn->real_escape_string($_POST['upi_id']));

     $sql = "INSERT INTO freelancer_profile (user_id, experience, bio, portfolio_link, profile_picture, upi_id)
        VALUES ('" . $_SESSION['user_id'] . "', '$experience', '$bio', '$portfolio', '$profilePic', '$upi_id')";


    if ($conn->query($sql) === TRUE) {
        header('Location: fetch_skills.php');
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TaskMate - Create Freelancer Profile</title>
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
    box-sizing: border-box;
}

.form-container {
    background: white;
    padding: 2.5rem;
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    width: 90%;
    max-width: 700px;
    animation: slideUp 0.5s ease;
    box-sizing: border-box;
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.logo {
    text-align: center;
    margin-bottom: 2rem;
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

.logo span {
    font-size: 2.2rem;
    font-weight: 800;
}

.logo span:first-child {
    background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
    -webkit-background-clip: text;
    color: #2563eb;
}

.logo span:last-child {
    color: #3b82f6;
}

/* PROFILE SECTION */
.profile-section {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    text-align: center;
}

.profile-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 3px dashed #e2e8f0;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.profile-preview:hover {
    border-color: var(--primary-color);
    transform: scale(1.05);
}

/* FORM CONTENT */
.form-content {
    display: flex;
    flex-direction: column;
    gap: 1.3rem;
}

/* INPUT GROUP */
.input-group {
    position: relative;
    width: 100%;
    box-sizing: border-box;
}

.input-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #1e293b;
    font-weight: 600;
    font-size: 0.95rem;
}

/* Fix icon alignment */
.input-group i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 1rem;
    pointer-events: none;
}

/* Special Fix for Textarea Icon (Bio Field) */
.input-group textarea + i {
    top: 1rem;
}

/* INPUT STYLES */
.input-group input,
.input-group select,
.input-group textarea {
    width: 100%;
    padding: 1rem 1rem 1rem 2.8rem; /* Left padding for icons */
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8fafc;
    box-sizing: border-box;
}

/* Bio (Textarea) Fix */
.input-group textarea {
    padding-left: 2.8rem;
    resize: vertical;
    min-height: 120px;
}

/* Select Field Styling */
.input-group select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
}

/* SELECT ICON */
.select-wrapper {
    position: relative;
}

.select-wrapper::after {
    content: '\f107';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #64748b;
}

/* ERROR MESSAGE */
.error-message {
    color: #ef4444;
    font-size: 0.85rem;
    margin-top: 4px;
    display: none;
}

/* FORM SUBMIT BUTTON */
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

/* RESPONSIVE DESIGN */
@media (max-width: 640px) {
    .form-container {
        padding: 2rem;
    }

    .profile-section {
        padding: 1.2rem;
    }

    .profile-preview {
        width: 90px;
        height: 90px;
    }

    .input-group i {
        left: 0.8rem;
    }

    .input-group input,
    .input-group textarea {
        padding: 0.8rem 0.8rem 0.8rem 2.5rem;
    }
}
.verify-btn {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 10px 15px;
        font-size: 1rem;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.3s;
        display: block;
        width: 100%;
        margin-top: 10px;
    }

    .verify-btn:hover {
        background: var(--gradient-end);
        transform: scale(1.05);
    }
    .error-message {
        color: #ef4444;
        font-size: 0.85rem;
        margin-top: 4px;
        display: none;
    }
    
    .input-error {
        border-color: #ef4444 !important;
    }
    
    .input-success {
        border-color: #10b981 !important;
    }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" id="freelancerForm">
        <div class="profile-section">
    <div class="profile-preview" id="profilePreview" onclick="document.getElementById('profile-pic').click()">
        <img id="previewImage" src="default-avatar.png" alt="Profile" style="display: none; width: 100px; height: 100px; border-radius: 50%;">
        <i class="fas fa-user fa-2x" id="defaultIcon"></i>
    </div>
    <input type="file" id="profile-pic" name="profile_picture" hidden accept="image/*">
    <div class="error-message" id="profilePicError"></div>
</div>


            <div class="form-content">
                <div class="input-group">
                    <label class="required">Experience Level</label>
                    <div class="select-wrapper">
                        <select name="experience" id="experience">
                            <option value="">Select Experience Level</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                    <div class="error-message" id="experienceError"></div>
                </div>

                <div class="input-group">
                    <label class="required">Bio</label>
                    <textarea name="bio" id="bio" placeholder="Tell us about yourself and your expertise"></textarea>
                    <div class="error-message" id="bioError"></div>
                </div>

                <div class="input-group">
                    <label>Portfolio Link</label>
                    <input type="url" name="portfolio" id="portfolio" placeholder="https://example.com">
                    <div class="error-message" id="portfolioError"></div>
                </div>
                
                <div class="input-group">
                    <label class="required">UPI ID</label>
                    <input type="text" name="upi_id" id="upi_id" placeholder="example@upi">
                    <div class="error-message" id="upiError"></div>
                    <input type="hidden" id="upi_verified" name="upi_verified" value="0">

                    <button type="button" class="verify-btn" id="verifyUPI">Verify</button>
                </div>

                <button type="submit" class="submit-button">Save Profile</button>
            </div>
        </form>
    </div>
    <script>
        
        document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('freelancerForm');
    const upiInput = document.getElementById('upi_id');
    const verifyUpiBtn = document.getElementById('verifyUPI');
    const upiVerifiedInput = document.getElementById('upi_verified');
    const profilePicInput = document.getElementById('profile-pic');
    const errors = {
        upi: document.getElementById('upiError'),
        profilePic: document.getElementById('profilePicError'),
        experience: document.getElementById('experienceError'),
        bio: document.getElementById('bioError'),
        portfolio: document.getElementById('portfolioError')
    };

    let upiVerified = false;

    function showError(field, errorElement, message) {
        field.classList.add('input-error');
        field.classList.remove('input-success');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        errorElement.style.color = '#ef4444';
        return false;
    }

    function showSuccess(field, errorElement, message = '') {
        field.classList.remove('input-error');
        field.classList.add('input-success');
        errorElement.textContent = message;
        errorElement.style.display = message ? 'block' : 'none';
        errorElement.style.color = message ? '#10b981' : '';
        return true;
    }

    function validateUPI() {
        if (upiVerified) return true;

        const upiId = upiInput.value.trim();
        if (!upiId) {
            return showError(upiInput, errors.upi, 'UPI ID is required.');
        }

        const upiPattern = /^[a-zA-Z0-9.-]{2,256}@[a-zA-Z][a-zA-Z]{2,64}$/;
        if (!upiPattern.test(upiId)) {
            return showError(upiInput, errors.upi, 'Invalid UPI ID format (example: username@bank).');
        }

        errors.upi.textContent = 'UPI format looks valid. Click verify to confirm.';
        errors.upi.style.display = 'block';
        errors.upi.style.color = '#3b82f6';
        return true;
    }

    function verifyUPIWithServer() {
        const upiId = upiInput.value.trim();
        if (!validateUPI()) return;

        verifyUpiBtn.textContent = 'Verifying...';
        verifyUpiBtn.disabled = true;

        fetch('validate_upi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ upi_id: upiId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                upiVerified = true;
                upiVerifiedInput.value = "1";
                showSuccess(upiInput, errors.upi, 'UPI ID has been verified!');
                verifyUpiBtn.textContent = 'Verified ✓';
                verifyUpiBtn.disabled = true;
            } else {
                upiVerified = false;
                upiVerifiedInput.value = "0";
                showError(upiInput, errors.upi, data.message || 'UPI ID verification failed.');
                verifyUpiBtn.textContent = 'Retry';
                verifyUpiBtn.disabled = false;
            }
        })
        .catch(error => {
            upiVerified = false;
            upiVerifiedInput.value = "0";
            showError(upiInput, errors.upi, 'Network error while validating UPI.');
            verifyUpiBtn.textContent = 'Retry';
            verifyUpiBtn.disabled = false;
            console.error('Validation error:', error);
        });
    }

    function previewImage(event) {
        const profilePreview = document.getElementById('profilePreview');
        const file = event.target.files[0];

        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                profilePreview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
            };
            reader.readAsDataURL(file);
        } else {
            profilePreview.innerHTML = '<i class="fas fa-user fa-2x"></i>';
        }
    }

    function validateForm() {
        let isValid = true;

        // Profile Picture Validation
        const profilePic = profilePicInput.files[0];
        const profilePreview = document.getElementById('profilePreview');
        if (!profilePic) {
            showError(profilePicInput, errors.profilePic, 'Profile picture is required.');
            profilePreview.classList.add('invalid');
            isValid = false;
        } else {
            showSuccess(profilePicInput, errors.profilePic);
            profilePreview.classList.remove('invalid');
        }

        // Experience Validation
        const experience = document.getElementById('experience').value;
        if (!experience) {
            showError(document.getElementById('experience'), errors.experience, 'Please select your experience level.');
            isValid = false;
        } else {
            showSuccess(document.getElementById('experience'), errors.experience);
        }

        // Bio Validation
        const bio = document.getElementById('bio').value.trim();
        if (bio.length < 10) {
            showError(document.getElementById('bio'), errors.bio, 'Bio must be at least 10 characters long.');
            isValid = false;
        } else {
            showSuccess(document.getElementById('bio'), errors.bio);
        }

        // Portfolio Validation
        const portfolio = document.getElementById('portfolio').value.trim();
        const portfolioPattern = /^(https?:\/\/)?([\w\-]+\.)+[a-z]{2,6}(:\d{1,5})?(\/.*)?$/i;
        if (portfolio && !portfolioPattern.test(portfolio)) {
            showError(document.getElementById('portfolio'), errors.portfolio, 'Please enter a valid portfolio URL.');
            isValid = false;
        } else {
            showSuccess(document.getElementById('portfolio'), errors.portfolio);
        }

        // UPI Validation
        if (!upiVerified && upiVerifiedInput.value !== "1") {
            showError(upiInput, errors.upi, 'Please verify your UPI ID before submitting.');
            isValid = false;
        }

        return isValid;
    }

    // Event Listeners
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });

    upiInput.addEventListener('input', validateUPI);
    verifyUpiBtn.addEventListener('click', verifyUPIWithServer);
    profilePicInput.addEventListener('change', previewImage);
    
    ['experience', 'bio', 'portfolio'].forEach(id => {
        document.getElementById(id).addEventListener('input', validateForm);
    });
});
    </script>
</body>
</html>