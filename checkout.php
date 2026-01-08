<?php
session_start();
include('partial-front/header.php');
include('config/constants.php');

// Check if user is logged in
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode('checkout.php'));
    exit;
}

$customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;
$order_processed = false;
$order_details = [];
$order_total = 0;
$error_message = '';

// Process the order when form is submitted or when coming from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['process'])) {
    // Get cart items
    $cart_query = "SELECT c.*, p.product_name, p.price, p.image_name 
                   FROM shopping_cart c 
                   JOIN products p ON c.product_id = p.product_id 
                   WHERE c.customer_id = $customer_id";
    
    $cart_result = mysqli_query($conn, $cart_query);
    
    if ($cart_result && mysqli_num_rows($cart_result) > 0) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            $order_success = true;
            $insufficient_stock_items = [];
            
            // First, validate all items have sufficient stock
            while ($item = mysqli_fetch_assoc($cart_result)) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                
                // Check current stock
                $stock_query = "SELECT * FROM stock WHERE product_id = $product_id";
                $stock_result = mysqli_query($conn, $stock_query);
                $stock = mysqli_fetch_assoc($stock_result);
                
                if (!$stock) {
                    $insufficient_stock_items[] = $item['product_name'] . " (No stock record found)";
                    $order_success = false;
                    continue;
                }
                
                // Calculate actual available stock (total - sold - other reservations + this cart's reservation)
                $other_reservations = $stock['reserved_quantity'] - $quantity;
                $actual_available = $stock['total_quantity'] - $stock['sold_quantity'] - $other_reservations;
                
                if ($quantity > $actual_available) {
                    $insufficient_stock_items[] = $item['product_name'] . " (Only $actual_available available)";
                    $order_success = false;
                }
            }
            
            if (!$order_success) {
                throw new Exception("Insufficient stock for: " . implode(', ', $insufficient_stock_items));
            }
            
            // Reset result pointer and process the order
            mysqli_data_seek($cart_result, 0);
            
            // Get form data for order
            $delivery_address = mysqli_real_escape_string($conn, $_POST['delivery_address'] ?? '');
            $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
            $special_notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
            
            // Process each cart item and create individual order records
            while ($item = mysqli_fetch_assoc($cart_result)) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                $total_price = $price * $quantity;
                $product_name = mysqli_real_escape_string($conn, $item['product_name']);
                
                // Insert order record for this item
                $insert_order = "INSERT INTO orders (customer_id, product_id, product_name, quantity, unit_price, total_price, delivery_address, phone, order_status) 
                                VALUES ($customer_id, $product_id, '$product_name', $quantity, $price, $total_price, '$delivery_address', '$phone', 'confirmed')";
                
                if (!mysqli_query($conn, $insert_order)) {
                    throw new Exception("Failed to create order for product: " . $item['product_name']);
                }
                
                // Update stock - move from reserved to sold
                $update_stock = "UPDATE stock SET 
                                sold_quantity = sold_quantity + $quantity,
                                reserved_quantity = reserved_quantity - $quantity,
                                remaining_quantity = total_quantity - sold_quantity - reserved_quantity
                                WHERE product_id = $product_id";
                
                if (!mysqli_query($conn, $update_stock)) {
                    throw new Exception("Failed to update stock for product ID: $product_id");
                }
                
                // Store order details for display
                $order_details[] = [
                    'product_name' => $item['product_name'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $total_price,
                    'image' => !empty($item['image_name']) ? 'uploads/products/' . $item['image_name'] : 'images/placeholder.jpg'
                ];
                
                $order_total += $total_price;
            }
            
            // Clear the cart
            $clear_cart = "DELETE FROM shopping_cart WHERE customer_id = $customer_id";
            if (!mysqli_query($conn, $clear_cart)) {
                throw new Exception("Failed to clear cart");
            }
            
            // Commit transaction
            mysqli_commit($conn);
            $order_processed = true;
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    } else {
        $error_message = "Your cart is empty";
    }
}

// If no order processed yet, show cart items for confirmation
if (!$order_processed && empty($error_message)) {
    $cart_query = "SELECT c.*, p.product_name, p.price, p.image_name 
                   FROM shopping_cart c 
                   JOIN products p ON c.product_id = p.product_id 
                   WHERE c.customer_id = $customer_id";
    
    $cart_result = mysqli_query($conn, $cart_query);
    
    if ($cart_result && mysqli_num_rows($cart_result) > 0) {
        while ($item = mysqli_fetch_assoc($cart_result)) {
            $total_price = $item['price'] * $item['quantity'];
            $order_details[] = [
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $total_price,
                'image' => !empty($item['image_name']) ? 'uploads/products/' . $item['image_name'] : 'images/placeholder.jpg'
            ];
            $order_total += $total_price;
        }
    } else {
        $error_message = "Your cart is empty";
    }
}
?>

