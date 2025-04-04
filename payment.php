<?php
session_start();
include "config.php";
require_once 'razorpay-php/Razorpay.php';
use Razorpay\Api\Api;

$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$sql = "SELECT freelancer_id, budget FROM jobs WHERE job_id='$job_id'";
$result = $conn->query($sql);
$job_data = $result->fetch_assoc();
$freelancer_id = $job_data['freelancer_id'] ?? '';
$job_budget = $job_data['budget'] ?? 0;
$error_message = "";
$freelancer_name = "";
$job_title = "";
$upi_id = "";
$total_paid = 0;

if ($job_id == 0 || $freelancer_id == 0) {
    $error_message = "Missing job or freelancer information.";
} else {
    // Fetch total paid amount for this job
    $stmt = $conn->prepare("SELECT SUM(amount) as total_paid FROM jobs_payments WHERE job_id = ? AND payment_status = 'Completed'");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_paid = $result->fetch_assoc()['total_paid'] ?? 0;

    // Fetch freelancer details
    $stmt = $conn->prepare("SELECT u.name, fp.upi_id FROM users u 
                           INNER JOIN freelancer_profile fp ON u.id = fp.user_id 
                           WHERE u.id = ? AND u.role = 'freelancer'");
    $stmt->bind_param("i", $freelancer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $freelancer_name = $row['name'];
        $upi_id = $row['upi_id'];
        
        // Get job details
        $stmt = $conn->prepare("SELECT job_title FROM jobs WHERE job_id = ?");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $job_result = $stmt->get_result();
        
        if ($job_row = $job_result->fetch_assoc()) {
            $job_title = $job_row['job_title'];
            $suggested_amount = $job_budget - $total_paid; 
        } else {
            $error_message = "Job not found.";
        }
    } else {
        $error_message = "Freelancer not found or missing UPI ID.";
    }
}

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount']) && empty($error_message)) {
    $amount = floatval($_POST['amount']);
    
    // Check if new payment exceeds budget
    if ($amount <= 0) {
        $error_message = "Please enter a valid amount.";
    } elseif (($total_paid + $amount) > $job_budget) {
        $error_message = "Payment amount exceeds job budget. Total paid: ₹$total_paid, Budget: ₹$job_budget. Maximum allowed: ₹" . ($job_budget - $total_paid);
    } else {
        $amount_in_paisa = $amount * 100; // Convert to paisa
        
        $api = new Api($key_id, $key_secret);
        
        try {
            $order = $api->order->create([
                'amount' => $amount_in_paisa,
                'currency' => 'INR',
                'payment_capture' => 1,
                'notes' => [
                    'upi' => $upi_id,
                    'job_id' => $job_id,
                    'freelancer_id' => $freelancer_id
                ]
            ]);
            
            $order_id = $order['id'];
        } catch (Exception $e) {
            $error_message = "Error creating order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment to Freelancer</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563EB;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --white: #FFFFFF;
            --light-gray: #f3f4f6;
            --gray: #9ca3af;
            --dark-gray: #4b5563;
            --danger: #ef4444;
        }
        /* Rest of your CSS remains unchanged */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 600px;
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .error {
            background-color: #fee2e2;
            color: var(--danger);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
            font-size: 14px;
        }
        :root {
            --primary-color: #2563EB;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --white: #FFFFFF;
            --light-gray: #f3f4f6;
            --gray: #9ca3af;
            --dark-gray: #4b5563;
            --danger: #ef4444;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 600px;
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--gray);
            font-size: 14px;
        }
        
        .error {
            background-color: #fee2e2;
            color: var(--danger);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
            font-size: 14px;
        }
        
        .payment-details {
            background-color: var(--light-gray);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
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
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .payment-details .detail-value {
            font-weight: 600;
            color: #111827;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            outline: none;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-control::placeholder {
            color: var(--gray);
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 14px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            text-align: center;
            transition: background-color 0.3s;
            outline: none;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-upi {
            background-color: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .btn-upi:hover {
            background-color: rgba(37, 99, 235, 0.05);
        }
        
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .card-icon {
            color: var(--primary-color);
            font-size: 24px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .currency-symbol {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
            font-weight: 600;
        }
        
        .input-group .form-control {
            padding-left: 40px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: var(--gray);
        }
        
        .footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Make Payment to Freelancer</h1>
            <p>Secure payment via UPI through Razorpay</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($error_message)): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Payment Details</div>
                    <div class="card-icon"><i class="fas fa-file-invoice"></i></div>
                </div>
                <div class="payment-details">
                    <div class="detail-item">
                        <div class="detail-label">Freelancer</div>
                        <div class="detail-value"><?php echo htmlspecialchars($freelancer_name); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Job Title</div>
                        <div class="detail-value"><?php echo htmlspecialchars($job_title); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">UPI ID</div>
                        <div class="detail-value"><?php echo htmlspecialchars($upi_id); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total Paid</div>
                        <div class="detail-value">₹<?php echo number_format($total_paid, 2); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Budget</div>
                        <div class="detail-value">₹<?php echo number_format($job_budget, 2); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($order_id)): ?>
                <form method="post">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Amount Details</div>
                            <div class="card-icon"><i class="fas fa-rupee-sign"></i></div>
                        </div>
                        <div class="form-group">
                            <label for="amount">Payment Amount (Max: ₹<?php echo number_format($job_budget - $total_paid, 2); ?>)</label>
                            <div class="input-group">
                                <span class="currency-symbol">₹</span>
                                <input type="number" id="amount" name="amount" class="form-control" min="1" step="0.01" max="<?php echo $job_budget - $total_paid; ?>" value="<?php echo isset($suggested_amount) ? $suggested_amount : ''; ?>" placeholder="Enter amount" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-check-circle"></i> Continue to Payment
                    </button>
                </form>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Ready to Pay</div>
                        <div class="card-icon"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                    <div class="payment-details">
                        <div class="detail-item">
                            <div class="detail-label">Amount</div>
                            <div class="detail-value">₹<?php echo number_format($amount, 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value">UPI</div>
                        </div>
                    </div>
                </div>
                <button id="payBtn" class="btn">
                    <i class="fas fa-credit-card"></i> Pay ₹<?php echo number_format($amount, 2); ?>
                </button>
                
                <script>
                    var options = {
                        "key": "<?php echo $key_id; ?>",
                        "amount": "<?php echo $amount_in_paisa; ?>",
                        "currency": "INR",
                        "name": "TaskMate Payment",
                        "description": "Payment for <?php echo htmlspecialchars($job_title); ?>",
                        "order_id": "<?php echo $order_id; ?>",
                        "prefill": {
                            "email": "<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>",
                            "contact": "<?php echo isset($_SESSION['mobile']) ? $_SESSION['mobile'] : ''; ?>"
                        },
                        "method": {
                            "upi": true
                        },
                        "handler": function(response) {
                            window.location.href = "verify_payment.php?payment_id=" + response.razorpay_payment_id + "&order_id=" + response.razorpay_order_id + "&job_id=<?php echo $job_id; ?>&freelancer_id=<?php echo $freelancer_id; ?>";
                        },
                        "notes": {
                            "upi_id": "<?php echo $upi_id; ?>",
                            "job_id": "<?php echo $job_id; ?>",
                            "freelancer_id": "<?php echo $freelancer_id; ?>"
                        },
                        "theme": {
                            "color": "#2563EB"
                        }
                    };
                    var rzp = new Razorpay(options);
                    document.getElementById("payBtn").onclick = function(e) {
                        rzp.open();
                        e.preventDefault();
                    };
                </script>
            <?php endif; ?>
            
            <div class="footer">
                <p>Secure payment by <a href="https://razorpay.com" target="_blank">Razorpay</a> • TaskMate © <?php echo date('Y'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>