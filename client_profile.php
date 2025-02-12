<?php
session_start();
$host = 'localhost';
$db = 'taskmate';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (!isset($_SESSION['user_id'])) {
    header("Location: sign.php");
    exit();
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $companyName = trim($conn->real_escape_string($_POST['company_name']));
    $website = isset($_POST['website']) ? trim($conn->real_escape_string($_POST['website'])) : '';
    $address = trim($conn->real_escape_string($_POST['address']));

    // Server-side validation
    if (strlen($companyName) < 3 || strlen($address) < 10) {
        die("Invalid input: Company name must be at least 3 characters and address at least 10 characters.");
    }

    if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
        die("Invalid website URL.");
    }

    // Profile Picture Upload
    $profilePic = '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['profile_picture']['tmp_name']);
        $fileSize = $_FILES['profile_picture']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            die("Invalid file type. Only JPG, PNG, and GIF are allowed.");
        }

        if ($fileSize > 2 * 1024 * 1024) {
            die("File size exceeds the 2MB limit.");
        }

        $fileTmp = $_FILES['profile_picture']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        move_uploaded_file($fileTmp, $uploadDir . $fileName);
        $profilePic = $uploadDir . $fileName;
    }

    // Insert data into the database
    $sql = "INSERT INTO client_profile (id, company_name, website, address, profile_picture)
        VALUES ('" . $_SESSION['user_id'] . "', '$companyName', '$website', '$address', '$profilePic')";

    if ($conn->query($sql) === TRUE) {
        header('Location:client_dash.php');
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
    <title>TaskMate - Create Client</title>
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
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            color: #2563eb ;
            font-weight: 800;
        }

        .logo span:last-child {
            font-size: 2.5rem;
            color:  #3b82f6; ;
            font-weight: 800;
        }

        .profile-section {
            background: #f8fafc;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            text-align: center;
        }

        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 3px dashed #e2e8f0;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .profile-preview:hover {
            border-color: var(--primary-color);
            transform: scale(1.02);
        }

        .upload-button {
            background: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            color: var(--primary-color);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .upload-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .form-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .input-group {
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 1rem 1rem 1rem 2.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .input-group textarea {
            padding-left: 1rem;
            resize: vertical;
            min-height: 100px;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 2.7rem;
            color: #64748b;
        }

        .input-group input:focus,
        .input-group textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .required::after {
            content: ' *';
            color: #ef4444;
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
        * {
    box-sizing: border-box;
}

.input-group input,
.input-group textarea {
    width: 100%;
    padding: 1rem 1rem 1rem 2.5rem; /* This should be fine, as long as box-sizing is applied */
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.input-group input:focus,
.input-group textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    background: white;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}


        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(59, 130, 246, 0.2);
        }
        .error-message {
    color: #ef4444;
    font-size: 0.85rem;
    margin-top: 4px;
    display: none; /* Hidden until there's an error */
}

.input-group input:invalid,
.input-group textarea:invalid,
.profile-preview.invalid {
    border: 2px solid #ef4444 !important;
    box-shadow: 0 0 5px rgba(239, 68, 68, 0.5);
}


        @media (max-width: 640px) {
            .form-container {
                padding: 2rem;
            }
            
            .profile-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo">
            <span>Task</span><span>Mate</span>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" id="clientForm">
    <div class="profile-section">
        <div class="profile-preview" id="profilePreview" onclick="document.getElementById('profile-pic').click()">
            <i class="fas fa-building fa-2x"></i>
        </div>
        <input type="file" id="profile-pic" name="profile_picture" hidden accept="image/*" onchange="previewImage(event)">
        <div class="error-message" id="profilePicError"></div>
    </div>

    <div class="form-content">
        <div class="input-group">
            <label class="required">Company Name</label>
            <i class="fas fa-building"></i>
            <input type="text" name="company_name" id="companyName" placeholder="Enter company name">
            <div class="error-message" id="companyNameError"></div>
        </div>

        <div class="input-group">
            <label>Website</label>
            <i class="fas fa-globe"></i>
            <input type="url" name="website" id="website" placeholder="https://example.com">
            <div class="error-message" id="websiteError"></div>
        </div>

        <div class="input-group">
            <label class="required">Address</label>
            <textarea name="address" id="address" placeholder="Enter complete company address"></textarea>
            <div class="error-message" id="addressError"></div>
        </div>

        <button type="submit" class="submit-button">Create Profile</button>
    </div>
</form>


<script>
    // Preview Profile Image
    document.getElementById('clientForm').addEventListener('submit', function (e) {
    if (!validateForm()) {
        e.preventDefault(); // Prevent form submission if validation fails
    }
});

const inputs = ['companyName', 'website', 'address'];
inputs.forEach(id => {
    document.getElementById(id).addEventListener('input', validateForm);
});
document.getElementById('profile-pic').addEventListener('change', validateForm);

// Validate Form
function validateForm() {
    let isValid = true;

    const profilePic = document.getElementById('profile-pic').files[0];
    const profilePreview = document.getElementById('profilePreview');
    const profilePicError = document.getElementById('profilePicError');

    if (!profilePic) {
        profilePicError.textContent = 'Profile picture is required.';
        profilePicError.style.display = 'block';
        profilePreview.classList.add('invalid');
        isValid = false;
    } else {
        profilePicError.textContent = '';
        profilePicError.style.display = 'none';
        profilePreview.classList.remove('invalid');
    }

    
    const companyName = document.getElementById('companyName').value.trim();
    const companyNameError = document.getElementById('companyNameError');
    if (companyName.length < 3) {
        companyNameError.textContent = 'Company name must be at least 3 characters.';
        companyNameError.style.display = 'block';
        isValid = false;
    } else {
        companyNameError.textContent = '';
        companyNameError.style.display = 'none';
    }

   
    const website = document.getElementById('website').value.trim();
    const websiteError = document.getElementById('websiteError');
    const websitePattern = /^(https?:\/\/)?([\w\-]+\.)+[a-z]{2,6}(:\d{1,5})?(\/.*)?$/i;
    if (website && !websitePattern.test(website)) {
        websiteError.textContent = 'Please enter a valid website URL.';
        websiteError.style.display = 'block';
        isValid = false;
    } else {
        websiteError.textContent = '';
        websiteError.style.display = 'none';
    }

   
    const address = document.getElementById('address').value.trim();
    const addressError = document.getElementById('addressError');
    if (address.length < 10) {
        addressError.textContent = 'Address must be at least 10 characters long.';
        addressError.style.display = 'block';
        isValid = false;
    } else {
        addressError.textContent = '';
        addressError.style.display = 'none';
    }

    return isValid;
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
        profilePreview.innerHTML = '<i class="fas fa-building fa-2x"></i>';
    }
}

</script>


</body>
</html>