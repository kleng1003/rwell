<?php
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/RWELL/';
include_once($root_path . 'admin/include/connection.php');

echo "<h2>Test Database Connection</h2>";

// Test connection
if ($con) {
    echo "<p style='color:green'>✓ Database connection successful</p>";
    
    // Test query
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM customers");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>✓ Customers table has " . $row['count'] . " records</p>";
    } else {
        echo "<p style='color:red'>✗ Error querying customers table: " . mysqli_error($con) . "</p>";
    }
    
    // Show table structure
    echo "<h3>Customers Table Structure:</h3>";
    $columns = mysqli_query($con, "DESCRIBE customers");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($col = mysqli_fetch_assoc($columns)) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p style='color:red'>✗ Database connection failed</p>";
}
?>