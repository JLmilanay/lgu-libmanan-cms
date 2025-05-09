<?php
session_start();
include 'config.php'; // Assumes this file sets up MySQLi connection as $conn

// Check if the user is logged in.
if (!isset($_SESSION['username'])) {
    $_SESSION['message'] = "You need to login first.";
    header("Location: login.php");
    exit();
}

// Check if the logged-in user is a main admin.
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT COUNT(*) FROM main_admin_accounts WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($isMainAdmin);
$stmt->fetch();
$stmt->close();

if ($isMainAdmin == 0) {
    $_SESSION['message'] = "You need to login first.";
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$current_section = isset($_GET['section']) ? $_GET['section'] : 'awards';

// -----------------------------------------------------------------------------
// PROCESS POST (CREATE / UPDATE)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($current_section) {
            case 'awards':
                handleAwardsForm();
                break;
            case 'offices':
                handleOfficesForm();
                break;
            case 'services':
                handleServicesForm();
                break;
            case 'announcements':
                handleAnnouncementsForm();
                break;
            case 'news':
                handleNewsForm();
                break;
            case 'gallery':
                handleGalleryForm();
                break;
            case 'documents':
                handleDocumentsForm();
                break;
            case 'emergency':
                handleEmergencyForm();
                break;
            default:
                throw new Exception("Unknown section selected.");
        }
        $success = "Operation completed successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// -----------------------------------------------------------------------------
// PROCESS DELETE REQUEST (via GET)
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $id = (int) $_GET['id'];
        switch ($current_section) {
            case 'awards':
                deleteRecord('lgu_main_awards', 'award_id', $id);
                break;
            case 'offices':
                deleteRecord('lgu_main_offices', 'office_id', $id);
                break;
            case 'services':
                deleteRecord('lgu_main_services', 'service_id', $id);
                break;
            case 'announcements':
                deleteRecord('lgu_main_announcements', 'announcement_id', $id);
                break;
            case 'news':
                deleteRecord('lgu_main_news', 'news_id', $id);
                break;
            case 'gallery':
                deleteRecord('lgu_main_gallery', 'gallery_id', $id);
                break;
            case 'documents':
                deleteRecord('lgu_main_documents', 'document_id', $id);
                break;
            case 'emergency':
                deleteRecord('lgu_main_emergency', 'emergency_id', $id);
                break;
            default:
                throw new Exception("Unknown section selected.");
        }
        $success = "Record deleted successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// -----------------------------------------------------------------------------
// HELPER FUNCTIONS (backend – largely unchanged)
// -----------------------------------------------------------------------------

function deleteRecord($table, $id_field, $id)
{
    global $conn;
    $sql = "DELETE FROM $table WHERE $id_field = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

function handleFileUpload($field_name, $allowed_types, $max_size = 2, $upload_dir = 'uploads/')
{
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] == UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field_name];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $file['error']);
    }
    if ($file['size'] > $max_size * 1024 * 1024) {
        throw new Exception("File size exceeds maximum allowed size of {$max_size}MB");
    }
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed_types));
    }
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $filename = uniqid() . '.' . $file_ext;
    $target_path = $upload_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception("Failed to move uploaded file.");
    }
    return './' . $target_path;
}

function handleAwardsForm()
{
    global $conn;
    $id = isset($_POST['award_id']) ? (int) $_POST['award_id'] : 0;
    $title = trim($_POST['award_title']);
    $status = $_POST['award_status'];
    if (empty($title)) {
        throw new Exception("Award title is required");
    }
    $image_url = handleFileUpload('award_image', ['jpg', 'jpeg', 'png', 'gif'], 5, 'uploads/awards/');
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE lgu_main_awards SET award_title = ?, award_status = ? WHERE award_id = ?");
        $stmt->bind_param('ssi', $title, $status, $id);
        $stmt->execute();
        if ($image_url) {
            $stmt = $conn->prepare("UPDATE lgu_main_awards SET award_image_url = ? WHERE award_id = ?");
            $stmt->bind_param('si', $image_url, $id);
            $stmt->execute();
        }
    } else {
        if (!$image_url) {
            throw new Exception("Award image is required");
        }
        $stmt = $conn->prepare("INSERT INTO lgu_main_awards (award_title, award_image_url, award_status) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $title, $image_url, $status);
        $stmt->execute();
    }
}

function handleOfficesForm()
{
    global $conn;
    $id = isset($_POST['office_id']) ? (int) $_POST['office_id'] : 0;
    $name = trim($_POST['office_name']);
    $description = trim($_POST['office_description']);
    $link = trim($_POST['office_link']);
    $status = $_POST['office_status'];
    if (empty($name)) {
        throw new Exception("Office name is required");
    }
    $image_url = handleFileUpload('office_image', ['jpg', 'jpeg', 'png', 'gif'], 5, 'uploads/offices/');
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE lgu_main_offices SET office_name = ?, office_description = ?, office_link = ?, office_status = ? WHERE office_id = ?");
        $stmt->bind_param('ssssi', $name, $description, $link, $status, $id);
        $stmt->execute();
        if ($image_url) {
            $stmt = $conn->prepare("UPDATE lgu_main_offices SET office_image_url = ? WHERE office_id = ?");
            $stmt->bind_param('si', $image_url, $id);
            $stmt->execute();
        }
    } else {
        if (!$image_url) {
            throw new Exception("Office image is required");
        }
        $stmt = $conn->prepare("INSERT INTO lgu_main_offices (office_name, office_description, office_image_url, office_link, office_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $name, $description, $image_url, $link, $status);
        $stmt->execute();
    }
}

