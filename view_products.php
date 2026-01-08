<?php 
include('partial-front/header.php'); 
?>

<h2 style="text-align:center; color:#ff69b4; margin:30px 0;">Products by Category</h2>

<?php
function displayProductsByCategory($conn) {
    $main_categories_sql = "SELECT * FROM categories WHERE parent_category_id IS NULL";
    $main_categories_result = mysqli_query($conn, $main_categories_sql);

    if (!$main_categories_result) {
        echo "<p style='color:red; text-align:center;'>Error: " . mysqli_error($conn) . "</p>";
        return;
    }

    while ($main_category = mysqli_fetch_assoc($main_categories_result)) {
        echo "<h3 style='color:#c71585; margin-left:20px;'>".$main_category['category_name']."</h3>";

        $subcategories_sql = "SELECT * FROM categories WHERE parent_category_id = ".$main_category['category_id'];
        $subcategories_result = mysqli_query($conn, $subcategories_sql);

        if ($subcategories_result && mysqli_num_rows($subcategories_result) > 0) {
            while ($subcategory = mysqli_fetch_assoc($subcategories_result)) {
                echo "<h4 style='color:#ff69b4; margin-left:40px;'>".$subcategory['category_name']." Products:</h4>";

                $products_sql = "SELECT * FROM products WHERE category_id = ".$subcategory['category_id']." AND is_active='Yes' ORDER BY product_name";
                $products_result = mysqli_query($conn, $products_sql);

                if ($products_result && mysqli_num_rows($products_result) > 0) {
                    echo "<ul style='margin-left:60px;'>";
                    while ($product = mysqli_fetch_assoc($products_result)) {
                        echo "<li style='margin-bottom:8px; color:#333;'>";
                        echo "<strong>".$product['product_name']."</strong> - <span style='color:#ff69b4;'>$".number_format($product['price'],2)."</span>";
                        if(!empty($product['description'])){
                            echo " - ".substr($product['description'],0,50)." ...";
                        }
                        echo "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p style='margin-left:60px; color:#c71585;'>No products found for ".$subcategory['category_name']."</p>";
                }
            }
        } else {
            echo "<p style='margin-left:40px; color:#c71585;'>No subcategories found for ".$main_category['category_name']."</p>";
        }
        echo "<hr style='border-color:#ffc0cb;'>";
    }
}

displayProductsByCategory($conn);
mysqli_close($conn);
?>

<?php include('partial-front/footer.php'); ?>
