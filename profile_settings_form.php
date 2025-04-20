<?php

if (!isset($_SESSION['user_id'])) {
    echo "<p>Please log in to update your profile.</p>";
    exit;
}

require_once "config.php"; // Assumes $conn is created here

$user_id = $_SESSION['user_id'];
$errors = [];
$message = "";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate required fields
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    }
    if (empty($contact_number)) {
        $errors[] = "Contact number is required.";
    } elseif (!ctype_digit($contact_number)) {
        $errors[] = "Contact number must contain only numbers.";
    }

    // Process the profile photo (cropped image data first, then fallback)
    $profile_photo = null;
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        $cropped_image_data = $_POST['cropped_image'];
        // Expected format: data:image/png;base64,...
        if (preg_match('/^data:image\/(\w+);base64,/', $cropped_image_data, $type)) {
            $extension = strtolower($type[1]);
            $data = substr($cropped_image_data, strpos($cropped_image_data, ',') + 1);
            $data = base64_decode($data);
            if ($data === false) {
                $errors[] = "Failed to decode cropped image data.";
            }
        } else {
            $errors[] = "Invalid cropped image data.";
        }
        if (empty($errors)) {
            $uploadDir = 'uploads/profile_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = 'user_' . $user_id . '_' . time() . '.' . $extension;
            $destination = $uploadDir . $newFileName;
            if (file_put_contents($destination, $data)) {
                $profile_photo = $newFileName;
            } else {
                $errors[] = "Failed to save cropped image.";
            }
        }
    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        // Fallback traditional file upload if cropping not done
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $fileName = $_FILES['profile_photo']['name'];
        $fileTmp = $_FILES['profile_photo']['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowed_types)) {
            $errors[] = "Profile photo must be a JPG, JPEG, PNG, or GIF image.";
        } else {
            $uploadDir = 'uploads/profile_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = 'user_' . $user_id . '_' . time() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmp, $destination)) {
                $profile_photo = $newFileName;
            } else {
                $errors[] = "Failed to upload profile photo.";
            }
        }
    }

    // Validate the new password if provided
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
    }

    // If no errors, update the database
    if (empty($errors)) {
        $update_fields = "username = ?, full_name = ?, email = ?, contact_number = ?";
        $params = [$username, $full_name, $email, $contact_number];
        $types = "sssi"; // assuming contact_number is integer

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields .= ", password = ?";
            $params[] = $hashed_password;
            $types .= "s";
        }
        if ($profile_photo) {
            $update_fields .= ", profile_photo = ?";
            $params[] = $profile_photo;
            $types .= "s";
        }
        $params[] = $user_id;
        $types .= "i";

        $stmt = $conn->prepare("UPDATE accounts SET $update_fields WHERE id = ?");
        if ($stmt === false) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $message = "Profile updated successfully.";

            } else {
                $errors[] = "Failed to update profile: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Retrieve current user information with join on offices table
$stmt = $conn->prepare("SELECT a.username, a.full_name, a.email, a.contact_number, a.office_id, a.profile_photo, o.office_name 
                        FROM accounts a 
                        LEFT JOIN offices o ON a.office_id = o.id
                        WHERE a.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "<p>User not found.</p>";
    exit;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profile Settings</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0/css/bootstrap.min.css">
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1AnmM+ZowO6pG5Jp/6hZp6LTyWZZ3uSBm9Yt7BYy1LvfB1Uq" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>
        .modal-dialog {
            max-width: 90%;
            /* Allow modal to take up to 90% of the screen width */
            width: auto;
            /* Auto width for better responsiveness */
        }

        .crop-modal-body {
            padding: 20px;
            height: auto;
            /* Allow height to adjust based on content */
            max-height: 90vh;
            /* Limit max height */
            overflow-y: auto;
            /* Enable scrolling if content exceeds max height */
        }

        /* Modal adjustments for responsive behavior */
        .modal-dialog.modal-xl {
            max-width: 90%;
            /* Adjust to nearly full-screen */
            width: 90%;
            margin: auto;
        }

        /* Crop Modal Body: Fill a large portion of the viewport height */
        .crop-modal-body {
            padding: 20px;
            height: calc(90vh - 12px);
            background-color: #f7f7f7;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Image Container: Fully responsive */
        .img-container {
            width: 100%;
            height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 2px dashed #007bff;
            border-radius: 5px;
            position: relative;
        }

        .img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: block;
        }

        /* Make the profile photo circular */
        #profilePreview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }

        /* Right column will scroll if content overflows */
        .scrollable-right {
            max-height: 400px;
            overflow-y: auto;
        }

        /* Left column remains fixed and non-scrollable */
        .non-scrollable-left {
            max-height: 80%;
            overflow-: hidden;
        }

        .profile-photo-container {
            position: relative;
            display: inline-block;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .profile-photo-container:hover .profile-photo {
            transform: scale(1.05);
        }

        .change-photo-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 50%;
            padding: 6px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: background 0.3s ease;
        }

        .change-photo-overlay:hover {
            background: #0056b3;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data" id="profile-settings-form">
            <div class="container-fluid">
                <div class="row">

                    <div class="col-md-4 d-flex flex-column align-items-center border-end">
                        <!-- Profile Card -->
                        <div class="card shadow-sm w-100 mb-3">
                            <div class="card-body text-center">
                                <div class="profile-photo-container mb-3">
                                    <?php if (!empty($user['profile_photo'])): ?>
                                        <img id="profilePreview" class="profile-photo rounded-circle"
                                            src="uploads/profile_photos/<?php echo htmlspecialchars($user['profile_photo']); ?>"
                                            alt="Profile Photo">
                                    <?php else: ?>
                                        <img id="profilePreview" class="profile-photo rounded-circle"
                                            src="https://via.placeholder.com/150" alt="Default Profile Photo">
                                    <?php endif; ?>
                                    <label for="profile_photo" class="change-photo-overlay">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                </div>
                                <h4 class="mb-2">Hello,<b>
                                        <?php echo htmlspecialchars($user['full_name'] ?? "John Doe"); ?>!</b>
                                </h4>
                                <p>@<?php echo htmlspecialchars($user['username'] ?? 'johndoe'); ?></p>
                                <p class="small text-muted">Welcome to your dashboard</p>
                            </div>
                        </div>

                        <!-- Office Name Card -->
                        <div class="card shadow-sm w-100">
                            <div class="card-body text-center">
                                <h6 class="card-title">Office Name</h6>
                                <input type="text" class="form-control text-center mx-auto" style="max-width: 300px;"
                                    value="<?php echo htmlspecialchars($user['office_name'] ?? 'My Office'); ?>"
                                    readonly>
                            </div>
                        </div>

                        <!-- Hidden File Input -->
                        <input type="file" class="d-none" id="profile_photo" name="profile_photo" accept="image/*">
                    </div>

                    <!-- Right Column: Scrollable -->
                    <div class="col-md-8 scrollable-right">
                        <h5 class="mb-3">Edit Information Details</h5>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" class="form-control" id="username" name="username" readonly
                                value="<?php echo htmlspecialchars($user['username'] ?? 'johndoe'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name:</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required
                                value="<?php echo htmlspecialchars($user['full_name'] ?? 'John Doe'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                value="<?php echo htmlspecialchars($user['email'] ?? 'john@example.com'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number:</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" required
                                value="<?php echo htmlspecialchars($user['contact_number'] ?? '1234567890'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password:</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="form-text">Leave blank to keep your current password.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password:</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        <!-- Hidden input for cropped image data -->
                        <input type="hidden" name="cropped_image" id="cropped_image">

                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Enhanced Crop Modal (Large, responsive cropping area) -->
    <div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="cropModalLabel">Crop Your Profile Photo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body crop-modal-body">
                    <p class="text-muted">
                        Please adjust the cropping area to select the part of the image you want to keep.
                        Use the handles to resize and move the crop box.
                    </p>
                    <div class="img-container">
                        <img id="cropImage" src="" alt="Crop Image">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="resetCrop" class="btn btn-warning">Reset Crop</button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="cropBtn" class="btn btn-primary">Crop</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Required JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-bmbxuPwQa2lc/F7Xr+6Md5E/zV9ZZKdF1jyyF2GCFJHuL8G/7E7c0wK53J3weM"
        crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        var cropper;

        // Listen for file selection on the hidden input
        document.getElementById('profile_photo').addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (!file) return;

            // Destroy any existing Cropper instance
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                var cropImage = document.getElementById('cropImage');
                cropImage.src = event.target.result;

                // Wait until the image is fully loaded before initializing the cropper
                cropImage.onload = function () {
                    // Clear the onload event to avoid re-triggering
                    cropImage.onload = null;

                    // Show crop modal
                    var cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
                    cropModal.show();

                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        autoCropArea: 1,
                        movable: true, // Allow moving the crop box
                        zoomable: true,
                        rotatable: true,
                        scalable: true,
                        cropBoxResizable: true,
                        dragMode: 'move', // Change to 'move' to allow dragging
                        responsive: true,
                        minContainerWidth: Math.floor(window.innerWidth * 0.9),
                        minContainerHeight: Math.floor(window.innerHeight * 0.8)
                    });

                };
            };
            reader.readAsDataURL(file);
        });

        // Crop button: Crop image and update the preview
        document.getElementById('cropBtn').addEventListener('click', function () {
            if (cropper) {
                var canvas = cropper.getCroppedCanvas({
                    width: 150,
                    height: 150,
                    imageSmoothingQuality: 'high'
                });
                canvas.toBlob(function (blob) {
                    var url = URL.createObjectURL(blob);
                    document.getElementById('profilePreview').src = url;
                    document.getElementById('cropped_image').value = canvas.toDataURL('image/png');
                }, 'image/png');

                cropper.destroy();
                cropper = null;
                var cropModalEl = document.getElementById('cropModal');
                var cropModal = bootstrap.Modal.getInstance(cropModalEl);
                cropModal.hide();
                // Reset file input to allow re-upload if needed
                document.getElementById('profile_photo').value = '';
            }
        });

        // Reset button: Resets the cropper area
        document.getElementById('resetCrop').addEventListener('click', function () {
            if (cropper) {
                cropper.reset();
            }
        });

        // Optional: Listen for window resize and reset the cropper
        window.addEventListener('resize', function () {
            if (cropper) {
                cropper.reset();
            }
        });
    </script>
</body>

</html>