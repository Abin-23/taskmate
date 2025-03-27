<?php
include "config.php";
require_once 'razorpay-php/Razorpay.php';
use Razorpay\Api\Api;

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get parameters from URL
$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : '';
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$freelancer_id = isset($_GET['freelancer_id']) ? intval($_GET['freelancer_id']) : 0;

// Set default values
$payment_status = false;
$error_message = '';
$payment_amount = 0;
$payment_currency = '';
$payment_method = '';
$client_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Validate required parameters
if (empty($payment_id) || empty($order_id) || $job_id == 0 || $freelancer_id == 0 || $client_id == 0) {
    $error_message = "Missing required parameters for payment verification.";
} else {
    try {
        // Initialize Razorpay API
        $api = new Api($key_id, $key_secret);
        
        // Fetch payment details
        $payment = $api->payment->fetch($payment_id);
        
        // Store payment details
        $payment_status = ($payment['status'] === "captured");
        $payment_amount = $payment['amount'] / 100; // Convert from paise to rupees
        $payment_currency = $payment['currency'];
        $payment_method = isset($payment['method']) ? $payment['method'] : 'UPI';
        
        // If payment is successful, store in database
        if ($payment_status) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // 1. Create payment record in jobs_payments table
                $stmt = $conn->prepare("INSERT INTO jobs_payments 
                                       (job_id, client_id, freelancer_id, amount, currency, payment_id, 
                                        order_id, payment_method, payment_status, payment_date) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Completed', NOW())");
                
                $stmt->bind_param("iiidssss", 
                                 $job_id, 
                                 $client_id, 
                                 $freelancer_id, 
                                 $payment_amount, 
                                 $payment_currency, 
                                 $payment_id, 
                                 $order_id, 
                                 $payment_method);
                
                $stmt->execute();
                $payment_record_id = $conn->insert_id;
                
                                $stmt = $conn->prepare("SELECT job_title FROM jobs WHERE job_id = ?");
                $stmt->bind_param("i", $job_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $job_title = ($row = $result->fetch_assoc()) ? $row['job_title'] : 'this job';
                
                $system_message = "Paid {$payment_currency} {$payment_amount}. Payment ID: {$payment_id}";
                
                $stmt = $conn->prepare("INSERT INTO chat_messages 
                                       (job_id, sender_id, receiver_id, message, sent_at,is_payment) 
                                       VALUES (?, ?, ?, ?, NOW(),1)");
                
                $stmt->bind_param("iiis", 
                                 $job_id, 
                                 $client_id, 
                                 $freelancer_id, 
                                 $system_message);
                
                $stmt->execute();
                
                $conn->commit();
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error_message = "Database error: " . $e->getMessage();
                $payment_status = false;
            }
        } else {
            $error_message = "Payment was not captured. Status: " . $payment['status'];
        }
        
    } catch (Exception $e) {
        $error_message = "Error verifying payment: " . $e->getMessage();
        $payment_status = false;
    }
}

// Store status in session for displaying messages
$_SESSION['payment_status'] = $payment_status;
$_SESSION['payment_error'] = $error_message;
$_SESSION['payment_amount'] = $payment_amount;
$_SESSION['payment_id'] = $payment_id;

// Create table if it doesn't exist (first time setup)


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563EB;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --success-light: #d1fae5;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --white: #FFFFFF;
            --light-gray: #f3f4f6;
            --gray: #9ca3af;
            --dark-gray: #4b5563;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 40px 30px;
            margin-bottom: 20px;
        }
        
        .status-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .success .status-icon {
            color: var(--success);
        }
        
        .failed .status-icon {
            color: var(--danger);
        }
        
        .status-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .success .status-title {
            color: var(--success);
        }
        
        .failed .status-title {
            color: var(--danger);
        }
        
        .status-message {
            color: var(--dark-gray);
            margin-bottom: 30px;
        }
        
        .payment-details {
            background-color: var(--light-gray);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .payment-details .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .payment-details .detail-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .payment-details .detail-label {
            color: var(--gray);
            font-size: 14px;
        }
        
        .payment-details .detail-value {
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($payment_status)): ?>
            <?php if ($payment_status): ?>
                <div class="card success">
                    <div class="status-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="status-title">Payment Successful!</h1>
                    <p class="status-message">Your payment has been processed successfully.</p>
                    
                    <div class="payment-details">
                        <div class="detail-item">
                            <div class="detail-label">Amount</div>
                            <div class="detail-value"><?php echo $payment_currency; ?> <?php echo number_format($payment_amount, 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment ID</div>
                            <div class="detail-value"><?php echo $payment_id; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo $payment_method; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Date</div>
                            <div class="detail-value"><?php echo date('d M Y, h:i A'); ?></div>
                        </div>
                    </div>
                    
                    <a href="chat.php?job_id=<?php echo $job_id; ?>&freelancer_id=<?php echo $freelancer_id; ?>" class="btn">
                        <i class="fas fa-comments"></i> Go to Chat
                    </a>
                </div>
            <?php else: ?>
                <div class="card failed">
                    <div class="status-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h1 class="status-title">Payment Failed</h1>
                    <p class="status-message">
                        <?php echo !empty($error_message) ? $error_message : "Your payment could not be processed. Please try again."; ?>
                    </p>
                    
                    <a href="payment.php?job_id=<?php echo $job_id; ?>&freelancer_id=<?php echo $freelancer_id; ?>" class="btn">
                        <i class="fas fa-redo"></i> Try Again
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card loading">
                <div class="spinner"></div>
                <h1 class="status-title">Verifying Payment</h1>
                <p class="status-message">Please wait while we verify your payment...</p>
            </div>
            
            <script>
                // Redirect to prevent resubmission on refresh
                setTimeout(function() {
                    window.location.href = "chat.php?job_id=<?php echo $job_id; ?>&freelancer_id=<?php echo $freelancer_id; ?>";
                }, 5000);
            </script>
        <?php endif; ?>
    </div>
</body>
</html>

<script>
    // Auto-redirect to chat page after 5 seconds on successful payment
    <?php if ($payment_status): ?>
    setTimeout(function() {
        window.location.href = "chat.php?job_id=<?php echo $job_id; ?>&freelancer_id=<?php echo $freelancer_id; ?>";
    }, 5000);
    <?php endif; ?>
</script>