<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMate - OTP Verification</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.5s ease-out;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .task { color: #2563eb; }
        .mate { color: #3b82f6; }

        h2 {
            text-align: center;
            color: #1e293b;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .otp-inputs {
            display: flex;
            justify-content: space-between;
        }

        .otp-inputs input {
            width: 3rem;
            height: 3rem;
            text-align: center;
            font-size: 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .otp-inputs input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .resend-text {
            text-align: center;
            margin-top: 1.5rem;
            color: #64748b;
        }

        .resend-text a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .resend-text a:hover {
            color: #1d4ed8;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <span class="task">Task</span><span class="mate">Mate</span>
        </div>
        <h2>OTP Verification</h2>
        <p style="text-align: center; color: #64748b; margin-bottom: 1.5rem;">Enter the 6-digit code sent to your email.</p>
        <form method="POST" action="verify_otp.php">
            <div class="form-group otp-inputs">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp2')" id="otp1">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp3')" id="otp2">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp4')" id="otp3">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp5')" id="otp4">
                <input type="text" maxlength="1" required oninput="moveToNext(this, 'otp6')" id="otp5">
                <input type="text" maxlength="1" required id="otp6">
            </div>
            <button type="submit" class="login-btn">Verify OTP</button>
            <p class="resend-text">Didn't receive the code? <a href="#">Resend OTP</a></p>
        </form>
    </div>

    <script>
        function moveToNext(current, nextFieldID) {
            if (current.value.length === 1) {
                document.getElementById(nextFieldID)?.focus();
            }
        }
    </script>
</body>
</html>