function handleServicesForm()
{
    global $conn;
    $id = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
    $name = trim($_POST['service_name']);
    $description = trim($_POST['service_description']);
    $office_id = (int) $_POST['office_id'];
    $status = $_POST['service_status'];
    if (empty($name)) {
        throw new Exception("Service name is required");
    }
    if ($office_id <= 0) {
        throw new Exception("Please select an office");
    }
    $icon_url = handleFileUpload('service_icon', ['jpg', 'jpeg', 'png', 'gif', 'svg'], 2, 'uploads/services/');
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE lgu_main_services SET service_name = ?, service_description = ?, office_id = ?, service_status = ? WHERE service_id = ?");
        $stmt->bind_param('ssisi', $name, $description, $office_id, $status, $id);
        $stmt->execute();
        if ($icon_url) {
            $stmt = $conn->prepare("UPDATE lgu_main_services SET service_icon_url = ? WHERE service_id = ?");
            $stmt->bind_param('si', $icon_url, $id);
            $stmt->execute();
        }
    } else {
        if (!$icon_url) {
            throw new Exception("Service icon is required");
        }
        $stmt = $conn->prepare("INSERT INTO lgu_main_services (service_name, service_description, service_icon_url, office_id, service_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssis', $name, $description, $icon_url, $office_id, $status);
        $stmt->execute();
    }
}

function handleAnnouncementsForm()
{
    global $conn;
    $id = isset($_POST['announcement_id']) ? (int) $_POST['announcement_id'] : 0;
    $title = trim($_POST['announcement_title']);
    $content = trim($_POST['announcement_content']);
    $date = $_POST['announcement_date'];
    $status = $_POST['announcement_status'];
    if (empty($title)) {
        throw new Exception("Announcement title is required");
    }
    $image_url = handleFileUpload('announcement_image', ['jpg', 'jpeg', 'png', 'gif'], 5, 'uploads/announcements/');
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE lgu_main_announcements SET announcement_title = ?, announcement_content = ?, announcement_date = ?, announcement_status = ? WHERE announcement_id = ?");
        $stmt->bind_param('ssssi', $title, $content, $date, $status, $id);
        $stmt->execute();
        if ($image_url) {
            $stmt = $conn->prepare("UPDATE lgu_main_announcements SET announcement_image_url = ? WHERE announcement_id = ?");
            $stmt->bind_param('si', $image_url, $id);
            $stmt->execute();
        }
    } else {
        if (!$image_url) {
            throw new Exception("Announcement image is required");
        }
        $stmt = $conn->prepare("INSERT INTO lgu_main_announcements (announcement_title, announcement_content, announcement_image_url, announcement_date, announcement_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $title, $content, $image_url, $date, $status);
        $stmt->execute();
    }
}

function handleNewsForm()
{
    global $conn;
    $id = isset($_POST['news_id']) ? (int) $_POST['news_id'] : 0;
    $title = trim($_POST['news_title']);
    $content = trim($_POST['news_content']);
    $date = $_POST['news_date'];
    $status = $_POST['news_status'];
    if (empty($title)) {
        throw new Exception("News title is required");
    }
    $image_url = handleFileUpload('news_image', ['jpg', 'jpeg', 'png', 'gif'], 5, 'uploads/news/');
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE lgu_main_news SET news_title = ?, news_content = ?, news_date = ?, news_status = ? WHERE news_id = ?");
        $stmt->bind_param('ssssi', $title, $content, $date, $status, $id);
        $stmt->execute();
        if ($image_url) {
            $stmt = $conn->prepare("UPDATE lgu_main_news SET news_image_url = ? WHERE news_id = ?");
            $stmt->bind_param('si', $image_url, $id);
            $stmt->execute();
        }
    } else {
        if (!$image_url) {
            throw new Exception("News image is required");
        }
        $stmt = $conn->prepare("INSERT INTO lgu_main_news (news_title, news_content, news_image_url, news_date, news_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $title, $content, $image_url, $date, $status);
        $stmt->execute();
    }
}

function handleGalleryForm()
{
    global $conn;
    $id = isset($_POST['gallery_id']) ? (int) $_POST['gallery_id'] : 0;
    $caption = trim($_POST['gallery_caption']);
    $status = $_POST['gallery_status'];

    if (empty($caption)) {
        throw new Exception("Gallery caption is required");
    }

    // Dynamic media handling: local file or media link.
    $media_url = null;
    $detected_type = "";

    if ($_POST['media_source'] === 'local') {
        // Handle local file upload
        if (isset($_FILES['gallery_media']) && $_FILES['gallery_media']['error'] != UPLOAD_ERR_NO_FILE) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm'];
            $upload_dir = 'uploads/gallery/';
            $uploaded = handleFileUpload('gallery_media', $allowed, 10, $upload_dir);
            if ($uploaded) {
                $media_url = $uploaded;
                $ext = strtolower(pathinfo($uploaded, PATHINFO_EXTENSION));
                $detected_type = in_array($ext, ['mp4', 'webm']) ? "video" : "image";
            }
        }
    } else { // media_source === 'link'
        $media_url = trim($_POST['media_link']);
        if (empty($media_url)) {
            throw new Exception("Gallery media link is required");
        }
        // Set detected type to "link"
        $detected_type = "link";
    }

    if (!$media_url) {
        throw new Exception("Gallery media is required");
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE lgu_main_gallery SET gallery_caption = ?, gallery_image_url = ?, gallery_type = ?, gallery_status = ? WHERE gallery_id = ?");
        $stmt->bind_param('ssssi', $caption, $media_url, $detected_type, $status, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO lgu_main_gallery (gallery_caption, gallery_image_url, gallery_type, gallery_status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $caption, $media_url, $detected_type, $status);
        $stmt->execute();
    }
}

