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

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

if (!isset($_GET['job_id']) || empty($_GET['job_id'])) {
    header("Location: signin.php");
    exit();
}

$job_id = intval($_GET['job_id']); 
$user_id = intval($_SESSION['user_id']);

$jobAccessQuery = "SELECT * FROM jobs WHERE job_id = '$job_id' AND (client_id = '$user_id' OR freelancer_id = '$user_id')";
$jobResult = $conn->query($jobAccessQuery);

if ($jobResult->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

// Handle file upload
$uploadError = '';
$fileUploaded = false;
$uploadedFileName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
    $uploadDir = 'uploads/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Get file info
    $fileName = basename($_FILES['attachment']['name']);
    $fileSize = $_FILES['attachment']['size'];
    $fileType = $_FILES['attachment']['type'];
    
    // Generate unique filename
    $uniqueName = uniqid() . '_' . $fileName;
    $targetFile = $uploadDir . $uniqueName;
    
    
    if ($fileSize > 100000000) {
        $uploadError = "File is too large. Maximum size is 100MB.";
    } else {
        // Try to upload file
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
            // File uploaded successfully, now insert message with file info
            $message = isset($_POST['message']) && !empty($_POST['message']) ? 
                      $conn->real_escape_string(trim($_POST['message'])) : 
                      "Sent a file: $fileName";
            
            $receiver_id = intval($_POST['receiver_id']);
            
            $insertQuery = "INSERT INTO chat_messages (job_id, sender_id, receiver_id, message, file_name, file_path, file_type, file_size) 
                           VALUES ('$job_id', '$user_id', '$receiver_id', '$message', '$fileName', '$targetFile', '$fileType', '$fileSize')";
            
            if ($conn->query($insertQuery)) {
                $fileUploaded = true;
                $uploadedFileName = $fileName;
                
                // Redirect to prevent form resubmission
                header("Location: chat.php?job_id=$job_id&file_uploaded=1");
                exit();
            } else {
                $uploadError = "Failed to save file information.";
            }
        } else {
            $uploadError = "Failed to upload file.";
        }
    }
}

