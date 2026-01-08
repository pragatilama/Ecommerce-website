<?php
include('config/constants.php'); // include your DB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = $_POST['category_name'];
    $parent_category_id = $_POST['parent_category_id'];
    $is_featured = $_POST['is_featured'];
    $is_active = $_POST['is_active'];

    // Check if file was uploaded
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $image_name = $_FILES['image_file']['name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image_name);

        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
            // FIX: Handle NULL for parent_category_id when value is 0
            if ($parent_category_id == 0) {
                $sql = "INSERT INTO categories (category_name, image_name, parent_category_id, is_featured, is_active)
                        VALUES ('$category_name', '$image_name', NULL, '$is_featured', '$is_active')";
            } else {
                $sql = "INSERT INTO categories (category_name, image_name, parent_category_id, is_featured, is_active)
                        VALUES ('$category_name', '$image_name', '$parent_category_id', '$is_featured', '$is_active')";
            }

            if (mysqli_query($conn, $sql)) {
                echo "<p style='color:green;'>Category added successfully!</p>";
            } else {
                echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p style='color:red;'>Image upload failed (could not move file).</p>";
        }
    } else {
        echo "<p style='color:red;'>No image selected or upload error.</p>";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Category</title>
</head>
<body>
    <h2>Add Category</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Category Name:</label>
        <input type="text" name="category_name" required><br><br>

        <label>Image Name:</label>
        <input type="file" name="image_file"><br><br>

        <label>Parent Category:</label>
        <select name="parent_category_id">
            <option value="0">None (Main Category)</option>
            <?php
            // FIX: Also update the query to check for NULL instead of 0
            $result = mysqli_query($conn, "SELECT * FROM categories WHERE parent_category_id IS NULL");
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<option value='{$row['category_id']}'>{$row['category_name']}</option>";
            }
            ?>
        </select><br><br>

        <label>Featured:</label>
        <select name="is_featured">
            <option value="Yes">Yes</option>
            <option value="No" selected>No</option>
        </select><br><br>

        <label>Active:</label>
        <select name="is_active">
            <option value="Yes" selected>Yes</option>
            <option value="No">No</option>
        </select><br><br>

        <button type="submit">Add Category</button>
    </form>


   
</body>
</html>