function handleDocumentsForm()
{
    global $conn;
    $id = isset($_POST['document_id']) ? (int) $_POST['document_id'] : 0;
    $title = trim($_POST['document_title']);
    $status = $_POST['document_status'];
    if (empty($title)) {
        throw new Exception("Document title is required");
    }
    $file_url = handleFileUpload('document_file', ['pdf', 'doc', 'docx'], 5, 'uploads/documents/');
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE lgu_main_documents SET document_title = ?, document_status = ? WHERE document_id = ?");
        $stmt->bind_param('ssi', $title, $status, $id);
        $stmt->execute();
        if ($file_url) {
            $stmt = $conn->prepare("UPDATE lgu_main_documents SET document_url = ? WHERE document_id = ?");
            $stmt->bind_param('si', $file_url, $id);
            $stmt->execute();
        }
    } else {
        if (!$file_url) {
            throw new Exception("Document file is required");
        }
        $stmt = $conn->prepare("INSERT INTO lgu_main_documents (document_title, document_url, document_status) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $title, $file_url, $status);
        $stmt->execute();
    }
}

function handleEmergencyForm()
{
    global $conn;
    $id = isset($_POST['emergency_id']) ? (int) $_POST['emergency_id'] : 0;
    $name = trim($_POST['emergency_name']);
    $phone = trim($_POST['emergency_phone']);
    $alt_phone = trim($_POST['emergency_alt_phone']);
    $address = trim($_POST['emergency_address']);
    $category = trim($_POST['emergency_category']);
    $notes = trim($_POST['emergency_notes']);
    $status = $_POST['emergency_status'];
    if (empty($name)) {
        throw new Exception("Emergency name is required");
    }
    $logo_url = handleFileUpload('emergency_logo', ['jpg', 'jpeg', 'png', 'gif'], 5, 'uploads/emergency/');
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE lgu_main_emergency SET emergency_name = ?, emergency_phone = ?, emergency_alt_phone = ?, emergency_address = ?, emergency_category = ?, emergency_notes = ?, emergency_status = ? WHERE emergency_id = ?");
        $stmt->bind_param('sssssssi', $name, $phone, $alt_phone, $address, $category, $notes, $status, $id);
        $stmt->execute();
        if ($logo_url) {
            $stmt = $conn->prepare("UPDATE lgu_main_emergency SET emergency_logo = ? WHERE emergency_id = ?");
            $stmt->bind_param('si', $logo_url, $id);
            $stmt->execute();
        }
    } else {
        if (!$logo_url) {
            throw new Exception("Emergency logo is required");
        }
        $stmt = $conn->prepare("INSERT INTO lgu_main_emergency (emergency_name, emergency_phone, emergency_alt_phone, emergency_address, emergency_logo, emergency_category, emergency_notes, emergency_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssss', $name, $phone, $alt_phone, $address, $logo_url, $category, $notes, $status);
        $stmt->execute();
    }
}

// -----------------------------------------------------------------------------
// UTILITY FUNCTIONS FOR FETCHING DATA
// -----------------------------------------------------------------------------
function getSectionData($section)
{
    global $conn;
    switch ($section) {
        case 'awards':
            $result = $conn->query("SELECT * FROM lgu_main_awards ORDER BY award_id DESC");
            return $result->fetch_all(MYSQLI_ASSOC);
        case 'offices':
            $result = $conn->query("SELECT * FROM lgu_main_offices ORDER BY office_id DESC");
            return $result->fetch_all(MYSQLI_ASSOC);
        case 'services':
            $result = $conn->query("SELECT s.*, o.office_name FROM lgu_main_services s JOIN lgu_main_offices o ON s.office_id = o.office_id ORDER BY s.service_id DESC");
            return $result->fetch_all(MYSQLI_ASSOC);
        case 'announcements':
            $result = $conn->query("SELECT * FROM lgu_main_announcements ORDER BY announcement_date DESC");
            return $result->fetch_all(MYSQLI_ASSOC);
        case 'news':
            $result = $conn->query("SELECT * FROM lgu_main_news ORDER BY news_date DESC");
            return $result->fetch_all(MYSQLI_ASSOC);
        case 'gallery':
            $result = $conn->query("SELECT * FROM lgu_main_gallery ORDER BY gallery_id DESC");
            return $result->fetch_all(MYSQLI_ASSOC);
        case 'documents':
            $result = $conn->query("SELECT * FROM lgu_main_documents ORDER BY document_id DESC");
            return $result->fetch_all(MYSQLI_ASSOC);
        case 'emergency':
            $result = $conn->query("SELECT * FROM lgu_main_emergency ORDER BY emergency_category, emergency_name");
            return $result->fetch_all(MYSQLI_ASSOC);
        default:
            return [];
    }
}

