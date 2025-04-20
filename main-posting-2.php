<?php
// admin-media-management.php
// Single-file admin system for managing media content

// Database Connection
include 'config.php';

/*
// Authentication Check
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin-login.php");
    exit();
}*/

// File Upload Directory
$upload_dir = "ASSETS/uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Detect Media Type
function detectMediaType($file) {
    if (filter_var($file, FILTER_VALIDATE_URL)) {
        // Check if it's a video embed link (YouTube, Vimeo, etc.)
        if (preg_match('/youtube\.com|youtu\.be|vimeo\.com/', $file)) {
            return 'video_embed';
        }
        return 'link';
    }
    
    if (isset($file['tmp_name'])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (strpos($mime, 'image/') === 0) return 'image';
        if (strpos($mime, 'video/') === 0) return 'video';
    }
    
    return 'unknown';
}

// Handle Form Submissions
$message = '';
$error = '';

// CREATE Operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $title = $_POST['title'] ?? '';
    $caption = $_POST['caption'] ?? '';
    $media_type = 'text'; // default
    
    // Handle file upload or link
    $media_path = '';
    if (!empty($_FILES['media_file']['name'])) {
        $media_type = detectMediaType($_FILES['media_file']);
        $ext = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
        $media_path = $upload_dir . uniqid() . '.' . $ext;
        
        if (!move_uploaded_file($_FILES['media_file']['tmp_name'], $media_path)) {
            $error = "Failed to upload file.";
        }
    } elseif (!empty($_POST['media_link'])) {
        $media_type = detectMediaType($_POST['media_link']);
        $media_path = $_POST['media_link'];
    }
    
    if (empty($error)) {
        // Determine which table to insert into based on media type
        $table = '';
        $columns = '';
        $values = '';
        
        if (in_array($media_type, ['image', 'video'])) {
            $table = 'lgu_main_gallery';
            $columns = 'gallery_caption, gallery_image_url, gallery_type, gallery_status';
            $values = "'" . $conn->real_escape_string($caption) . "', '" . $conn->real_escape_string($media_path) . "', '" . $media_type . "', 'active'";
        } elseif ($media_type === 'video_embed') {
            $table = 'lgu_main_gallery';
            $columns = 'gallery_caption, gallery_image_url, gallery_type, gallery_status';
            $values = "'" . $conn->real_escape_string($caption) . "', '" . $conn->real_escape_string($media_path) . "', 'video_embed', 'active'";
        } elseif (!empty($title)) {
            // Assume it's for announcements if there's a title
            $table = 'lgu_main_announcements';
            $columns = 'announcement_title, announcement_content';
            $values = "'" . $conn->real_escape_string($title) . "', '" . $conn->real_escape_string($caption) . "'";
            
            if (!empty($media_path)) {
                $columns .= ', announcement_image_url';
                $values .= ", '" . $conn->real_escape_string($media_path) . "'";
            }
            $columns .= ', announcement_status';
            $values .= ", 'published'";
        }
        
        if (!empty($table)) {
            $sql = "INSERT INTO $table ($columns) VALUES ($values)";
            if ($conn->query($sql)) {
                $message = "Media added successfully!";
            } else {
                $error = "Error adding media: " . $conn->error;
            }
        } else {
            $error = "Could not determine where to store this media.";
        }
    }
}

