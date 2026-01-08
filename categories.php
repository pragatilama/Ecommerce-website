<?php 
include('partial-front/header.php');

?>      

<div class="category-container">         
    <!-- Filter buttons -->          
    <div class="filterable-buttons">             
        <button class="active" data-name="all">Show all</button>             
        <?php
        // Fetch only main categories (where parent_category_id IS NULL)
        $category_query = "SELECT * FROM categories WHERE parent_category_id IS NULL AND is_active = 'yes'";
        $category_result = mysqli_query($conn, $category_query);
        
        while ($category = mysqli_fetch_assoc($category_result)) {
            // Create a slug for the data-name attribute (remove spaces, special chars)
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $category['category_name']));
            echo '<button data-name="' . $slug . '">' . $category['category_name'] . '</button>';
        }
        ?>
    </div>           
    
    <!-- Images cards -->         
    <div class="filterable-cards">              
        <?php
        // Fetch main categories again for display
        $category_result = mysqli_query($conn, "SELECT * FROM categories WHERE parent_category_id IS NULL AND is_active = 'Yes'");
        
        while ($category = mysqli_fetch_assoc($category_result)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $category['category_name']));
            $image_path = !empty($category['image_name']) ? "uploads/" . $category['image_name'] : "images/default-category.jpg";
            
            echo '
            <div class="card clickable-card" data-name="' . $slug . '" data-category="' . $slug . '">                 
                <img src="' . $image_path . '" alt="' . $category['category_name'] . '" />                 
                <div class="card_body">                     
                    <h6 class="card_title">' . $category['category_name'] . '</h6>                 
                </div>             
            </div>';
        }
        
        // Close database connection
        mysqli_close($conn);
        ?>
    </div>     
</div>  

<script src="script.js" defer></script>