function getOffices()
{
    global $conn;
    $result = $conn->query("SELECT office_id, office_name FROM lgu_main_offices WHERE office_status = 'active' ORDER BY office_name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

$data = getSectionData($current_section);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Libmanan Admin Panel - <?php echo ucfirst($current_section); ?> Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #003366, #001f4d);
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .main-content {
            padding: 20px;
        }

        .card {
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .img-thumbnail {
            max-width: 100px;
        }

        #mediaPreviewContent img {
            display: block;
            margin: auto;
            max-width: 100%;
            height: auto;
            max-height: 80vh;
        }

        #currentMediaPreview {
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
        <!-- Sidebar Navigation -->
<div class="col-md-3 col-lg-2 sidebar p-0">
    <div class="p-4">
        <!-- Logo Section -->
        <div class="text-center mb-3">
            <img src="ASSETS/Hi-Res-BAGONG-PILIPINAS-LOGO.png" alt="Logo 1" class="img-fluid mx-1" style="max-height: 50px;">
            <img src="ASSETS/LIBMANAN LOGO.png" alt="Logo 2" class="img-fluid mx-1" style="max-height: 50px;">
            <img src="ASSETS/big jNEW.png" alt="Logo 3" class="img-fluid mx-1" style="max-height: 50px;">
        </div>

        <!-- System Name -->
        <h4 class="text-center mb-4">
            LGU-Libmanan Public Information Management System-(Main)
        </h4>
  
        <!-- Navigation Links -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_section == 'awards') ? 'active' : ''; ?>" href="?section=awards">
                    <i class="fas fa-trophy"></i> Awards
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_section == 'offices') ? 'active' : ''; ?>" href="?section=offices">
                    <i class="fas fa-building"></i> Offices
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_section == 'services') ? 'active' : ''; ?>" href="?section=services">
                    <i class="fas fa-concierge-bell"></i> Services
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_section == 'announcements') ? 'active' : ''; ?>" href="?section=announcements">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_section == 'news') ? 'active' : ''; ?>" href="?section=news">
                    <i class="fas fa-newspaper"></i> News
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_section == 'gallery') ? 'active' : ''; ?>" href="?section=gallery">
                    <i class="fas fa-images"></i> Gallery
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_section == 'documents') ? 'active' : ''; ?>" href="?section=documents">
                    <i class="fas fa-file-alt"></i> Documents
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_section == 'emergency') ? 'active' : ''; ?>" href="?section=emergency">
                    <i class="fas fa-phone-alt"></i> Emergency
                </a>
            </li>
            <!-- Back to Dashboard Link -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_section == 'dashboard') ? 'active' : ''; ?>" href="main-manage.php">
                    <i class="fas fa-dashboard"></i> Back to Dashboard
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo ucfirst($current_section); ?> Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal" id="addNewBtn">
                        <i class="fas fa-plus"></i> Add New
                    </button>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <?php if ($current_section === 'awards'): ?>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Image</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        <?php elseif ($current_section === 'offices'): ?>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Image</th>
                                            <th>Link</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        <?php elseif ($current_section === 'services'): ?>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Icon</th>
                                            <th>Office</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        <?php elseif ($current_section === 'announcements'): ?>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Image</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        <?php elseif ($current_section === 'news'): ?>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Image</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        <?php elseif ($current_section === 'gallery'): ?>
                                            <th>ID</th>
                                            <th>Caption</th>
                                            <th>Media</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        <?php elseif ($current_section === 'documents'): ?>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>File</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        <?php elseif ($current_section === 'emergency'): ?>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Logo</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $item): ?>
                                        <tr>
                                            <?php if ($current_section === 'awards'): ?>
                                                <td><?php echo $item['award_id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['award_title']); ?></td>
                                                <td>
                                                    <?php if ($item['award_image_url']): ?>
                                                        <img src="<?php echo ltrim($item['award_image_url'], './'); ?>"
                                                            class="img-thumbnail" alt="Award Image">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $item['award_status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($item['award_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?php echo $item['award_id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($item['award_title']); ?>"
                                                        data-status="<?php echo $item['award_status']; ?>"
                                                        data-media_url="<?php echo htmlspecialchars($item['award_image_url']); ?>"
                                                        data-media_type="image">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?section=awards&action=delete&id=<?php echo $item['award_id']; ?>"
                                                        class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-info view-btn"
                                                        data-media_content="<?php echo htmlspecialchars($item['award_image_url']); ?>"
                                                        data-media_type="image"
                                                        data-caption="<?php echo htmlspecialchars($item['award_title']); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            <?php elseif ($current_section === 'offices'): ?>
                                                <td><?php echo $item['office_id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['office_name']); ?></td>
                                                <td>
                                                    <?php if ($item['office_image_url']): ?>
                                                        <img src="<?php echo ltrim($item['office_image_url'], './'); ?>"
                                                            class="img-thumbnail" alt="Office Image">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['office_link']); ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $item['office_status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($item['office_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?php echo $item['office_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($item['office_name']); ?>"
                                                        data-description="<?php echo htmlspecialchars($item['office_description']); ?>"
                                                        data-link="<?php echo htmlspecialchars($item['office_link']); ?>"
                                                        data-status="<?php echo $item['office_status']; ?>"
                                                        data-media_url="<?php echo htmlspecialchars($item['office_image_url']); ?>"
                                                        data-media_type="image">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?section=offices&action=delete&id=<?php echo $item['office_id']; ?>"
                                                        class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-info view-btn"
                                                        data-media_content="<?php echo htmlspecialchars($item['office_image_url']); ?>"
                                                        data-media_type="image"
                                                        data-caption="<?php echo htmlspecialchars($item['office_name']); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            <?php elseif ($current_section === 'services'): ?>
                                                <td><?php echo $item['service_id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                                                <td>
                                                    <?php if ($item['service_icon_url']): ?>
                                                        <img src="<?php echo ltrim($item['service_icon_url'], './'); ?>"
                                                            class="img-thumbnail" alt="Service Icon">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['office_name']); ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $item['service_status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($item['service_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?php echo $item['service_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($item['service_name']); ?>"
                                                        data-description="<?php echo htmlspecialchars($item['service_description']); ?>"
                                                        data-office="<?php echo $item['office_id']; ?>"
                                                        data-status="<?php echo $item['service_status']; ?>"
                                                        data-media_url="<?php echo htmlspecialchars($item['service_icon_url']); ?>"
                                                        data-media_type="image">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?section=services&action=delete&id=<?php echo $item['service_id']; ?>"
                                                        class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-info view-btn"
                                                        data-media_content="<?php echo htmlspecialchars($item['service_icon_url']); ?>"
                                                        data-media_type="image"
                                                        data-caption="<?php echo htmlspecialchars($item['service_name']); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            <?php elseif ($current_section === 'announcements'): ?>
                                                <td><?php echo $item['announcement_id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['announcement_title']); ?></td>
                                                <td>
                                                    <?php if ($item['announcement_image_url']): ?>
                                                        <img src="<?php echo ltrim($item['announcement_image_url'], './'); ?>"
                                                            class="img-thumbnail" alt="Announcement Image">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($item['announcement_date'])); ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $item['announcement_status'] === 'published' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($item['announcement_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?php echo $item['announcement_id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($item['announcement_title']); ?>"
                                                        data-content="<?php echo htmlspecialchars($item['announcement_content']); ?>"
                                                        data-date="<?php echo $item['announcement_date']; ?>"
                                                        data-status="<?php echo $item['announcement_status']; ?>"
                                                        data-media_url="<?php echo htmlspecialchars($item['announcement_image_url']); ?>"
                                                        data-media_type="image">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?section=announcements&action=delete&id=<?php echo $item['announcement_id']; ?>"
                                                        class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-info view-btn"
                                                        data-media_content="<?php echo htmlspecialchars($item['announcement_image_url']); ?>"
                                                        data-media_type="image"
                                                        data-caption="<?php echo htmlspecialchars($item['announcement_title']); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            <?php elseif ($current_section === 'news'): ?>
                                                <td><?php echo $item['news_id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['news_title']); ?></td>
                                                <td>
                                                    <?php if ($item['news_image_url']): ?>
                                                        <img src="<?php echo ltrim($item['news_image_url'], './'); ?>"
                                                            class="img-thumbnail" alt="News Image">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($item['news_date'])); ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $item['news_status'] === 'published' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($item['news_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?php echo $item['news_id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($item['news_title']); ?>"
                                                        data-content="<?php echo htmlspecialchars($item['news_content']); ?>"
                                                        data-date="<?php echo $item['news_date']; ?>"
                                                        data-status="<?php echo $item['news_status']; ?>"
                                                        data-media_url="<?php echo htmlspecialchars($item['news_image_url']); ?>"
                                                        data-media_type="image">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?section=news&action=delete&id=<?php echo $item['news_id']; ?>"
                                                        class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-info view-btn"
                                                        data-media_content="<?php echo htmlspecialchars($item['news_image_url']); ?>"
                                                        data-media_type="image"
                                                        data-caption="<?php echo htmlspecialchars($item['news_title']); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                                <?php elseif ($current_section === 'gallery'): ?>
    <td><?php echo $item['gallery_id']; ?></td>
    <td><?php echo htmlspecialchars($item['gallery_caption']); ?></td>
    <td>
        <?php 
        $url = ltrim($item['gallery_image_url'], './');
        $type = $item['gallery_type'];
        if ($type == 'image') {
            echo '<img src="' . $url . '" class="img-thumbnail" alt="Gallery Media">';
        } elseif ($type == 'video') {
            echo '<video controls class="img-thumbnail" style="max-width: 100px;"><source src="' . $url . '" type="video/mp4">Your browser does not support the video tag.</video>';
        } elseif ($type == 'video_link') {
            if (preg_match('/(youtu\.be\/|v=)([^&]+)/', $url, $matches)) {
                $video_id = $matches[2];
                $thumb = "https://img.youtube.com/vi/" . $video_id . "/hqdefault.jpg";
                echo '<img src="' . $thumb . '" class="img-thumbnail" alt="Video Thumbnail">';
            } else {
                echo '<i class="fas fa-video fa-3x"></i>';
            }
        } elseif ($type == 'link') {
            echo '<i class="fas fa-link fa-3x"></i>';
        }
        ?>
    </td>
    <td><?php echo htmlspecialchars($item['gallery_type']); ?></td>
    <td>
        <span class="badge bg-<?php echo $item['gallery_status'] === 'active' ? 'success' : 'secondary'; ?>">
            <?php echo ucfirst($item['gallery_status']); ?>
        </span>
    </td>
    <td>
        <button class="btn btn-sm btn-warning edit-btn"
                data-id="<?php echo $item['gallery_id']; ?>"
                data-caption="<?php echo htmlspecialchars($item['gallery_caption']); ?>"
                data-status="<?php echo $item['gallery_status']; ?>"
                data-media_url="<?php echo htmlspecialchars($item['gallery_image_url']); ?>"
                data-media_type="<?php echo $item['gallery_type']; ?>">
            <i class="fas fa-edit"></i>
        </button>
        <a href="?section=gallery&action=delete&id=<?php echo $item['gallery_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
            <i class="fas fa-trash"></i>
        </a>
        <?php if ($type !== 'link'): // Hide eye button for gallery_type "link" ?>
        <button class="btn btn-sm btn-info view-btn" 
                data-media_content="<?php echo htmlspecialchars($item['gallery_image_url']); ?>"
                data-media_type="<?php echo $item['gallery_type']; ?>"
                data-caption="<?php echo htmlspecialchars($item['gallery_caption']); ?>">
            <i class="fas fa-eye"></i>
        </button>
        <?php endif; ?>
        <?php if (in_array($item['gallery_type'], ['link', 'video_link'])): ?>
        <a href="<?php echo htmlspecialchars($item['gallery_image_url']); ?>" target="_blank" class="btn btn-sm btn-secondary">
            <i class="fas fa-external-link-alt"></i>
        </a>
        <?php endif; ?>
    </td>
                                            <?php elseif ($current_section === 'documents'): ?>
                                                <td><?php echo $item['document_id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['document_title']); ?></td>
                                                <td>
                                                    <?php if ($item['document_url']): ?>
                                                        <a href="<?php echo ltrim($item['document_url'], './'); ?>"
                                                            target="_blank">View Document</a>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo pathinfo($item['document_url'], PATHINFO_EXTENSION); ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $item['document_status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($item['document_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?php echo $item['document_id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($item['document_title']); ?>"
                                                        data-status="<?php echo $item['document_status']; ?>"
                                                        data-media_url="<?php echo htmlspecialchars($item['document_url']); ?>"
                                                        data-media_type="link">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?section=documents&action=delete&id=<?php echo $item['document_id']; ?>"
                                                        class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            <?php elseif ($current_section === 'emergency'): ?>
                                                <td><?php echo $item['emergency_id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['emergency_name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['emergency_phone']); ?></td>
                                                <td>
                                                    <?php if ($item['emergency_logo']): ?>
                                                        <img src="<?php echo ltrim($item['emergency_logo'], './'); ?>"
                                                            class="img-thumbnail" alt="Emergency Logo">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['emergency_category']); ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $item['emergency_status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($item['emergency_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?php echo $item['emergency_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($item['emergency_name']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($item['emergency_phone']); ?>"
                                                        data-alt-phone="<?php echo htmlspecialchars($item['emergency_alt_phone']); ?>"
                                                        data-address="<?php echo htmlspecialchars($item['emergency_address']); ?>"
                                                        data-category="<?php echo htmlspecialchars($item['emergency_category']); ?>"
                                                        data-notes="<?php echo htmlspecialchars($item['emergency_notes']); ?>"
                                                        data-status="<?php echo $item['emergency_status']; ?>"
                                                        data-media_url="<?php echo htmlspecialchars($item['emergency_logo']); ?>"
                                                        data-media_type="image">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?section=emergency&action=delete&id=<?php echo $item['emergency_id']; ?>"
                                                        class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-info view-btn"
                                                        data-media_content="<?php echo htmlspecialchars($item['emergency_logo']); ?>"
                                                        data-media_type="image"
                                                        data-caption="<?php echo htmlspecialchars($item['emergency_name']); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- -----------------------------------------------------------------------------
// MODAL FOR ADDING/EDITING RECORDS
// -----------------------------------------------------------------------------
-->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="crudForm" method="POST" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Add New <?php echo ucfirst($current_section); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Dynamic form fields based on $current_section -->
                        <?php if ($current_section === 'awards'): ?>
                            <input type="hidden" name="award_id" id="award_id">
                            <div class="mb-3">
                                <label for="award_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="award_title" name="award_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="award_image" class="form-label">Award Image</label>
                                <input type="file" class="form-control" id="award_image" name="award_image"
                                    accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="award_status" class="form-label">Status</label>
                                <select name="award_status" id="award_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        <?php elseif ($current_section === 'offices'): ?>
                            <input type="hidden" name="office_id" id="office_id">
                            <div class="mb-3">
                                <label for="office_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="office_name" name="office_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="office_description" class="form-label">Description</label>
                                <textarea class="form-control" id="office_description" name="office_description"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="office_link" class="form-label">Link</label>
                                <input type="url" class="form-control" id="office_link" name="office_link">
                            </div>
                            <div class="mb-3">
                                <label for="office_image" class="form-label">Office Image</label>
                                <input type="file" class="form-control" id="office_image" name="office_image"
                                    accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="office_status" class="form-label">Status</label>
                                <select name="office_status" id="office_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        <?php elseif ($current_section === 'services'): ?>
                            <input type="hidden" name="service_id" id="service_id">
                            <div class="mb-3">
                                <label for="service_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="service_name" name="service_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="service_description" class="form-label">Description</label>
                                <textarea class="form-control" id="service_description"
                                    name="service_description"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="office_id" class="form-label">Office</label>
                                <select class="form-select" name="office_id" id="office_id_select" required>
                                    <option value="">Select Office</option>
                                    <?php foreach (getOffices() as $office): ?>
                                        <option value="<?php echo $office['office_id']; ?>">
                                            <?php echo htmlspecialchars($office['office_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="service_icon" class="form-label">Service Icon</label>
                                <input type="file" class="form-control" id="service_icon" name="service_icon"
                                    accept="image/*,svg">
                            </div>
                            <div class="mb-3">
                                <label for="service_status" class="form-label">Status</label>
                                <select name="service_status" id="service_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        <?php elseif ($current_section === 'announcements'): ?>
                            <input type="hidden" name="announcement_id" id="announcement_id">
                            <div class="mb-3">
                                <label for="announcement_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="announcement_title" name="announcement_title"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="announcement_content" class="form-label">Content</label>
                                <textarea class="form-control" id="announcement_content" name="announcement_content"
                                    required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="announcement_image" class="form-label">Image</label>
                                <input type="file" class="form-control" id="announcement_image" name="announcement_image"
                                    accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="announcement_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="announcement_date" name="announcement_date"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="announcement_status" class="form-label">Status</label>
                                <select name="announcement_status" id="announcement_status" class="form-select">
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        <?php elseif ($current_section === 'news'): ?>
                            <input type="hidden" name="news_id" id="news_id">
                            <div class="mb-3">
                                <label for="news_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="news_title" name="news_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="news_content" class="form-label">Content</label>
                                <textarea class="form-control" id="news_content" name="news_content" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="news_image" class="form-label">Image</label>
                                <input type="file" class="form-control" id="news_image" name="news_image" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="news_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="news_date" name="news_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="news_status" class="form-label">Status</label>
                                <select name="news_status" id="news_status" class="form-select">
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        <?php elseif ($current_section === 'gallery'): ?>
                            <input type="hidden" name="gallery_id" id="gallery_id">
                            <div class="mb-3">
                                <label for="gallery_caption" class="form-label">Caption</label>
                                <input type="text" class="form-control" id="gallery_caption" name="gallery_caption"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="media_source" class="form-label">Media Source</label>
                                <select class="form-select" name="media_source" id="media_source">
                                    <option value="local">Local Media</option>
                                    <option value="link">Media Link</option>
                                </select>
                            </div>
                            <div class="mb-3" id="local_media_field">
                                <label for="gallery_media" class="form-label">Media File</label>
                                <input type="file" class="form-control" id="gallery_media" name="gallery_media"
                                    accept="image/*,video/*">
                            </div>
                            <div class="mb-3" id="media_link_field" style="display:none;">
                                <label for="media_link" class="form-label">Media Link</label>
                                <input type="url" class="form-control" id="media_link" name="media_link"
                                    placeholder="https://">
                            </div>
                            <div class="mb-3">
                                <label for="gallery_status" class="form-label">Status</label>
                                <select name="gallery_status" id="gallery_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        <?php elseif ($current_section === 'documents'): ?>
                            <input type="hidden" name="document_id" id="document_id">
                            <div class="mb-3">
                                <label for="document_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="document_title" name="document_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="document_file" class="form-label">File</label>
                                <input type="file" class="form-control" id="document_file" name="document_file"
                                    accept=".pdf,.doc,.docx" required>
                            </div>
                            <div class="mb-3">
                                <label for="document_status" class="form-label">Status</label>
                                <select name="document_status" id="document_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        <?php elseif ($current_section === 'emergency'): ?>
                            <input type="hidden" name="emergency_id" id="emergency_id">
                            <div class="mb-3">
                                <label for="emergency_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="emergency_name" name="emergency_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="emergency_phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="emergency_phone" name="emergency_phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="emergency_alt_phone" class="form-label">Alternate Phone</label>
                                <input type="tel" class="form-control" id="emergency_alt_phone" name="emergency_alt_phone">
                            </div>
                            <div class="mb-3">
                                <label for="emergency_address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="emergency_address" name="emergency_address"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="emergency_category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="emergency_category" name="emergency_category"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="emergency_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="emergency_notes" name="emergency_notes"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="emergency_logo" class="form-label">Logo</label>
                                <input type="file" class="form-control" id="emergency_logo" name="emergency_logo"
                                    accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="emergency_status" class="form-label">Status</label>
                                <select name="emergency_status" id="emergency_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        <!-- Preview container for current media (if editing) -->
                        <div id="currentMediaPreview" class="mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- -----------------------------------------------------------------------------
// MODAL FOR VIEWING MEDIA
// -----------------------------------------------------------------------------
-->
    <div class="modal fade" id="viewMediaModal" tabindex="-1" aria-labelledby="viewMediaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewMediaModalLabel">Media Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="mediaPreviewContent">
                    <!-- Content populated by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- -----------------------------------------------------------------------------
// JAVASCRIPT: Dependencies and Custom Scripts
// -----------------------------------------------------------------------------
-->
    <script src="https://kit.fontawesome.com/a2d9d6efc1.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle media input for gallery based on source selection
        document.getElementById('media_source')?.addEventListener('change', function () {
            const localField = document.getElementById('local_media_field');
            const linkField = document.getElementById('media_link_field');
            if (this.value === 'local') {
                localField.style.display = 'block';
                linkField.style.display = 'none';
            } else {
                localField.style.display = 'none';
                linkField.style.display = 'block';
            }
        });

        // When "Add New" is clicked, reset the modal form and clear current media preview.
        document.getElementById('addNewBtn').addEventListener('click', function () {
            document.getElementById("crudForm").reset();
            document.getElementById('currentMediaPreview').innerHTML = '';
            <?php if ($current_section === 'awards'): ?>
                document.getElementById('award_id').value = "";
                document.getElementById('addModalLabel').textContent = "Add New Award";
            <?php elseif ($current_section === 'offices'): ?>
                document.getElementById('office_id').value = "";
                document.getElementById('addModalLabel').textContent = "Add New Office";
            <?php elseif ($current_section === 'services'): ?>
                document.getElementById('service_id').value = "";
                document.getElementById('addModalLabel').textContent = "Add New Service";
            <?php elseif ($current_section === 'announcements'): ?>
                document.getElementById('announcement_id').value = "";
                document.getElementById('addModalLabel').textContent = "Add New Announcement";
            <?php elseif ($current_section === 'news'): ?>
                document.getElementById('news_id').value = "";
                document.getElementById('addModalLabel').textContent = "Add New News";
            <?php elseif ($current_section === 'gallery'): ?>
                document.getElementById('gallery_id').value = "";
                document.getElementById('addModalLabel').textContent = "Add New Gallery Item";
            <?php elseif ($current_section === 'documents'): ?>
                document.getElementById('document_id').value = "";
                document.getElementById('addModalLabel').textContent = "Add New Document";
            <?php elseif ($current_section === 'emergency'): ?>
                document.getElementById('emergency_id').value = "";
                document.getElementById('addModalLabel').textContent = "Add New Emergency Contact";
            <?php endif; ?>
        });

        // Enhanced "Edit" button handler: populate modal fields and show current media preview.
        document.querySelectorAll('.edit-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                var section = "<?php echo $current_section; ?>";
                var modalLabel = document.getElementById('addModalLabel');
                var currentMediaPreview = document.getElementById('currentMediaPreview');
                currentMediaPreview.innerHTML = "";
                // Get the media URL and type from data attributes.
                var mediaUrl = this.getAttribute('data-media_url') || "";
                var mediaType = this.getAttribute('data-media_type') || "";

                if (section === 'awards') {
                    document.getElementById('award_id').value = this.getAttribute('data-id');
                    document.getElementById('award_title').value = this.getAttribute('data-title');
                    document.getElementById('award_status').value = this.getAttribute('data-status');
                    modalLabel.textContent = "Edit Award";
                } else if (section === 'offices') {
                    document.getElementById('office_id').value = this.getAttribute('data-id');
                    document.getElementById('office_name').value = this.getAttribute('data-name');
                    document.getElementById('office_description').value = this.getAttribute('data-description');
                    document.getElementById('office_link').value = this.getAttribute('data-link');
                    document.getElementById('office_status').value = this.getAttribute('data-status');
                    modalLabel.textContent = "Edit Office";
                } else if (section === 'services') {
                    document.getElementById('service_id').value = this.getAttribute('data-id');
                    document.getElementById('service_name').value = this.getAttribute('data-name');
                    document.getElementById('service_description').value = this.getAttribute('data-description');
                    document.getElementById('office_id_select').value = this.getAttribute('data-office');
                    document.getElementById('service_status').value = this.getAttribute('data-status');
                    modalLabel.textContent = "Edit Service";
                } else if (section === 'announcements') {
                    document.getElementById('announcement_id').value = this.getAttribute('data-id');
                    document.getElementById('announcement_title').value = this.getAttribute('data-title');
                    document.getElementById('announcement_content').value = this.getAttribute('data-content');
                    document.getElementById('announcement_date').value = this.getAttribute('data-date');
                    document.getElementById('announcement_status').value = this.getAttribute('data-status');
                    modalLabel.textContent = "Edit Announcement";
                } else if (section === 'news') {
                    document.getElementById('news_id').value = this.getAttribute('data-id');
                    document.getElementById('news_title').value = this.getAttribute('data-title');
                    document.getElementById('news_content').value = this.getAttribute('data-content');
                    document.getElementById('news_date').value = this.getAttribute('data-date');
                    document.getElementById('news_status').value = this.getAttribute('data-status');
                    modalLabel.textContent = "Edit News";
                } else if (section === 'gallery') {
                    document.getElementById('gallery_id').value = this.getAttribute('data-id');
                    document.getElementById('gallery_caption').value = this.getAttribute('data-caption');
                    document.getElementById('gallery_status').value = this.getAttribute('data-status');
                    modalLabel.textContent = "Edit Gallery Item";
                    // Clear file inputs to avoid accidental override.
                    document.getElementById('gallery_media').value = "";
                    document.getElementById('media_link').value = "";
                    // If the media was from a link, set the source selection accordingly.
                    if (mediaType === "video_link" || mediaType === "link") {
                        document.getElementById('media_source').value = "link";
                        document.getElementById('local_media_field').style.display = 'none';
                        document.getElementById('media_link_field').style.display = 'block';
                        document.getElementById('media_link').value = mediaUrl;
                    } else {
                        document.getElementById('media_source').value = "local";
                        document.getElementById('local_media_field').style.display = 'block';
                        document.getElementById('media_link_field').style.display = 'none';
                    }
                } else if (section === 'documents') {
                    document.getElementById('document_id').value = this.getAttribute('data-id');
                    document.getElementById('document_title').value = this.getAttribute('data-title');
                    document.getElementById('document_status').value = this.getAttribute('data-status');
                    modalLabel.textContent = "Edit Document";
                } else if (section === 'emergency') {
                    document.getElementById('emergency_id').value = this.getAttribute('data-id');
                    document.getElementById('emergency_name').value = this.getAttribute('data-name');
                    document.getElementById('emergency_phone').value = this.getAttribute('data-phone');
                    document.getElementById('emergency_alt_phone').value = this.getAttribute('data-alt-phone');
                    document.getElementById('emergency_address').value = this.getAttribute('data-address');
                    document.getElementById('emergency_category').value = this.getAttribute('data-category');
                    document.getElementById('emergency_notes').value = this.getAttribute('data-notes');
                    document.getElementById('emergency_status').value = this.getAttribute('data-status');
                    modalLabel.textContent = "Edit Emergency Contact";
                }

                // Show current media preview if available.
                if (mediaUrl && mediaUrl.trim() !== "") {
                    var previewHtml = "";
                    if (mediaType === "video_link") {
                        previewHtml = '<p>Current Video: <a href="#" class="btn btn-secondary btn-sm" onclick="openEmbeddedVideo(\'' + mediaUrl + '\'); return false;">Open Video Link</a></p>';
                    } else if (mediaType === "video") {
                        previewHtml = '<video controls class="w-100"><source src="' + mediaUrl + '" type="video/mp4">Your browser does not support the video tag.</video>';
                    } else {
                        previewHtml = '<img src="' + mediaUrl + '" alt="Current Media" class="img-fluid rounded">';
                    }
                    currentMediaPreview.innerHTML = previewHtml;
                }

                var modal = new bootstrap.Modal(document.getElementById('addModal'));
                modal.show();
            });
        });

        // "View" button: Open media in the view modal.
        document.querySelectorAll('.view-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                var mediaContent = this.getAttribute('data-media_content');
                var mediaType = this.getAttribute('data-media_type');
                var caption = this.getAttribute('data-caption');
                var content = '<h5>' + caption + '</h5>';
                if (mediaType === 'image') {
                    content += '<img src="' + mediaContent + '" class="img-fluid" alt="Media">';
                } else if (mediaType === 'video') {
                    content += '<video controls class="w-100"><source src="' + mediaContent + '" type="video/mp4">Your browser does not support the video tag.</video>';
                } else if (mediaType === 'video_link') {
                    content += '<p><a href="#" class="btn btn-secondary" onclick="openEmbeddedVideo(\'' + mediaContent + '\'); return false;">Open Video Link</a></p>';
                } else if (mediaType === 'link') {
                    content += '<p><a href="' + mediaContent + '" target="_blank" class="btn btn-primary">Visit Site</a></p>';
                }
                document.getElementById('mediaPreviewContent').innerHTML = content;
                var viewModal = new bootstrap.Modal(document.getElementById('viewMediaModal'));
                viewModal.show();
            });
        });

        // Global function to open an embedded video for various providers.
        function openEmbeddedVideo(url) {
            var embedUrl = '';
            if (url.indexOf('youtube') !== -1 || url.indexOf('youtu.be') !== -1) {
                var videoId = '';
                var regex = /(youtu\.be\/|v=)([^&]+)/;
                var match = url.match(regex);
                if (match) {
                    videoId = match[2];
                    embedUrl = 'https://www.youtube.com/embed/' + videoId;
                }
            } else if (url.indexOf('vimeo') !== -1) {
                var regex = /vimeo\.com\/(\d+)/;
                var match = url.match(regex);
                if (match) {
                    embedUrl = 'https://player.vimeo.com/video/' + match[1];
                }
            } else if (url.indexOf('facebook') !== -1) {
                embedUrl = 'https://www.facebook.com/plugins/video.php?href=' + encodeURIComponent(url);
            } else if (url.indexOf('tiktok') !== -1) {
                // TikTok embed may need alternatives; direct link is provided in a new window.
                window.open(url, '_blank');
                return;
            } else {
                // Fallback: open in new window.
                window.open(url, '_blank');
                return;
            }
            var content = '<div class="ratio ratio-16x9"><iframe src="' + embedUrl + '" frameborder="0" allowfullscreen></iframe></div>';
            document.getElementById('mediaPreviewContent').innerHTML = content;
            var viewModal = new bootstrap.Modal(document.getElementById('viewMediaModal'));
            viewModal.show();
        }
    </script>
</body>

</html>