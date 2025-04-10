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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cart_id']) || !isset($_POST['change'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    $cart_id = intval($_POST['cart_id']);
    $change = intval($_POST['change']);
    
    // First get current quantity
    $stmt = $conn->prepare("SELECT quantity FROM cart WHERE cart_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit();
    }
    
    $row = $result->fetch_assoc();
    $current_quantity = $row['quantity'];
    $new_quantity = $current_quantity + $change;
    
    // Enforce min/max limits
    if ($new_quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Minimum quantity is 1']);
        exit();
    }
    
    if ($new_quantity > 10) {
        echo json_encode(['success' => false, 'message' => 'Maximum quantity is 10']);
        exit();
    }
    
    // Update quantity
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
    $stmt->bind_param("iii", $new_quantity, $cart_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Quantity updated successfully',
            'new_quantity' => $new_quantity
        ]);
    } else {
        throw new Exception('Error updating quantity');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?> 