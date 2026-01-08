<?php 
include('partial-front/header.php');
include('config/constants.php');

// Check if a specific brand is requested
$brand_slug = isset($_GET['brand']) ? $_GET['brand'] : '';

// Build the SQL query based on whether a brand is specified
if (!empty($brand_slug)) {
    // Get brand details
    $brand_query = "SELECT * FROM categories WHERE LOWER(REPLACE(category_name, ' ', '-')) = '$brand_slug'";
    $brand_result = mysqli_query($conn, $brand_query);
    
    if ($brand_result && mysqli_num_rows($brand_result) > 0) {
        $brand = mysqli_fetch_assoc($brand_result);
        $brand_id = $brand['category_id'];
        $brand_name = $brand['category_name'];
        
        // Query for ONLY this brand's products with stock information
        $product_query = "SELECT p.*, s.total_quantity, s.sold_quantity, s.reserved_quantity, s.remaining_quantity 
                         FROM products p 
                         LEFT JOIN stock s ON p.product_id = s.product_id 
                         WHERE p.category_id = $brand_id AND p.is_active = 'Yes' 
                         ORDER BY p.product_name";
        $page_title = $brand_name . " Products";
    } else {
        // Brand not found, show all products instead
        $product_query = "SELECT p.*, s.total_quantity, s.sold_quantity, s.reserved_quantity, s.remaining_quantity 
                         FROM products p 
                         LEFT JOIN stock s ON p.product_id = s.product_id 
                         WHERE p.is_active = 'Yes' 
                         ORDER BY p.category_id, p.product_name";
        $page_title = "All Products";
    }
} else {
    // No brand specified, show ALL products with stock
    $product_query = "SELECT p.*, s.total_quantity, s.sold_quantity, s.reserved_quantity, s.remaining_quantity 
                     FROM products p 
                     LEFT JOIN stock s ON p.product_id = s.product_id 
                     WHERE p.is_active = 'Yes' 
                     ORDER BY p.category_id, p.product_name";
    $page_title = "All Products";
}
?>

