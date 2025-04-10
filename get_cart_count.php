<?php
session_start();
header('Content-Type: application/json');

// Default count
$count = 0;

if (isset($_SESSION['user_id'])) {
    // Include database connection
    include 'database/connect.php';
    
    // Get user ID
    $userId = $_SESSION['user_id'];
    
    try {
        // Get cart count
        $sql = "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $count = intval($row['count']);
        }
    } catch (Exception $e) {
        error_log("Error getting cart count: " . $e->getMessage());
    }
}

echo json_encode(['count' => $count]);
?> 