<div class="checkout-container">
    <?php if ($error_message): ?>
        <div class="error-section">
            <h1>Order Error</h1>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <div class="action-buttons">
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            </div>
        </div>
        
    <?php elseif ($order_processed): ?>
        <!-- Order Success -->
        <div class="success-section">
            <h1>Thank You for Your Purchase!</h1>
            <p class="success-message">Your order has been processed successfully.</p>
            
            <div class="order-info">
                <p><strong>Order Date:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
                <p><strong>Status:</strong> Confirmed</p>
            </div>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="order-items">
                    <?php foreach ($order_details as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image" onerror="this.src='images/placeholder.jpg'">
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <p>Quantity: <?php echo $item['quantity']; ?></p>
                                <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                                <p class="item-total">Total: $<?php echo number_format($item['total'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-total">
                    <h3>Total Amount: $<?php echo number_format($order_total, 2); ?></h3>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Order Confirmation -->
        <div class="confirmation-section">
            <h1 style="color:black">Confirm Your Order</h1>
            <p style="color:black">Please review your order before confirming the purchase.</p>
            
            <div class="order-summary">
                <h3>Order Items</h3>
                <div class="order-items">
                    <?php foreach ($order_details as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image" onerror="this.src='images/placeholder.jpg'">
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <p>Quantity: <?php echo $item['quantity']; ?></p>
                                <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                                <p class="item-total">Total: $<?php echo number_format($item['total'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-total">
                    <h3>Total Amount: $<?php echo number_format($order_total, 2); ?></h3>
                </div>
            </div>
            
            <form method="POST" class="checkout-form">
                <div class="customer-info">
                    <h3>Delivery Information</h3>
                    <div class="form-group">
                        <label for="delivery_address">Delivery Address:</label>
                        <textarea id="delivery_address" name="delivery_address" required placeholder="Enter your delivery address..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number">
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-success">Confirm Order</button>
                    <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
.checkout-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.success-section, .error-section, .confirmation-section {
    text-align: center;
    padding: 40px 20px;
}
.success-section h1 {
    color: #28a745;
    font-size: 32px;
    margin-bottom: 15px;
}

.error-section h1 {
    color: #dc3545;
    font-size: 32px;
    margin-bottom: 15px;
}

.confirmation-section h1 {
    color: #333;
    font-size: 32px;
    margin-bottom: 15px;
}

.success-message, .error-message {
    font-size: 18px;
    color: #666;
    margin-bottom: 30px;
}

.order-info {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 15px 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.order-info p {
    margin: 5px 0;
    color: #1565c0;
    font-size: 16px;
}

.order-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 25px;
    margin: 30px 0;
    text-align: left;
}

.order-summary h3 {
    color: #333;
    margin-bottom: 20px;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 10px;
}

.order-items {
    margin-bottom: 20px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #dee2e6;
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 20px;
    border: 2px solid #eee;
}

.item-details h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 18px;
}

.item-details p {
    margin: 4px 0;
    color: #666;
    font-size: 14px;
}

.item-total {
    font-weight: bold;
    color: #007bff;
    font-size: 16px !important;
}

.order-total {
    text-align: right;
    padding-top: 20px;
    border-top: 2px solid black;
}

.order-total h3 {
    color: darkred;
    font-size: 24px;
    margin: 0;
    border: none;
    padding: 0;
}

.customer-info {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    margin: 30px 0;
    text-align: left;
}

.customer-info h3 {
    color: #333;
    margin-bottom: 20px;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #007bff;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 30px;
}

.btn {
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s;
    border: 2px solid transparent;
    cursor: pointer;
    min-width: 150px;
}

.btn-primary {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.btn-primary:hover {
    background: #0056b3;
    border-color: #0056b3;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border-color: #6c757d;
}

.btn-secondary:hover {
    background: #545b62;
    border-color: #545b62;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
    border-color: #28a745;
}

.btn-success:hover {
    background: #1e7e34;
    border-color: #1e7e34;
}

/* Responsive Design */
@media (max-width: 768px) {
    .checkout-container {
        margin: 20px;
        padding: 15px;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .item-image {
        margin: 0 0 15px 0;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .success-section h1,
    .error-section h1,
    .confirmation-section h1 {
        font-size: 24px;
    }
    
    .success-icon,
    .error-icon {
        font-size: 48px;
    }
}

/* Loading animation */
.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.checkout-form button[type="submit"]:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<script>
// Add form submission handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.checkout-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Processing Order <span class="loading"></span>';
        });
    }
});
</script>

<?php
mysqli_close($conn);
?>