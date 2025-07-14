<?php
require_once 'db.php';

$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : 0;
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;
$rooms = isset($_GET['rooms']) ? (int)$_GET['rooms'] : 1;

if (!$hotel_id || !$checkin || !$checkout) {
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

$days = calculateDays($checkin, $checkout);
$subtotal = $hotel['price_per_night'] * $days * $rooms;
$taxes = $subtotal * 0.12; // 12% tax
$total = $subtotal + $taxes;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $guest_name = sanitize_input($_POST['guest_name']);
    $guest_email = sanitize_input($_POST['guest_email']);
    $guest_phone = sanitize_input($_POST['guest_phone']);
    $special_requests = sanitize_input($_POST['special_requests']);
    
    if (empty($guest_name) || empty($guest_email)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $booking_reference = generateBookingReference();
            
            $stmt = $pdo->prepare("INSERT INTO bookings (hotel_id, guest_name, guest_email, guest_phone, check_in_date, check_out_date, guests, rooms, total_amount, special_requests, booking_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $hotel_id,
                $guest_name,
                $guest_email,
                $guest_phone,
                $checkin,
                $checkout,
                $guests,
                $rooms,
                $total,
                $special_requests,
                $booking_reference
            ]);
            
            // Redirect to confirmation page
            header("Location: confirmation.php?ref=$booking_reference");
            exit;
            
        } catch(PDOException $e) {
            $error = "Booking failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo htmlspecialchars($hotel['name']); ?> - Hotels.com Clone</title>
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

        /* Main Content */
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
            padding: 2rem 0;
        }

        .booking-form-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .required {
            color: #e74c3c;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }

        /* Booking Summary */
        .booking-summary {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .hotel-summary {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e1e5e9;
        }

        .hotel-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .hotel-info h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .hotel-location {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .hotel-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stars {
            color: #ffd700;
            font-size: 0.9rem;
        }

        .rating-text {
            color: #666;
            font-size: 0.8rem;
        }

        .booking-details {
            margin-bottom: 2rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
        }

        .detail-row.border-top {
            border-top: 1px solid #e1e5e9;
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .detail-label {
            color: #666;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
        }

        .price-breakdown {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .price-row.total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            border-top: 2px solid #ddd;
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .confirm-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 24px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
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
            .booking-container {
                grid-template-columns: 1fr;
            }

            .booking-summary {
                position: static;
                order: -1;
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
            <a href="index.php">Home</a> > <a href="hotels.php">Hotels</a> > <a href="hotel-details.php?id=<?php echo $hotel_id; ?>"><?php echo htmlspecialchars($hotel['name']); ?></a> > Booking
        </div>
    </section>

    <section class="booking-container">
        <div class="container" style="max-width: none; padding: 0;">
            <div class="booking-form-section">
                <h2 class="section-title">Guest Information</h2>
                
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" id="bookingForm">
                    <div class="form-group">
                        <label for="guest_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="guest_name" name="guest_name" required value="<?php echo isset($_POST['guest_name']) ? htmlspecialchars($_POST['guest_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="guest_email">Email Address <span class="required">*</span></label>
                        <input type="email" id="guest_email" name="guest_email" required value="<?php echo isset($_POST['guest_email']) ? htmlspecialchars($_POST['guest_email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="guest_phone">Phone Number</label>
                        <input type="tel" id="guest_phone" name="guest_phone" value="<?php echo isset($_POST['guest_phone']) ? htmlspecialchars($_POST['guest_phone']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="special_requests">Special Requests</label>
                        <textarea id="special_requests" name="special_requests" placeholder="Any special requests or preferences..."><?php echo isset($_POST['special_requests']) ? htmlspecialchars($_POST['special_requests']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="confirm-btn">Confirm Booking</button>
                </form>
            </div>

            <div class="booking-summary">
                <h3 class="section-title">Booking Summary</h3>
                
                <div class="hotel-summary">
                    <img src="<?php echo htmlspecialchars($hotel['image_url']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" class="hotel-image">
                    <div class="hotel-info">
                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <div class="hotel-location"><?php echo htmlspecialchars($hotel['city'] . ', ' . $hotel['country']); ?></div>
                        <div class="hotel-rating">
                            <span class="stars">
                                <?php 
                                $rating = $hotel['rating'];
                                for($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '⭐' : '☆';
                                }
                                ?>
                            </span>
                            <span class="rating-text"><?php echo $hotel['rating']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="booking-details">
                    <div class="detail-row">
                        <span class="detail-label">Check-in:</span>
                        <span class="detail-value"><?php echo date('M j, Y', strtotime($checkin)); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Check-out:</span>
                        <span class="detail-value"><?php echo date('M j, Y', strtotime($checkout)); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value"><?php echo $days; ?> night<?php echo $days > 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Guests:</span>
                        <span class="detail-value"><?php echo $guests; ?> guest<?php echo $guests > 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Rooms:</span>
                        <span class="detail-value"><?php echo $rooms; ?> room<?php echo $rooms > 1 ? 's' : ''; ?></span>
                    </div>
                </div>

                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Room Rate (<?php echo $days; ?> night<?php echo $days > 1 ? 's' : ''; ?> × <?php echo $rooms; ?> room<?php echo $rooms > 1 ? 's' : ''; ?>):</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="price-row">
                        <span>Taxes & Fees:</span>
                        <span>$<?php echo number_format($taxes, 2); ?></span>
                    </div>
                    <div class="price-row total">
                        <span>Total Amount:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2024 Hotels.com Clone. All rights reserved. | Built with ❤️ for travelers worldwide</p>
        </div>
    </footer>

    <script>
        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const name = document.getElementById('guest_name').value.trim();
            const email = document.getElementById('guest_email').value.trim();
            
            if (!name || !email) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
    </script>
</body>
</html>
