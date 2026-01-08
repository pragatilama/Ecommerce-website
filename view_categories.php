<?php include ('config/constants.php'); ?>
<h2>Category List</h2>
    <ul>
        <?php
        function displayCategories($parent_id = null, $conn) {
            // FIX: Handle NULL parent_id correctly
            if ($parent_id === null) {
                $sql = "SELECT * FROM categories WHERE parent_category_id IS NULL";
            } else {
                $sql = "SELECT * FROM categories WHERE parent_category_id = $parent_id";
            }
            
            $res = mysqli_query($conn, $sql);
            
            while ($row = mysqli_fetch_assoc($res)) {
                echo "<li>" . $row['category_name'];

                // Recursive call to display subcategories
                $sub_sql = "SELECT * FROM categories WHERE parent_category_id = {$row['category_id']}";
                $sub_res = mysqli_query($conn, $sub_sql);
                if (mysqli_num_rows($sub_res) > 0) {
                    echo "<ul>";
                    displayCategories($row['category_id'], $conn);
                    echo "</ul>";
                }

                echo "</li>";
            }
        }

        displayCategories(null, $conn);
        ?>
    </ul>


