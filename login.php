<?php 
// Start session at the very top
session_start();
include('partial-front/header.php');
include('config/constants.php');

$loginMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_input = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM customers WHERE email='$email'";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) == 1) {
        $row = mysqli_fetch_assoc($res);
        $hashed_password = $row['password'];

        if (password_verify($password_input, $hashed_password)) {
            // ✅ Only use customer_id, no user_id confusion
            $_SESSION['customer_id'] = $row['customer_id'];
            $_SESSION['user_name']   = $row['name'];
            $_SESSION['user_email']  = $row['email'];
            $_SESSION['logged_in']   = true;
            
            // Redirect if needed
            if (isset($_GET['redirect'])) {
                $redirect_url = urldecode($_GET['redirect']);
                header("Location: $redirect_url");
            } else {
                header("Location: index.php");
            }
            exit;
        
        } else {
            $loginMessage = "<span style='color:red;'>Invalid email or password.</span>";
        }
    } else {
        $loginMessage = "<span style='color:red;'>Invalid email or password.</span>";
    }
}

// Show login message if any
if (!empty($loginMessage)) {
    echo "<div style='text-align: center; margin: 20px;'>$loginMessage</div>";
}

// Show redirect message if coming from cart
if (isset($_GET['redirect'])) {
    echo "<div style='text-align: center; margin: 20px; color: blue;'>Please login to add items to your cart</div>";
}
?>

<!-- Login -->
<div class="signup-container">
    <div class="form-box" id="signup-form">
        <form action="" id="form" method="POST">
            <h2>Login</h2>
            <p id="error_messages"></p>
            
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="Enter your email" required><br>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required><br>

            <button type="submit">Login</button>
            <p>Don't have an account? <a href="signup.php">Signup</a></p>
        </form>
    </div>
</div>

<script>
// Add redirect parameter to form action to preserve it
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const redirect = urlParams.get('redirect');
    
    if (redirect) {
        const form = document.getElementById('form');
        form.action = 'login.php?redirect=' + encodeURIComponent(redirect);
    }
});
</script>

</body>
</html>
