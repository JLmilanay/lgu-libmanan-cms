<?php
// main-manage.php

session_start();
include 'config.php';

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

// Handle JSON POST requests.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $_POST = json_decode(file_get_contents('php://input'), true);
}

// Process AJAX requests.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {

            case 'reset_password':
                $adminId   = intval($_POST['adminId']);
                $adminType = $_POST['adminType']; // 'main' or 'office'
                $defaultPwdPlain = "LGU-ADMIN_2025";
                $defaultHashedPwd = password_hash($defaultPwdPlain, PASSWORD_DEFAULT);
                if ($adminType === 'main') {
                    $stmt = $conn->prepare("UPDATE main_admin_accounts SET password = ? WHERE id = ?");
                } elseif ($adminType === 'office') {
                    $stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE id = ?");
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid admin type.']);
                    exit();
                }
                $stmt->bind_param("si", $defaultHashedPwd, $adminId);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Password reset to default LGU-ADMIN_2025']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error resetting password.']);
                }
                $stmt->close();
                exit();

            case 'add_office':
                $officeName = trim($_POST['officeName']);
                $officeAddress = trim($_POST['officeAddress']);
                if (!isset($_FILES['officeLogo']) || $_FILES['officeLogo']['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => 'Error uploading logo.']);
                    exit();
                }
                $target_dir = 'uploads/';
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                $fileName = basename($_FILES['officeLogo']['name']);
                $target_file = $target_dir . $fileName;
                $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($fileType, $allowed_types)) {
                    echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.']);
                    exit();
                }
                if (move_uploaded_file($_FILES['officeLogo']['tmp_name'], $target_file)) {
                    $stmt = $conn->prepare("INSERT INTO offices (office_name, office_address, logo_path) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $officeName, $officeAddress, $target_file);
                    if ($stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Office added successfully.']);
                    } else {
                        error_log("Insert error: " . $stmt->error);
                        echo json_encode(['success' => false, 'message' => 'Error inserting office into database.']);
                    }
                    $stmt->close();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error moving uploaded file.']);
                }
                exit();

            case 'edit_office':
                $officeId = intval($_POST['editOfficeId']);
                $officeName = trim($_POST['editOfficeName']);
                $officeAddress = trim($_POST['editOfficeAddress']);
                $stmt = $conn->prepare("SELECT logo_path FROM offices WHERE id = ?");
                $stmt->bind_param("i", $officeId);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows == 0) {
                    echo json_encode(['success' => false, 'message' => 'Office not found.']);
                    $stmt->close();
                    exit();
                }
                $stmt->bind_result($existingLogo);
                $stmt->fetch();
                $stmt->close();
                $logoPath = $existingLogo;
                if (isset($_FILES['newOfficeLogo']) && $_FILES['newOfficeLogo']['error'] === UPLOAD_ERR_OK && !empty($_FILES['newOfficeLogo']['name'])) {
                    $target_dir = 'uploads/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0755, true);
                    }
                    $newLogoFile = basename($_FILES['newOfficeLogo']['name']);
                    $newLogoPath = $target_dir . $newLogoFile;
                    $fileType = strtolower(pathinfo($newLogoPath, PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($fileType, $allowed_types)) {
                        echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed for the new logo.']);
                        exit();
                    }
                    if (move_uploaded_file($_FILES['newOfficeLogo']['tmp_name'], $newLogoPath)) {
                        if ($existingLogo && file_exists($existingLogo)) {
                            unlink($existingLogo);
                        }
                        $logoPath = $newLogoPath;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to upload new logo.']);
                        exit();
                    }
                }
                $stmt = $conn->prepare("UPDATE offices SET office_name = ?, office_address = ?, logo_path = ? WHERE id = ?");
                $stmt->bind_param("sssi", $officeName, $officeAddress, $logoPath, $officeId);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Office updated successfully.']);
                } else {
                    error_log("Update error: " . $stmt->error);
                    echo json_encode(['success' => false, 'message' => 'Error updating office.']);
                }
                $stmt->close();
                exit();

            case 'delete_office':
                $officeId = intval($_POST['officeId']);
                if ($officeId <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid office ID.']);
                    exit();
                }
                if (!isset($_POST['password']) || empty($_POST['password'])) {
                    echo json_encode(['success' => false, 'message' => 'Password is required.']);
                    exit();
                }
                $providedPassword = $_POST['password'];
                $stmtUser = $conn->prepare("SELECT password FROM main_admin_accounts WHERE username = ? LIMIT 1");
                $username = $_SESSION['username'];
                $stmtUser->bind_param("s", $username);
                $stmtUser->execute();
                $stmtUser->bind_result($stored_hash);
                if ($stmtUser->fetch()) {
                    if (!password_verify($providedPassword, $stored_hash)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid password for deletion.']);
                        exit();
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found.']);
                    exit();
                }
                $stmtUser->close();

                $conn->begin_transaction();
                try {
                    $conn->query("DELETE FROM services_content WHERE section_id IN (SELECT id FROM sections WHERE office_id = $officeId)");
                    $conn->query("DELETE FROM news_content WHERE office_id = $officeId");
                    $conn->query("DELETE FROM announcements_content WHERE office_id = $officeId");
                    $conn->query("DELETE FROM documents WHERE office_id = $officeId");
                    $conn->query("DELETE FROM office_visitors WHERE office_id = $officeId");
                    $conn->query("DELETE FROM gallery_content WHERE section_id IN (SELECT id FROM sections WHERE office_id = $officeId)");
                    $conn->query("DELETE FROM sections WHERE office_id = $officeId");
                    $stmt = $conn->prepare("SELECT logo_path FROM offices WHERE id = ?");
                    $stmt->bind_param("i", $officeId);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows == 0) {
                        echo json_encode(['success' => false, 'message' => 'Office not found.']);
                        $stmt->close();
                        exit();
                    }
                    $stmt->bind_result($logoPath);
                    $stmt->fetch();
                    $stmt->close();
                    $conn->query("DELETE FROM accounts WHERE office_id = $officeId");
                    $stmt = $conn->prepare("DELETE FROM offices WHERE id = ?");
                    $stmt->bind_param("i", $officeId);
                    if ($stmt->execute()) {
                        if ($logoPath && file_exists($logoPath)) {
                            unlink($logoPath);
                        }
                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Office and related contents deleted successfully.']);
                    } else {
                        throw new Exception("Error deleting office.");
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Error deleting office: ' . $e->getMessage()]);
                }
                exit();

            case 'update_profile':
                // PROFILE UPDATE HANDLER with optional password change.
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $contact_number = trim($_POST['contact_number']);
                // Process profile photo cropping if provided.
                if (isset($_POST['croppedImage']) && !empty($_POST['croppedImage'])) {
                    // The image is a base64-encoded PNG.
                    $data = $_POST['croppedImage'];
                    list($type, $data) = explode(';', $data);
                    list(, $data) = explode(',', $data);
                    $data = base64_decode($data);
                    $target_dir = 'uploads/profile_photos/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0755, true);
                    }
                    $filename = $target_dir . 'profile_' . $_SESSION['username'] . '_' . time() . '.png';
                    if (file_put_contents($filename, $data) === false) {
                        echo json_encode(['success' => false, 'message' => 'Failed to save profile photo.']);
                        exit();
                    }
                    $profile_photo = $filename;
                } else {
                    $profile_photo = null;
                }
                // Process optional new password field.
                $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
                if (!empty($new_password)) {
                    $hashedNewPassword = password_hash($new_password, PASSWORD_DEFAULT);
                }
                $username = $_SESSION['username'];
                // Build update query based on provided values.
                if ($profile_photo !== null && !empty($new_password)) {
                    $stmt = $conn->prepare("UPDATE main_admin_accounts SET full_name = ?, email = ?, contact_number = ?, profile_photo = ?, password = ? WHERE username = ?");
                    $stmt->bind_param("ssssss", $full_name, $email, $contact_number, $profile_photo, $hashedNewPassword, $username);
                } elseif ($profile_photo !== null) {
                    $stmt = $conn->prepare("UPDATE main_admin_accounts SET full_name = ?, email = ?, contact_number = ?, profile_photo = ? WHERE username = ?");
                    $stmt->bind_param("sssss", $full_name, $email, $contact_number, $profile_photo, $username);
                } elseif (!empty($new_password)) {
                    $stmt = $conn->prepare("UPDATE main_admin_accounts SET full_name = ?, email = ?, contact_number = ?, password = ? WHERE username = ?");
                    $stmt->bind_param("sssss", $full_name, $email, $contact_number, $hashedNewPassword, $username);
                } else {
                    $stmt = $conn->prepare("UPDATE main_admin_accounts SET full_name = ?, email = ?, contact_number = ? WHERE username = ?");
                    $stmt->bind_param("ssss", $full_name, $email, $contact_number, $username);
                }
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating profile.']);
                }
                $stmt->close();
                exit();

            case 'toggle_account':
                // New case to update account status for office admins.
                $adminId = intval($_POST['adminId']);
                $toggleType = $_POST['toggleType']; // Expected to be 'activate' or 'disable'
                if ($toggleType === 'activate') {
                    $newStatus = 'active';
                } elseif ($toggleType === 'disable') {
                    $newStatus = 'inactive';
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid account toggle type.']);
                    exit();
                }
                $stmt = $conn->prepare("UPDATE accounts SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $newStatus, $adminId);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Account status updated to ' . ucwords($newStatus) . '.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating account status.']);
                }
                $stmt->close();
                exit();

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
                exit();
        }
    }
}

