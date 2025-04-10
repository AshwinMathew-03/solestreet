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
$user = null; // Initialize user variable
$errors = []; // Array to store validation errors

// Fetch user details before form handling
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validate name (letters, spaces, and some special characters only)
    if (empty($name)) {
        $errors[] = "Full name is required";
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = "Full name must be between 2 and 100 characters";
    } elseif (!preg_match("/^[a-zA-Z\s\-'\.]+$/", $name)) {
        $errors[] = "Full name can only contain letters, spaces, hyphens, apostrophes, and periods";
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    // Validate phone (optional but must be valid if provided)
    if (!empty($phone)) {
        // Remove any non-digit characters for validation
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's exactly 10 digits
        if (strlen($cleanPhone) !== 10) {
            $errors[] = "Phone number must be exactly 10 digits";
        } 
        // Check if it starts with 6, 7, 8, or 9
        elseif (!in_array(substr($cleanPhone, 0, 1), ['6', '7', '8', '9'])) {
            $errors[] = "Phone number must start with 6, 7, 8, or 9";
        }
        // Check for repeated digits (all 10 digits the same)
        elseif (preg_match('/^(\d)\1{9}$/', $cleanPhone)) {
            $errors[] = "Phone number cannot be 10 identical digits";
        }
    }
    
    // Validate address (optional but must be valid if provided)
    if (!empty($address) && (strlen($address) < 5 || strlen($address) > 500)) {
        $errors[] = "Address must be between 5 and 500 characters";
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] != 4) { // 4 means no file was uploaded
        if ($_FILES['profile_image']['error'] != 0) {
            $errors[] = "Error uploading file: " . getFileUploadErrorMessage($_FILES['profile_image']['error']);
        } else {
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $_FILES['profile_image']['tmp_name']);
            finfo_close($file_info);
            
            if (!in_array($mime_type, $allowed_types)) {
                $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed. Detected type: " . $mime_type;
            }
            
            // Validate file extension
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($ext, $allowed_extensions)) {
                $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed. Detected extension: " . $ext;
            }
            
            // Validate file size (5MB max)
            $maxsize = 5 * 1024 * 1024; // 5MB
            if ($_FILES['profile_image']['size'] > $maxsize) {
                $errors[] = "File size must be less than 5MB";
            }
        }
    }
    
    // If no validation errors, proceed with update
    if (empty($errors)) {
        try {
            // Check if email already exists for another user
            $checkEmail = "SELECT id FROM user WHERE email = ? AND id != ?";
            $stmt = $conn->prepare($checkEmail);
            $stmt->bind_param("si", $email, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "Email already in use by another account";
                $messageType = "error";
            } else {
                // Handle profile picture upload
                $profile_image = $user['profile_image']; // Default to current image
                
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    // Create upload directory if it doesn't exist
                    $upload_dir = "../uploads/profile_images/";
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Create unique filename
                    $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    $new_filename = "user_" . $_SESSION['user_id'] . "_" . time() . "." . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    // Upload file
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        $profile_image = $new_filename;
                    } else {
                        throw new Exception("Failed to upload image");
                    }
                }
                
                // Update user profile with or without new image
                $updateSql = "UPDATE user SET name = ?, email = ?, phone = ?, address = ?, profile_image = ? WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("sssssi", $name, $email, $phone, $address, $profile_image, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    // Update session name
                    $_SESSION['name'] = $name;
                    $_SESSION['profile_image'] = $profile_image;
                    
                    $message = "Profile updated successfully";
                    $messageType = "success";
                    
                    // Refresh user data after update
                    $sql = "SELECT * FROM user WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    throw new Exception("Failed to update profile");
                }
            }
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $message = "An error occurred while updating profile: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        // If there are validation errors, set error message
        $message = "Please fix the following errors:<br>" . implode("<br>", $errors);
        $messageType = "error";
    }
}

