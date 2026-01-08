<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('config/constants.php');

header('Content-Type: application/json');

// ✅ Always use only customer_id
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage cart']);
    exit;
}

$customer_id = intval($_SESSION['customer_id']);

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $product_id = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        
        if ($customer_id <= 0 || $product_id <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart data']);
            exit;
        }

        // Get current stock information
        $stock_query = "SELECT * FROM stock WHERE product_id = $product_id";
        $stock_result = mysqli_query($conn, $stock_query);
        
        if (!$stock_result || mysqli_num_rows($stock_result) === 0) {
            echo json_encode(['success' => false, 'message' => 'Product not available in stock']);
            exit;
        }
        
        $stock = mysqli_fetch_assoc($stock_result);
        
        // Calculate actual remaining stock
        $actual_remaining = $stock['total_quantity'] - $stock['sold_quantity'] - $stock['reserved_quantity'];
        
        if ($actual_remaining < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available. Only ' . $actual_remaining . ' left']);
            exit;
        }
        
        // Check if already in cart
        $existing_cart = mysqli_query($conn, "SELECT * FROM shopping_cart WHERE customer_id = $customer_id AND product_id = $product_id");
        
        if (mysqli_num_rows($existing_cart) > 0) {
            // Update existing cart item
            $existing_item = mysqli_fetch_assoc($existing_cart);
            $old_quantity = $existing_item['quantity'];
            $new_quantity = $old_quantity + $quantity;
            
            // Check if we have enough stock for the additional quantity
            if ($actual_remaining < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more. Only ' . $actual_remaining . ' available in stock']);
                exit;
            }
            
            // Update cart
            $update_query = "UPDATE shopping_cart SET quantity = $new_quantity WHERE customer_id = $customer_id AND product_id = $product_id";
            $result = mysqli_query($conn, $update_query);
            
            if ($result && mysqli_affected_rows($conn) >= 0) {
                // Reserve the additional quantity
                $reserve_stock = "UPDATE stock SET reserved_quantity = reserved_quantity + $quantity WHERE product_id = $product_id";
                mysqli_query($conn, $reserve_stock);
                
                // Update remaining quantity
                $update_remaining = "UPDATE stock SET remaining_quantity = total_quantity - sold_quantity - reserved_quantity WHERE product_id = $product_id";
                mysqli_query($conn, $update_remaining);
                
                echo json_encode(['success' => true, 'message' => 'Product quantity updated in cart']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
            }
        } else {
            // Insert new cart item
            $insert_query = "INSERT INTO shopping_cart (customer_id, product_id, quantity, added_at) VALUES ($customer_id, $product_id, $quantity, NOW())";
            $result = mysqli_query($conn, $insert_query);
            
            if ($result && mysqli_affected_rows($conn) > 0) {
                // Reserve the stock
                $reserve_stock = "UPDATE stock SET reserved_quantity = reserved_quantity + $quantity WHERE product_id = $product_id";
                if (!mysqli_query($conn, $reserve_stock)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to update stock: ' . mysqli_error($conn)]);
                    exit;
                }
                
                // Update remaining quantity
                $update_remaining = "UPDATE stock SET remaining_quantity = total_quantity - sold_quantity - reserved_quantity WHERE product_id = $product_id";
                mysqli_query($conn, $update_remaining);
                
                echo json_encode(['success' => true, 'message' => 'Product added to cart']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cart insert error: ' . mysqli_error($conn)]);
            }
        }
        break;

    case 'update':
        $product_id = intval($_POST['product_id'] ?? 0);
        $new_quantity = intval($_POST['quantity'] ?? 0);
        
        $current_query = mysqli_query($conn, "SELECT quantity FROM shopping_cart WHERE customer_id = $customer_id AND product_id = $product_id");
        if (!$current_query || mysqli_num_rows($current_query) === 0) {
            echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
            exit;
        }
        
        $current_data = mysqli_fetch_assoc($current_query);
        $current_quantity = $current_data['quantity'];
        
        $stock_query = mysqli_query($conn, "SELECT * FROM stock WHERE product_id = $product_id");
        $stock = mysqli_fetch_assoc($stock_query);
        
        if ($new_quantity <= 0) {
            $delete_query = "DELETE FROM shopping_cart WHERE customer_id = $customer_id AND product_id = $product_id";
            $result = mysqli_query($conn, $delete_query);
            
            if ($result && mysqli_affected_rows($conn) > 0) {
                $release_stock = "UPDATE stock SET reserved_quantity = reserved_quantity - $current_quantity WHERE product_id = $product_id";
                mysqli_query($conn, $release_stock);
                
                $update_remaining = "UPDATE stock SET remaining_quantity = total_quantity - sold_quantity - reserved_quantity WHERE product_id = $product_id";
                mysqli_query($conn, $update_remaining);
                
                echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove item: ' . mysqli_error($conn)]);
            }
        } else {
            $actual_remaining = $stock['total_quantity'] - $stock['sold_quantity'] - $stock['reserved_quantity'] + $current_quantity;
            
            if ($new_quantity > $actual_remaining) {
                echo json_encode(['success' => false, 'message' => 'Only ' . $actual_remaining . ' available in stock']);
                exit;
            }
            
            $update_query = "UPDATE shopping_cart SET quantity = $new_quantity WHERE customer_id = $customer_id AND product_id = $product_id";
            $result = mysqli_query($conn, $update_query);
            
            if ($result && mysqli_affected_rows($conn) >= 0) {
                $quantity_diff = $new_quantity - $current_quantity;
                $update_stock = "UPDATE stock SET reserved_quantity = reserved_quantity + $quantity_diff WHERE product_id = $product_id";
                mysqli_query($conn, $update_stock);
                
                $update_remaining = "UPDATE stock SET remaining_quantity = total_quantity - sold_quantity - reserved_quantity WHERE product_id = $product_id";
                mysqli_query($conn, $update_remaining);
                
                echo json_encode(['success' => true, 'message' => 'Quantity updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update quantity: ' . mysqli_error($conn)]);
            }
        }
        break;

    case 'remove':
        $product_id = intval($_POST['product_id'] ?? 0);
        
        $current_query = mysqli_query($conn, "SELECT quantity FROM shopping_cart WHERE customer_id = $customer_id AND product_id = $product_id");
        if (!$current_query || mysqli_num_rows($current_query) === 0) {
            echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
            exit;
        }
        
        $current_data = mysqli_fetch_assoc($current_query);
        $current_quantity = $current_data['quantity'];
        
        $delete_query = "DELETE FROM shopping_cart WHERE customer_id = $customer_id AND product_id = $product_id";
        $result = mysqli_query($conn, $delete_query);
        
        if ($result && mysqli_affected_rows($conn) > 0) {
            $release_stock = "UPDATE stock SET reserved_quantity = reserved_quantity - $current_quantity WHERE product_id = $product_id";
            mysqli_query($conn, $release_stock);
            
            $update_remaining = "UPDATE stock SET remaining_quantity = total_quantity - sold_quantity - reserved_quantity WHERE product_id = $product_id";
            mysqli_query($conn, $update_remaining);
            
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove item: ' . mysqli_error($conn)]);
        }
        break;

    case 'get':
        $cart_query = "SELECT c.*, p.product_name, p.price, p.image_name
                      FROM shopping_cart c 
                      JOIN products p ON c.product_id = p.product_id 
                      WHERE c.customer_id = $customer_id 
                      ORDER BY c.added_at DESC";
        
        $cart_result = mysqli_query($conn, $cart_query);
        
        if (!$cart_result) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
            exit;
        }
        
        $cart_items = [];
        
        while ($item = mysqli_fetch_assoc($cart_result)) {
            $stock_query = "SELECT * FROM stock WHERE product_id = " . $item['product_id'];
            $stock_result = mysqli_query($conn, $stock_query);
            $stock = mysqli_fetch_assoc($stock_result);
            
            $available_stock = $stock['total_quantity'] - $stock['sold_quantity'] - $stock['reserved_quantity'] + $item['quantity'];
            
            $cart_items[] = [
                'cart_id' => $item['cart_id'],
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'max_quantity' => $available_stock,
                'image' => !empty($item['image_name']) ? 'uploads/products/' . $item['image_name'] : 'images/placeholder.jpg'
            ];
        }
        
        echo json_encode(['success' => true, 'items' => $cart_items]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>