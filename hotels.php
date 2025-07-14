<?php
require_once 'db.php';

// Debug: Show total hotels in database
try {
    $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM hotels");
    $total_hotels = $count_stmt->fetch()['total'];
    
    if ($total_hotels == 0) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>❌ No hotels found in database. Please run the database setup first.</div>";
    }
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>❌ Database Error: " . $e->getMessage() . "</div>";
}

// Get search parameters
$destination = isset($_GET['destination']) ? sanitize_input($_GET['destination']) : '';
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'rating';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$hotel_type = isset($_GET['hotel_type']) ? $_GET['hotel_type'] : '';

// Build query with better error handling
$query = "SELECT * FROM hotels WHERE 1=1";
$params = [];

if (!empty($destination)) {
    $query .= " AND (city LIKE ? OR country LIKE ? OR location LIKE ? OR name LIKE ?)";
    $searchTerm = "%$destination%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($min_price > 0) {
    $query .= " AND price_per_night >= ?";
    $params[] = $min_price;
}

if ($max_price < 1000) {
    $query .= " AND price_per_night <= ?";
    $params[] = $max_price;
}

if (!empty($hotel_type)) {
    $query .= " AND hotel_type = ?";
    $params[] = $hotel_type;
}

// Add sorting
switch($sort) {
    case 'price_low':
        $query .= " ORDER BY price_per_night ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price_per_night DESC";
        break;
    case 'rating':
        $query .= " ORDER BY rating DESC";
        break;
    case 'name':
        $query .= " ORDER BY name ASC";
        break;
    default:
        $query .= " ORDER BY rating DESC";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $hotels = $stmt->fetchAll();
    
    // Debug: Show query results
    if (empty($hotels) && !empty($destination)) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; margin: 10px; border-radius: 5px;'>ℹ️ No hotels found for '$destination'. Try searching for: New York, Chicago, Miami, Las Vegas, San Francisco, Los Angeles, Denver, or Aspen</div>";
    }
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>❌ Search Error: " . $e->getMessage() . "</div>";
    $hotels = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Search Results - Hotels.com Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        /* Search Summary */
        .search-summary {
            background: white;
            padding: 2rem 0;
            border-bottom: 1px solid #e1e5e9;
        }

        .summary-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-info h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .search-details {
            color: #666;
            font-size: 0.9rem;
        }

        /* Filters and Sort */
        .filters-section {
            background: white;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e1e5e9;
        }

        .filters-container {
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #333;
        }

        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .filter-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 1.5rem;
        }

        /* Results Section */
        .results-section {
            padding: 2rem 0;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .results-count {
            font-size: 1.1rem;
            color: #333;
        }

        .sort-dropdown {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            background: white;
        }

        /* Hotel Cards */
        .hotels-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .hotel-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .hotel-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .hotel-card-content {
            display: grid;
            grid-template-columns: 300px 1fr auto;
            gap: 1.5rem;
        }

        .hotel-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .hotel-info {
            padding: 1.5rem 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hotel-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .hotel-location {
            color: #666;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hotel-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stars {
            color: #ffd700;
        }

        .rating-text {
            color: #666;
            font-size: 0.9rem;
        }

        .hotel-amenities {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .hotel-type {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .hotel-pricing {
            padding: 1.5rem;
            text-align: right;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
        }

        .hotel-price {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .price-unit {
            font-size: 0.9rem;
            color: #666;
            font-weight: normal;
        }

        .book-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            margin-top: 1rem;
        }

        .book-btn:hover {
            transform: translateY(-2px);
        }

        .no-results {
            text-align: center;
            padding: 4rem 0;
            color: #666;
        }

        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Footer */
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 4rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hotel-card-content {
                grid-template-columns: 1fr;
            }

            .hotel-image {
                height: 250px;
            }

            .filters-container {
                flex-direction: column;
                align-items: stretch;
            }

            .summary-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .results-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">🏨 Hotels.com</a>
                <nav>
                    <ul class="nav-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="hotels.php">Hotels</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <section class="search-summary">
        <div class="container">
            <div class="summary-content">
                <div class="search-info">
                    <h2>
                        <?php if(!empty($destination)): ?>
                            Hotels in <?php echo htmlspecialchars($destination); ?>
                        <?php else: ?>
                            All Hotels
                        <?php endif; ?>
                    </h2>
                    <div class="search-details">
                        <?php if($checkin && $checkout): ?>
                            <?php echo date('M j', strtotime($checkin)); ?> - <?php echo date('M j, Y', strtotime($checkout)); ?> • 
                        <?php endif; ?>
                        <?php echo $guests; ?> guest<?php echo $guests > 1 ? 's' : ''; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="filters-section">
        <div class="container">
            <form class="filters-container" id="filtersForm">
                <input type="hidden" name="destination" value="<?php echo htmlspecialchars($destination); ?>">
                <input type="hidden" name="checkin" value="<?php echo htmlspecialchars($checkin); ?>">
                <input type="hidden" name="checkout" value="<?php echo htmlspecialchars($checkout); ?>">
                <input type="hidden" name="guests" value="<?php echo $guests; ?>">
                
                <div class="filter-group">
                    <label>Price Range</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="number" name="min_price" placeholder="Min" value="<?php echo $min_price; ?>" min="0" style="width: 80px;">
                        <span>-</span>
                        <input type="number" name="max_price" placeholder="Max" value="<?php echo $max_price == 1000 ? '' : $max_price; ?>" min="0" style="width: 80px;">
                    </div>
                </div>

                <div class="filter-group">
                    <label>Hotel Type</label>
                    <select name="hotel_type">
                        <option value="">All Types</option>
                        <option value="luxury" <?php echo $hotel_type == 'luxury' ? 'selected' : ''; ?>>Luxury</option>
                        <option value="business" <?php echo $hotel_type == 'business' ? 'selected' : ''; ?>>Business</option>
                        <option value="budget" <?php echo $hotel_type == 'budget' ? 'selected' : ''; ?>>Budget</option>
                        <option value="resort" <?php echo $hotel_type == 'resort' ? 'selected' : ''; ?>>Resort</option>
                        <option value="boutique" <?php echo $hotel_type == 'boutique' ? 'selected' : ''; ?>>Boutique</option>
                    </select>
                </div>

                <button type="submit" class="filter-btn">Apply Filters</button>
            </form>
        </div>
    </section>

    <section class="results-section">
        <div class="container">
            <div class="results-header">
                <div class="results-count">
                    <?php echo count($hotels); ?> hotel<?php echo count($hotels) != 1 ? 's' : ''; ?> found
                </div>
                <select class="sort-dropdown" id="sortSelect">
                    <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Best Rated</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                </select>
            </div>

            <div class="hotels-list">
                <?php if(empty($hotels)): ?>
                    <div class="no-results">
                        <h3>No hotels found</h3>
                        <p>Try adjusting your search criteria or filters</p>
                    </div>
                <?php else: ?>
                    <?php foreach($hotels as $hotel): ?>
                    <div class="hotel-card" onclick="viewHotel(<?php echo $hotel['id']; ?>)">
                        <div class="hotel-card-content">
                            <img src="<?php echo htmlspecialchars($hotel['image_url']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" class="hotel-image">
                            
                            <div class="hotel-info">
                                <div>
                                    <span class="hotel-type"><?php echo ucfirst($hotel['hotel_type']); ?></span>
                                    <h3 class="hotel-name"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                    <div class="hotel-location">
                                        📍 <?php echo htmlspecialchars($hotel['city'] . ', ' . $hotel['country']); ?>
                                    </div>
                                    <div class="hotel-rating">
                                        <span class="stars">
                                            <?php 
                                            $rating = $hotel['rating'];
                                            for($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '⭐' : '☆';
                                            }
                                            ?>
                                        </span>
                                        <span class="rating-text"><?php echo $hotel['rating']; ?> (<?php echo $hotel['total_reviews']; ?> reviews)</span>
                                    </div>
                                </div>
                                <div class="hotel-amenities">
                                    <?php echo htmlspecialchars(substr($hotel['amenities'], 0, 100)); ?>...
                                </div>
                            </div>

                            <div class="hotel-pricing">
                                <div>
                                    <div class="hotel-price">
                                        $<?php echo number_format($hotel['price_per_night'], 2); ?>
                                        <span class="price-unit">per night</span>
                                    </div>
                                    <?php if($checkin && $checkout): ?>
                                        <?php $days = calculateDays($checkin, $checkout); ?>
                                        <div style="font-size: 0.9rem; color: #666;">
                                            $<?php echo number_format($hotel['price_per_night'] * $days, 2); ?> total
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button class="book-btn" onclick="event.stopPropagation(); bookHotel(<?php echo $hotel['id']; ?>)">
                                    Book Now
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2024 Hotels.com Clone. All rights reserved. | Built with ❤️ for travelers worldwide</p>
        </div>
    </footer>

    <script>
        // Handle sort change
        document.getElementById('sortSelect').addEventListener('change', function() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('sort', this.value);
            window.location.href = currentUrl.toString();
        });

        // Handle filters form
        document.getElementById('filtersForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const currentUrl = new URL(window.location.href);
            
            // Update URL parameters
            for (let [key, value] of formData.entries()) {
                if (value) {
                    currentUrl.searchParams.set(key, value);
                } else {
                    currentUrl.searchParams.delete(key);
                }
            }
            
            window.location.href = currentUrl.toString();
        });

        // View hotel details
        function viewHotel(hotelId) {
            const checkin = '<?php echo $checkin; ?>';
            const checkout = '<?php echo $checkout; ?>';
            const guests = '<?php echo $guests; ?>';
            
            let url = 'hotel-details.php?id=' + hotelId;
            if (checkin) url += '&checkin=' + checkin;
            if (checkout) url += '&checkout=' + checkout;
            if (guests) url += '&guests=' + guests;
            
            window.location.href = url;
        }

        // Book hotel
        function bookHotel(hotelId) {
            const checkin = '<?php echo $checkin; ?>';
            const checkout = '<?php echo $checkout; ?>';
            const guests = '<?php echo $guests; ?>';
            
            if (!checkin || !checkout) {
                alert('Please select check-in and check-out dates first.');
                return;
            }
            
            let url = 'booking.php?hotel_id=' + hotelId;
            url += '&checkin=' + checkin;
            url += '&checkout=' + checkout;
            url += '&guests=' + guests;
            
            window.location.href = url;
        }
    </script>
</body>
</html>
