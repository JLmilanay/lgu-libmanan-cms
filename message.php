<?php
// message.php
session_start();
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    $_SESSION['message'] = "You need to login first.";
    header("Location: login.php");
    exit();
}

// Determine if the user is a main admin or office admin
$username = $_SESSION['username'];
$current_user_id = null;
$current_user_type = null;
$current_user_fullname = null;

// Check main admin accounts first
$stmt = $conn->prepare("SELECT id, full_name FROM main_admin_accounts WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($main_admin_id, $main_admin_fullname);
$stmt->fetch();
$stmt->close();

if ($main_admin_id) {
    $current_user_id = $main_admin_id;
    $current_user_type = 'main_admin';
    $current_user_fullname = $main_admin_fullname;
} else {
    // Check office admin accounts
    $stmt = $conn->prepare("SELECT id, full_name FROM accounts WHERE username = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($office_admin_id, $office_admin_fullname);
    $stmt->fetch();
    $stmt->close();
    
    if ($office_admin_id) {
        $current_user_id = $office_admin_id;
        $current_user_type = 'office_admin';
        $current_user_fullname = $office_admin_fullname;
    } else {
        $_SESSION['message'] = "Invalid account or account not active.";
        header("Location: login.php");
        exit();
    }
}

// Create messages table if it doesn't exist with all required columns
$create_table_sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_type ENUM('main_admin', 'office_admin') NOT NULL,
    receiver_id INT NOT NULL,
    receiver_type ENUM('main_admin', 'office_admin') NOT NULL,
    message TEXT NOT NULL,
    attachment_path VARCHAR(255),
    attachment_type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (sender_id, sender_type),
    INDEX (receiver_id, receiver_type)
)";
if (!$conn->query($create_table_sql)) {
    die("Error creating messages table: " . $conn->error);
}

// Handle file upload
function handleFileUpload() {
    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                     'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $file_type = $_FILES['attachment']['type'];
    $file_size = $_FILES['attachment']['size'];

    if (!in_array($file_type, $allowed_types)) {
        return ['error' => 'Invalid file type. Only images, PDFs, and Word documents are allowed.'];
    }

    if ($file_size > 5 * 1024 * 1024) { // 5MB limit
        return ['error' => 'File size exceeds 5MB limit.'];
    }

    $upload_dir = 'uploads/message_attachments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('msg_') . '.' . $file_ext;
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
        return [
            'path' => $file_path,
            'type' => $file_type
        ];
    }

    return ['error' => 'Failed to upload file.'];
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $receiver_type = $_POST['receiver_type'];
    $message = trim($_POST['message']);
    
    if (empty($message) && empty($_FILES['attachment']['name'])) {
        $error = "Message or attachment is required.";
    } else {
        $attachment = handleFileUpload();
        
        if (isset($attachment['error'])) {
            $error = $attachment['error'];
        } else {
            $attachment_path = $attachment['path'] ?? null;
            $attachment_type = $attachment['type'] ?? null;
            
            $sql = "INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, message, attachment_path, attachment_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isissss", $current_user_id, $current_user_type, $receiver_id, $receiver_type, $message, $attachment_path, $attachment_type);
            
            if ($stmt->execute()) {
                $success = "Message sent successfully!";
                // Clear the message input after successful send
                $_POST['message'] = '';
            } else {
                $error = "Failed to send message: " . $conn->error;
            }
        }
    }
}

// Mark messages as read
if (isset($_GET['conversation_with'])) {
    $other_user_id = intval($_GET['conversation_with']);
    $other_user_type = $_GET['other_user_type'];
    
    $update_sql = "UPDATE messages SET is_read = TRUE 
                   WHERE receiver_id = ? AND receiver_type = ? 
                   AND sender_id = ? AND sender_type = ? 
                   AND is_read = FALSE";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("isis", $current_user_id, $current_user_type, $other_user_id, $other_user_type);
    $stmt->execute();
}

// Get conversations list
$conversations_sql = "SELECT 
    CASE 
        WHEN (sender_id = ? AND sender_type = ?) THEN receiver_id
        ELSE sender_id
    END as other_user_id,
    CASE 
        WHEN (sender_id = ? AND sender_type = ?) THEN receiver_type
        ELSE sender_type
    END as other_user_type,
    MAX(created_at) as last_message_time,
    SUM(CASE WHEN (receiver_id = ? AND receiver_type = ? AND is_read = FALSE) THEN 1 ELSE 0 END) as unread_count
FROM messages
WHERE (sender_id = ? AND sender_type = ?) OR (receiver_id = ? AND receiver_type = ?)
GROUP BY other_user_id, other_user_type
ORDER BY last_message_time DESC";