// Handle AJAX message submission
// Handle AJAX message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
    if (isset($_POST['message']) && !empty($_POST['message'])) {
        $message = $conn->real_escape_string(trim($_POST['message']));
        $receiver_id = intval($_POST['receiver_id']);
        
        $insertQuery = "INSERT INTO chat_messages (job_id, sender_id, receiver_id, message) 
                       VALUES ('$job_id', '$user_id', '$receiver_id', '$message')";
        if ($conn->query($insertQuery)) {
            // Get the message details to send back
            $msgId = $conn->insert_id;
            $msgQuery = "SELECT cm.*, 
                            sender.name as sender_name, 
                            receiver.name as receiver_name,
                            cm.sent_at
                     FROM chat_messages cm
                     LEFT JOIN users sender ON cm.sender_id = sender.id
                     LEFT JOIN users receiver ON cm.receiver_id = receiver.id
                     WHERE cm.id = '$msgId'";
            $msgResult = $conn->query($msgQuery);
            $msg = $msgResult->fetch_assoc();
            
            echo json_encode([
                'success' => true, 
                'message' => nl2br(htmlspecialchars($message)),
                'message_id' => $msgId, // Added this line to return the message ID
                'time' => date('h:i A', strtotime($msg['sent_at'])),
                'date' => date('Y-m-d', strtotime($msg['sent_at'])),
                'formatted_date' => date('F j, Y', strtotime($msg['sent_at']))
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    }
    exit();
}

// Handle AJAX fetch new messages
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_messages']) && $_GET['fetch_messages'] === 'true') {
    $lastId = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    $newMessagesQuery = "SELECT cm.*, 
                            sender.name as sender_name, 
                            sender.role,
                            receiver.name as receiver_name,
                            cm.id as message_id,
                            cm.file_name,
                            cm.file_path
                     FROM chat_messages cm
                     LEFT JOIN users sender ON cm.sender_id = sender.id
                     LEFT JOIN users receiver ON cm.receiver_id = receiver.id
                     WHERE cm.job_id = '$job_id' AND cm.id > '$lastId'
                     ORDER BY cm.sent_at ASC";
    $newMessagesResult = $conn->query($newMessagesQuery);
    
    $messages = [];
    while ($row = $newMessagesResult->fetch_assoc()) {
        $hasFile = !empty($row['file_name']) && !empty($row['file_path']);
        $fileHTML = '';
        
        if ($hasFile) {
            $fileHTML = '<div class="file-attachment">
                <i class="fas fa-file"></i>
                <a href="' . htmlspecialchars($row['file_path']) . '" target="_blank">' . htmlspecialchars($row['file_name']) . '</a>
            </div>';
        }
        
        $messages[] = [
            'id' => $row['message_id'],
            'message' => nl2br(htmlspecialchars($row['message'])),
            'sender_id' => $row['sender_id'],
            'time' => date('h:i A', strtotime($row['sent_at'])),
            'date' => date('Y-m-d', strtotime($row['sent_at'])),
            'formatted_date' => date('F j, Y', strtotime($row['sent_at'])),
            'has_file' => $hasFile,
            'file_html' => $fileHTML
        ];
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit();
}

$jobDetailsQuery = "SELECT client_id, freelancer_id FROM jobs WHERE job_id = '$job_id'";
$jobDetailsResult = $conn->query($jobDetailsQuery);
$jobDetails = $jobDetailsResult->fetch_assoc();

$isClient = ($user_id == $jobDetails['client_id']);
$otherPartyId = $isClient ? $jobDetails['freelancer_id'] : $jobDetails['client_id'];

$nameQuery = "SELECT name FROM users WHERE id = '$otherPartyId'";
$nameResult = $conn->query($nameQuery);
$otherPartyName = $nameResult->fetch_assoc()['name'] ?? 'User';
$messagesQuery = "SELECT cm.*, 
                        sender.name as sender_name, 
                        receiver.name as receiver_name,
                        cm.id as message_id,
                        cm.file_name,
                        cm.file_path,
                        cm.is_payment
                 FROM chat_messages cm
                 LEFT JOIN users sender ON cm.sender_id = sender.id
                 LEFT JOIN users receiver ON cm.receiver_id = receiver.id
                 WHERE cm.job_id = '$job_id'
                 ORDER BY cm.sent_at ASC";
$messagesResult = $conn->query($messagesQuery);

$sql = "SELECT role FROM users WHERE id = '" . $_SESSION['user_id'] . "'";
$result = $conn->query($sql);
$role = $result->fetch_assoc()['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - TaskMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
    --primary-color: #3b82f6;
    --primary-light: #60a5fa;
    --primary-dark: #2563eb;
    --primary-bg: #f8fafc;
    --secondary-color: #64748b;
    --border-color: #e2e8f0;
    --success-color: #10b981;
    --border-radius: 16px;
    --shadow: 0 4px 20px rgba(59, 130, 246, 0.15);
    --message-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html, body {
    height: 100%;
    width: 100%;
    overflow: hidden;
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: #f8fafc;
}

.container {
    width: 90%;
    max-width: 800px;
    height: 90vh;
    margin: 2vh auto;
    background: white;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow);
    position: relative;
    border-radius: 16px;
}

.chat-header {
    background: var(--primary-color);
    color: white;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 10;
    box-shadow: 0 2px 10px rgba(59, 130, 246, 0.25);
    position: relative;
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
}

.chat-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    letter-spacing: 0.3px;
}

.chat-header h2 .status {
    width: 10px;
    height: 10px;
    background: var(--success-color);
    border-radius: 50%;
    margin-right: 10px;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

.back-button {
    color: white;
    text-decoration: none;
    font-size: 15px;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 12px;
    border-radius: 8px;
}

.back-button:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

.back-button i {
    margin-right: 8px;
}

.chat-box {
    flex: 1;
    overflow-y: auto;
    padding: 25px;
    background: white;
    scroll-behavior: smooth;
}

.chat-box::-webkit-scrollbar {
    width: 6px;
}

.chat-box::-webkit-scrollbar-track {
    background: white;
}

.chat-box::-webkit-scrollbar-thumb {
    background: var(--primary-light);
    border-radius: 10px;
}

.chat-box::-webkit-scrollbar-thumb:hover {
    background: var(--primary-color);
}

.message {
    max-width: 75%;
    padding: 14px 18px;
    border-radius: 18px;
    margin-bottom: 20px;
    position: relative;
    line-height: 1.5;
    font-size: 15px;
    box-shadow: var(--message-shadow);
    transition: all 0.2s ease;
    animation: fadeIn 0.3s ease;
}

.message:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message p {
    margin: 0 0 5px 0;
    word-wrap: break-word;
}

.message small {
    font-size: 11px;
    opacity: 0.8;
    display: block;
    text-align: right;
    margin-top: 5px;
}

.sent {
    background: var(--primary-color);
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 5px;
}

.received {
    background: white;
    color: #1e293b;
    margin-right: auto;
    border-bottom-left-radius: 5px;
    border: 1px solid var(--border-color);
}

.message-form {
    display: flex;
    padding: 16px 20px;
    background: white;
    border-top: 1px solid var(--border-color);
    z-index: 10;
    position: relative;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
}

.message-input-container {
    position: relative;
    flex: 1;
    display: flex;
}

.message-form input[type="text"] {
    flex: 1;
    padding: 14px 20px;
    padding-right: 50px; /* Space for attachment icon */
    border: 1px solid var(--border-color);
    border-radius: 25px;
    outline: none;
    font-size: 15px;
    background: var(--primary-bg);
    transition: all 0.3s ease;
}

.message-form input[type="text"]:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.attachment-btn {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--secondary-color);
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 1;
}

.attachment-btn:hover {
    color: var(--primary-color);
}

.message-form button {
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 25px;
    padding: 14px 22px;
    margin-left: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.payment-btn {
    background: var(--accent-color);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.payment-btn:hover {
    background: #e29000 !important;
}

.message-form button i {
    margin-left: 8px;
}

.message-form button:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.message-form button:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

.date-divider {
    text-align: center;
    margin: 25px 0;
    position: relative;
    z-index: 1;
}

.date-divider span {
    background: white;
    padding: 8px 16px;
    border-radius: 15px;
    font-size: 12px;
    color: var(--secondary-color);
    box-shadow: var(--message-shadow);
    font-weight: 500;
    border: 1px solid var(--border-color);
}

.typing-indicator {
    display: none;
    color: var(--secondary-color);
    font-size: 13px;
    margin-bottom: 15px;
    font-style: italic;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 0.5; }
    50% { opacity: 1; }
    100% { opacity: 0.5; }
}

.empty-chat {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--secondary-color);
    text-align: center;
    padding: 20px;
}

.empty-chat i {
    font-size: 60px;
    margin-bottom: 20px;
    opacity: 0.8;
    color: var(--primary-color);
    background: white;
    height: 120px;
    width: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
}

.empty-chat p {
    font-size: 16px;
    max-width: 300px;
    line-height: 1.6;
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: var(--message-shadow);
    border: 1px solid var(--border-color);
}

/* File attachment styles */
.file-attachment {
    margin-top: 10px;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
}

.sent .file-attachment {
    background: rgba(255, 255, 255, 0.2);
}

.received .file-attachment {
    background: rgba(59, 130, 246, 0.1);
}

.file-attachment i {
    margin-right: 8px;
    font-size: 14px;
}

.file-attachment a {
    color: inherit;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    word-break: break-all;
}

.received .file-attachment a {
    color: var(--primary-color);
}

.file-attachment:hover {
    transform: translateY(-2px);
}

/* File input styles */
input[type="file"] {
    position: absolute;
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    overflow: hidden;
    z-index: -1;
}

/* Upload toast notification */
.upload-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--success-color);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    display: flex;
    align-items: center;
    animation: slideIn 0.3s ease, fadeOut 0.5s ease 3s forwards;
    z-index: 1000;
}