// UPDATE Operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $table = $_POST['table'];
    $title = $_POST['title'] ?? '';
    $caption = $_POST['caption'] ?? '';
    
    // Build the update query based on provided fields
    $updates = [];
    
    if (!empty($title)) $updates[] = "announcement_title = '" . $conn->real_escape_string($title) . "'";
    if (!empty($caption)) {
        if ($table === 'lgu_main_gallery') {
            $updates[] = "gallery_caption = '" . $conn->real_escape_string($caption) . "'";
        } else {
            $updates[] = "announcement_content = '" . $conn->real_escape_string($caption) . "'";
        }
    }
    
    // Handle file upload if provided
    if (!empty($_FILES['media_file']['name'])) {
        $media_type = detectMediaType($_FILES['media_file']);
        $ext = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
        $media_path = $upload_dir . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['media_file']['tmp_name'], $media_path)) {
            if ($table === 'lgu_main_gallery') {
                $updates[] = "gallery_image_url = '" . $conn->real_escape_string($media_path) . "'";
                $updates[] = "gallery_type = '" . $media_type . "'";
            } else {
                $updates[] = "announcement_image_url = '" . $conn->real_escape_string($media_path) . "'";
            }
            
            // Delete old file if it exists
            $old_file_sql = "SELECT " . ($table === 'lgu_main_gallery' ? 'gallery_image_url' : 'announcement_image_url') . " FROM $table WHERE " . ($table === 'lgu_main_gallery' ? 'gallery_id' : 'announcement_id') . " = $id";
            $old_file_result = $conn->query($old_file_sql);
            if ($old_file_result && $old_file_row = $old_file_result->fetch_assoc()) {
                $old_file = $old_file_row[$table === 'lgu_main_gallery' ? 'gallery_image_url' : 'announcement_image_url'];
                if (file_exists($old_file) && strpos($old_file, $upload_dir) === 0) {
                    unlink($old_file);
                }
            }
        } else {
            $error = "Failed to upload new file.";
        }
    } elseif (!empty($_POST['media_link'])) {
        $media_type = detectMediaType($_POST['media_link']);
        $media_path = $_POST['media_link'];
        
        if ($table === 'lgu_main_gallery') {
            $updates[] = "gallery_image_url = '" . $conn->real_escape_string($media_path) . "'";
            $updates[] = "gallery_type = '" . ($media_type === 'video_embed' ? 'video_embed' : 'link') . "'";
        } else {
            $updates[] = "announcement_image_url = '" . $conn->real_escape_string($media_path) . "'";
        }
    }
    
    if (!empty($updates) && empty($error)) {
        $sql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE " . ($table === 'lgu_main_gallery' ? 'gallery_id' : 'announcement_id') . " = $id";
        if ($conn->query($sql)) {
            $message = "Media updated successfully!";
        } else {
            $error = "Error updating media: " . $conn->error;
        }
    } elseif (empty($error)) {
        $message = "No changes were made.";
    }
}

// DELETE Operation
if (isset($_GET['delete'])) {
    $id = $_GET['id'];
    $table = $_GET['table'];
    
    // First get the file path if it's a file
    $file_path = '';
    $sql = "SELECT " . ($table === 'lgu_main_gallery' ? 'gallery_image_url' : 'announcement_image_url') . " FROM $table WHERE " . ($table === 'lgu_main_gallery' ? 'gallery_id' : 'announcement_id') . " = $id";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $file_path = $row[$table === 'lgu_main_gallery' ? 'gallery_image_url' : 'announcement_image_url'];
    }
    
    // Delete the record
    $sql = "DELETE FROM $table WHERE " . ($table === 'lgu_main_gallery' ? 'gallery_id' : 'announcement_id') . " = $id";
    if ($conn->query($sql)) {
        // Delete the associated file if it exists locally
        if (!empty($file_path) && file_exists($file_path) && strpos($file_path, $upload_dir) === 0) {
            unlink($file_path);
        }
        $message = "Media deleted successfully!";
    } else {
        $error = "Error deleting media: " . $conn->error;
    }
}

// READ Operation - Fetch all media
$announcements = [];
$gallery_items = [];

