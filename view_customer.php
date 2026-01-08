<?php 
include('partial-front/header.php'); 
$sql = "SELECT * FROM customers"; 
$result = mysqli_query($conn, $sql);  
?>  

<div style="margin-top: 30px">
    <h2>All registered Users</h2>
    
    <table border="1">
        <thead>
            <tr>
                <th>S.N.</th>
                <th>Username</th>
                <th>Address</th>
                <th>Email</th>
                <th>Contact</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if($result) {
                // Counting rows to check whether we have data in database
                $count = mysqli_num_rows($result); 
                $sn = 1; // Creating sn variable
                
                if($count > 0) {
                    while($rows = mysqli_fetch_assoc($result)) { 
                        $id = $rows['customer_id'];
                        $username = $rows['name'];
                        $address = $rows['address'];
                        $email = $rows['email'];  
                        $contact = $rows['phone']; 
                        
                        // Displaying the values in table
                        ?>
                        <tr>
                            <td><?php echo $sn++; ?></td>
                            <td><?php echo htmlspecialchars($username); ?></td>
                            <td><?php echo htmlspecialchars($address); ?></td>
                            <td><?php echo htmlspecialchars($email); ?></td>
                            <td><?php echo htmlspecialchars($contact); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No customers found</td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Error: <?php echo mysqli_error($conn); ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
    <button style="width: 200px; margin-top: 15px;"><a href="login.php" style="color: #fff; text-decoration: none;">Back to Login</a></button>
</div>

</body>
</html>