<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

// Initialize message variables
$message = '';
$messageType = '';

// Handle incoming messages from redirects
if(isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Initialize user variable to prevent undefined variable error
$user = [];

// Fetch user details
try {
    $sql = "SELECT * FROM user WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $user = $result->fetch_assoc();
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $message = "An error occurred while fetching user details";
    $messageType = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - Solestreet</title>
    <link rel="stylesheet" href="profile.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if($message): ?>
    <div class="message <?php echo htmlspecialchars($messageType); ?>">
        <?php echo htmlspecialchars($message); ?>
        <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
    </div>
    <?php endif; ?>
    
    <nav>
        <div class="logo">Solestreet</div>
        <div class="nav-links">
            <a href="userdashboard.php">Home</a>
            <a href="#">Footwear</a>
            <a href="#">Shop</a>
            <a href="#">Contact</a>
        </div>
        <div class="account-section">
            <img src="<?php echo isset($user['profile_image']) && !empty($user['profile_image']) ? '../uploads/profile_images/' . htmlspecialchars($user['profile_image']) : 'https://images.unsplash.com/photo-1499996860823-5214fcc65f8f?q=80&w=1966&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'; ?>" 
                 style="border-radius: 50%;" 
                 height="40px" 
                 width="40px" 
                 alt="Profile">
            <p><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?></p>
            <a href="cart.php" class="cart-btn">🛒</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <h1>Your Profile</h1>
            <a href="edit_profile.php" class="edit-profile-btn">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
        </div>

        <div class="profile-content">
            <div class="profile-card">
                <div class="profile-image">
                    <img src="<?php echo isset($user['profile_image']) && !empty($user['profile_image']) ? '../uploads/profile_images/' . htmlspecialchars($user['profile_image']) : 'https://images.unsplash.com/photo-1499996860823-5214fcc65f8f?q=80&w=1966&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'; ?>" alt="Profile Picture">
                </div>
                <div class="profile-info">
                    <h2><?php echo isset($user['name']) ? htmlspecialchars($user['name']) : 'N/A'; ?></h2>
                    <p class="email"><i class="fas fa-envelope"></i> <?php echo isset($user['email']) ? htmlspecialchars($user['email']) : 'N/A'; ?></p>
                    <p class="joined"><i class="fas fa-calendar-alt"></i> Joined: <?php echo isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                </div>
            </div>

            <div class="profile-details">
                <div class="details-section">
                    <h3>Personal Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Full Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not provided'; ?></span>
                    </div>
                </div>

                <div class="details-section">
                    <h3>Shipping Address</h3>
                    <?php if (!empty($user['address'])): ?>
                        <p class="address"><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
                    <?php else: ?>
                        <p class="no-address">No shipping address provided</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-actions">
                <a href="order_history.php" class="action-btn">
                    <i class="fas fa-history"></i>
                    Order History
                </a>
                <a href="cart.php" class="action-btn">
                    <i class="fas fa-shopping-cart"></i>
                    View Cart
                </a>
                <a href="../login/forgot-password.php" class="action-btn" id="change-password-btn">
                    <i class="fas fa-lock"></i>
                    Change Password
                </a>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="password-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Change Password</h2>
            <form id="change-password-form">
                <div class="form-group">
                    <label for="current-password">Current Password</label>
                    <input type="password" id="current-password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm New Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" required>
                </div>
                <button type="submit" class="submit-btn">Update Password</button>
            </form>
        </div>
    </div>

    <script>
        // Auto-hide message after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const message = document.querySelector('.message');
            if(message) {
                setTimeout(() => {
                    message.style.display = 'none';
                }, 5000);
            }

            // Modal functionality
            const modal = document.getElementById('password-modal');
            const btn = document.getElementById('change-password-btn');
            const span = document.getElementsByClassName('close')[0];

            btn.onclick = function() {
                modal.style.display = 'block';
            }

            span.onclick = function() {
                modal.style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }

            // Password change form submission
            const passwordForm = document.getElementById('change-password-form');
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const currentPassword = document.getElementById('current-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                if (newPassword !== confirmPassword) {
                    alert('New passwords do not match');
                    return;
                }
                
                fetch('change_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `