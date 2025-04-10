<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php?message=Invalid request&type=error");
    exit();
}

// Get form data
$orderId = $_POST['order_id'] ?? '';
$totalAmount = $_POST['total_amount'] ?? 0;
$paymentMethod = $_POST['payment_method'] ?? 'cod';
$paymentId = $_POST['razorpay_payment_id'] ?? null;

// Customer information
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$zip = $_POST['zip'] ?? '';

// Validate required fields
if (empty($orderId) || empty($totalAmount) || empty($name) || empty($email) || empty($phone) || empty($address)) {
    header("Location: checkout.php?message=Please fill in all required fields&type=error");
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // 1. Create order in orders table
    $orderStatus = ($paymentMethod === 'cod') ? 'pending' : 'paid';
    $fullAddress = $address . ', ' . $city . ', ' . $state . ' - ' . $zip;
    
    $orderSql = "INSERT INTO orders (order_id, user_id, name, email, phone, address, total_amount, payment_method, payment_id, status, order_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param("sisssdsss", $orderId, $_SESSION['user_id'], $name, $email, $phone, $fullAddress, $totalAmount, $paymentMethod, $paymentId, $orderStatus);
    $orderStmt->execute();
    
    // 2. Get cart items
    $cartSql = "SELECT c.product_id, c.quantity, p.price, p.name 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?";
    
    $cartStmt = $conn->prepare($cartSql);
    $cartStmt->bind_param("i", $_SESSION['user_id']);
    $cartStmt->execute();
    $cartItems = $cartStmt->get_result();
    
    // 3. Create order items
    $orderItemSql = "INSERT INTO order_items (order_id, product_id, quantity, price, product_name) VALUES (?, ?, ?, ?, ?)";
    $orderItemStmt = $conn->prepare($orderItemSql);
    
    while ($item = $cartItems->fetch_assoc()) {
        $orderItemStmt->bind_param("siids", $orderId, $item['product_id'], $item['quantity'], $item['price'], $item['name']);
        $orderItemStmt->execute();
    }
    
    // 4. Clear the user's cart
    $clearCartSql = "DELETE FROM cart WHERE user_id = ?";
    $clearCartStmt = $conn->prepare($clearCartSql);
    $clearCartStmt->bind_param("i", $_SESSION['user_id']);
    $clearCartStmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Redirect to order confirmation page
    header("Location: order_confirmation.php?order_id=" . urlencode($orderId));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Order processing error: " . $e->getMessage());
    header("Location: checkout.php?message=Error processing order: " . urlencode($e->getMessage()) . "&type=error");
    exit();
}