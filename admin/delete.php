<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if product ID is provided
if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    
    // First check if product exists
    $check = $conn->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $check->bind_param("i", $product_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }
    
    // Prepare and execute delete query
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No product was deleted']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Product ID not provided']);
}

$conn->close();
?>