// Function to get file upload error message
function getFileUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "A PHP extension stopped the file upload";
        default:
            return "Unknown upload error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Solestreet</title>
    <link rel="stylesheet" href="profile.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-image-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .image-upload-container {
            margin-top: 15px;
        }
        
        .custom-file-upload {
            border: 1px solid #ccc;
            display: inline-block;
            padding: 6px 12px;
            cursor: pointer;
            background: #f8f9fa;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .custom-file-upload:hover {
            background: #e9ecef;
        }
        
        #profile_image {
            display: none;
        }
        
        .selected-file {
            margin-top: 8px;
            font-size: 0.9em;
            color: #6c757d;
        }
        
        .error-text {
            color: #dc3545;
            font-size: 0.85em;
            margin-top: 5px;
        }
        
        .form-group.has-error input,
        .form-group.has-error textarea {
            border-color: #dc3545;
        }
        
        .message {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .close-btn {
            cursor: pointer;
            font-size: 1.2em;
        }
        
        /* Add these styles for validation feedback */
        .form-group.has-error input,
        .form-group.has-error textarea {
            border-color: #dc3545;
            background-color: #fff8f8;
        }
        
        .form-group.has-success input,
        .form-group.has-success textarea {
            border-color: #28a745;
            background-color: #f8fff8;
        }
        
        .error-text {
            color: #dc3545;
            font-size: 0.85em;
            margin-top: 5px;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Validation icon indicators */
        .form-group.has-error input,
        .form-group.has-error textarea {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23dc3545' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cline x1='12' y1='8' x2='12' y2='12'%3E%3C/line%3E%3Cline x1='12' y1='16' x2='12.01' y2='16'%3E%3C/line%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px;
        }
        
        .form-group.has-success input,
        .form-group.has-success textarea {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2328a745' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M22 11.08V12a10 10 0 1 1-5.93-9.14'%3E%3C/path%3E%3Cpolyline points='22 4 12 14.01 9 11.01'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px;
        }
    </style>
</head>
<body>
    <?php if($message): ?>
    <div class="message <?php echo htmlspecialchars($messageType); ?>">
        <?php echo $message; ?>
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
            <h1>Edit Profile</h1>
            <a href="profile.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
        </div>

        <div class="profile-content">
            <form class="edit-profile-form" method="POST" action="edit_profile.php" enctype="multipart/form-data" novalidate>
                <div class="form-section">
                    <h3>Profile Picture</h3>
                    
                    <div class="form-group">
                        <div class="profile-upload">
                            <div id="profile_preview" class="profile-preview" style="background-image: url('<?php echo !empty($user['profile_image']) ? "../uploads/profile_images/" . htmlspecialchars($user['profile_image']) : "https://images.unsplash.com/photo-1499996860823-5214fcc65f8f?q=80&w=1966&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"; ?>')"></div>
                            <label for="profile_image" class="upload-btn" style="color:white;">
                                <i class="fas fa-camera"></i> Change Photo
                            </label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif" style="display: none;">
                        </div>
                        <div id="file-name" class="selected-file">No file selected</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Personal Information</h3>
                    
                    <div class="form-group <?php echo in_array('Full name is required', $errors) || in_array('Full name must be between 2 and 100 characters', $errors) || in_array('Full name can only contain letters, spaces, hyphens, apostrophes, and periods', $errors) ? 'has-error' : ''; ?>">
                        <label for="name">Full Name*</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : (isset($user['name']) ? htmlspecialchars($user['name']) : ''); ?>" 
                               required>
                        <?php if(in_array('Full name is required', $errors)): ?>
                            <div class="error-text">Full name is required</div>
                        <?php elseif(in_array('Full name must be between 2 and 100 characters', $errors)): ?>
                            <div class="error-text">Full name must be between 2 and 100 characters</div>
                        <?php elseif(in_array('Full name can only contain letters, spaces, hyphens, apostrophes, and periods', $errors)): ?>
                            <div class="error-text">Full name can only contain letters, spaces, hyphens, apostrophes, and periods</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group <?php echo in_array('Email address is required', $errors) || in_array('Please enter a valid email address', $errors) ? 'has-error' : ''; ?>">
                        <label for="email">Email Address*</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($user['email']) ? htmlspecialchars($user['email']) : ''); ?>" 
                               required>
                        <?php if(in_array('Email address is required', $errors)): ?>
                            <div class="error-text">Email address is required</div>
                        <?php elseif(in_array('Please enter a valid email address', $errors)): ?>
                            <div class="error-text">Please enter a valid email address</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group <?php echo in_array('Please enter a valid phone number', $errors) ? 'has-error' : ''; ?>">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : (isset($user['phone']) ? htmlspecialchars($user['phone']) : ''); ?>">
                        <?php if(in_array('Please enter a valid phone number', $errors)): ?>
                            <div class="error-text">Please enter a valid phone number</div>
                        <?php endif; ?>
                        <!-- <small>Format: +XX-XXX-XXX-XXXX or similar</small> -->
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Shipping Address</h3>
                    
                    <div class="form-group <?php echo in_array('Address must be between 5 and 500 characters', $errors) ? 'has-error' : ''; ?>">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="4"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : (isset($user['address']) ? htmlspecialchars($user['address']) : ''); ?></textarea>
                        <?php if(in_array('Address must be between 5 and 500 characters', $errors)): ?>
                            <div class="error-text">Address must be between 5 and 500 characters</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="save-btn">Save Changes</button>
                    <a href="profile.php" class="cancel-btn">Cancel</a>
                </div>
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
            
            // Show selected filename
            document.getElementById('profile_image').addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : 'No file selected';
                document.getElementById('file-name').textContent = fileName;
                
                // Preview image
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.querySelector('.profile-image').src = e.target.result;
                    }
                    reader.readAsDataURL(this.files[0]);
                    
                    // Validate file type client-side
                    const fileType = this.files[0].type;
                    const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    
                    if (!validImageTypes.includes(fileType)) {
                        alert('Please select a valid image file (JPG, JPEG, PNG, or GIF)');
                        this.value = ''; // Clear the file input
                        document.getElementById('file-name').textContent = 'No file selected';
                    }
                    
                    // Validate file size client-side
                    const fileSize = this.files[0].size;
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if (fileSize > maxSize) {
                        alert('File size must be less than 5MB');
                        this.value = ''; // Clear the file input
                        document.getElementById('file-name').textContent = 'No file selected';
                    }
                }
            });
            
            // Live validation for name field
            document.getElementById('name').addEventListener('input', function() {
                validateName(this);
            });
            
            // Live validation for email field
            document.getElementById('email').addEventListener('input', function() {
                validateEmail(this);
            });
            
            // Live validation for phone field
            document.getElementById('phone').addEventListener('input', function() {
                validatePhone(this);
            });
            
            // Live validation for address field
            document.getElementById('address').addEventListener('input', function() {
                validateAddress(this);
            });
            
            // Client-side validation on form submission
            document.querySelector('.edit-profile-form').addEventListener('submit', function(e) {
                let hasErrors = false;
                
                // Validate all fields
                if (!validateName(document.getElementById('name'))) hasErrors = true;
                if (!validateEmail(document.getElementById('email'))) hasErrors = true;
                if (!validatePhone(document.getElementById('phone'))) hasErrors = true;
                if (!validateAddress(document.getElementById('address'))) hasErrors = true;
                
                if (hasErrors) {
                    e.preventDefault();
                }
            });
            
            // Validation functions
            function validateName(field) {
                const value = field.value.trim();
                const formGroup = field.closest('.form-group');
                
                // Remove existing error
                removeError(formGroup);
                
                // Validate
                if (value === '') {
                    addError(formGroup, 'Full name is required');
                    return false;
                } else if (value.length < 2 || value.length > 100) {
                    addError(formGroup, 'Full name must be between 2 and 100 characters');
                    return false;
                } else if (!/^[a-zA-Z\s\-'\.]+$/.test(value)) {
                    addError(formGroup, 'Full name can only contain letters, spaces, hyphens, apostrophes, and periods');
                    return false;
                }
                
                // Valid
                formGroup.classList.add('has-success');
                return true;
            }
            
            function validateEmail(field) {
                const value = field.value.trim();
                const formGroup = field.closest('.form-group');
                
                // Remove existing error
                removeError(formGroup);
                
                // Validate
                if (value === '') {
                    addError(formGroup, 'Email address is required');
                    return false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    addError(formGroup, 'Please enter a valid email address');
                    return false;
                }
                
                // Valid
                formGroup.classList.add('has-success');
                return true;
            }
            
            function validatePhone(field) {
                const value = field.value.trim();
                const formGroup = field.closest('.form-group');
                
                // Remove existing error
                removeError(formGroup);
                
                if (value !== '') {
                    // Remove any non-digit characters for validation
                    const cleanPhone = value.replace(/[^0-9]/g, '');
                    
                    // Check if it's exactly 10 digits
                    if (cleanPhone.length !== 10) {
                        addError(formGroup, 'Phone number must be exactly 10 digits');
                        return false;
                    } 
                    // Check if it starts with 6, 7, 8, or 9
                    else if (!['6', '7', '8', '9'].includes(cleanPhone.charAt(0))) {
                        addError(formGroup, 'Phone number must start with 6, 7, 8, or 9');
                        return false;
                    }
                    // Check for repeated digits (all 10 digits the same)
                    else if (/^(\d)\1{9}$/.test(cleanPhone)) {
                        addError(formGroup, 'Phone number cannot be 10 identical digits');
                        return false;
                    }
                    
                    // Valid
                    formGroup.classList.add('has-success');
                }
                
                return true;
            }
            
            function validateAddress(field) {
                const value = field.value.trim();
                const formGroup = field.closest('.form-group');
                
                // Remove existing error
                removeError(formGroup);
                
                // Validate (optional)
                if (value !== '' && (value.length < 5 || value.length > 500)) {
                    addError(formGroup, 'Address must be between 5 and 500 characters');
                    return false;
                }
                
                // Valid
                if (value !== '') {
                    formGroup.classList.add('has-success');
                }
                return true;
            }
            
            function addError(formGroup, message) {
                formGroup.classList.remove('has-success');
                formGroup.classList.add('has-error');
                
                // Remove any existing error message
                const existingError = formGroup.querySelector('.error-text');
                if (existingError) {
                    existingError.remove();
                }
                
                // Add new error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-text';
                errorDiv.textContent = message;
                formGroup.appendChild(errorDiv);
            }
            
            function removeError(formGroup) {
                formGroup.classList.remove('has-error');
                formGroup.classList.remove('has-success');
                
                const errorText = formGroup.querySelector('.error-text');
                if (errorText) {
                    errorText.remove();
                }
            }

            // Fix profile picture validation
            const profileImageInput = document.getElementById('profile_image');
            if (profileImageInput) {
                profileImageInput.addEventListener('change', function() {
                    const formGroup = this.closest('.form-group');
                    removeError(formGroup);
                    
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        
                        // Validate file type
                        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                        if (!validTypes.includes(file.type)) {
                            addError(formGroup, 'Only JPG, JPEG, PNG, and GIF files are allowed');
                            this.value = ''; // Clear the input
                            return false;
                        }
                        
                        // Validate file size
                        const maxFileSize = 5 * 1024 * 1024; // 5MB
                        if (file.size > maxFileSize) {
                            addError(formGroup, 'File size must be less than 5MB');
                            this.value = ''; // Clear the input
                            return false;
                        }
                        
                        // Show filename
                        const fileNameElement = document.getElementById('file-name');
                        if (fileNameElement) {
                            fileNameElement.textContent = file.name;
                            fileNameElement.classList.add('selected');
                        }
                        
                        formGroup.classList.add('has-success');
                    }
                });
            }
        });
    </script>
</body>
</html> 