// Fetch data for modals, charts, and cards.
$main_admins = $conn->query("SELECT * FROM main_admin_accounts");
$office_admins = $conn->query("SELECT accounts.*, offices.office_name 
                               FROM accounts 
                               LEFT JOIN offices ON accounts.office_id = offices.id");

$services = $conn->query("SELECT COUNT(*) AS cnt FROM services_content")->fetch_assoc()['cnt'];
$news = $conn->query("SELECT COUNT(*) AS cnt FROM news_content")->fetch_assoc()['cnt'];
$announcements = $conn->query("SELECT COUNT(*) AS cnt FROM announcements_content")->fetch_assoc()['cnt'];
$documents = $conn->query("SELECT COUNT(*) AS cnt FROM documents")->fetch_assoc()['cnt'];
$gallery = $conn->query("SELECT COUNT(*) AS cnt FROM gallery_content")->fetch_assoc()['cnt'];

$totals = [
    'offices'       => 'SELECT COUNT(*) AS total FROM offices',
    'main_admins'   => 'SELECT COUNT(*) AS total FROM main_admin_accounts',
    'office_admins' => 'SELECT COUNT(*) AS total FROM accounts',
    'services'      => 'SELECT COUNT(*) AS total FROM services_content',
    'news'          => 'SELECT COUNT(*) AS total FROM news_content',
    'announcements' => 'SELECT COUNT(*) AS total FROM announcements_content',
    'documents'     => 'SELECT COUNT(*) AS total FROM documents',
    'gallery'       => 'SELECT COUNT(*) AS total FROM gallery_content',
    'sections'      => 'SELECT COUNT(*) AS total FROM sections'
];
$results = [];
foreach ($totals as $key => $query) {
    $res = $conn->query($query);
    $data = $res->fetch_assoc();
    $results[$key] = $data['total'];
}

$cards = [
    ['title' => 'Total Offices', 'value' => $results['offices'], 'icon' => 'bi-building', 'link' => '#office-summary', 'gradient' => 'linear-gradient(135deg, #0d6efd, #0b5ed7)'],
    ['title' => 'Main Admins', 'value' => $results['main_admins'], 'icon' => 'bi-person-badge', 'modal' => 'mainAdminsModal', 'gradient' => 'linear-gradient(135deg, #6610f2, #520dc2)'],
    ['title' => 'Office Admins', 'value' => $results['office_admins'], 'icon' => 'bi-person-workspace', 'modal' => 'officeAdminsModal', 'gradient' => 'linear-gradient(135deg, #198754, #146c43)'],
    ['title' => 'Total Services', 'value' => $results['services'], 'icon' => 'bi-gear', 'link' => '#office-summary', 'gradient' => 'linear-gradient(135deg, #dc3545, #b02a37)'],
    ['title' => 'News Articles', 'value' => $results['news'], 'icon' => 'bi-newspaper', 'link' => '#office-summary', 'gradient' => 'linear-gradient(135deg, #fd7e14, #dc6509)'],
    ['title' => 'Announcements', 'value' => $results['announcements'], 'icon' => 'bi-megaphone', 'link' => '#office-summary', 'gradient' => 'linear-gradient(135deg, #ffc107, #d39e00)'],
    ['title' => 'Documents', 'value' => $results['documents'], 'icon' => 'bi-file-earmark-text', 'link' => '#office-summary', 'gradient' => 'linear-gradient(135deg, #0dcaf0, #0aa2c0)'],
    ['title' => 'Gallery Images', 'value' => $results['gallery'], 'icon' => 'bi-images', 'link' => '#office-summary', 'gradient' => 'linear-gradient(135deg, #6c757d, #565e64)'],
    ['title' => 'Sections', 'value' => $results['sections'], 'icon' => 'bi-diagram-3', 'link' => '#office-summary', 'gradient' => 'linear-gradient(135deg, #212529, #16191c)']
];

/*
   Build data for the "Visitor Movement" line chart.
   Get the last 7 days as the date range.
*/
$startDate = date("Y-m-d", strtotime("-6 days"));
$endDate = date("Y-m-d");
$labels = [];
$currentDate = strtotime($startDate);
while ($currentDate <= strtotime($endDate)) {
    $labels[] = date("Y-m-d", $currentDate);
    $currentDate = strtotime("+1 day", $currentDate);
}
// Get all offices.
$officeRes = $conn->query("SELECT id, office_name FROM offices");
$officeData = [];
while ($row = $officeRes->fetch_assoc()) {
    $officeData[$row['id']] = [
        'name' => $row['office_name'],
        'data' => array_fill(0, count($labels), 0)
    ];
}
// Query visitor counts per office per day.
$vQuery = "SELECT office_id, DATE(visited_at) as visit_date, COUNT(*) as count FROM office_visitors 
           WHERE DATE(visited_at) BETWEEN '$startDate' AND '$endDate'
           GROUP BY office_id, DATE(visited_at)";
$vResult = $conn->query($vQuery);
if ($vResult) {
    while ($vRow = $vResult->fetch_assoc()) {
        $officeId = $vRow['office_id'];
        $visitDate = $vRow['visit_date'];
        $count = $vRow['count'];
        $index = array_search($visitDate, $labels);
        if ($index !== false && isset($officeData[$officeId])) {
            $officeData[$officeId]['data'][$index] = $count;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Office Management System</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" crossorigin="anonymous" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }
        /* Sidebar styling */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2c3034;
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 10px 20px;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #1a1d20;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        /* Count Cards styling */
        .summary-card.horizontal {
            border-radius: 8px;
            color: #fff;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .summary-card.horizontal:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
        }
        .icon-container {
            font-size: 3rem;
        }
        .content-container h5 {
            margin: 0;
            font-size: 1.2rem;
        }
        .content-container h2 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 40px;
            color: #343a40;
        }
        #recent-activity, #data-visualization {
            margin-top: 20px;
        }
        footer {
            background-color: #343a40;
            color: #fff;
            padding: 10px 0;
            text-align: center;
        }
        .logo-container {
            display: flex;
            justify-content: space-around;
            padding: 10px;
        }
        .logo-container img {
            width: 50px;
            height: 50px;
        }
        .scrollable-table {
            max-height: 300px;
            overflow-y: auto;
        }
        /* Profile Settings preview image */
        .profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }
    </style>
</head>
<body>
<?php
// Retrieve the profile information from the database.
$stmt = $conn->prepare("SELECT full_name, profile_photo FROM main_admin_accounts WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$stmt->bind_result($fullName, $profilePhoto);
$stmt->fetch();
$stmt->close();

$currentFullName = $fullName ? $fullName : $_SESSION['username'];
$currentProfilePhoto = $profilePhoto ? $profilePhoto : 'ASSETS/default-profile.png';
?>
<style>
  /* Sidebar container */
  .sidebar {
    width: 250px;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background-color: #007bff; /* Blue background */
    color: #fff;
    box-shadow: 2px 0px 5px rgba(0, 0, 0, 0.2);
    overflow-y: auto;
    transition: all 0.3s ease;
  }
  /* Header logo and title */
  .sidebar .sidebar-header {
    text-align: center;
    padding: 20px 10px;
  }
  .sidebar .logo-container img {
    max-width: 80px;
    margin-bottom: 5px;
  }
  .sidebar .sidebar-header h4 {
    font-weight: bold;
    margin-bottom: 0;
  }
  .sidebar .sidebar-header small {
    display: block;
    font-size: 0.85rem;
    margin-top: 5px;
  }
  /* Profile info styling */
  .sidebar .profile-info {
    text-align: center;
    margin: 20px 0;
  }
  .sidebar .profile-info img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border: 2px solid #fff;
  }
  .sidebar .profile-info h5 {
    margin: 10px 0 0;
    font-size: 1.1rem;
  }
  .sidebar .profile-info small {
    font-size: 0.8rem;
  }
  /* Navigation styling */
  .sidebar nav a.nav-link {
    color: #fff;
    padding: 10px 20px;
    font-size: 16px;
    display: block;
    transition: background-color 0.2s ease-in-out;
  }
  .sidebar nav a.nav-link:hover,
  .sidebar nav a.nav-link:focus {
    background-color: #0056b3;
    text-decoration: none;
  }
  .sidebar nav a.nav-link.active,
  .sidebar nav a.nav-link.active:hover {
    background-color: #00408a;
    font-weight: bold;
  }
  /* Divider */
  .sidebar hr {
    border-color: rgba(255,255,255,0.3);
    margin: 10px 0;
  }
  /* Footer links */
  .sidebar .sidebar-footer a.nav-link {
    padding: 10px 20px;
    display: block;
  }
</style>

<!-- Enhanced Sidebar -->
<aside class="sidebar" role="navigation" aria-label="Sidebar Navigation">
<div class="sidebar-header" style="padding: 20px; text-align: center; border-bottom: 2px solid rgba(255,255,255,0.3);">
  <div class="logo-container" style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 15px;">
      <img src="ASSETS/Hi-Res-BAGONG-PILIPINAS-LOGO.png" alt="LGU Logo 1" style="max-height: 60px; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
      <img src="ASSETS/LIBMANAN LOGO.png" alt="LGU Logo 2" style="max-height: 60px; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
      <img src="ASSETS/big jNEW.png" alt="LGU Logo 3" style="max-height: 60px; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
  </div>
  <h4 style="font-size: 1.5rem; margin: 0; font-weight: bold; color: #fff;">
    LGU-Libmanan Public Information Management System - Main Admin Site
  </h4>
  <p style="font-size: 0.9rem; margin: 8px 0 0; color: #e0e0e0;">Admin Management &amp; Control Panel</p>
</div>

  <!-- Enhanced Profile Info -->
  <div class="profile-info">
    <img src="<?php echo htmlspecialchars($currentProfilePhoto); ?>" alt="Profile Photo" class="rounded-circle">
    <h5><?php echo htmlspecialchars($currentFullName); ?></h5>
    <h5>@<?php echo htmlspecialchars($_SESSION['username']); ?></h5>
  </div>

  <hr>

  <!-- Navigation Menu -->
  <nav class="nav flex-column">
    <a class="nav-link active" href="#" aria-label="Dashboard">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a class="nav-link" href="main-posting.php" aria-label="Manage LGU Landing Page">
      <i class="bi bi-house-door-fill"></i> Manage LGU Landing Page
    </a>
    <a class="nav-link" href="#" data-toggle="modal" data-target="#addOfficeModal" aria-label="Add Office">
      <i class="bi bi-plus-circle"></i> Add Office
    </a>
    <a class="nav-link" href="#office-summary" aria-label="Office Summary">
      <i class="bi bi-card-list"></i> Office Summary
    </a>
    <a class="nav-link" href="#recent-activity" aria-label="Recent Activity">
      <i class="bi bi-clock-history"></i> Recent Activity
    </a>
    <a class="nav-link" href="conversation.php" aria-label="Data Visualization">
      <i class="bi bi-bar-chart"></i> Data Visualization
    </a>
    <a class="nav-link" href="message.php" aria-label="Chat">
    <i class="bi bi-chat-dots"></i> Chat
</a>

  </nav>

  <hr>

  <!-- Footer Navigation -->
  <div class="sidebar-footer">
    <a class="nav-link" href="#" data-toggle="modal" data-target="#profileSettingsModal" aria-label="Profile Settings">
      <i class="bi bi-person-lines-fill"></i> Profile Settings
    </a>
    <a class="nav-link" href="logout.php" aria-label="Logout">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</aside>

<!-- Main Content -->
<div class="main-content">
    <h1 class="mb-4 text-center">Admin Dashboard</h1>
    <!-- Count Cards Section -->
    <div class="container mt-4">
        <div class="row">
            <?php
            foreach ($cards as $card) {
                $link = isset($card['link']) ? $card['link'] : '#';
                $modal = isset($card['modal']) ? 'data-toggle="modal" data-target="#' . $card['modal'] . '"' : '';
                echo '
                <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                    <a href="' . $link . '" ' . $modal . ' class="text-decoration-none">
                        <div class="summary-card horizontal" style="background: ' . $card['gradient'] . '">
                            <div class="icon-container"><i class="bi ' . $card['icon'] . '"></i></div>
                            <div class="content-container">
                                <h5>' . $card['title'] . '</h5>
                                <h2>' . $card['value'] . '</h2>
                            </div>
                        </div>
                    </a>
                </div>';
            }
            ?>
        </div>
    </div>

    <!-- Office Summary Table -->
    <div class="section-title" id="office-summary">Office-wise Content Summary</div>
    <div class="table-responsive mt-3">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Logo</th>
                    <th>Office Name</th>
                    <th>Address</th>
                    <th>Services</th>
                    <th>News</th>
                    <th>Announcements</th>
                    <th>Documents</th>
                    <th>Gallery</th>
                    <th>Visitors</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $offices = $conn->query("SELECT * FROM offices");
                while ($office = $offices->fetch_assoc()) {
                    $oid = $office['id'];
                    $servicesCount = $conn->query("SELECT COUNT(*) AS cnt FROM services_content sc JOIN sections s ON sc.section_id = s.id WHERE s.office_id = $oid")->fetch_assoc()['cnt'];
                    $newsCount = $conn->query("SELECT COUNT(*) AS cnt FROM news_content WHERE office_id = $oid")->fetch_assoc()['cnt'];
                    $announcementsCount = $conn->query("SELECT COUNT(*) AS cnt FROM announcements_content WHERE office_id = $oid")->fetch_assoc()['cnt'];
                    $documentsCount = $conn->query("SELECT COUNT(*) AS cnt FROM documents WHERE office_id = $oid")->fetch_assoc()['cnt'];
                    $galleryCount = $conn->query("SELECT COUNT(*) AS cnt FROM gallery_content gc JOIN sections s ON gc.section_id = s.id WHERE s.office_id = $oid")->fetch_assoc()['cnt'];
                    $visitorCount = $conn->query("SELECT COUNT(*) AS total FROM office_visitors WHERE office_id = $oid")->fetch_assoc()['total'];
                    
                    echo "
                    <tr>
                        <td><img src='" . htmlspecialchars($office['logo_path']) . "' alt='Logo' style='width: 50px; height: 50px;'></td>
                        <td>" . htmlspecialchars($office['office_name']) . "</td>
                        <td>" . htmlspecialchars($office['office_address']) . "</td>
                        <td>$servicesCount</td>
                        <td>$newsCount</td>
                        <td>$announcementsCount</td>
                        <td>$documentsCount</td>
                        <td>$galleryCount</td>
                        <td>$visitorCount</td>
                        <td>
                            <button class='btn btn-sm btn-info' data-toggle='modal' data-target='#editOfficeModal' 
                                    data-id='$oid' data-name='" . htmlspecialchars($office['office_name']) . "' 
                                    data-address='" . htmlspecialchars($office['office_address']) . "' 
                                    data-logo='" . htmlspecialchars($office['logo_path']) . "'>Edit</button>
                            <button class='btn btn-sm btn-success' onclick=\"window.open('view_webpage.php?office_id=$oid', '_blank')\">View</button>
                            <button class='btn btn-sm btn-danger' onclick='confirmDelete($oid)'>Delete</button>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Recent Activity & Data Visualization Section -->
    <?php
    // Define date ranges for filtering recent activity.
    $dateRanges = [
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_year' => 'This Year'
    ];
    $selectedRange = isset($_GET['date_range']) ? $_GET['date_range'] : 'last_30_days';
    switch ($selectedRange) {
        case 'last_7_days':
            $startDateFilter = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'last_30_days':
            $startDateFilter = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'this_month':
            $startDateFilter = date('Y-m-01');
            break;
        case 'last_month':
            $startDateFilter = date('Y-m-01', strtotime('-1 month'));
            $endDateFilter = date('Y-m-t', strtotime('-1 month'));
            break;
        case 'this_year':
            $startDateFilter = date('Y-01-01');
            break;
        default:
            $startDateFilter = date('Y-m-d', strtotime('-30 days'));
    }
    $endDateFilter = isset($endDateFilter) ? $endDateFilter : date('Y-m-d');
    $query = "
        SELECT 'News' AS type, n.title, n.created_at, o.office_name 
        FROM news_content n
        JOIN offices o ON n.office_id = o.id
        WHERE DATE(n.created_at) BETWEEN '$startDateFilter' AND '$endDateFilter'
        UNION ALL
        SELECT 'Document' AS type, d.title, d.created_at, o.office_name 
        FROM documents d
        JOIN offices o ON d.office_id = o.id
        WHERE DATE(d.created_at) BETWEEN '$startDateFilter' AND '$endDateFilter'
        UNION ALL
        SELECT 'Announcement' AS type, a.title, a.created_at, o.office_name 
        FROM announcements_content a
        JOIN offices o ON a.office_id = o.id
        WHERE DATE(a.created_at) BETWEEN '$startDateFilter' AND '$endDateFilter'
        ORDER BY created_at DESC
    ";
    $result = $conn->query($query);
    ?>
    <section id="data-visualization" class="container">
        <div class="section-title d-flex justify-content-between align-items-center" id="recent-activity">
            <span>Recent Activity Feed</span>
            <form method="GET" class="d-inline-block">
                <label for="date_range" class="me-2 small">Filter:</label>
                <select name="date_range" id="date_range" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                    <?php foreach ($dateRanges as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $selectedRange === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="table-responsive scrollable-table mt-2">
            <?php if ($result->num_rows > 0): ?>
                <table class="table table-sm table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Office</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['type']); ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['office_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info mt-2">No data to show.</div>
            <?php endif; ?>
        </div>
        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">Content Distribution (Pie Chart)</h6>
                        <canvas id="servicesChart"></canvas>
                        <div class="text-right mt-2">
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#servicesChartModal">
                                View Larger
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Office Summary (Bar Chart)</h6>
                        <canvas id="officesChart"></canvas>
                        <div class="text-right mt-2">
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#officesChartModal">
                                View Larger
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">Visitor Movement (Line Chart)</h6>
                        <canvas id="visitorsLineChart"></canvas>
                        <div class="text-right mt-2">
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#visitorsChartModal">
                                View Larger
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modals Section -->

<!-- Offices Chart Modal (Bar Chart) -->
<div class="modal fade" id="officesChartModal" tabindex="-1" role="dialog" aria-labelledby="officesChartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="officesChartModalLabel">Office Summary - Detailed (Bar Chart)</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <canvas id="officesChartModalCanvas"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Services Chart Modal (Pie Chart) -->
<div class="modal fade" id="servicesChartModal" tabindex="-1" role="dialog" aria-labelledby="servicesChartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="servicesChartModalLabel">Content Distribution - Detailed (Pie Chart)</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body d-flex justify-content-center align-items-center">
        <canvas id="servicesChartModalCanvas" style="max-width: 100%; height: auto;"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Visitors Chart Modal (Line Chart) -->
<div class="modal fade" id="visitorsChartModal" tabindex="-1" role="dialog" aria-labelledby="visitorsChartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="visitorsChartModalLabel">Visitor Movement - Detailed (Line Chart)</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <canvas id="visitorsChartModalCanvas"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Main Admins -->
<div class="modal fade" id="mainAdminsModal" tabindex="-1" aria-labelledby="mainAdminsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mainAdminsModalLabel">Main Admins</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Responsive table container -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($admin = $main_admins->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['created_at']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="resetPassword(<?php echo $admin['id']; ?>, 'main')">
                                            Reset Password
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Office Admins -->
<div class="modal fade" id="officeAdminsModal" tabindex="-1" aria-labelledby="officeAdminsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="officeAdminsModalLabel">Office Admins</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Responsive table container -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Office</th>
                                <th>Created At</th>
                                <th>Account Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($admin = $office_admins->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <?php 
                                            echo !empty($admin['office_name']) 
                                                ? htmlspecialchars($admin['office_name']) 
                                                : '<span class="text-muted">N/A</span>'; 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($admin['created_at']); ?></td>
                                    <td>
                                        <?php 
                                            $status = strtolower($admin['status']); 
                                            echo ($status === 'active') 
                                                ? '<span class="text-success">Active</span>' 
                                                : '<span class="text-danger">Inactive</span>'; 
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="resetPassword(<?php echo $admin['id']; ?>, 'office')">
                                            Reset Password
                                        </button>
                                        <?php 
                                            if ($status === 'active') {
                                                echo ' <button class="btn btn-sm btn-danger" onclick="toggleAccountStatus(' . $admin['id'] . ', \'disable\')">Disable Account</button>';
                                            } else {
                                                echo ' <button class="btn btn-sm btn-success" onclick="toggleAccountStatus(' . $admin['id'] . ', \'activate\')">Activate Account</button>';
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Office Modal -->
<div class="modal fade" id="addOfficeModal" tabindex="-1" aria-labelledby="addOfficeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Office</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addOfficeForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="officeName">Office Name</label>
                        <input type="text" class="form-control" id="officeName" name="officeName" required>
                    </div>
                    <div class="form-group">
                        <label for="officeAddress">Address</label>
                        <input type="text" class="form-control" id="officeAddress" name="officeAddress" required>
                    </div>
                    <div class="form-group">
                        <label for="officeLogo">Upload Office Logo</label>
                        <input type="file" class="form-control" id="officeLogo" name="officeLogo" accept="image/*" required>
                        <img id="logoPreview" src="#" alt="Logo Preview" style="display:none; width: 100px; height: 100px; margin-top: 10px;">
                    </div>
                    <input type="hidden" name="action" value="add_office">
                    <button type="submit" class="btn btn-primary">Add Office</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Office Modal -->
<div class="modal fade" id="editOfficeModal" tabindex="-1" aria-labelledby="editOfficeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Office</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editOfficeForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="editOfficeName">Office Name</label>
                        <input type="text" class="form-control" id="editOfficeName" name="editOfficeName" required>
                    </div>
                    <div class="form-group">
                        <label for="editOfficeAddress">Address</label>
                        <input type="text" class="form-control" id="editOfficeAddress" name="editOfficeAddress" required>
                    </div>
                    <div class="form-group">
                        <label for="currentLogo">Current Logo</label>
                        <img id="currentLogo" src="#" alt="Current Logo" style="width: 100px; height: 100px; margin-top: 10px;">
                    </div>
                    <div class="form-group">
                        <label for="newOfficeLogo">Upload New Logo</label>
                        <input type="file" class="form-control" id="newOfficeLogo" name="newOfficeLogo" accept="image/*">
                    </div>
                    <input type="hidden" id="editOfficeId" name="editOfficeId">
                    <input type="hidden" name="action" value="edit_office">
                    <button type="submit" class="btn btn-success">Update Office</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Profile Settings Modal -->
<div class="modal fade" id="profileSettingsModal" tabindex="-1" aria-labelledby="profileSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl"> <!-- Changed to modal-xl -->
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header" style="background-color: #007bff; color: white;">
                <h5 class="modal-title">Profile Settings</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <?php
                    // Fetch current profile info including creation date.
                    $stmt = $conn->prepare("SELECT full_name, email, contact_number, profile_photo, created_at FROM main_admin_accounts WHERE username = ?");
                    $stmt->bind_param("s", $_SESSION['username']);
                    $stmt->execute();
                    $stmt->bind_result($currentFullName, $currentEmail, $currentContact, $currentProfilePhoto, $accountCreatedAt);
                    $stmt->fetch();
                    $stmt->close();
                    $accountCreatedDate = date("F j, Y", strtotime($accountCreatedAt));
                    $greetingMessage = empty($currentFullName) ? "Hello @{$_SESSION['username']}, please setup your account first" : "Hello, " . htmlspecialchars($currentFullName);
                ?>
                <div class="container-fluid">
                    <div class="row">
                        <!-- Left Side: Profile Info -->
                        <div class="col-md-4 text-center border-right" style="max-height: 400px; overflow-y: auto;">
                            <img id="profilePhotoPreview" src="<?php echo $currentProfilePhoto ? htmlspecialchars($currentProfilePhoto) : 'ASSETS/default-profile.png'; ?>" alt="Profile Photo" class="profile-preview mb-3" style="width: 150px; height: 150px; border-radius: 75px; border: 2px solid #007bff;">
                            <h5><?php echo $greetingMessage; ?></h5>
                            <p class="mb-1">@<?php echo htmlspecialchars($_SESSION['username']); ?></p>
                            <p class="mb-1">LGU-MAIN ADMIN</p>
                            <p class="mb-1">WELCOME TO YOUR DASHBOARD</p>
                            <p class="mb-0">Account Active since <?php echo $accountCreatedDate; ?></p>
                        </div>
                        <!-- Right Side: Profile Form -->
                        <div class="col-md-8" style="max-height: 400px; overflow-y: auto;">
                            <form id="profileSettingsForm">
                                <div class="form-group">
                                    <label for="profilePhoto">Change Profile Photo</label>
                                    <input type="file" class="form-control-file" id="profilePhoto" name="profilePhoto" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($currentFullName); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($currentEmail); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($currentContact); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">Change Password (Optional)</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                                </div>
                                <!-- Hidden field to store cropped image data -->
                                <input type="hidden" name="croppedImage" id="croppedImage">
                                <input type="hidden" name="action" value="update_profile">
                                <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Crop Image Modal for Profile Photo (Responsive) -->
<div class="modal fade" id="cropImageModal" tabindex="-1" aria-labelledby="cropImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header" style="background-color: #007bff; color: white;">
                <h5 class="modal-title">Crop Profile Photo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <!-- Cropper container wrapper -->
                <div class="cropper-container" style="width: 100%; max-height: calc(100vh - 210px); overflow: hidden; margin: auto;">
                    <img id="cropImagePreview" src="#" alt="Crop Image" class="img-fluid" 
                         style="display: block; width: 100%; border-radius: 10px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="cropButton" class="btn btn-primary">Crop Image</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<footer class="mt-5">
    <div class="container">
        <small>&copy; <?php echo date("Y"); ?> Office Management System - Admin Panel</small>
    </div>
</footer>

<!-- jQuery, Popper, Bootstrap JS, and Chart.js -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Cropper.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js" crossorigin="anonymous"></script>

<script>
    // Activate sidebar navigation links.
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.addEventListener('click', function() {
            document.querySelectorAll('.sidebar .nav-link').forEach(el => el.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Office and Content Charts Setup.
    const officesChart = new Chart(document.getElementById('officesChart'), {
        type: 'bar',
        data: {
            labels: ['Offices', 'Main Admins', 'Office Admins'],
            datasets: [{
                label: 'Count',
                data: [<?php echo $results['offices']; ?>, <?php echo $results['main_admins']; ?>, <?php echo $results['office_admins']; ?>],
                backgroundColor: ['#4e73df', '#858796', '#5a5c69'],
                borderColor: ['#4e73df', '#858796', '#5a5c69'],
                borderWidth: 1
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });

    const servicesChart = new Chart(document.getElementById('servicesChart'), {
        type: 'pie',
        data: {
            labels: ['Services', 'News', 'Announcements', 'Documents', 'Gallery'],
            datasets: [{
                label: 'Count',
                data: [<?php echo $results['services']; ?>, <?php echo $results['news']; ?>, <?php echo $results['announcements']; ?>, <?php echo $results['documents']; ?>, <?php echo $results['gallery']; ?>],
                backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#fd7e14'],
                borderColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#fd7e14'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'Content Distribution' }
            }
        }
    });

    const visitorsLineChart = new Chart(document.getElementById('visitorsLineChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                <?php 
                $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#ff9f40'];
                $i = 0;
                foreach ($officeData as $officeId => $officeInfo) {
                    $color = $colors[$i % count($colors)];
                    echo json_encode([
                        'label' => $officeInfo['name'],
                        'data' => $officeInfo['data'],
                        'fill' => false,
                        'borderColor' => $color,
                        'backgroundColor' => $color
                    ]);
                    $i++;
                    if ($i < count($officeData)) echo ",\n";
                }
                ?>
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'Visitor Movement (Last 7 Days)' }
            }
        }
    });

    // Enlarged charts in modals.
    $('#officesChartModal').on('shown.bs.modal', function () {
        if (window.officesChartModalInstance) {
            window.officesChartModalInstance.destroy();
        }
        var ctx = document.getElementById('officesChartModalCanvas').getContext('2d');
        window.officesChartModalInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Offices', 'Main Admins', 'Office Admins'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo $results['offices']; ?>, <?php echo $results['main_admins']; ?>, <?php echo $results['office_admins']; ?>],
                    backgroundColor: ['#4e73df', '#858796', '#5a5c69'],
                    borderColor: ['#4e73df', '#858796', '#5a5c69'],
                    borderWidth: 1
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
    });

    $('#servicesChartModal').on('shown.bs.modal', function () {
        if (window.servicesChartModalInstance) {
            window.servicesChartModalInstance.destroy();
        }
        var ctx = document.getElementById('servicesChartModalCanvas').getContext('2d');
        window.servicesChartModalInstance = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Services', 'News', 'Announcements', 'Documents', 'Gallery'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo $results['services']; ?>, <?php echo $results['news']; ?>, <?php echo $results['announcements']; ?>, <?php echo $results['documents']; ?>, <?php echo $results['gallery']; ?>],
                    backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#fd7e14'],
                    borderColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#fd7e14'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Content Distribution' }
                }
            }
        });
    });

    $('#visitorsChartModal').on('shown.bs.modal', function () {
        if (window.visitorsChartModalInstance) {
            window.visitorsChartModalInstance.destroy();
        }
        var ctx = document.getElementById('visitorsChartModalCanvas').getContext('2d');
        window.visitorsChartModalInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [
                    <?php 
                    $i = 0;
                    foreach ($officeData as $officeId => $officeInfo) {
                        $color = $colors[$i % count($colors)];
                        echo json_encode([
                            'label' => $officeInfo['name'],
                            'data' => $officeInfo['data'],
                            'fill' => false,
                            'borderColor' => $color,
                            'backgroundColor' => $color
                        ]);
                        $i++;
                        if ($i < count($officeData)) echo ",\n";
                    }
                    ?>
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Visitor Movement (Last 7 Days)' }
                }
            }
        });
    });

    // Office forms submission.
    document.getElementById('addOfficeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('main-manage.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => { data.success ? location.reload() : alert(data.message); })
            .catch(error => console.error('Error:', error));
    });
    document.getElementById('editOfficeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('main-manage.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => { data.success ? location.reload() : alert(data.message); })
            .catch(error => console.error('Error:', error));
    });
    function confirmDelete(officeId) {
        let password = prompt("Please enter your password to confirm deletion:");
        if (!password || password.trim() === "") {
            alert("Deletion cancelled. Password is required.");
            return;
        }
        if (confirm("Are you sure you want to delete this office?")) {
            fetch('main-manage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_office', officeId: officeId, password: password })
            })
            .then(response => response.json())
            .then(data => { data.success ? location.reload() : alert(data.message); })
            .catch(error => console.error('Error:', error));
        }
    }
    function resetPassword(adminId, adminType) {
        if (confirm("Are you sure you want to reset this admin's password to the default LGU-ADMIN_2025?")) {
            fetch('main-manage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reset_password', adminId: adminId, adminType: adminType })
            })
            .then(response => response.json())
            .then(data => { alert(data.message); if (data.success) location.reload(); })
            .catch(error => console.error('Error:', error));
        }
    }
    // New function to toggle account status.
    function toggleAccountStatus(adminId, toggleType) {
        if (confirm("Are you sure you want to " + toggleType + " this account?")) {
            fetch('main-manage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_account', adminId: adminId, toggleType: toggleType })
            })
            .then(response => response.json())
            .then(data => { 
                alert(data.message); 
                if (data.success) location.reload();
            })
            .catch(error => console.error('Error:', error));
        }
    }

    // Profile Settings: Handle profile photo selection and cropping.
    let cropper;
    const profilePhotoInput = document.getElementById('profilePhoto');
    profilePhotoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                document.getElementById('cropImagePreview').src = evt.target.result;
                $('#cropImageModal').modal('show');
            }
            reader.readAsDataURL(file);
        }
    });
    $('#cropImageModal').on('shown.bs.modal', function() {
        const image = document.getElementById('cropImagePreview');
        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            movable: true,
            zoomable: true
        });
    });
    $('#cropImageModal').on('hidden.bs.modal', function() {
        if(cropper){
            cropper.destroy();
            cropper = null;
        }
    });
    document.getElementById('cropButton').addEventListener('click', function() {
        if(cropper){
            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 300
            });
            const croppedImageDataURL = canvas.toDataURL('image/png');
            document.getElementById('profilePhotoPreview').src = croppedImageDataURL;
            document.getElementById('croppedImage').value = croppedImageDataURL;
            $('#cropImageModal').modal('hide');
        }
    });
    // Profile Settings form submission.
    document.getElementById('profileSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('main-manage.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => { 
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(error => console.error('Error:', error));
    });

    // Populate Edit Office Modal.
    $('#editOfficeModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const officeId = button.data('id');
        const officeName = button.data('name');
        const officeAddress = button.data('address');
        const officeLogo = button.data('logo');
        const modal = $(this);
        modal.find('#editOfficeId').val(officeId);
        modal.find('#editOfficeName').val(officeName);
        modal.find('#editOfficeAddress').val(officeAddress);
        modal.find('#currentLogo').attr('src', officeLogo);
    });
</script>
</body>
</html>