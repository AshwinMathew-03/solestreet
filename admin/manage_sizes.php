<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../database/connect.php';

$message = '';
$messageType = '';

// Handle size update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sizes'])) {
    $product_id = $_POST['product_id'];
    $size_ids = $_POST['size_ids'] ?? [];
    $sizes = $_POST['sizes'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $statuses = $_POST['statuses'] ?? [];
    
    // Update existing sizes
    $updateSuccess = true;
    if (!empty($size_ids)) {
        $updateQuery = "UPDATE product_sizes SET size = ?, stock_quantity = ?, status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        
        for ($i = 0; $i < count($size_ids); $i++) {
            $size = $sizes[$i];
            $quantity = $quantities[$i];
            $status = $statuses[$i];
            $size_id = $size_ids[$i];
            
            // If quantity is 0, set status to out_of_stock
            if ($quantity <= 0) {
                $status = 'out_of_stock';
            }
            
            $updateStmt->bind_param("sisi", $size, $quantity, $status, $size_id);
            if (!$updateStmt->execute()) {
                $updateSuccess = false;
                break;
            }
        }
    }
    
    // Insert new sizes
    $new_sizes = $_POST['new_sizes'] ?? [];
    $new_quantities = $_POST['new_quantities'] ?? [];
    $new_statuses = $_POST['new_statuses'] ?? [];
    
    if (!empty($new_sizes)) {
        $insertQuery = "INSERT INTO product_sizes (product_id, size, stock_quantity, status) VALUES (?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        
        for ($i = 0; $i < count($new_sizes); $i++) {
            if (!empty($new_sizes[$i])) {
                $size = $new_sizes[$i];
                $quantity = $new_quantities[$i];
                $status = $new_statuses[$i];
                
                // If quantity is 0, set status to out_of_stock
                if ($quantity <= 0) {
                    $status = 'out_of_stock';
                }
                
                $insertStmt->bind_param("isis", $product_id, $size, $quantity, $status);
                if (!$insertStmt->execute()) {
                    $updateSuccess = false;
                    break;
                }
            }
        }
    }
    
    if ($updateSuccess) {
        $message = "Product sizes updated successfully!";
        $messageType = 'success';
    } else {
        $message = "Error updating sizes: " . $conn->error;
        $messageType = 'danger';
    }
}

// Get all products
$productsQuery = "SELECT id, name FROM products ORDER BY name";
$productsResult = $conn->query($productsQuery);

// If product ID is set, get sizes for that product
$selectedProduct = null;
$productSizes = [];

if (isset($_GET['product_id']) && !empty($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    
    // Get product details
    $productQuery = "SELECT * FROM products WHERE id = ?";
    $productStmt = $conn->prepare($productQuery);
    $productStmt->bind_param("i", $product_id);
    $productStmt->execute();
    $selectedProduct = $productStmt->get_result()->fetch_assoc();
    
    // Get sizes for this product
    $sizesQuery = "SELECT * FROM product_sizes WHERE product_id = ? ORDER BY size";
    $sizesStmt = $conn->prepare($sizesQuery);
    $sizesStmt->bind_param("i", $product_id);
    $sizesStmt->execute();
    $sizesResult = $sizesStmt->get_result();
    
    while ($size = $sizesResult->fetch_assoc()) {
        $productSizes[] = $size;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Product Sizes - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        select, input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        button:hover {
            background-color: #0069d9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .size-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        #new-sizes-container {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .new-size-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Manage Product Sizes</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form method="get" action="">
                <div class="form-group">
                    <label for="product_id">Select Product</label>
                    <select id="product_id" name="product_id" onchange="this.form.submit()">
                        <option value="">-- Select a Product --</option>
                        <?php while($product = $productsResult->fetch_assoc()): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo (isset($_GET['product_id']) && $_GET['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
            
            <?php if ($selectedProduct): ?>
                <h2>Sizes for: <?php echo htmlspecialchars($selectedProduct['name']); ?></h2>
                
                <form method="post" action="">
                    <input type="hidden" name="product_id" value="<?php echo $selectedProduct['id']; ?>">
                    
                    <?php if (count($productSizes) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Size</th>
                                    <th>Stock Quantity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($productSizes as $size): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="size_ids[]" value="<?php echo $size['id']; ?>">
                                            <input type="text" name="sizes[]" value="<?php echo htmlspecialchars($size['size']); ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" name="quantities[]" value="<?php echo $size['stock_quantity']; ?>" min="0" required>
                                        </td>
                                        <td>
                                            <select name="statuses[]">
                                                <option value="available" <?php echo $size['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                                <option value="out_of_stock" <?php echo $size['status'] == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                            </select>
                                        </td>
                                        <td class="size-actions">
                                            <button type="button" class="btn-danger" onclick="deleteSize(<?php echo $size['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No sizes found for this product. Add some below.</p>
                    <?php endif; ?>
                    
                    <div id="new-sizes-container">
                        <h3>Add New Sizes</h3>
                        <div id="new-sizes">
                            <div class="new-size-row">
                                <input type="text" name="new_sizes[]" placeholder="Size (e.g. S, M, L, 7, 8, 9)">
                                <input type="number" name="new_quantities[]" placeholder="Stock Quantity" value="0" min="0">
                                <select name="new_statuses[]">
                                    <option value="available">Available</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                </select>
                                <button type="button" onclick="removeNewSize(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="button" onclick="addNewSize()" style="margin-top: 10px;">
                            <i class="fas fa-plus"></i> Add More Sizes
                        </button>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" name="update_sizes">
                            <i class="fas fa-save"></i> Save All Sizes
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function addNewSize() {
        const container = document.getElementById('new-sizes');
        const newRow = document.createElement('div');
        newRow.className = 'new-size-row';
        
        newRow.innerHTML = `
            <input type="text" name="new_sizes[]" placeholder="Size (e.g. S, M, L, 7, 8, 9)">
            <input type="number" name="new_quantities[]" placeholder="Stock Quantity" value="0" min="0">
            <select name="new_statuses[]">
                <option value="available">Available</option>
                <option value="out_of_stock">Out of Stock</option>
            </select>
            <button type="button" onclick="removeNewSize(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(newRow);
    }
    
    function removeNewSize(button) {
        const row = button.closest('.new-size-row');
        row.remove();
    }
    
    function deleteSize(sizeId) {
        if (confirm('Are you sure you want to delete this size? This action cannot be undone.')) {
            window.location.href = 'delete_size.php?id=' + sizeId + '&product_id=<?php echo isset($_GET['product_id']) ? $_GET['product_id'] : ''; ?>';
        }
    }
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html> 