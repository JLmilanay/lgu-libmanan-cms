<?php
session_start();
require_once 'config.php'; // Include your database configuration
require_once 'functions.php'; // Include your functions

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the logged-in user is a main admin
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT COUNT(*) FROM accounts WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($isMainAdmin);
$stmt->fetch();
$stmt->close();

if ($isMainAdmin == 0) {
    // If the user is not a main admin, set message and redirect to login page
    $_SESSION['message'] = "You need to login first.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details including office_id, full_name, email, contact_number, and status from the accounts table
$sql = "SELECT office_id, full_name, email, contact_number, status FROM accounts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Access denied: User not found.");
}

$user = $result->fetch_assoc();
$user_office_id = $user['office_id'];

// Check if the account status is active (case-insensitive)
if (strtoupper(trim($user['status'])) !== 'ACTIVE') {
    include('account_disable_message.html');
    exit();
}

// Initialize messages
$success_msg = $error_msg = '';

// Process status updates (mark as replied/spam/important)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_msg = "Security token validation failed. Please try again.";
    } elseif (isset($_POST['update_status'])) {
        $message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'update_status', FILTER_SANITIZE_STRING);
        
        // Validate inputs
        if (!$message_id || $message_id < 1) {
            $error_msg = "Invalid message ID!";
        } else {
            // Validate status
            $valid_statuses = ['replied', 'pending', 'spam', 'important'];
            if (!in_array($status, $valid_statuses)) {
                $error_msg = "Invalid status!";
            } else {
                try {
                    $stmt = $conn->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("si", $status, $message_id);
                    
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            $_SESSION['success_msg'] = "Status updated successfully!";
                        } else {
                            $error_msg = "No changes made - message not found or already has this status.";
                        }
                    } else {
                        $error_msg = "Error updating status: " . $conn->error;
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Filter and search parameters
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? 'all';
$search_query = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '');

// Validate status filter
$valid_filters = ['all', 'replied', 'pending', 'spam', 'important'];
if (!in_array($status_filter, $valid_filters)) {
    $status_filter = 'all';
}

// Build the query with filters, ensuring messages are from the user's office
$query = "SELECT * FROM contact_messages WHERE office_id = ?";
$params = [$user_office_id];
$types = 'i'; // 'i' for integer

if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's'; // 's' for string
}

if (!empty($search_query)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR message LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss'; // three strings
}

$query .= " ORDER BY created_at DESC";

// Prepare and execute the query
try {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Database query preparation failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $total_messages = $result->num_rows;
} catch (Exception $e) {
    $error_msg = "Database error: " . $e->getMessage();
    $total_messages = 0;
    $result = false;
}

// Fetch office information
$office_query = "SELECT office_name, office_address FROM offices WHERE id = ?";
$office_stmt = $conn->prepare($office_query);
$office_stmt->bind_param("i", $user_office_id);
$office_stmt->execute();
$office_result = $office_stmt->get_result();

