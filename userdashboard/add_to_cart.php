<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Include DB connection
include '../database/connect.php';

// Check if product ID is posted
if (!isset($_POST['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No product ID provided'
    ]);
    exit();
}

$productId = (int) $_POST['product_id'];
$userId = $_SESSION['user_id'];

// Get selected size and quantity
$size = isset($_POST['selected_size']) ? trim($_POST['selected_size']) : '';
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

if (empty($size)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please select a size'
    ]);
    exit();
}

if ($quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid quantity selected'
    ]);
    exit();
}

try {
    // Check if the same product + size already exists in cart
    $checkSql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ? AND size = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("iis", $userId, $productId, $size);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Product with size already in cart - redirect to cart page
        header('Location: cart.php?message=Item already in cart');
        exit();
    }

    // Add new item to cart
    $addSql = "INSERT INTO cart (user_id, product_id, quantity, size) VALUES (?, ?, ?, ?)";
    $addStmt = $conn->prepare($addSql);
    $addStmt->bind_param("iiis", $userId, $productId, $quantity, $size);

    if ($addStmt->execute()) {
        // Get updated cart count
        $countSql = "SELECT COUNT(*) as total FROM cart WHERE user_id = ?";
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("i", $userId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $cartCount = ($countResult->num_rows > 0) ? $countResult->fetch_assoc()['total'] : 0;

        echo json_encode([
            'success' => true,
            'message' => 'Product added to your cart successfully!',
            'cart_count' => $cartCount
        ]);
    } else {
        throw new Exception("Failed to insert into cart: " . $conn->error);
    }

} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Error adding product to cart: ' . $e->getMessage()
    ]);
}
?>
