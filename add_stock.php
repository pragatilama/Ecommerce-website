<?php
include('config/constants.php');


$message = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $total_quantity = mysqli_real_escape_string($conn, $_POST['total_quantity']);
    
    // Check if stock record already exists
    $check_query = "SELECT * FROM stock WHERE product_id = $product_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing stock
        $update_query = "UPDATE stock 
                        SET total_quantity = $total_quantity,
                            remaining_quantity = $total_quantity - sold_quantity - reserved_quantity
                        WHERE product_id = $product_id";
        
        if (mysqli_query($conn, $update_query)) {
            $message = "Stock updated successfully!";
        } else {
            $error = "Error updating stock: " . mysqli_error($conn);
        }
    } else {
        // Insert new stock record
        $insert_query = "INSERT INTO stock (product_id, total_quantity, sold_quantity, reserved_quantity, remaining_quantity) 
                        VALUES ($product_id, $total_quantity, 0, 0, $total_quantity)";
        
        if (mysqli_query($conn, $insert_query)) {
            $message = "Stock added successfully!";
        } else {
            $error = "Error adding stock: " . mysqli_error($conn);
        }
    }
}

// Get all products for dropdown
$products_query = "SELECT * FROM products WHERE is_active = 'Yes' ORDER BY product_name";
$products_result = mysqli_query($conn, $products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stock - ClearCare</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        select, input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        select:focus, input[type="number"]:focus {
            border-color: #007bff;
            outline: none;
        }

        .btn {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #0056b3;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stock-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }

        .stock-table th, .stock-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .stock-table th {
            background: #007bff;
            color: white;
        }

        .stock-table tr:hover {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .nav-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
        }

        .nav-tab {
            padding: 12px 24px;
            text-decoration: none;
            color: #333;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .nav-tab.active {
            border-bottom-color: #007bff;
            color: #007bff;
            font-weight: bold;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Stock Management</h2>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <a href="#add-stock" class="nav-tab active">Add/Update Stock</a>
            <a href="#view-stock" class="nav-tab">View All Stock</a>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add Stock Tab -->
        <div id="add-stock" class="tab-content active">
            <form method="POST">
                <div class="form-group">
                    <label for="product_id">Select Product</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">Select a Product</option>
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                            <option value="<?php echo $product['product_id']; ?>">
                                <?php echo $product['product_name']; ?> (ID: <?php echo $product['product_id']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="total_quantity">Total Quantity</label>
                    <input type="number" id="total_quantity" name="total_quantity" min="0" required>
                </div>

                <button type="submit" class="btn">Save Stock</button>
            </form>
        </div>

        <!-- View Stock Tab -->
        <div id="view-stock" class="tab-content">
            <h3>Current Stock Levels</h3>
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Total Quantity</th>
                        <th>Sold</th>
                        <th>Reserved</th>
                        <th>Available</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stock_query = "SELECT s.*, p.product_name 
                                   FROM stock s 
                                   JOIN products p ON s.product_id = p.product_id 
                                   ORDER BY p.product_name";
                    $stock_result = mysqli_query($conn, $stock_query);
                    
                    if (mysqli_num_rows($stock_result) > 0) {
                        while ($stock = mysqli_fetch_assoc($stock_result)) {
                            ?>
                            <tr>
                                <td><?php echo $stock['product_id']; ?></td>
                                <td><?php echo $stock['product_name']; ?></td>
                                <td><?php echo $stock['total_quantity']; ?></td>
                                <td><?php echo $stock['sold_quantity']; ?></td>
                                <td><?php echo $stock['reserved_quantity']; ?></td>
                                <td>
                                    <span style="color: <?php echo $stock['remaining_quantity'] > 0 ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                        <?php echo $stock['remaining_quantity']; ?>
                                    </span>
                                </td>
    
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="6" style="text-align: center;">No stock records found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and contents
                document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const target = this.getAttribute('href');
                document.querySelector(target).classList.add('active');
            });
        });

        // Auto-fill existing stock quantity when product is selected
        document.getElementById('product_id').addEventListener('change', function() {
            const productId = this.value;
            if (productId) {
                fetch(`get_stock.php?product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            document.getElementById('total_quantity').value = data.total_quantity;
                        } else {
                            document.getElementById('total_quantity').value = '';
                        }
                    });
            }
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>