<div class="category-container">
    <!-- Back button - Only show if we're viewing a specific brand -->
    <?php if (!empty($brand_slug)): ?>
    <div class="back-navigation">
        <a href="javascript:history.back()" class="back-btn">← Back to Brands</a>
        <h2><?php echo $page_title; ?></h2>
    </div>
    <?php else: ?>
    <div class="back-navigation">
        <h2 style="color: white;"><?php echo $page_title; ?></h2>
    </div>
    <?php endif; ?>

    <!-- Products grid -->
    <div class="products-grid">
        <?php
        $product_result = mysqli_query($conn, $product_query);
        
        if ($product_result && mysqli_num_rows($product_result) > 0) {
            while ($product = mysqli_fetch_assoc($product_result)) {
                $image_path = !empty($product['image_name']) ? "uploads/products/" . $product['image_name'] : "images/placeholder.jpg";
                
                // Stock information
                $remaining_stock = $product['remaining_quantity'] ?? 0;
                $out_of_stock = $remaining_stock <= 0;
                $low_stock = $remaining_stock > 0 && $remaining_stock <= 5;
                ?>
                <div class="product-card <?php echo $out_of_stock ? 'out-of-stock' : ''; ?>" 
                     data-product-id="<?php echo $product['product_id']; ?>"
                     data-max-quantity="<?php echo $remaining_stock; ?>">
                    <img src="<?php echo $image_path; ?>" alt="<?php echo $product['product_name']; ?>" onerror="this.src='images/placeholder.jpg'">
                    <div class="product-info">
                        <h3 class="product-title"><?php echo $product['product_name']; ?></h3>
                        <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                        
                        <!-- Stock Information -->
                        <div class="stock-info">
                            <?php if ($out_of_stock): ?>
                                <span class="stock-badge out-of-stock-badge">Out of Stock</span>
                            <?php elseif ($low_stock): ?>
                                <span class="stock-badge low-stock-badge">Low Stock: <?php echo $remaining_stock; ?> left</span>
                            <?php else: ?>
                                <span class="stock-badge in-stock-badge">In Stock: <?php echo $remaining_stock; ?> available</span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="description"><?php echo !empty($product['description']) ? substr($product['description'], 0, 100) . '...' : 'No description available'; ?></p>
                        
                        <div class="product-actions">
    <?php if ($out_of_stock): ?>
        <button class="add-to-cart" disabled>Out of Stock</button>
    <?php else: ?>
        <button class="add-to-cart" 
                onclick="addToCart(this.closest('.product-card'))">
            Add to Cart
        </button>
    <?php endif; ?>
    
    <button class="wishlist-btn"
            onclick="window.location.href='wishlist.php?product_id=<?php echo $product['product_id']; ?>'">
        ❤Wishlist
    </button>
</div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="no-products">
                    <h3>No products found</h3>
                    <p>There are no products available at the moment.</p>
                  </div>';
        }
        
        mysqli_close($conn);
        ?>
    </div>
</div>

<div class="cart">
    <h2 class="cart-title">Your Cart</h2>
    <div class="cart-content">
        <!-- Cart items will be loaded dynamically -->
    </div>
    <div class="total">
        <div class="total-title">Total</div>
        <div class="total-price">$0</div>
    </div>
    <button class="btn-buy">Buy Now</button>
    <i class="ri-close-line" id="cart-close"></i>
</div>

<style>
.category-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.back-navigation {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.back-navigation h2 {
    color: #333;
    margin: 0;
    font-size: 28px;
}

.back-btn {
    display: inline-block;
    background: #6c757d;
    color: white;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 6px;
    margin-right: 20px;
    transition: background 0.3s;
}

.back-btn:hover {
    background: #5a6268;
    color: white;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.product-card {
    border: 1px solid rgb(236, 189, 189);;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
.product-info{
    background: rgb(236, 189, 189);
}

.product-card.out-of-stock {
    opacity: 0.7;
}

.product-card.out-of-stock::after {
    content: 'Out of Stock';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: bold;
}

.product-card img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}

.product-info {
    padding: 20px;
}

.product-info h3 {
    margin: 0 0 12px 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.price {
    font-size: 22px;
    font-weight: bold;
    color: darkred;
    margin: 0 0 12px 0;
}

.stock-info {
    margin: 10px 0;
}

.stock-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.in-stock-badge {
    background: #b8f9c7ff;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.low-stock-badge {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.out-of-stock-badge {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.description {
    color: #666;
    margin: 0 0 20px 0;
    line-height: 1.5;
    font-size: 14px;
}

.product-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
.product-actions button {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    color: white;
}


/* Specific styles for Add to Cart button */
.add-to-cart {
    background: #28a745;
    flex: 2; /* It takes up 2 parts of the available space */
}

/* Specific styles for Wishlist button */
.wishlist-btn {
    background: red;
    flex: 1; /* It takes up 1 part of the available space */
}

/* Hover and disabled states */
.add-to-cart:hover:not(:disabled) {
    background: #218838;
}

.add-to-cart:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.wishlist-btn:hover {
    background: #ee5a5a;
}

.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-products h3 {
    color: #333;
    margin-bottom: 15px;
}

/* Cart Styles */
.cart {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100vh;
    background: white;
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    transition: right 0.3s ease;
    z-index: 1000;
    padding: 20px;
    overflow-y: auto;
}

.cart.active {
    right: 0;
}

.cart-title {
    margin-bottom: 20px;
    color: #333;
}

.cart-content {
    margin-bottom: 20px;
}

.cart-box {
    display: flex;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
    margin-bottom: 10px;
}

.cart-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    margin-right: 15px;
    border-radius: 4px;
}

.cart-detail {
    flex-grow: 1;
}

.cart-product-title {
    font-size: 14px;
    margin-bottom: 5px;
    color: #333;
}

.cart-price {
    font-weight: bold;
    color: darkred;
    display: block;
    margin-bottom: 8px;
}

.cart-quantity {
    display: flex;
    align-items: center;
    gap: 8px;
}

.cart-quantity button {
    padding: 2px 8px;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
    border-radius: 3px;
}

.cart-quantity .number {
    min-width: 20px;
    text-align: center;
}

.cart-remove {
    color: #dc3545;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: background 0.3s;
}

.cart-remove:hover {
    background: #f8d7da;
}

.total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-top: 2px solid #eee;
    font-size: 18px;
    color: darkred;
    font-weight: bold;
}

.btn-buy {
    width: 100%;
    padding: 15px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-buy:hover {
    background: #218838;
}

#cart-close {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

#cart-close:hover {
    color: #333;
}

</style>

<script src="script.js" defer></script>