<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to add items to your cart',
        'redirect' => 'login/login.html'
    ]);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Include database connection
include 'database/connect.php';

// Get user ID
$userId = $_SESSION['user_id'];

// Get product ID and quantity from request
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Validate inputs
if ($productId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]);
    exit();
}

if ($quantity <= 0) {
    $quantity = 1;
}

try {
    // Check if product exists
    $productSql = "SELECT * FROM products WHERE id = ?";
    $productStmt = $conn->prepare($productSql);
    $productStmt->bind_param("i", $productId);
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    
    if ($productResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit();
    }
    
    // Check if product is already in cart
    $checkSql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $userId, $productId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Product is already in cart, update quantity
        $cartItem = $checkResult->fetch_assoc();
        $newQuantity = $cartItem['quantity'] + $quantity;
        
        $updateSql = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ii", $newQuantity, $cartItem['cart_id']);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Cart updated successfully',
                'quantity' => $newQuantity
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update cart'
            ]);
        }
    } else {
        // Product is not in cart, add it
        $insertSql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iii", $userId, $productId, $quantity);
        $insertStmt->execute();
        
        if ($insertStmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart',
                'quantity' => $quantity
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add product to cart'
            ]);
        }
    }
} catch (Exception $e) {
    error_log("Error adding to cart: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?> 