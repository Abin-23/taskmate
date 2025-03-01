<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$database = "taskmate";

$conn = mysqli_connect($servername, $username, $password);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql_create_db = "CREATE DATABASE IF NOT EXISTS taskmate";
if (!mysqli_query($conn, $sql_create_db)) {
    die("Error creating database: " . mysqli_error($conn));
}
mysqli_select_db($conn, $database);

$sql_create_table = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(20) NOT NULL,
    role ENUM('freelancer', 'client','admin') NOT NULL,
    password VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
if (!mysqli_query($conn, $sql_create_table)) {
    die("Error creating table: " . mysqli_error($conn));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email= trim($_POST['email']);
    $mobile  = trim($_POST['mobile']);
    $role= trim($_POST['role']);
    $password= trim($_POST['password']);
    $confirm_password = trim($_POST['cpassword']);

    if (empty($name) || strlen($name) < 3 || strlen($name) > 20) {
        die("Name should be between 3 to 20 characters.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }
    if (!preg_match('/^\+\d{10,15}$/', $mobile)) {
        die("Invalid mobile number format. Ensure it includes the country code (e.g., +11234567890).");
    }
    if (substr($mobile, 0, 3) == '+91') {
        $indianNumber = substr($mobile, 3);
        if (!preg_match('/^[6-9]\d{9}$/', $indianNumber)) {
            die("Invalid  mobile number.");
        }
    }
    if ($role !== "freelancer" && $role !== "client") {
        die("Invalid role selection.");
    }
    if (strlen($password) < 8) {
        die("Password should be at least 8 characters long.");
    }
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }
    
    $check_email = "SELECT email FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check_email);
    if (mysqli_num_rows($result) > 0) {
        die("This email is already registered. Please use a different email address.");
    }
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $query = "INSERT INTO users (name, email, mobile, role, password) 
              VALUES ('$name', '$email', '$mobile', '$role', '$hashed_password')";
    if (mysqli_query($conn, $query)) {
        header('Location: signin.php');
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
    
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TaskMate - Sign Up</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- intl-tel-input CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css"/>
  <style>
      /* Basic styling */
      * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
          font-family: 'Poppins', sans-serif;
          color: #333333;
      }
      body {
          background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
          min-height: 100vh;
          display: flex;
          justify-content: center;
          align-items: center;
          padding: 2rem;
      }
      .signup-container {
          background: white;
          padding: 3rem;
          border-radius: 20px;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
          width: 100%;
          max-width: 500px;
      }
      .logo {
          text-align: center;
          margin-bottom: 2rem;
          font-size: 2rem;
          font-weight: 600;
      }
      .task, .mate { 
          color: #2563eb;
      }
      h2 {
          text-align: center;
          margin-bottom: 2.5rem;
          font-weight: 500;
      }
      .form-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 1.5rem;
      }
      .form-group {
          margin-bottom: 1.5rem;
      }
      .form-group.full-width {
          grid-column: 1 / -1;
      }
      .form-group label {
          display: block;
          margin-bottom: 0.75rem;
          font-size: 0.95rem;
          font-weight: 500;
      }
      .input-group {
          position: relative;
      }
      .input-group i {
          position: absolute;
          left: 1rem;
          top: 50%;
          transform: translateY(-50%);
          color: #666666;
          z-index: 1;
      }
      .form-group input,
      .form-group select {
          width: 100%;
          height: 48px;
          padding: 0.8rem 1rem 0.8rem 2.5rem;
          border: 1px solid #e0e0e0;
          border-radius: 8px;
          font-size: 0.95rem;
          background-color: white;
          transition: all 0.3s ease;
      }
      .form-group select {
          appearance: none;
          -webkit-appearance: none;
          background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666666' d='M6 8L1 3h10L6 8z'/%3E%3C/svg%3E");
          background-repeat: no-repeat;
          background-position: right 1rem center;
          padding-right: 2.5rem;
          cursor: pointer;
      }
      .form-group input::placeholder,
      .form-group select::placeholder {
          color: #999999;
      }
      .form-group input:focus,
      .form-group select:focus {
          outline: none;
          border-color: #2563eb;
          box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
      }
      .error-message {
          color: #dc2626;
          font-size: 0.85rem;
          margin-top: 0.5rem;
          font-weight: 400;
      }
      .signup-btn {
          width: 100%;
          height: 48px;
          background: #2563eb;
          color: white;
          border: none;
          border-radius: 8px;
          font-size: 1rem;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
          margin-top: 1rem;
          grid-column: 1 / -1;
      }
      .signup-btn:hover {
          background: #1d4ed8;
      }
      .login-link {
          text-align: center;
          margin-top: 1.5rem;
          grid-column: 1 / -1;
          font-size: 0.95rem;
      }
      .login-link a {
          color: #2563eb;
          text-decoration: none;
          font-weight: 500;
          margin-left: 0.25rem;
      }
      .login-link a:hover {
          text-decoration: underline;
      }
      .back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #64748b;
    text-decoration: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.back-button:hover {
    transform: translateX(-3px);
    color: #2563eb;
    border-color: #2563eb;
    box-shadow: 0 6px 12px rgba(37, 99, 235, 0.1);
}
      @media (max-width: 480px) {
          .form-grid {
              grid-template-columns: 1fr;
          }
          .signup-container {
              padding: 2rem;
          }
      }
  </style>
</head>
<body>
  <div class="signup-container">
      <div class="logo">
          <span class="task">Task</span><span class="mate">Mate</span>
      </div>
      <a href="home.html" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
      <h2>Create Your Account</h2>
      <form id="form" name="myform" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
          <div class="form-grid">
              <div class="form-group full-width">
                  <label for="name">Full Name</label>
                  <div class="input-group">
                      <i class="fas fa-user"></i>
                      <input type="text" id="name" required placeholder="Enter your name" name="name">
                  </div>
                  <small class="error-message" id="name-error"></small>
              </div>
              <div class="form-group full-width">
                  <label for="email">Email</label>
                  <div class="input-group">
                      <i class="fas fa-envelope"></i>
                      <input type="email" id="email" required placeholder="Enter your email" name="email">
                  </div>
                  <small class="error-message" id="email-error"></small>
              </div>
              <div class="form-group">
                  <label for="mobile">Mobile Number</label>
                  <div class="input-group">
                      <input type="tel" id="mobile" required placeholder="Enter mobile number" name="mobile">
                  </div>
                  <small class="error-message" id="mob-error"></small>
              </div>
              <div class="form-group">
                  <label for="user-type">I want to</label>
                  <div class="input-group">
                      <i class="fas fa-briefcase"></i>
                      <select id="user-type" required name="role">
                          <option value="">Select your role</option>
                          <option value="freelancer">Work as a Freelancer</option>
                          <option value="client">Hire Freelancers</option>
                      </select>
                  </div>
                  <small class="error-message" id="role-error"></small>
              </div>
              <div class="form-group">
                  <label for="password">Password</label>
                  <div class="input-group">
                      <i class="fas fa-lock"></i>
                      <input type="password" id="password" required placeholder="Create password" name="password">
                  </div>
                  <small class="error-message" id="password-error"></small>
              </div>
              <div class="form-group">
                  <label for="confirm-password">Confirm Password</label>
                  <div class="input-group">
                      <i class="fas fa-lock"></i>
                      <input type="password" id="confirm-password" required placeholder="Confirm password" name="cpassword">
                  </div>
                  <small class="error-message" id="cpassword-error"></small>
              </div>
              <input type="submit" class="signup-btn" value="Create Account">
              <div class="login-link">
                  Already have an account? <a href="signin.php">Login</a>
              </div>
          </div>
      </form>
  </div>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const nameError    = document.getElementById('name-error');
        const emailError   = document.getElementById('email-error');
        const mobError     = document.getElementById('mob-error');
        const passwordError= document.getElementById('password-error');
        const cpasswordError= document.getElementById('cpassword-error');
        const roleError    = document.getElementById('role-error');

        const nameInput    = document.getElementById('name');
        const emailInput   = document.getElementById('email');
        const mobInput     = document.getElementById('mobile');
        const passwordInput= document.getElementById('password');
        const cpasswordInput= document.getElementById('confirm-password');
        const roleInput    = document.getElementById('user-type');

        // Initialize intl-tel-input for the mobile field
        const iti = window.intlTelInput(mobInput, {
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
            initialCountry: "auto",
            geoIpLookup: function(callback) {
                fetch('https://ipinfo.io/json?token=<your_token>')
                    .then(res => res.json())
                    .then(data => callback(data.country))
                    .catch(() => callback('us'));
            }
        });

        function checkName() {
            const namePattern = /^[A-Za-z\s]+$/;
            const nameValue   = nameInput.value.trim();
            if (nameValue === '') {
                nameError.innerHTML = "Name is required";
                nameInput.style.border = "2px solid red";
                return false;
            } else if (nameValue.length < 3 || nameValue.length > 20) {
                nameError.innerHTML = "Name should be between 3 to 20 characters";
                nameInput.style.border = "2px solid red";
                return false;
            } else if (!namePattern.test(nameValue)) {
                nameError.innerHTML = "Name should contain only letters and spaces";
                nameInput.style.border = "2px solid red";
                return false;
            } else {
                nameError.innerHTML = "";
                nameInput.style.border = "2px solid green";
                return true;
            }
        }

        async function checkEmail() {
            const email       = emailInput.value.trim();
            const emailPattern= /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email === '') {
                emailError.innerHTML = "Email is required.";
                emailInput.style.border = "2px solid red";
                return false;
            }
            if (!emailPattern.test(email)) {
                emailError.innerHTML = "Please enter a valid email address.";
                emailInput.style.border = "2px solid red";
                return false;
            }
            try {
                const response = await fetch('check_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `email=${encodeURIComponent(email)}`
                });
                const data = await response.text();
                if (data === 'invalid_domain') {
                    emailError.innerHTML = "This email domain is not valid.";
                    emailInput.style.border = "2px solid red";
                    return false;
                } else if (data === 'taken') {
                    emailError.innerHTML = "This email is already registered.";
                    emailInput.style.border = "2px solid red";
                    return false;
                } else if (data === 'available') {
                    emailError.innerHTML = "";
                    emailInput.style.border = "2px solid green";
                    return true;
                } else {
                    emailError.innerHTML = "An unexpected error occurred.";
                    emailInput.style.border = "2px solid red";
                    return false;
                }
            } catch (error) {
                console.error('Error:', error);
                emailError.innerHTML = "Unable to verify email at the moment.";
                emailInput.style.border = "2px solid red";
                return false;
            }
        }

        function checkMobile() {
    const mobileValue = mobInput.value.trim();
    const countryData = iti.getSelectedCountryData();
    
    if (mobileValue === '') {
        mobError.innerHTML = "Mobile number is required";
        mobInput.style.border = "2px solid red";
        return false;
    } else if (!iti.isValidNumber()) {
        mobError.innerHTML = "Enter a valid mobile number";
        mobInput.style.border = "2px solid red";
        return false;
    } else if (countryData.iso2 === 'in') {
        let nationalNumber = iti.getNumber(intlTelInputUtils.numberFormat.NATIONAL);
        nationalNumber = nationalNumber.replace(/\D/g, '');
        if (nationalNumber.charAt(0) === '0') {
            nationalNumber = nationalNumber.substring(1);
        }
        if (!/^[6-9]/.test(nationalNumber) || nationalNumber.length !== 10) {
            mobError.innerHTML = "Enter a valid mobile number";
            mobInput.style.border = "2px solid red";
            return false;
        }
    }
    mobError.innerHTML = "";
    mobInput.style.border = "2px solid green";
    return true;
}


        function checkPassword() {
            const password       = passwordInput.value.trim();
            const passwordPattern= /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/;
            if (password === '') {
                passwordError.innerHTML = "Password is required";
                passwordInput.style.border = "2px solid red";
                return false;
            } else if (!passwordPattern.test(password)) {
                passwordError.innerHTML = "Password must be 8+ characters with uppercase, lowercase, and a number.";
                passwordInput.style.border = "2px solid red";
                return false;
            } else {
                passwordError.innerHTML = "";
                passwordInput.style.border = "2px solid green";
                return true;
            }
        }

        function checkConfirmPassword() {
            if (cpasswordInput.value.trim() === '') {
                cpasswordError.innerHTML = "Please confirm your password";
                cpasswordInput.style.border = "2px solid red";
                return false;
            } else if (cpasswordInput.value !== passwordInput.value) {
                cpasswordError.innerHTML = "Passwords do not match";
                cpasswordInput.style.border = "2px solid red";
                return false;
            } else {
                cpasswordError.innerHTML = "";
                cpasswordInput.style.border = "2px solid green";
                return true;
            }
        }

        function checkRole() {
            if (roleInput.value === '') {
                roleError.innerHTML = "Please select a role";
                roleInput.style.border = "2px solid red";
                return false;
            } else {
                roleError.innerHTML = "";
                roleInput.style.border = "2px solid green";
                return true;
            }
        }

        nameInput.addEventListener('input', checkName);
        emailInput.addEventListener('input', checkEmail);
        mobInput.addEventListener('input', checkMobile);
        passwordInput.addEventListener('input', function() {
            checkPassword();
            if (cpasswordInput.value !== '') {
                checkConfirmPassword();
            }
        });
        cpasswordInput.addEventListener('input', checkConfirmPassword);
        roleInput.addEventListener('change', checkRole);

        document.getElementById('form').addEventListener('submit', async function(e) {
            e.preventDefault();
            let isValid = true;
            if (!checkName()) isValid = false;
            if (!await checkEmail()) isValid = false;
            if (!checkMobile()) isValid = false;
            if (!checkPassword()) isValid = false;
            if (!checkConfirmPassword()) isValid = false;
            if (!checkRole()) isValid = false;
            if (isValid) {
                mobInput.value = iti.getNumber();
                this.submit();
            }
        });
    });
  </script>
</body>
</html>
