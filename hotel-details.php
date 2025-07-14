<?php
require_once 'db.php';

$hotel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;

if (!$hotel_id) {
    header('Location: index.php');
    exit;
}

// Get hotel details
try {
    $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->execute([$hotel_id]);
    $hotel = $stmt->fetch();
    
    if (!$hotel) {
        header('Location: index.php');
        exit;
    }
} catch(PDOException $e) {
    header('Location: index.php');
    exit;
}

// Get hotel reviews
try {
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE hotel_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$hotel_id]);
    $reviews = $stmt->fetchAll();
} catch(PDOException $e) {
    $reviews = [];
}

$days = 0;
$total_price = 0;
if ($checkin && $checkout) {
    $days = calculateDays($checkin, $checkout);
    $total_price = $hotel['price_per_night'] * $days;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['name']); ?> - Hotels.com Clone</title>
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

        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 1rem 0;
            border-bottom: 1px solid #e1e5e9;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Hotel Details */
        .hotel-details {
            background: white;
            padding: 2rem 0;
        }

        .hotel-header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .hotel-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .hotel-location {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hotel-rating {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stars {
            color: #ffd700;
            font-size: 1.2rem;
        }

        .rating-text {
            color: #666;
            font-size: 1rem;
        }

        .hotel-type {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .price-section {
            text-align: right;
        }

        .current-price {
            font-size: 3rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .price-unit {
            font-size: 1rem;
            color: #666;
            font-weight: normal;
        }

        .total-price {
            font-size: 1.2rem;
            color: #333;
            margin-top: 0.5rem;
        }

        /* Hotel Image */
        .hotel-image-section {
            margin-bottom: 3rem;
        }

        .hotel-main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        /* Hotel Info Grid */
        .hotel-info-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .hotel-description {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333;
        }

        .description-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
        }

        .amenities-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .amenities-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            color: #555;
        }

        /* Booking Section */
        .booking-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 2rem;
        }

        .booking-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group input, .form-group select {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .book-now-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 24px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            margin-top: 1rem;
        }

        .book-now-btn:hover {
            transform: translateY(-2px);
        }

        .book-now-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        /* Reviews Section */
        .reviews-section {
            background: white;
            padding: 2rem 0;
        }

        .reviews-container {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .review-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .reviewer-name {
            font-weight: 600;
            color: #333;
        }

        .review-rating {
            color: #ffd700;
        }

        .review-text {
            color: #555;
            line-height: 1.6;
        }

        .review-date {
            color: #999;
            font-size: 0.9rem;
            margin-top: 0.5rem;
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
            .hotel-header {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hotel-info-grid {
                grid-template-columns: 1fr;
            }

            .booking-section {
                position: static;
            }

            .hotel-title {
                font-size: 2rem;
            }

            .current-price {
                font-size: 2rem;
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

    <section class="breadcrumb">
        <div class="container">
            <a href="index.php">Home</a> > <a href="hotels.php">Hotels</a> > <?php echo htmlspecialchars($hotel['name']); ?>
        </div>
    </section>

    <section class="hotel-details">
        <div class="container">
            <div class="hotel-header">
                <div>
                    <h1 class="hotel-title"><?php echo htmlspecialchars($hotel['name']); ?></h1>
                    <div class="hotel-location">
                        📍 <?php echo htmlspecialchars($hotel['location'] . ', ' . $hotel['city'] . ', ' . $hotel['country']); ?>
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
                        <span class="rating-text"><?php echo $hotel['rating']; ?> out of 5 (<?php echo $hotel['total_reviews']; ?> reviews)</span>
                    </div>
                    <span class="hotel-type"><?php echo ucfirst($hotel['hotel_type']); ?> Hotel</span>
                </div>
                <div class="price-section">
                    <div class="current-price">
                        $<?php echo number_format($hotel['price_per_night'], 2); ?>
                        <span class="price-unit">per night</span>
                    </div>
                    <?php if($days > 0): ?>
                        <div class="total-price">
                            Total for <?php echo $days; ?> night<?php echo $days > 1 ? 's' : ''; ?>: $<?php echo number_format($total_price, 2); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hotel-image-section">
                <img src="<?php echo htmlspecialchars($hotel['image_url']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" class="hotel-main-image">
            </div>

            <div class="hotel-info-grid">
                <div>
                    <div class="hotel-description">
                        <h2 class="section-title">About This Hotel</h2>
                        <p class="description-text"><?php echo htmlspecialchars($hotel['description']); ?></p>
                    </div>

                    <div class="amenities-section">
                        <h2 class="section-title">Amenities & Services</h2>
                        <div class="amenities-list">
                            <?php 
                            $amenities = explode(', ', $hotel['amenities']);
                            foreach($amenities as $amenity): 
                            ?>
                                <div class="amenity-item">
                                    ✅ <?php echo htmlspecialchars(trim($amenity)); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="booking-section">
                    <h3 class="section-title">Book Your Stay</h3>
                    <form class="booking-form" id="bookingForm">
                        <div class="form-group">
                            <label for="checkin">Check-in Date</label>
                            <input type="date" id="checkin" name="checkin" value="<?php echo $checkin; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="checkout">Check-out Date</label>
                            <input type="date" id="checkout" name="checkout" value="<?php echo $checkout; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="guests">Number of Guests</label>
                            <select id="guests" name="guests">
                                <option value="1" <?php echo $guests == 1 ? 'selected' : ''; ?>>1 Guest</option>
                                <option value="2" <?php echo $guests == 2 ? 'selected' : ''; ?>>2 Guests</option>
                                <option value="3" <?php echo $guests == 3 ? 'selected' : ''; ?>>3 Guests</option>
                                <option value="4" <?php echo $guests == 4 ? 'selected' : ''; ?>>4 Guests</option>
                                <option value="5" <?php echo $guests == 5 ? 'selected' : ''; ?>>5+ Guests</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rooms">Number of Rooms</label>
                            <select id="rooms" name="rooms">
                                <option value="1">1 Room</option>
                                <option value="2">2 Rooms</option>
                                <option value="3">3 Rooms</option>
                                <option value="4">4+ Rooms</option>
                            </select>
                        </div>
                        <button type="submit" class="book-now-btn" id="bookBtn">
                            Book Now - $<?php echo number_format($hotel['price_per_night'], 2); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php if(!empty($reviews)): ?>
    <section class="reviews-section">
        <div class="container">
            <div class="reviews-container">
                <h2 class="section-title">Guest Reviews</h2>
                <div class="reviews-list">
                    <?php foreach($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <span class="reviewer-name"><?php echo htmlspecialchars($review['guest_name']); ?></span>
                            <span class="review-rating">
                                <?php 
                                for($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['rating'] ? '⭐' : '☆';
                                }
                                ?>
                            </span>
                        </div>
                        <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                        <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <footer>
        <div class="container">
            <p>&copy; 2024 Hotels.com Clone. All rights reserved. | Built with ❤️ for travelers worldwide</p>
        </div>
    </footer>

    <script>
        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('checkin').min = today;
            document.getElementById('checkout').min = today;
        });

        // Update checkout minimum date when checkin changes
        document.getElementById('checkin').addEventListener('change', function() {
            const checkinDate = this.value;
            const checkoutInput = document.getElementById('checkout');
            checkoutInput.min = checkinDate;
            
            // If checkout is before checkin, reset it
            if (checkoutInput.value && checkoutInput.value <= checkinDate) {
                checkoutInput.value = '';
            }
            
            updateBookingButton();
        });

        document.getElementById('checkout').addEventListener('change', updateBookingButton);

        function updateBookingButton() {
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            const bookBtn = document.getElementById('bookBtn');
            
            if (checkin && checkout) {
                const checkinDate = new Date(checkin);
                const checkoutDate = new Date(checkout);
                const timeDiff = checkoutDate.getTime() - checkinDate.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (daysDiff > 0) {
                    const totalPrice = <?php echo $hotel['price_per_night']; ?> * daysDiff;
                    bookBtn.textContent = `Book Now - $${totalPrice.toFixed(2)} (${daysDiff} night${daysDiff > 1 ? 's' : ''})`;
                    bookBtn.disabled = false;
                } else {
                    bookBtn.textContent = 'Invalid dates';
                    bookBtn.disabled = true;
                }
            } else {
                bookBtn.textContent = 'Book Now - $<?php echo number_format($hotel['price_per_night'], 2); ?>';
                bookBtn.disabled = false;
            }
        }

        // Handle booking form submission
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            const guests = document.getElementById('guests').value;
            const rooms = document.getElementById('rooms').value;
            
            if (!checkin || !checkout) {
                alert('Please select check-in and check-out dates.');
                return;
            }
            
            const checkinDate = new Date(checkin);
            const checkoutDate = new Date(checkout);
            
            if (checkoutDate <= checkinDate) {
                alert('Check-out date must be after check-in date.');
                return;
            }
            
            // Redirect to booking page
            const url = `booking.php?hotel_id=<?php echo $hotel_id; ?>&checkin=${checkin}&checkout=${checkout}&guests=${guests}&rooms=${rooms}`;
            window.location.href = url;
        });
    </script>
</body>
</html>
