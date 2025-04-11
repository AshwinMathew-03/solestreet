<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

/**
 * Constants and configuration
 */
define('MIN_QUANTITY', 1);
define('MAX_QUANTITY', 10);

/**
 * Security check: Verify user authentication
 * 
 * @return bool True if user is authenticated, false otherwise
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Validate CSRF token to prevent cross-site request forgery
 * 
 * @param string $token The token from the form
 * @return bool True if token is valid, false otherwise
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate product data for cart addition
 * 
 * @param int $productId Product ID
 * @param string $size Product size
 * @param int $quantity Quantity to add
 * @return array Array containing validation status and error messages
 */
function validateCartInput($productId, $size, $quantity) {
    $errors = [];
    
    // Validate product ID
    if (empty($productId) || !is_numeric($productId) || $productId <= 0) {
        $errors[] = 'Invalid product ID';
    }
    
    // Validate size
    if (empty($size)) {
        $errors[] = 'Please select a size';
    }
    
    // Validate quantity
    if (!is_numeric($quantity) || $quantity < MIN_QUANTITY || $quantity > MAX_QUANTITY) {
        $errors[] = 'Invalid quantity. Must be between ' . MIN_QUANTITY . ' and ' . MAX_QUANTITY;
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Check if product with specified size exists in user's cart
 * 
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @param int $productId Product ID
 * @param string $size Product size
 * @return bool True if product exists in cart, false otherwise
 */
function productExistsInCart($conn, $userId, $productId, $size) {
    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND size = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("iis", $userId, $productId, $size);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Add product to user's cart
 * 
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @param int $productId Product ID
 * @param string $size Product size
 * @param int $quantity Quantity to add
 * @return array Result of the operation
 */
function addToCart($conn, $userId, $productId, $size, $quantity) {
    try {
        // First verify if product and size are actually available in inventory
        $stockSql = "SELECT stock_quantity FROM product_sizes 
                     WHERE product_id = ? AND size = ? AND status = 'available'";
        $stockStmt = $conn->prepare($stockSql);
        if (!$stockStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stockStmt->bind_param("is", $productId, $size);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        
        if ($stockResult->num_rows === 0) {
            throw new Exception("Selected size is not available");
        }
        
        $stockData = $stockResult->fetch_assoc();
        if ($quantity > $stockData['stock_quantity']) {
            throw new Exception("Requested quantity exceeds available stock");
        }
        
        $stockStmt->close();
        
        // Add product to cart
        $sql = "INSERT INTO cart (user_id, product_id, quantity, size) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iiis", $userId, $productId, $quantity, $size);
        $success = $stmt->execute();
        
        if (!$success) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        
        return [
            'success' => true,
            'message' => 'Product added to your cart successfully!'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get current cart count for user
 * 
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @return int Number of items in cart
 */
function getCartCount($conn, $userId) {
    try {
        $sql = "SELECT COUNT(*) as total FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return $data['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting cart count: " . $e->getMessage());
        return 0;
    }
}

// Main execution
try {
    // Check if user is logged in
    if (!isAuthenticated()) {
        throw new Exception('You must be logged in');
    }
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token');
    }
    
    // Get and validate input data
    $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $size = isset($_POST['selected_size']) ? trim($_POST['selected_size']) : '';
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
    $userId = $_SESSION['user_id'];
    
    $validation = validateCartInput($productId, $size, $quantity);
    
    if (!$validation['valid']) {
        throw new Exception(implode('. ', $validation['errors']));
    }
    
    // Connect to database
    include '../database/connect.php';
    
    // Check if product already exists in cart
    if (productExistsInCart($conn, $userId, $productId, $size)) {
        echo json_encode([
            'success' => false,
            'message' => 'This product with the selected size is already in your cart',
            'redirect' => 'cart.php'
        ]);
        exit();
    }
    
    // Add product to cart
    $result = addToCart($conn, $userId, $productId, $size, $quantity);
    
    if ($result['success']) {
        $cartCount = getCartCount($conn, $userId);
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'cart_count' => $cartCount
        ]);
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error adding product to cart: ' . $e->getMessage()
    ]);
}
?>