$announcements_result = $conn->query("SELECT announcement_id, announcement_title, announcement_content, announcement_image_url, announcement_date FROM lgu_main_announcements ORDER BY announcement_date DESC");
if ($announcements_result) {
    while ($row = $announcements_result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

$gallery_result = $conn->query("SELECT gallery_id, gallery_caption, gallery_image_url, gallery_type, gallery_date FROM lgu_main_gallery ORDER BY gallery_date DESC");
if ($gallery_result) {
    while ($row = $gallery_result->fetch_assoc()) {
        $gallery_items[] = $row;
    }
}

// For editing - fetch single item
$edit_item = null;
if (isset($_GET['edit'])) {
    $id = $_GET['id'];
    $table = $_GET['table'];
    
    $sql = "SELECT * FROM $table WHERE " . ($table === 'lgu_main_gallery' ? 'gallery_id' : 'announcement_id') . " = $id";
    $result = $conn->query($sql);
    if ($result) {
        $edit_item = $result->fetch_assoc();
        $edit_item['table'] = $table;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libmanan Admin - Media Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .media-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }
        .embed-responsive {
            position: relative;
            display: block;
            width: 100%;
            padding: 0;
            overflow: hidden;
        }
        .embed-responsive::before {
            display: block;
            content: "";
            padding-top: 56.25%;
        }
        .embed-responsive-item {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .media-card {
            transition: all 0.3s ease;
        }
        .media-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Libmanan Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-images me-1"></i> Media Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h1 class="mb-4">Media Management</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo isset($edit_item) ? 'Edit Media' : 'Add New Media'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php if (isset($edit_item)): ?>
                                <input type="hidden" name="id" value="<?php echo $edit_item[$edit_item['table'] === 'lgu_main_gallery' ? 'gallery_id' : 'announcement_id']; ?>">
                                <input type="hidden" name="table" value="<?php echo $edit_item['table']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Title (for announcements)</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                    value="<?php echo isset($edit_item) && $edit_item['table'] === 'lgu_main_announcements' ? htmlspecialchars($edit_item['announcement_title']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="caption" class="form-label">Caption/Content</label>
                                <textarea class="form-control" id="caption" name="caption" rows="3"><?php 
                                    if (isset($edit_item)) {
                                        echo htmlspecialchars($edit_item['table'] === 'lgu_main_gallery' ? $edit_item['gallery_caption'] : $edit_item['announcement_content']);
                                    }
                                ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Media</label>
                                <div class="mb-2">
                                    <input type="file" class="form-control" id="media_file" name="media_file" accept="image/*,video/*">
                                    <small class="text-muted">Upload an image or video file</small>
                                </div>
                                <div class="text-center my-2">OR</div>
                                <div>
                                    <input type="url" class="form-control" id="media_link" name="media_link" 
                                        placeholder="Enter video embed link (YouTube, Vimeo)" 
                                        value="<?php echo isset($edit_item) && detectMediaType($edit_item[$edit_item['table'] === 'lgu_main_gallery' ? 'gallery_image_url' : 'announcement_image_url']) === 'video_embed' ? htmlspecialchars($edit_item[$edit_item['table'] === 'lgu_main_gallery' ? 'gallery_image_url' : 'announcement_image_url']) : ''; ?>">
                                </div>
                            </div>
                            
                            <?php if (isset($edit_item)): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Media</label>
                                    <?php 
                                    $current_media = $edit_item[$edit_item['table'] === 'lgu_main_gallery' ? 'gallery_image_url' : 'announcement_image_url'];
                                    $media_type = detectMediaType($current_media);
                                    
                                    if (!empty($current_media)): 
                                        if ($media_type === 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($current_media); ?>" class="img-fluid rounded mb-2" alt="Current media">
                                        <?php elseif ($media_type === 'video_embed'): ?>
                                            <div class="embed-responsive embed-responsive-16by9 mb-2">
                                                <iframe class="embed-responsive-item" src="<?php echo htmlspecialchars(embedUrl($current_media)); ?>" allowfullscreen></iframe>
                                            </div>
                                        <?php elseif ($media_type === 'video'): ?>
                                            <video controls class="img-fluid rounded mb-2">
                                                <source src="<?php echo htmlspecialchars($current_media); ?>">
                                            </video>
                                        <?php else: ?>
                                            <div class="alert alert-info">Current: <?php echo htmlspecialchars($current_media); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">No media currently attached</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <?php if (isset($edit_item)): ?>
                                    <button type="submit" name="update" class="btn btn-primary">Update Media</button>
                                    <a href="admin-media-management.php" class="btn btn-secondary">Cancel</a>
                                <?php else: ?>
                                    <button type="submit" name="create" class="btn btn-success">Add Media</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <ul class="nav nav-tabs" id="mediaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="announcements-tab" data-bs-toggle="tab" data-bs-target="#announcements" type="button" role="tab">Announcements</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="gallery-tab" data-bs-toggle="tab" data-bs-target="#gallery" type="button" role="tab">Gallery</button>
                    </li>
                </ul>
                
                <div class="tab-content p-3 border border-top-0 rounded-bottom" id="mediaTabsContent">
                    <div class="tab-pane fade show active" id="announcements" role="tabpanel">
                        <h4 class="mb-3">Announcements</h4>
                        <?php if (empty($announcements)): ?>
                            <div class="alert alert-info">No announcements found.</div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-2 g-4">
                                <?php foreach ($announcements as $item): ?>
                                    <div class="col">
                                        <div class="card media-card h-100">
                                            <?php if (!empty($item['announcement_image_url'])): 
                                                $media_type = detectMediaType($item['announcement_image_url']);
                                                if ($media_type === 'image'): ?>
                                                    <img src="<?php echo htmlspecialchars($item['announcement_image_url']); ?>" class="card-img-top" alt="Announcement image">
                                                <?php elseif ($media_type === 'video_embed'): ?>
                                                    <div class="embed-responsive embed-responsive-16by9">
                                                        <iframe class="embed-responsive-item" src="<?php echo htmlspecialchars(embedUrl($item['announcement_image_url'])); ?>" allowfullscreen></iframe>
                                                    </div>
                                                <?php elseif ($media_type === 'video'): ?>
                                                    <video controls class="card-img-top">
                                                        <source src="<?php echo htmlspecialchars($item['announcement_image_url']); ?>">
                                                    </video>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($item['announcement_title']); ?></h5>
                                                <p class="card-text text-muted small"><?php echo date('M j, Y', strtotime($item['announcement_date'])); ?></p>
                                                <p class="card-text"><?php echo substr(htmlspecialchars($item['announcement_content']), 0, 100); ?>...</p>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <div class="d-flex justify-content-between">
                                                    <a href="admin-media-management.php?edit=1&id=<?php echo $item['announcement_id']; ?>&table=lgu_main_announcements" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <a href="admin-media-management.php?delete=1&id=<?php echo $item['announcement_id']; ?>&table=lgu_main_announcements" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this announcement?')">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane fade" id="gallery" role="tabpanel">
                        <h4 class="mb-3">Gallery Items</h4>
                        <?php if (empty($gallery_items)): ?>
                            <div class="alert alert-info">No gallery items found.</div>
                        <?php else: ?>
                            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                                <?php foreach ($gallery_items as $item): ?>
                                    <div class="col">
                                        <div class="card media-card h-100">
                                            <?php if (!empty($item['gallery_image_url'])): 
                                                $media_type = detectMediaType($item['gallery_image_url']);
                                                if ($media_type === 'image'): ?>
                                                    <img src="<?php echo htmlspecialchars($item['gallery_image_url']); ?>" class="card-img-top" alt="Gallery image">
                                                <?php elseif ($media_type === 'video_embed'): ?>
                                                    <div class="embed-responsive embed-responsive-16by9">
                                                        <iframe class="embed-responsive-item" src="<?php echo htmlspecialchars(embedUrl($item['gallery_image_url'])); ?>" allowfullscreen></iframe>
                                                    </div>
                                                <?php elseif ($media_type === 'video'): ?>
                                                    <video controls class="card-img-top">
                                                        <source src="<?php echo htmlspecialchars($item['gallery_image_url']); ?>">
                                                    </video>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <p class="card-text"><?php echo htmlspecialchars($item['gallery_caption']); ?></p>
                                                <p class="card-text text-muted small"><?php echo date('M j, Y', strtotime($item['gallery_date'])); ?></p>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <div class="d-flex justify-content-between">
                                                    <a href="admin-media-management.php?edit=1&id=<?php echo $item['gallery_id']; ?>&table=lgu_main_gallery" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <a href="admin-media-management.php?delete=1&id=<?php echo $item['gallery_id']; ?>&table=lgu_main_gallery" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this gallery item?')">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">Municipality of Libmanan &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to convert video URLs to embed URLs
        function embedUrl(url) {
            // YouTube
            if (url.includes('youtube.com/watch?v=')) {
                return url.replace('youtube.com/watch?v=', 'youtube.com/embed/');
            }
            // YouTube short URL
            if (url.includes('youtu.be/')) {
                return url.replace('youtu.be/', 'youtube.com/embed/');
            }
            // Vimeo
            if (url.includes('vimeo.com/')) {
                return url.replace('vimeo.com/', 'player.vimeo.com/video/');
            }
            return url;
        }
        
        // Auto-detect media type when file is selected or link is entered
        document.getElementById('media_file').addEventListener('change', function() {
            document.getElementById('media_link').value = '';
        });
        
        document.getElementById('media_link').addEventListener('input', function() {
            document.getElementById('media_file').value = '';
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>