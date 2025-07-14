<?php
// Database configuration
$host = 'localhost';
$dbname = 'dben6wrggdmtyd';
$username = 'uc7ggok7oyoza';
$password = 'gqypavorhbbc';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Test connection by checking if hotels table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'hotels'");
    if ($stmt->rowCount() == 0) {
        // If hotels table doesn't exist, create it and insert sample data
        createTablesAndData($pdo);
    }
    
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "<br>Please check your database credentials in db.php");
}

// Function to create tables and insert sample data
function createTablesAndData($pdo) {
    try {
        // Create hotels table
        $pdo->exec("CREATE TABLE IF NOT EXISTS hotels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            location VARCHAR(255) NOT NULL,
            city VARCHAR(100) NOT NULL,
            country VARCHAR(100) NOT NULL,
            price_per_night DECIMAL(10,2) NOT NULL,
            rating DECIMAL(2,1) DEFAULT 0,
            total_reviews INT DEFAULT 0,
            amenities TEXT,
            image_url VARCHAR(500),
            available_rooms INT DEFAULT 10,
            hotel_type ENUM('luxury', 'business', 'budget', 'resort', 'boutique') DEFAULT 'business',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create bookings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id INT NOT NULL,
            guest_name VARCHAR(255) NOT NULL,
            guest_email VARCHAR(255) NOT NULL,
            guest_phone VARCHAR(20),
            check_in_date DATE NOT NULL,
            check_out_date DATE NOT NULL,
            guests INT NOT NULL,
            rooms INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            booking_status ENUM('confirmed', 'pending', 'cancelled') DEFAULT 'confirmed',
            special_requests TEXT,
            booking_reference VARCHAR(50) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hotel_id) REFERENCES hotels(id)
        )");

        // Create reviews table
        $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id INT NOT NULL,
            guest_name VARCHAR(255) NOT NULL,
            rating INT CHECK (rating >= 1 AND rating <= 5),
            review_text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hotel_id) REFERENCES hotels(id)
        )");

        // Insert sample hotels data
        $hotels = [
            ['Grand Palace Hotel', 'Luxury 5-star hotel in the heart of the city with world-class amenities and exceptional service.', 'Downtown District', 'New York', 'USA', 299.99, 4.8, 1247, 'Free WiFi, Swimming Pool, Spa, Gym, Restaurant, Room Service, Concierge', 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800', 25, 'luxury'],
            ['Business Central Inn', 'Modern business hotel perfect for corporate travelers with meeting facilities and high-speed internet.', 'Business District', 'Chicago', 'USA', 159.99, 4.3, 892, 'Free WiFi, Business Center, Meeting Rooms, Gym, Restaurant, Parking', 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800', 40, 'business'],
            ['Seaside Resort & Spa', 'Beachfront resort offering stunning ocean views, spa services, and recreational activities.', 'Ocean Drive', 'Miami', 'USA', 249.99, 4.6, 2156, 'Beach Access, Spa, Pool, Water Sports, Restaurant, Bar, WiFi', 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800', 60, 'resort'],
            ['Budget Stay Motel', 'Clean and comfortable accommodation at affordable prices, perfect for budget-conscious travelers.', 'Highway 101', 'Las Vegas', 'USA', 79.99, 3.9, 445, 'Free WiFi, Parking, Air Conditioning, Cable TV', 'https://images.unsplash.com/photo-1563911302283-d2bc129e7570?w=800', 30, 'budget'],
            ['Boutique Garden Hotel', 'Charming boutique hotel with unique design, personalized service, and beautiful garden views.', 'Arts Quarter', 'San Francisco', 'USA', 189.99, 4.5, 678, 'Garden View, Free WiFi, Restaurant, Bar, Concierge, Pet Friendly', 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800', 20, 'boutique'],
            ['Metropolitan Luxury', 'Ultra-modern luxury hotel with panoramic city views and premium amenities.', 'Financial District', 'Los Angeles', 'USA', 349.99, 4.9, 1834, 'City View, Spa, Pool, Gym, Fine Dining, Valet Parking, Butler Service', 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800', 35, 'luxury'],
            ['Airport Express Hotel', 'Convenient hotel near the airport with shuttle service and modern facilities.', 'Airport District', 'Denver', 'USA', 129.99, 4.1, 567, 'Airport Shuttle, Free WiFi, Restaurant, Gym, Business Center', 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=800', 50, 'business'],
            ['Mountain View Lodge', 'Rustic lodge with breathtaking mountain views and outdoor activities.', 'Mountain Ridge', 'Aspen', 'USA', 199.99, 4.4, 923, 'Mountain View, Fireplace, Restaurant, Spa, Hiking Trails, WiFi', 'https://images.unsplash.com/photo-1549294413-26f195200c16?w=800', 15, 'resort']
        ];

        $stmt = $pdo->prepare("INSERT INTO hotels (name, description, location, city, country, price_per_night, rating, total_reviews, amenities, image_url, available_rooms, hotel_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($hotels as $hotel) {
            $stmt->execute($hotel);
        }

        // Insert sample reviews
        $reviews = [
            [1, 'John Smith', 5, 'Absolutely amazing experience! The service was impeccable and the room was luxurious.'],
            [1, 'Sarah Johnson', 5, 'Perfect location and beautiful hotel. Will definitely stay here again.'],
            [2, 'Mike Wilson', 4, 'Great for business trips. Clean rooms and excellent WiFi.'],
            [3, 'Emily Davis', 5, 'The beach view was incredible and the spa was so relaxing.'],
            [4, 'Tom Brown', 4, 'Good value for money. Clean and comfortable.'],
            [5, 'Lisa Garcia', 5, 'Charming hotel with unique character. Loved the garden!']
        ];

        $stmt = $pdo->prepare("INSERT INTO reviews (hotel_id, guest_name, rating, review_text) VALUES (?, ?, ?, ?)");
        
        foreach ($reviews as $review) {
            $stmt->execute($review);
        }

        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;'>✅ Database tables created and sample data inserted successfully!</div>";
        
    } catch(PDOException $e) {
        die("Error creating tables: " . $e->getMessage());
    }
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate booking reference
function generateBookingReference() {
    return 'HTL' . strtoupper(substr(uniqid(), -8));
}

// Function to calculate days between dates
function calculateDays($checkin, $checkout) {
    $date1 = new DateTime($checkin);
    $date2 = new DateTime($checkout);
    $interval = $date1->diff($date2);
    return $interval->days;
}

// Test database connection and show status
function testDatabaseConnection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as hotel_count FROM hotels");
        $result = $stmt->fetch();
        return $result['hotel_count'];
    } catch(PDOException $e) {
        return 0;
    }
}
?>
