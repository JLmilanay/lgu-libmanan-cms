<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Include your database connection
    require_once 'db_connection.php';

    // Retrieve and validate inputs
    $admin_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Basic validation
    if ($admin_id <= 0 || !in_array($action, ['activate', 'disable'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
        exit;
    }

    // Determine the new status based on the action
    $newStatus = ($action === 'activate') ? 'active' : 'inactive';

    // Prepare and execute the update query
    $stmt = $conn->prepare("UPDATE office_admins SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $admin_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database update failed: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>