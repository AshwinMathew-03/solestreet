<!-- Add this to your product edit/add form -->
<div class="form-section">
    <h3>Product Sizes</h3>
    <div class="sizes-container">
        <table class="sizes-table">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Stock Quantity</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="size-rows">
                <?php 
                // If editing product, fetch existing sizes
                if (isset($product_id) && !empty($product_id)) {
                    $sizesQuery = "SELECT * FROM product_sizes WHERE product_id = ? ORDER BY size";
                    $sizesStmt = $conn->prepare($sizesQuery);
                    $sizesStmt->bind_param("i", $product_id);
                    $sizesStmt->execute();
                    $sizesResult = $sizesStmt->get_result();
                    
                    if ($sizesResult->num_rows > 0) {
                        while ($size = $sizesResult->fetch_assoc()) {
                            ?>
                            <tr>
                                <td>
                                    <input type="text" name="sizes[]" value="<?php echo htmlspecialchars($size['size']); ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="stock_quantities[]" value="<?php echo $size['stock_quantity']; ?>" min="0" required>
                                </td>
                                <td>
                                    <select name="statuses[]">
                                        <option value="available" <?php echo $size['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="out_of_stock" <?php echo $size['status'] == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="remove-size-btn" onclick="removeSize(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        // No sizes yet, show empty row
                        ?>
                        <tr>
                            <td>
                                <input type="text" name="sizes[]" placeholder="e.g. S, M, L, 7, 8, 9" required>
                            </td>
                            <td>
                                <input type="number" name="stock_quantities[]" value="0" min="0" required>
                            </td>
                            <td>
                                <select name="statuses[]">
                                    <option value="available">Available</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="remove-size-btn" onclick="removeSize(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    // New product, show empty row
                    ?>
                    <tr>
                        <td>
                            <input type="text" name="sizes[]" placeholder="e.g. S, M, L, 7, 8, 9" required>
                        </td>
                        <td>
                            <input type="number" name="stock_quantities[]" value="0" min="0" required>
                        </td>
                        <td>
                            <select name="statuses[]">
                                <option value="available">Available</option>
                                <option value="out_of_stock">Out of Stock</option>
                            </select>
                        </td>
                        <td>
                            <button type="button" class="remove-size-btn" onclick="removeSize(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        
        <button type="button" id="add-size-btn" class="btn btn-success">
            <i class="fas fa-plus"></i> Add Another Size
        </button>
    </div>
</div>

<style>
/* Styles for size management */
.sizes-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

.sizes-table th, .sizes-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

.sizes-table th {
    background-color: #f5f5f5;
}

.sizes-table input[type="text"],
.sizes-table input[type="number"],
.sizes-table select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.remove-size-btn {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.remove-size-btn:hover {
    background-color: #c82333;
}

#add-size-btn {
    margin-bottom: 20px;
}
</style>

<script>
// JavaScript to handle adding and removing size rows
document.addEventListener('DOMContentLoaded', function() {
    // Add new size row
    document.getElementById('add-size-btn').addEventListener('click', function() {
        const tbody = document.getElementById('size-rows');
        const newRow = document.createElement('tr');
        
        newRow.innerHTML = `
            <td>
                <input type="text" name="sizes[]" placeholder="e.g. S, M, L, 7, 8, 9" required>
            </td>
            <td>
                <input type="number" name="stock_quantities[]" value="0" min="0" required>
            </td>
            <td>
                <select name="statuses[]">
                    <option value="available">Available</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
            </td>
            <td>
                <button type="button" class="remove-size-btn" onclick="removeSize(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(newRow);
    });
});

// Function to remove a size row
function removeSize(button) {
    const row = button.closest('tr');
    const tbody = document.getElementById('size-rows');
    
    if (tbody.children.length > 1) {
        row.remove();
    } else {
        alert('You must have at least one size for the product.');
    }
}
</script> 