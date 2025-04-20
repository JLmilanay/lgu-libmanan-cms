<?php
// updateAccountStatus.php
header('Content-Type: application/json');
include 'db_connection.php'; // Make sure your database connection file is included

$response = ['success' => false, 'message' => 'An error occurred'];

if (isset($_POST['id']) && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    
    // Determine the new status based on the action.
    $newStatus = ($action === 'activate') ? 'active' : 'inactive';
    
    // Prepare the SQL statement.
    if ($stmt = $conn->prepare("UPDATE admins SET status = ? WHERE id = ?")) {
        $stmt->bind_param("si", $newStatus, $id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Account status updated successfully.";
            $response['newStatus'] = $newStatus;
        } else {
            $response['message'] = "Database update error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = "Database error: " . $conn->error;
    }
} else {
    $response['message'] = "Invalid request data.";
}

echo json_encode($response);
?>