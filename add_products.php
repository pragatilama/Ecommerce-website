<?php
include('config/constants.php');

// Initialize variables
$product_name = $description = $price = $category_id = $image_name = '';
$success_msg = $error_msg = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    $is_active = isset($_POST['is_active']) ? 'Yes' : 'No';

    // Handle file upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $image_name = $_FILES['image_file']['name'];
        $target_dir = "uploads/products/";
        $target_file = $target_dir . basename($image_name);
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Check if file is an image
        $check = getimagesize($_FILES['image_file']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                // File uploaded successfully
            } else {
                $error_msg = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error_msg = "File is not an image.";
        }
    }

    // Insert into database if no errors
    if (empty($error_msg)) {
        $sql = "INSERT INTO products (product_name, description, price, image_name, category_id, is_active)
                VALUES ('$product_name', '$description', '$price', '$image_name', '$category_id', '$is_active')";

        if (mysqli_query($conn, $sql)) {
            $success_msg = "Product added successfully!";
            // Reset form fields
            $product_name = $description = $price = $category_id = $image_name = '';
        } else {
            $error_msg = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product -ClearCare</title>
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
            max-width: 800px;
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

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            border-color: #007bff;
            outline: none;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        input[type="file"] {
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            background: #f8f9fa;
            width: 100%;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
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

        .btn-back {
            background: #6c757d;
            margin-right: 10px;
        }

        .btn-back:hover {
            background: #5a6268;
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

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Product</h2>

        <?php if (!empty($success_msg)): ?>
            <div class="message success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="message error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="product_name">Product Name *</label>
                <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price ($) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($price); ?>" required>
            </div>

            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php
                    // Fetch only subcategories (brands) that can have products
                    $category_query = "SELECT c.*, p.category_name AS parent_name
                                        FROM categories c
                                        LEFT JOIN categories p ON c.parent_category_id = p.category_id
                                        ORDER BY p.category_name, c.category_name";
                                                                                    
                    $category_result = mysqli_query($conn, $category_query);
                    
                    while ($cat = mysqli_fetch_assoc($category_result)) {
                        $selected = ($category_id == $cat['category_id']) ? 'selected' : '';
                        echo '<option value="' . $cat['category_id'] . '" ' . $selected . '>' . $cat['parent_name'] . ' - ' . $cat['category_name'] . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="image_file">Product Image</label>
                <input type="file" id="image_file" name="image_file" accept="image/*" onchange="previewImage(this)">
                <img id="image_preview" class="preview-image" alt="Image preview">
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="is_active" name="is_active" checked>
                    <label for="is_active">Active Product</label>
                </div>
            </div>

            <div class="form-actions">
                <a href="manage-products.php" class="btn btn-back">← Back to Products</a>
                <button type="submit" class="btn">Add Product</button>
            </div>
        </form>
    </div>

    <script>
        function validateForm() {
            const price = document.getElementById('price').value;
            if (price <= 0) {
                alert('Price must be greater than 0');
                return false;
            }
            return true;
        }

        function previewImage(input) {
            const preview = document.getElementById('image_preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php
// Close database connection
mysqli_close($conn);
?>