$stmt = $conn->prepare($conversations_sql);
$stmt->bind_param('isisisisis', 3, 'main_admin', 3, 'main_admin', 3, 'main_admin', 3, 'main_admin', 3, 'main_admin');
    $current_user_id, $current_user_type,
    $current_user_id, $current_user_type,
    $current_user_id, $current_user_type,
    $current_user_id, $current_user_type,
$stmt->execute();
$conversations_result = $stmt->get_result();
$conversations = $conversations_result->fetch_all(MYSQLI_ASSOC);

// Get messages for a specific conversation
$messages = [];
if (isset($_GET['conversation_with'])) {
    $other_user_id = intval($_GET['conversation_with']);
    $other_user_type = $_GET['other_user_type'];
    
    $messages_sql = "SELECT * FROM messages 
                    WHERE ((sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?) 
                    OR (sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?))
                    ORDER BY created_at ASC";
    $stmt = $conn->prepare($messages_sql);
    $stmt->bind_param("isisisis", 
        $current_user_id, $current_user_type, $other_user_id, $other_user_type,
        $other_user_id, $other_user_type, $current_user_id, $current_user_type);
    $stmt->execute();
    $messages_result = $stmt->get_result();
    $messages = $messages_result->fetch_all(MYSQLI_ASSOC);
}

