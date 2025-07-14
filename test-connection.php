<?php
// Test database connection file
echo "<h2>Database Connection Test</h2>";

// Database configuration
$host = 'localhost';
$dbname = 'dben6wrggdmtyd';
$username = 'uc7ggok7oyoza';
$password = 'gqypavorhbbc';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>✅ Database connection successful!</div>";
    
    // Test if hotels table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'hotels'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>✅ Hotels table exists!</div>";
        
        // Count hotels
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM hotels");
        $count = $stmt->fetch()['count'];
        echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px; border-radius: 5px;'>ℹ️ Found $count hotels in database</div>";
        
        if ($count == 0) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; margin: 10px; border-radius: 5px;'>⚠️ Hotels table is empty. Visit <a href='index.php'>homepage</a> to auto-populate with sample data.</div>";
        }
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; margin: 10px; border-radius: 5px;'>⚠️ Hotels table doesn't exist. Visit <a href='index.php'>homepage</a> to create tables automatically.</div>";
    }
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>❌ Connection failed: " . $e->getMessage() . "</div>";
    echo "<p><strong>Common solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Check if your database credentials are correct in db.php</li>";
    echo "<li>Make sure your database server is running</li>";
    echo "<li>Verify the database name exists</li>";
    echo "<li>Check if the user has proper permissions</li>";
    echo "</ul>";
}

echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a></p>";
?>
