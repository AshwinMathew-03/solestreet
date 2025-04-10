<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Include database connection
include '../database/connect.php';

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['current_password']) || !isset($_POST['new_password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];

// Basic validation
if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
    exit();
}

try {
    // Get current password hash
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $user = $result->fetch_assoc();
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }
    
    // Hash new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_password_hash, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        throw new Exception("Failed to update password");
    }
    
} catch (Exception $e) {
    error_log("Password update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating password']);
}

$conn->close();
?> 