// Get users for new conversation
if ($current_user_type === 'main_admin') {
    $users_sql = "SELECT id, username, full_name, 'office_admin' as user_type FROM accounts WHERE status = 'active'";
} else {
    $users_sql = "SELECT id, username, full_name, 'main_admin' as user_type FROM main_admin_accounts";
}
$users_result = $conn->query($users_sql);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Search functionality
if (isset($_GET['search'])) {
    $search_term = '%' . $conn->real_escape_string($_GET['search']) . '%';
    
    if ($current_user_type === 'main_admin') {
        $search_sql = "SELECT id, username, full_name, 'office_admin' as user_type FROM accounts 
                      WHERE (username LIKE ? OR full_name LIKE ?) AND status = 'active'";
    } else {
        $search_sql = "SELECT id, username, full_name, 'main_admin' as user_type FROM main_admin_accounts 
                      WHERE username LIKE ? OR full_name LIKE ?";
    }
    
    $stmt = $conn->prepare($search_sql);
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $search_result = $stmt->get_result();
    $search_users = $search_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Messaging System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            height: calc(100vh - 100px);
        }
        .sidebar {
            width: 300px;
            border-right: 1px solid #e0e0e0;
            background-color: #f9f9f9;
            overflow-y: auto;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .conversation-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .conversation-item:hover {
            background-color: #e9e9e9;
        }
        .conversation-item.active {
            background-color: #e0e0e0;
        }
        .conversation-info {
            flex: 1;
            overflow: hidden;
        }
        .conversation-name {
            font-weight: bold;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conversation-preview {
            font-size: 0.9em;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conversation-time {
            font-size: 0.8em;
            color: #999;
        }
        .unread-count {
            background-color: #4CAF50;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7em;
            font-weight: bold;
        }
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background-color: #f0f0f0;
        }
        .message {
            margin-bottom: 15px;
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        .message.sent {
            background-color: #DCF8C6;
            margin-left: auto;
            border-bottom-right-radius: 0;
        }
        .message.received {
            background-color: #fff;
            margin-right: auto;
            border-bottom-left-radius: 0;
        }
        .message-time {
            font-size: 0.7em;
            color: #666;
            margin-top: 5px;
            text-align: right;
        }
        .message-input {
            display: flex;
            padding: 15px;
            border-top: 1px solid #e0e0e0;
            background-color: #fff;
        }
        .message-input textarea {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 10px 15px;
            resize: none;
            outline: none;
            font-family: inherit;
            font-size: 0.9em;
            height: 40px;
            max-height: 100px;
        }
        .message-input button {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            margin-left: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .message-input button:hover {
            background-color: #45a049;
        }
        .attachment-icon {
            margin-right: 10px;
            cursor: pointer;
            color: #666;
        }
        .header {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .search-bar {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .search-bar input {
            width: 100%;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            font-size: 0.9em;
        }
        .new-conversation {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        .new-conversation button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .new-conversation button:hover {
            background-color: #45a049;
        }
        .user-list {
            display: none;
            position: absolute;
            background-color: white;
            width: 280px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 100;
        }
        .user-list-item {
            padding: 10px;
            cursor: pointer;
        }
        .user-list-item:hover {
            background-color: #f0f0f0;
        }
        .attachment-preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
        }
        .attachment-link {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 10px;
            background-color: #e9e9e9;
            border-radius: 5px;
            color: #333;
            text-decoration: none;
        }
        .attachment-link:hover {
            background-color: #ddd;
        }
        .error {
            color: #f44336;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 5px;
            margin: 10px;
        }
        .success {
            color: #4CAF50;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 5px;
            margin: 10px;
        }
        small {
            font-size: 0.8em;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="header">
                <?php echo htmlspecialchars($current_user_fullname ? $current_user_fullname : $username); ?>
                <small>(<?php echo $current_user_type === 'main_admin' ? 'Main Admin' : 'Office Admin'; ?>)</small>
            </div>
            
            <div class="search-bar">
                <form method="GET" action="message.php">
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </form>
            </div>
            
            <div class="new-conversation">
                <button id="newConversationBtn">New Conversation</button>
                <div class="user-list" id="userList">
                    <?php foreach($users as $user): ?>
                        <div class="user-list-item" onclick="startConversation(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                            <?php echo htmlspecialchars($user['full_name'] ? $user['full_name'] : $user['username']); ?>
                            <small>(<?php echo $user['user_type'] === 'main_admin' ? 'Main Admin' : 'Office Admin'; ?>)</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <ul class="conversation-list">
                <?php if (isset($_GET['search']) && !empty($search_users)): ?>
                    <?php foreach($search_users as $user): ?>
                        <li class="conversation-item" onclick="startConversation(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                            <div class="conversation-info">
                                <div class="conversation-name"><?php echo htmlspecialchars($user['full_name'] ? $user['full_name'] : $user['username']); ?></div>
                                <div class="conversation-preview">Click to start conversation</div>
                            </div>
                            <small>(<?php echo $user['user_type'] === 'main_admin' ? 'Main Admin' : 'Office Admin'; ?>)</small>
                        </li>
                    <?php endforeach; ?>
                <?php elseif (empty($conversations)): ?>
                    <li style="padding: 15px; text-align: center; color: #666;">No conversations yet</li>
                <?php else: ?>
                    <?php foreach($conversations as $conv): 
                        $user_type = $conv['other_user_type'];
                        $user_id = $conv['other_user_id'];
                        
                        if ($user_type === 'main_admin') {
                            $user_sql = "SELECT username, full_name FROM main_admin_accounts WHERE id = ?";
                        } else {
                            $user_sql = "SELECT username, full_name FROM accounts WHERE id = ?";
                        }
                        
                        $stmt = $conn->prepare($user_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $user_result = $stmt->get_result();
                        $user = $user_result->fetch_assoc();
                        
                        $active_class = (isset($_GET['conversation_with']) && $_GET['conversation_with'] == $user_id && $_GET['other_user_type'] == $user_type) ? 'active' : '';
                    ?>
                        <li class="conversation-item <?php echo $active_class; ?>" onclick="startConversation(<?php echo $user_id; ?>, '<?php echo $user_type; ?>')">
                            <div class="conversation-info">
                                <div class="conversation-name"><?php echo htmlspecialchars($user['full_name'] ? $user['full_name'] : $user['username']); ?></div>
                                <div class="conversation-preview">
                                    <?php 
                                        $preview_sql = "SELECT message FROM messages 
                                                        WHERE ((sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?) 
                                                        OR (sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?))
                                                        ORDER BY created_at DESC LIMIT 1";
                                        $stmt = $conn->prepare($preview_sql);
                                        $stmt->bind_param("isisisis", 
                                            $current_user_id, $current_user_type, $user_id, $user_type,
                                            $user_id, $user_type, $current_user_id, $current_user_type);
                                        $stmt->execute();
                                        $preview_result = $stmt->get_result();
                                        $preview = $preview_result->fetch_assoc();
                                        echo htmlspecialchars(substr($preview['message'], 0, 30) . (strlen($preview['message']) > 30 ? '...' : ''));
                                    ?>
                                </div>
                            </div>
                            <div class="conversation-time">
                                <?php echo date("M j", strtotime($conv['last_message_time'])); ?>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <div class="unread-count"><?php echo $conv['unread_count']; ?></div>
                                <?php endif; ?>
                                <small>(<?php echo $user_type === 'main_admin' ? 'Main Admin' : 'Office Admin'; ?>)</small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="chat-area">
            <?php if (isset($_GET['conversation_with'])): 
                $other_user_id = intval($_GET['conversation_with']);
                $other_user_type = $_GET['other_user_type'];
                
                if ($other_user_type === 'main_admin') {
                    $user_sql = "SELECT username, full_name FROM main_admin_accounts WHERE id = ?";
                } else {
                    $user_sql = "SELECT username, full_name FROM accounts WHERE id = ?";
                }
                
                $stmt = $conn->prepare($user_sql);
                $stmt->bind_param("i", $other_user_id);
                $stmt->execute();
                $user_result = $stmt->get_result();
                $other_user = $user_result->fetch_assoc();
            ?>
                <div class="header">
                    <?php echo htmlspecialchars($other_user['full_name'] ? $other_user['full_name'] : $other_user['username']); ?>
                    <small>(<?php echo $other_user_type === 'main_admin' ? 'Main Admin' : 'Office Admin'; ?>)</small>
                </div>
                
                <div class="messages-container" id="messagesContainer">
                    <?php foreach($messages as $message): 
                        $is_sent = ($message['sender_id'] == $current_user_id && $message['sender_type'] == $current_user_type);
                    ?>
                        <div class="message <?php echo $is_sent ? 'sent' : 'received'; ?>">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            
                            <?php if ($message['attachment_path']): 
                                $file_ext = pathinfo($message['attachment_path'], PATHINFO_EXTENSION);
                                if (strpos($message['attachment_type'], 'image/') === 0): ?>
                                    <img src="<?php echo htmlspecialchars($message['attachment_path']); ?>" class="attachment-preview">
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($message['attachment_path']); ?>" class="attachment-link" download>
                                        Download Attachment (<?php echo strtoupper($file_ext); ?>)
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="message-time">
                                <?php echo date("M j, g:i a", strtotime($message['created_at'])); ?>
                                <?php if ($is_sent && $message['is_read']): ?>
                                    ✓✓
                                <?php elseif ($is_sent): ?>
                                    ✓
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form class="message-input" method="POST" enctype="multipart/form-data" id="messageForm">
                    <input type="hidden" name="receiver_id" value="<?php echo $other_user_id; ?>">
                    <input type="hidden" name="receiver_type" value="<?php echo $other_user_type; ?>">
                    
                    <label for="attachment" class="attachment-icon" title="Attach file">📎</label>
                    <input type="file" id="attachment" name="attachment" style="display: none;">
                    
                    <textarea name="message" placeholder="Type your message here..." id="messageInput"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    <button type="submit" name="send_message" title="Send message">➤</button>
                </form>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <script>
                    // Scroll to bottom of messages
                    const messagesContainer = document.getElementById('messagesContainer');
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    
                    // Auto-resize textarea
                    const textarea = document.getElementById('messageInput');
                    textarea.addEventListener('input', function() {
                        this.style.height = 'auto';
                        this.style.height = (this.scrollHeight) + 'px';
                    });
                    
                    // Handle form submission
                    document.getElementById('messageForm').addEventListener('submit', function(e) {
                        const messageInput = document.getElementById('messageInput');
                        const attachmentInput = document.getElementById('attachment');
                        
                        if (messageInput.value.trim() === '' && attachmentInput.files.length === 0) {
                            e.preventDefault();
                            alert('Please enter a message or attach a file.');
                        }
                    });
                    
                    // Focus the message input when conversation is opened
                    document.getElementById('messageInput').focus();
                </script>
            <?php else: ?>
                <div style="display: flex; justify-content: center; align-items: center; height: 100%; color: #666;">
                    Select a conversation or start a new one
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Show/hide user list for new conversation
        document.getElementById('newConversationBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            const userList = document.getElementById('userList');
            userList.style.display = userList.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close user list when clicking outside
        document.addEventListener('click', function() {
            document.getElementById('userList').style.display = 'none';
        });
        
        // Prevent user list from closing when clicking inside it
        document.getElementById('userList').addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Start new conversation
        function startConversation(userId, userType) {
            window.location.href = `message.php?conversation_with=${userId}&other_user_type=${userType}`;
        }
        
        // Auto-refresh messages every 5 seconds
        <?php if (isset($_GET['conversation_with'])): ?>
            setInterval(function() {
                const currentUrl = new URL(window.location.href);
                const conversationWith = currentUrl.searchParams.get('conversation_with');
                const otherUserType = currentUrl.searchParams.get('other_user_type');
                
                fetch(`message.php?conversation_with=${conversationWith}&other_user_type=${otherUserType}&refresh=true`)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newMessages = doc.querySelector('.messages-container').innerHTML;
                        const currentMessages = document.querySelector('.messages-container').innerHTML;
                        
                        if (newMessages !== currentMessages) {
                            document.querySelector('.messages-container').innerHTML = newMessages;
                            document.querySelector('.messages-container').scrollTop = document.querySelector('.messages-container').scrollHeight;
                        }
                        
                        // Update conversation list unread counts
                        const newConversations = doc.querySelectorAll('.conversation-item');
                        newConversations.forEach(newConv => {
                            const convId = newConv.getAttribute('onclick').match(/\d+/)[0];
                            const convType = newConv.getAttribute('onclick').match(/'([^']+)'/)[1];
                            const existingConv = document.querySelector(`.conversation-item[onclick*="${convId}"][onclick*="${convType}"]`);
                            
                            if (existingConv) {
                                existingConv.innerHTML = newConv.innerHTML;
                            }
                        });
                    });
            }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>