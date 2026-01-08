
<?php 
include('partial-front/header.php');
include('config/constants.php');

session_start();
$customer_id = $_SESSION['customer_id'] ?? 1; // fallback for testing

// Fetch wishlist with product + stock info
$sql = "SELECT p.*, s.total_quantity, s.sold_quantity, s.reserved_quantity, s.remaining_quantity 
        FROM wish_list w
        JOIN products p ON w.product_id = p.product_id
        LEFT JOIN stock s ON p.product_id = s.product_id
        WHERE w.customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="category-container">
    <div class="back-navigation">
        <h2 style="color: white;">My Wishlist</h2>
    </div>

    <div class="products-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($product = $result->fetch_assoc()): 
                $image_path = !empty($product['image_name']) 
                                ? "uploads/products/" . $product['image_name'] 
                                : "images/placeholder.jpg";

                $remaining_stock = $product['remaining_quantity'] ?? 0;
                $out_of_stock = $remaining_stock <= 0;
                $low_stock = $remaining_stock > 0 && $remaining_stock <= 5;
            ?>
                <div class="product-card <?php echo $out_of_stock ? 'out-of-stock' : ''; ?>" 
                     data-product-id="<?php echo $product['product_id']; ?>"
                     data-max-quantity="<?php echo $remaining_stock; ?>">
                    
                    <img src="<?php echo $image_path; ?>" 
                         alt="<?php echo $product['product_name']; ?>" 
                         onerror="this.src='images/placeholder.jpg'">
                    
                    <div class="product-info">
                        <h3 class="product-title"><?php echo $product['product_name']; ?></h3>
                        <p class="price">$<?php echo number_format($product['price'], 2); ?></p>

                        <!-- Stock Info -->
                        <div class="stock-info">
                            <?php if ($out_of_stock): ?>
                                <span class="stock-badge out-of-stock-badge">Out of Stock</span>
                            <?php elseif ($low_stock): ?>
                                <span class="stock-badge low-stock-badge">
                                    Low Stock: <?php echo $remaining_stock; ?> left
                                </span>
                            <?php else: ?>
                                <span class="stock-badge in-stock-badge">
                                    In Stock: <?php echo $remaining_stock; ?> available
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="description">
                            <?php echo !empty($product['description']) 
                                ? substr($product['description'], 0, 100) . '...' 
                                : 'No description available'; ?>
                        </p>

                        <div class="product-actions">
                            <?php if ($out_of_stock): ?>
                                <button class="add-to-cart" disabled>Out of Stock</button>
                            <?php else: ?>
                                <button class="add-to-cart" 
                                        onclick="addToCart(this.closest('.product-card'))">
                                    Add to Cart
                                </button>
                            <?php endif; ?>

                           <button class="btn-remove" 
        onclick="window.location.href='wishlist-remove.php?product_id=<?php echo $product['product_id']; ?>'">
    Remove
</button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-products">
                <h3 style="color: white;">No products in wishlist</h3>
                <p style="color: white;">You haven't added any items yet.</p>
            </div>
        <?php endif; ?>
    </div>
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
    border: 1px solid #e0e0e0;
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
    border-bottom: 1px solid #eee;
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
    background: #d4edda;
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
}

.add-to-cart, .wishlist {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.add-to-cart {
    background: #28a745;
    color: white;
    flex: 2;
}

.add-to-cart:hover:not(:disabled) {
    background: #218838;
}

.add-to-cart:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.wishlist {
    background: #ff6b6b;
    color: white;
    flex: 1;
}

.wishlist:hover {
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
    color: red;
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

.btn-remove {
    padding: 10px 10px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
    background: red; 
    color: white;
    text-decoration: none; 
    text-align: center;
    display: inline-block;
    flex: 1; 
}

.btn-remove:hover {
    background: #c82333; 
}

</style>

<script src="script.js" defer></script>