<?php
// Start session at the very beginning
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include '../database/database.php';

// Ensure database connection is working
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Initialize message variables
$success_message = $error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    try {
        // Get and sanitize form data
        $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = floatval($_POST['price']);
        $category_id = intval($_POST['category']);

        // Check if category is selected
        if ($category_id == 0) {
            throw new Exception("Please select a valid category.");
        }

        // Check if file is uploaded
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            // File upload handling
            $target_dir = "../uploads/products/";

            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Get file information
            $file = $_FILES['product_image'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_type = mime_content_type($file_tmp);

            // Generate unique filename
            $new_filename = uniqid() . '_' . basename($file_name);
            $target_file = $target_dir . $new_filename;

            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Error: Only JPG, JPEG, PNG & GIF files are allowed.");
            }

            // Move uploaded file
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Prepare SQL statement
                $sql = "INSERT INTO products (product_name, description,price, category_id, product_image) 
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdis", $product_name, $description, $price, $category_id, $new_filename);

                // Execute the statement
                if ($stmt->execute()) {
                    $success_message = "Product added successfully!";
                } else {
                    throw new Exception("Error inserting product: " . $stmt->error);
                }
            } else {
                throw new Exception("Sorry, there was an error uploading your file.");
            }
        } else {
            throw new Exception("Please select a product image.");
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin</title>
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .cancel-btn {
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-left: 10px;
            display: inline-block;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Add New Product</h2>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="product_name">Product Name:</label>
            <input type="text" id="product_name" name="product_name" required>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>
        </div>

        <div class="form-group">
            <label for="price">Price:</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required>
        </div>

        <div class="form-group">
    <label for="category">Category:</label>
    <select id="category" name="category" required>
        <option value="">Select Category</option>
        <?php
        // Ensure database connection is valid
        if (!$conn) {
            die("Database connection failed: " . mysqli_connect_error());
        }

        // Fetch categories from the database
        $sql = "SELECT category_id, category_name FROM categories";
        $result = mysqli_query($conn, $sql);

        // Check if there are categories in the database
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<option value='" . htmlspecialchars($row['category_id']) . "'>" . htmlspecialchars($row['category_name']) . "</option>";
            }
        } else {
            echo "<option value=''>No categories found</option>";
        }

        // Free result set and close the connection
        mysqli_free_result($result);
        ?>
    </select>
</div>


        <div class="form-group">
            <label for="product_image">Product Image:</label>
            <input type="file" id="product_image" name="product_image" accept="image/*" required>
            <small>Allowed formats: JPG, JPEG, PNG, GIF</small>
        </div>

        <div class="button-group">
            <button type="submit" name="submit" class="submit-btn">Add Product</button>
            <a href="admindashboard.php" class="cancel-btn">Cancel</a>
        </div>
    </form>
        <a href="admindashboard.php"> Back</a>
</div>

<script>
    document.getElementById('product_image').onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                let preview = document.getElementById('image-preview');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.id = 'image-preview';
                    preview.style.maxWidth = '200px';
                    preview.style.marginTop = '10px';
                    document.querySelector('.form-group:last-of-type').appendChild(preview);
                }
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    };
</script>

</body>
</html>
