<?php 
include('partial-front/header.php');

// Get category from URL parameter
$category_slug = isset($_GET['category']) ? $_GET['category'] : '';

// Fetch category details
$category_query = "SELECT * FROM categories WHERE LOWER(REPLACE(category_name, ' ', '-')) = '$category_slug' AND parent_category_id IS NULL";
$category_result = mysqli_query($conn, $category_query);
$category = mysqli_fetch_assoc($category_result);

if (!$category) {
    die("Category not found!");
}

$category_id = $category['category_id'];
$category_title = $category['category_name'];
?>

<div class="category-container">
    <!-- Back button -->
    <div class="back-navigation">
        <button onclick="history.back()" class="back-btn">← Back to Categories</button>
        <h2 id="category-title"><?php echo $category_title; ?> Brands</h2>
    </div>

    <!-- Brand filter buttons -->
    <div class="filterable-buttons" id="brand-buttons">
        <button class="active" data-name="all">Show all</button>
        <?php
        // Fetch brands/subcategories for this category
        $brand_query = "SELECT * FROM categories WHERE parent_category_id = $category_id AND is_active = 'Yes'";
        $brand_result = mysqli_query($conn, $brand_query);
        
        while ($brand = mysqli_fetch_assoc($brand_result)) {
            $brand_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $brand['category_name']));
            echo '<button data-name="' . $brand_slug . '">' . $brand['category_name'] . '</button>';
        }
        ?>
    </div>

    <!-- Brand items cards -->
    <div class="filterable-cards" id="brand-cards">
        <?php
        // Fetch products for this category and its subcategories
        $product_query = "SELECT p.*, c.category_name as brand_name 
                         FROM products p 
                         JOIN categories c ON p.category_id = c.category_id 
                         WHERE c.parent_category_id = $category_id 
                         OR p.category_id = $category_id
                         ORDER BY c.category_name, p.product_name";
        $product_result = mysqli_query($conn, $product_query);
        
        while ($product = mysqli_fetch_assoc($product_result)) {
            $brand_slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $product['brand_name']));
            $image_path = !empty($product['image_name']) ? "uploads/products/" . $product['image_name'] : "images/placeholder.jpg";
            
            echo '
            <div class="card" data-name="' . $brand_slug . '">
                <img src="' . $image_path . '" alt="' . $product['product_name'] . '" />
                <div class="card_body">
                    <h6 class="card_title">' . $product['product_name'] . '</h6>
                    <a href="products.php?brand=' . $brand_slug . '" class="view-btn">View Products</a>
                </div>
            </div>';
        }
        
        // Close database connection
        mysqli_close($conn);
        ?>
    </div>
</div>

<script src="script.js" defer></script>
<script>
// Initialize brand filtering functionality
function initializeBrandFiltering() {
    const filterButtons = document.querySelectorAll(".filterable-buttons button");
    const filterableCards = document.querySelectorAll(".filterable-cards .card");
    
    const filterCards = e => {
        // Remove active class from previous button
        document.querySelector(".filterable-buttons .active").classList.remove("active");
        // Add active class to clicked button
        e.target.classList.add("active");
        
        const filterName = e.target.dataset.name;
        
        // Filter cards
        filterableCards.forEach(card => {
            if (filterName === "all" || card.dataset.name === filterName) {
                card.classList.remove("hide");
            } else {
                card.classList.add("hide");
            }
        });
    };
    
    // Add event listeners to filter buttons
    filterButtons.forEach(button => button.addEventListener("click", filterCards));
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', initializeBrandFiltering);
</script>