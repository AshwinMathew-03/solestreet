<?php
session_start();
include '../database/connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    $cart_id = intval($_POST['cart_id']);
    
    // Delete the cart item
    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Item removed successfully'
        ]);
    } else {
        throw new Exception('Item not found or already removed');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 