if ($office_result->num_rows > 0) {
    $office = $office_result->fetch_assoc();
} else {
    die("Error: Office information is not available.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .message-card {
            border-left: 4px solid;
            transition: all 0.2s;
        }
        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-replied { border-left-color: #198754; }
        .status-pending { border-left-color: #fd7e14; }
        .status-spam { border-left-color: #dc3545; }
        .status-important { border-left-color: #ffc107; }
        .message-text {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .badge-replied { background-color: #198754; }
        .badge-pending { background-color: #fd7e14; }
        .badge-spam { background-color: #dc3545; }
        .badge-important { background-color: #ffc107; }
        .search-box { max-width: 300px; }
        
        /* Dropdown fixes */
        .dropdown { position: relative; }
        .dropdown-menu {
            z-index: 1050; /* Ensure dropdown is above other elements */
            position: absolute;
            right: 0;
            left: auto;
            top: 100%;
            margin-top: 0.125rem;
            display: none; /* Hide by default */
        }
        .dropdown.show .dropdown-menu {
            display: block; /* Show when dropdown is active */
        }
        .dropdown-toggle { position: relative; z-index: 1; }
        .list-group-item { overflow: visible !important; }
        
        /* Status badges */
        .badge-status {
            min-width: 80px;
            text-transform: capitalize;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .btn-text {
                display: none;
            }
            .search-box {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php 
            // Check if sidebar.php exists before including
            if (file_exists('sidebar.php')) {
                include 'sidebar.php'; 
            } else {
                echo '<div class="alert alert-danger">Sidebar file not found.</div>';
            }
            ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h2 class="h3">Contact Messages</h2>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="?status=all" class="btn btn-sm btn-outline-secondary <?= $status_filter === 'all' ? 'active' : '' ?>">All</a>
                            <a href="?status=pending" class="btn btn-sm btn-outline-secondary <?= $status_filter === 'pending' ? 'active' : '' ?>">Pending</a>
                            <a href="?status=replied" class="btn btn-sm btn-outline-secondary <?= $status_filter === 'replied' ? 'active' : '' ?>">Replied</a>
                            <a href="?status=important" class="btn btn-sm btn-outline-secondary <?= $status_filter === 'important' ? 'active' : '' ?>">Important</a>
                            <a href="?status=spam" class="btn btn-sm btn-outline-secondary <?= $status_filter === 'spam' ? 'active' : '' ?>">Spam</a>
                        </div>
                    </div>
                </div>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <form class="row g-2" method="GET">
                            <div class="col-md-6">
                                <div class="input-group search-box">
                                    <input type="text" class="form-control" name="search" placeholder="Search messages..." 
                                           value="<?= htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="bi bi-search"></i> <span class="btn-text">Search</span>
                                    </button>
                                    <?php if (!empty($search_query) || $status_filter !== 'all'): ?>
                                        <a href="?" class="btn btn-outline-danger">
                                            <i class="bi bi-x-circle"></i> <span class="btn-text">Clear</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="text-muted me-2"><?= number_format($total_messages) ?> message(s) found</span>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <div class="list-group-item message-card status-<?= htmlspecialchars($row['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8') ?> mb-2">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></h5>
                                            <small class="text-muted"><?= format_date($row['created_at']) ?></small>
                                        </div>
                                        <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                            <small>
                                                <a href="mailto:<?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            </small>
                                            <span class="badge rounded-pill badge-<?= htmlspecialchars($row['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8') ?> badge-status">
                                                <?= ucfirst(htmlspecialchars($row['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8')) ?>
                                            </span>
                                        </div>
                                        <p class="mb-2 message-text"><?= nl2br(htmlspecialchars(truncate_text($row['message'], 200), ENT_QUOTES, 'UTF-8')) ?></p>
                                        
                                        <div class="d-flex justify-content-end flex-wrap">
                                            <button class="btn btn-sm btn-outline-primary me-2 mb-1 view-message-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#messageModal"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-message="<?= htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-created="<?= format_date($row['created_at'], true) ?>"
                                                    data-status="<?= htmlspecialchars($row['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-eye"></i> <span class="btn-text">View</span>
                                            </button>
                                            
                                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($row['email']) ?>&su=<?= urlencode('Re: Your inquiry') ?>&body=<?= urlencode(
                                                "Dear " . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . ",\n\n" .
                                                "Thank you for contacting us regarding:\n\n\"" . htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8') . "\"\n\n" .
                                                "We appreciate your feedback and will get back to you shortly.\n\n" .
                                                "<strong>Best regards,</strong>\n" .
                                                "<strong>" . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') . "</strong>\n" .
                                                "<strong>Office Name:</strong> " . (isset($office['office_name']) ? htmlspecialchars($office['office_name'], ENT_QUOTES, 'UTF-8') : 'Your Office Name') . "\n" .
                                                "<strong>Office Address:</strong> " . (isset($office['office_address']) ? htmlspecialchars($office['office_address'], ENT_QUOTES, 'UTF-8') : 'Your Office Address') . "\n" .
                                                "<strong>Phone:</strong> " . (isset($user['contact_number']) ? htmlspecialchars($user['contact_number'], ENT_QUOTES, 'UTF-8') : 'Your Office Phone') . "\n" .
                                                "<strong>Email:</strong> " . (isset($user['email']) ? htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') : 'Your Office Email') . "\n\n"
                                            ) ?>" class="btn btn-sm btn-success me-2 mb-1" target="_blank" rel="noopener noreferrer">
                                                <i class="bi bi-reply"></i> <span class="btn-text">Reply</span>
                                            </a>
                                            
                                            <div class="dropdown d-inline-block ms-2 mb-1">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="message_id" value="<?= $row['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="statusDropdown<?= $row['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-tag"></i> <span class="btn-text">Status</span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="statusDropdown<?= $row['id'] ?>">
                                                        <li><button type="submit" name="update_status" value="pending" class="dropdown-item <?= $row['status'] === 'pending' ? 'active' : '' ?>"><i class="bi bi-clock"></i> Pending</button></li>
                                                        <li><button type="submit" name="update_status" value="replied" class="dropdown-item <?= $row['status'] === 'replied' ? 'active' : '' ?>"><i class="bi bi-check-circle"></i> Replied</button></li>
                                                        <li><button type="submit" name="update_status" value="important" class="dropdown-item <?= $row['status'] === 'important' ? 'active' : '' ?>"><i class="bi bi-exclamation-circle"></i> Important</button></li>
                                                        <li><button type="submit" name="update_status" value="spam" class="dropdown-item <?= $row['status'] === 'spam' ? 'active' : '' ?>"><i class="bi bi-trash"></i> Spam</button></li>
                                                    </ul>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">No messages found matching your criteria.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Message Details Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Message Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <span id="modal-name"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <span id="modal-email"></span></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Date Received:</strong> <span id="modal-date"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> <span id="modal-status" class="badge rounded-pill"></span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p><strong>Message:</strong></p>
                            <div class="message-text p-3 bg-light rounded" id="modal-message"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-success" id="modal-reply-btn" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-reply"></i> Reply via Gmail
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Message modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.view-message-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const name = this.getAttribute('data-name');
                    const email = this.getAttribute('data-email');
                    const message = this.getAttribute('data-message');
                    const date = this.getAttribute('data-created');
                    const status = this.getAttribute('data-status');
                    
                    document.getElementById('modal-name').textContent = name;
                    document.getElementById('modal-email').textContent = email;
                    document.getElementById('modal-date').textContent = date;
                    document.getElementById('modal-message').textContent = message;
                    
                    const statusBadge = document.getElementById('modal-status');
                    statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    statusBadge.className = 'badge rounded-pill badge-' + status;
                    
                    // Set up reply button
                    const replyBtn = document.getElementById('modal-reply-btn');
                    const subject = encodeURIComponent('Re: Your inquiry');
                    const body = encodeURIComponent(`Dear ${name},\n\nThank you for contacting us regarding:\n\n"${message}"\n\nWe appreciate your feedback and will get back to you shortly.\n\nBest regards,\n<strong>${<?= json_encode($user['full_name'] ?? 'Your Name') ?>}</strong>\n<strong>Office Name:</strong> ${<?= json_encode($office['office_name'] ?? 'Your Office Name') ?>}\n<strong>Office Address:</strong> ${<?= json_encode($office['office_address'] ?? 'Your Office Address') ?>}`);
                    replyBtn.href = `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(email)}&su=${subject}&body=${body}`;
                });
            });
            
            // Auto-close alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>