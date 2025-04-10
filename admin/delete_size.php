<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../database/connect.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $size_id = $_GET['id'];
    $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
    
    $deleteQuery = "DELETE FROM product_sizes WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $size_id);
    
    if ($deleteStmt->execute()) {
        $_SESSION['message'] = "Size deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error deleting size: " . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }
    
    // Redirect back to manage sizes
    if (!empty($product_id)) {
        header("Location: manage_sizes.php?product_id=$product_id");
    } else {
        header("Location: manage_sizes.php");
    }
    exit();
} else {
    $_SESSION['message'] = "Invalid size ID";
    $_SESSION['message_type'] = 'danger';
    header("Location: manage_sizes.php");
    exit();
}
?> 