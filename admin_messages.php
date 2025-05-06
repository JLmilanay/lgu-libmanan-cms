<?php 
// Database Connection
include 'config.php';

// Delete Message Logic: now supports AJAX deletion.
if (isset($_POST['delete_message_id'])) {
    $message_id = intval($_POST['delete_message_id']);
    $stmt = $conn->prepare("DELETE FROM messages WHERE message_id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->close();
    // If this is an AJAX request, return a JSON status:
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'success']);
        exit();
    }
    header("Location: admin_messages.php");
    exit();
}

// Fetch Messages sorted by created_at to keep the chat in order.
$result = $conn->query("SELECT * FROM messages ORDER BY created_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messaging App - Manage Messages</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .chat-container {
            max-width: 700px;
            margin: 20px auto;
            border: 1px solid #dee2e6;
            background: #ffffff;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 100px);
        }
        .chat-header {
            padding: 15px;
            background-color: #007bff;
            color: #fff;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            font-size: 1.25rem;
            font-weight: bold;
            text-align: center;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #e9ecef;
        }
        .chat-message {
            margin-bottom: 15px;
            position: relative;
            max-width: 80%;
        }
        .chat-message .message-bubble {
            padding: 10px 15px;
            border-radius: 15px;
            background: #ffffff;
            border: 1px solid #dee2e6;
            position: relative;
        }
        .chat-message .message-info {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
        }
        /* For admin messages, you might want to align them to the right */
        .chat-message.admin-message {
            align-self: flex-end;
        }
        .chat-message.admin-message .message-bubble {
            background: #d1e7dd;
            border: 1px solid #badbcc;
        }
        .delete-btn, .reply-btn {
            position: absolute;
            top: -5px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .delete-btn { right: -5px; color: #dc3545; }
        .reply-btn { right: 25px; color: #0d6efd; }
        .chat-input { border-top: 1px solid #dee2e6; }
        /* Optional reply preview area styling */
        .reply-preview {
            background: #f1f1f1;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
<div class="chat-container">
    <div class="chat-header">
        Messaging App - Manage Messages
    </div>
    <div class="chat-messages" id="chatMessages">
        <?php 
        // Display messages in a chat style.
        while ($row = $result->fetch_assoc()) {
            // For demonstration, mark messages as “admin” conditionally if needed:
            $isAdmin = false; 
            // Example: if the message sender's email equals admin@example.com, mark as admin.
            // if (strtolower($row['email']) === 'admin@example.com') {
            //     $isAdmin = true;
            // }
        ?>
        <div class="chat-message <?= $isAdmin ? 'admin-message' : '' ?>" data-id="<?= $row['message_id'] ?>">
            <div class="message-bubble">
                <!-- Delete button -->
                <button class="delete-btn" data-id="<?= $row['message_id'] ?>" title="Delete message">
                    <i class="fa fa-trash"></i>
                </button>
                <!-- Reply button -->
                <button class="reply-btn" 
                        data-id="<?= $row['message_id'] ?>" 
                        data-email="<?= htmlspecialchars($row['email']); ?>" 
                        data-message="<?= htmlspecialchars($row['message']); ?>" 
                        title="Reply message">
                    <i class="fa fa-reply"></i>
                </button>
                <strong><?= htmlspecialchars($row['email']); ?></strong><br>
                <?= nl2br(htmlspecialchars($row['message'])); ?>
            </div>
            <div class="message-info">
                <?= htmlspecialchars($row['created_at']); ?>
            </div>
        </div>
        <?php } ?>
    </div>
    <!-- Reply preview area (hidden by default) -->
    <div class="reply-preview p-3" id="replyPreview" style="display:none;">
        <div>
            <small>Replying to: <span id="replyToEmail"></span></small>
            <button type="button" id="cancelReply" class="btn btn-link btn-sm">Cancel</button>
        </div>
        <blockquote id="replyToMessage" style="margin:0; font-size:0.9rem;"></blockquote>
    </div>
    <!-- Chat input (new message form) -->
    <div class="chat-input p-3">
        <form id="chatForm" method="POST" action="send_message.php" class="d-flex">
            <!-- Hidden field holding the ID of the message being replied to -->
            <input type="hidden" name="reply_message_id" id="replyMessageId" value="">
            <input type="text" name="message" class="form-control me-2" placeholder="Type your message here..." required>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </div>
</div>
<!-- jQuery and Bootstrap JS (you can also load Popper if needed) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    // Handle deletion of messages via AJAX.
    $('.delete-btn').click(function(e){
        e.preventDefault();
        if (!confirm("Are you sure you want to delete this message?")) {
            return;
        }
        var messageId = $(this).data('id');
        var messageDiv = $(this).closest('.chat-message');
        $.ajax({
            url: 'admin_messages.php',
            type: 'POST',
            data: { delete_message_id: messageId },
            dataType: 'json',
            success: function(response){
                if(response.status === 'success') {
                    messageDiv.fadeOut(500, function(){ $(this).remove(); });
                } else {
                    alert("Failed to delete message.");
                }
            },
            error: function(){
                alert("Error deleting message.");
            }
        });
    });

    // Handle reply clicks.
    $('.reply-btn').click(function(e){
        e.preventDefault();
        var replyMessageId = $(this).data('id');
        var replyEmail = $(this).data('email');
        var replyMessage = $(this).data('message');

        // Set the reply message id in the form.
        $('#replyMessageId').val(replyMessageId);

        // Update the reply preview area.
        $('#replyToEmail').text(replyEmail);
        $('#replyToMessage').text(replyMessage);
        $('#replyPreview').slideDown();

        // Focus the chat input field.
        $('input[name="message"]').focus();
    });

    // Cancel reply.
    $('#cancelReply').click(function(e){
        e.preventDefault();
        $('#replyMessageId').val('');
        $('#replyPreview').slideUp();
    });
});
</script>
</body>
</html>