.upload-toast i {
    margin-right: 10px;
}

@keyframes slideIn {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; visibility: hidden; }
}

/* Error toast */
.error-toast {
    background: #ef4444;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* Add animation for message hover state */
.message::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 3px;
}

.message:hover::after {
    opacity: 1;
}

/* Custom animations */
@keyframes slideIn {
    from { transform: translateX(-20px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.chat-header {
    animation: slideIn 0.5s ease;
}

@keyframes floatUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.message-form {
    animation: floatUp 0.5s ease;
}

/* Media queries for responsiveness */
@media (max-width: 768px) {
    .container {
        width: 95%;
        height: 95vh;
        margin: 2.5vh auto;
    }
    
    .message {
        max-width: 85%;
    }
}

@media (max-width: 480px) {
    .container {
        width: 100%;
        height: 100vh;
        margin: 0;
        border-radius: 0;
    }
    
    .chat-header {
        border-radius: 0;
    }
    
    .message-form {
        border-radius: 0;
    }
    
    .message {
        max-width: 90%;
    }
}

.payment-link {
    position: absolute;
    right: 45px; /* Position it to the left of the paperclip icon */
    top: 50%;
    transform: translateY(-50%);
    color: var(--secondary-color);
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.payment-link:hover {
    color: #f59e0b; /* A gold/amber color for the payment icon on hover */
}

.attachment-btn {
    right: 15px;
}
.payment {
    background: #10b981; /* Green color for payment messages */
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 5px;
}

.received.payment {
    background: #dcfce7; /* Light green for received payment messages */
    color: #064e3b;
    border-color: #10b981;
}

.payment .file-attachment {
    background: rgba(255, 255, 255, 0.3);
}

.payment::after {
    background: linear-gradient(90deg, #059669, #10b981) !important;
}
    </style>
</head>
<body>
    <div class="container">
        <div class="chat-header">
            <?php
            if ($role == 'client') {
                echo '<a href="client_project.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>';
            } else {
                echo '<a href="freelancer_job.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>';
            }
            ?>
            <h2><span class="status"></span> Chat with <?php echo htmlspecialchars($otherPartyName); ?></h2>
            <span></span>
        </div>
        
        <div class="chat-box" id="chatBox">
            <?php 
            $messageCount = 0;
            $lastMessageId = 0;
            $prevDate = null;
            
            while ($row = $messagesResult->fetch_assoc()) :
                $messageCount++;
                $lastMessageId = max($lastMessageId, $row['message_id']);
                $messageDate = date('Y-m-d', strtotime($row['sent_at']));
                
                if ($prevDate != $messageDate) {
                    echo '<div class="date-divider">';
                    echo '<span>' . date('F j, Y', strtotime($row['sent_at'])) . '</span>';
                    echo '</div>';
                    $prevDate = $messageDate;
                }
            ?>
               <div class="message <?php echo ($row['sender_id'] == $user_id) ? 'sent' : 'received'; ?> <?php echo ($row['is_payment'] == 1) ? 'payment' : ''; ?>">
    <p><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
    
    <?php if (!empty($row['file_name']) && !empty($row['file_path'])): ?>
    <div class="file-attachment">
        <i class="fas fa-file"></i>
        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank">
            <?php echo htmlspecialchars($row['file_name']); ?>
        </a>
    </div>
    <?php endif; ?>
    
    <small><?php echo date('h:i A', strtotime($row['sent_at'])); ?></small>
</div>
            <?php endwhile; ?>
            
            <?php if ($messageCount === 0): ?>
            <div class="empty-chat">
                <i class="far fa-comment-dots"></i>
                <p>No messages yet. Start the conversation with <?php echo htmlspecialchars($otherPartyName); ?>!</p>
            </div>
            <?php endif; ?>
            
            <div class="typing-indicator" id="typingIndicator">
                <?php echo htmlspecialchars($otherPartyName); ?> is typing...
            </div>
        </div>
        
        <form class="message-form" id="messageForm" method="post" enctype="multipart/form-data">
            <input type="hidden" name="receiver_id" value="<?php echo $otherPartyId; ?>">
            <input type="hidden" name="ajax" value="true" id="ajaxFlag">
            
<div class="message-input-container">
    <input type="text" name="message" id="messageInput" placeholder="Type your message here..." autocomplete="off">
    <label for="fileInput" class="attachment-btn">
        <i class="fas fa-paperclip"></i>
    </label>
    <input type="file" name="attachment" id="fileInput">
<?php 
if($role == 'client') { 
    echo "<a href='payment.php?job_id=" . $job_id . "' class='payment-link'>
    <i class='fas fa-money-bill-wave'></i></a>";
} 
?>
</div>
            
            <button type="submit" id="sendBtn">Send <i class="fas fa-paper-plane"></i></button>
           
        </form>
    </div>

    <?php if (isset($_GET['file_uploaded']) && $_GET['file_uploaded'] == 1): ?>
    <div class="upload-toast">
        <i class="fas fa-check-circle"></i> File uploaded successfully!
    </div>
    <?php endif; ?>
    
    <?php if (!empty($uploadError)): ?>
    <div class="upload-toast error-toast">
        <i class="fas fa-exclamation-circle"></i> <?php echo $uploadError; ?>
    </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
       $(document).ready(function() {
    const chatBox = document.getElementById('chatBox');
    let lastMessageId = <?php echo $lastMessageId; ?>;
    let typingTimer;
    let lastSentMessageId = null;
    
    scrollToBottom();
    
    // Handle file input change
    $('#fileInput').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        if (fileName) {
            // Visual feedback
            $('.attachment-btn i').removeClass('fa-paperclip').addClass('fa-check');
            
            // Submit form with file
            $('#ajaxFlag').val('false');
            $('#messageForm').submit();
        }
    });
    
    // Submit message via AJAX (when no file is attached)
    $('#messageForm').on('submit', function(e) {
        // If a file is selected, let the form submit normally (no Ajax)
        if ($('#fileInput').val()) {
            return true;
        }
        
        e.preventDefault();
        
        const messageInput = $('#messageInput');
        const message = messageInput.val().trim();
        
        if (message !== '') {
            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: $(this).serialize(),
                dataType: 'json',
                beforeSend: function() {
                    messageInput.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // Store the message ID we just sent to avoid duplicate display
                        lastSentMessageId = response.message_id;
                        lastMessageId = Math.max(lastMessageId, response.message_id || 0);
                        
                        // Check if we need a new date divider
                        const lastDateDivider = $('.date-divider span').last().text();
                        
                        if (!lastDateDivider || lastDateDivider !== response.formatted_date) {
                            $('#chatBox').append(`
                                <div class="date-divider">
                                    <span>${response.formatted_date}</span>
                                </div>
                            `);
                        }
                        
                        // Append the message
                        $('#chatBox').append(`
                            <div class="message sent" data-message-id="${response.message_id}">
                                <p>${response.message}</p>
                                <small>${response.time}</small>
                            </div>
                        `);
                        
                        // Clear and enable input
                        messageInput.val('').focus();
                        
                        // Hide empty chat message if it exists
                        $('.empty-chat').hide();
                        
                        // Scroll to bottom
                        scrollToBottom();
                    } else {
                        alert('Failed to send message: ' + response.error);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    messageInput.prop('disabled', false);
                }
            });
        }
    });
    
    // Typing indicator functionality
    $('#messageInput').on('keydown', function() {
        clearTimeout(typingTimer);
        // TODO: Implement logic to show typing indicator to the other user
    });
    
    $('#messageInput').on('keyup', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() {
            // TODO: Implement logic to hide typing indicator
        }, 2000);
    });
    
    // Function to scroll to bottom of chat
    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
    
    // Poll for new messages
    function fetchNewMessages() {
        $.ajax({
            type: 'GET',
            url: window.location.href,
            data: {
                fetch_messages: 'true',
                last_id: lastMessageId,
                job_id: <?php echo $job_id; ?>
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.messages.length > 0) {
                    let currentDate = '';
                    
                    response.messages.forEach(function(msg) {
                        // Skip if this is the message we just sent ourselves
                        if (msg.id === lastSentMessageId) {
                            return;
                        }
                        
                        // Update last message ID
                        lastMessageId = Math.max(lastMessageId, msg.id);
                        
                        // Check if we need a new date divider
                        if (currentDate !== msg.date) {
                            currentDate = msg.date;
                            
                            // Get the text of the last date divider
                            const lastDateDivider = $('.date-divider span').last().text();
                            
                            // Only add a new divider if it doesn't match the current message date
                            if (!lastDateDivider || lastDateDivider !== msg.formatted_date) {
                                $('#chatBox').append(`
                                    <div class="date-divider">
                                        <span>${msg.formatted_date}</span>
                                    </div>
                                `);
                            }
                        }
                        
                        // Append the message
                        const messageClass = msg.sender_id == <?php echo $user_id; ?> ? 'sent' : 'received';
                        
                        let messageHtml = `
                            <div class="message ${messageClass}" data-message-id="${msg.id}">
                                <p>${msg.message}</p>
                        `;
                        
                        // Add file attachment if exists
                        if (msg.has_file) {
                            messageHtml += msg.file_html;
                        }
                        
                        messageHtml += `<small>${msg.time}</small></div>`;
                        
                        $('#chatBox').append(messageHtml);
                        
                        // Hide empty chat message if it exists
                        $('.empty-chat').hide();
                    });
                    
                    // Only scroll if user is already at the bottom
                    const isAtBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 100;
                    if (isAtBottom) {
                        scrollToBottom();
                    }
                }
            }
        });
    }
    
    // Initial fetch and then every 3 seconds
    fetchNewMessages();
    setInterval(fetchNewMessages, 3000);
    
    // Reset file input when clicked
    $('.attachment-btn').on('click', function() {
        // Reset icon if it was changed
        $(this).find('i').removeClass('fa-check').addClass('fa-paperclip');
    });
    
    // Auto-hide notifications
    setTimeout(function() {
        $('.upload-toast').fadeOut();
    }, 5000);
});
</script>
</html>