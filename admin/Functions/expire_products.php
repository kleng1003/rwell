<?php
// expire_products.php - Run this via cron job daily
include_once('include/connection.php');

// Auto-expire products past their expiration date
$expire_query = "UPDATE products 
                 SET status = 'expired' 
                 WHERE expiration_date IS NOT NULL 
                 AND expiration_date <= CURDATE() 
                 AND status != 'expired'";

if (mysqli_query($con, $expire_query)) {
    $affected_rows = mysqli_affected_rows($con);
    if ($affected_rows > 0) {
        // Log the auto-expiration
        $log_message = "Auto-expired {$affected_rows} products that reached expiration date";
        mysqli_query($con, "INSERT INTO activity_logs (user_id, username, role, action, details, created_at) 
                           VALUES (0, 'system', 'admin', 'Auto-expired products', '$log_message', NOW())");
        
        echo date('Y-m-d H:i:s') . " - {$affected_rows} products auto-expired.\n";
    } else {
        echo date('Y-m-d H:i:s') . " - No products to expire.\n";
    }
} else {
    echo date('Y-m-d H:i:s') . " - Error: " . mysqli_error($con) . "\n";
}
?>