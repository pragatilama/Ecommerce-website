<?php include('config/constants.php')?>
<!DOCTYPE html> 
<html lang="en">
     <head> 
        <meta charset="UTF-8"> 
        <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
        <link rel="stylesheet" href="styles.css"> 
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
        <title>BeautyBae</title>
     </head> 
     <body> 
        <!-- Navbar --> 
         <nav> 
            <h2 class="logo">BeautyBae</h2> 
            <ul class="nav-links"> 
                <li><a href="index.php">Home</a></li> 
                <li><a href="categories.php">Categories</a></li> 
                <li><a href="products.php">Products</a></li> 
                <li><a href="wishlist-view.php">Wishlist</a></li> 
                <li><a href="login.php">Login</a></li> 
            </ul> 
            <div class="cart-icon">
                <i class="ri-shopping-bag-line"></i>
                <span class="cart-items-count"></span>
            </div>
        </nav>

        