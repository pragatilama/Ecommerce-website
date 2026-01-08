<?php include('partial-front/header.php');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // secure hash

    $sql = "INSERT INTO customers (name, address, email, password, phone)
            VALUES ('$username', '$address', '$email', '$hashedPassword', '$phone')";

    $res = mysqli_query($conn, $sql);

    if ($res) {
        // Login user immediately
        $_SESSION['user_id'] = mysqli_insert_id($conn);
        $_SESSION['user_name'] = $username;
        $_SESSION['user_email'] = $email;

        // Optional cookie
        setcookie('user_email', $email, time() + (86400 * 7), "/");

        echo "<script>window.location.replace('order.php');</script>";
        echo "<meta http-equiv='refresh' content='0;url=login.php'>";
        header("Location: login.php");
        exit();
    } else {
        echo "<script>alert('Signup failed. Please try again.');</script>";
    }
}
?>
    <!-- Signup -->
    <div class="signup-container">
        <div class="form-box" id="signup-form">
            <form action="" id="form" method="POST">
                <h2>Signup</h2>
                <p id="error_messages"></p>
                <label for="username">Name</label>
                <input type="text" name="username" id="username" placeholder="Enter your fullname"><br>
            
                <label for="address">Address</label>
                <input type="text" name="address" id="address" placeholder="Enter your address"><br>
            
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Enter your email"><br>
            
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Create new password"><br>
            
                <label for="confirm-password">Confirm Password</label>
                <input type="password" name="confirm-password" id= "confirm_password" placeholder="Retype your password"><br>
            
                <label for="phone">Contact</label>
                <input type="text" name="phone" id="phone" placeholder="Enter your Contact"><br>
            
                <button type="submit">Signup</button>
                <p>Already have an account? <a href="login.html">Login</a></p>
            </form>
            
        </div>
        
    </div>

    <script src="script.js" defer></script>

</body>

</html>