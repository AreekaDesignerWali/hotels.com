<?php
// Simple database setup file
require_once 'db.php';

echo "<h2>Database Setup</h2>";

try {
    // Check if hotels table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM hotels");
    $hotel_count = $stmt->fetch()['count'];
    
    if ($hotel_count > 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>✅ Database is already set up with $hotel_count hotels!</div>";
        
        // Show sample hotels
        $stmt = $pdo->query("SELECT name, city, price_per_night FROM hotels LIMIT 5");
        $sample_hotels = $stmt->fetchAll();
        
        echo "<h3>Sample Hotels:</h3><ul>";
        foreach ($sample_hotels as $hotel) {
            echo "<li>" . htmlspecialchars($hotel['name']) . " - " . htmlspecialchars($hotel['city']) . " - $" . $hotel['price_per_night'] . "/night</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; margin: 10px; border-radius: 5px;'>⚠️ No hotels found. The database will be automatically set up when you visit the homepage.</div>";
    }
    
    echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a></p>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>❌ Database Error: " . $e->getMessage() . "</div>";
    echo "<p>Please check your database credentials in db.php file.</p>";
}
?>
