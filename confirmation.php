<?php
require_once 'db.php';

$booking_ref = isset($_GET['ref']) ? sanitize_input($_GET['ref']) : '';

if (!$booking_ref) {
    header('Location: index.php');
    exit;
}

// Get booking details
try {
    $stmt = $pdo->prepare("
        SELECT b.*, h.name as hotel_name, h.image_url, h.location, h.city, h.country, h.rating 
        FROM bookings b 
        JOIN hotels h ON b.hotel_id = h.id 
        WHERE b.booking_reference = ?
    ");
    $stmt->execute([$booking_ref]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header('Location: index.php');
        exit;
    }
} catch(PDOException $e) {
    header('Location: index.php');
    exit;
}

$days = calculateDays($booking['check_in_date'], $booking['check_out_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Hotels.com Clone</title>
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
            max-width: 800px;
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

        /* Confirmation Section */
        .confirmation-section {
            padding: 3rem 0;
        }

        .confirmation-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .success-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .success-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .success-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .booking-reference {
            background: rgba(255,255,255,0.2);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .confirmation-details {
            padding: 2rem;
        }

        .hotel-summary {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e1e5e9;
        }

        .hotel-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
        }

        .hotel-info h2 {
            font-size: 1.5rem;
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
        }

        .stars {
            color: #ffd700;
        }

        .rating-text {
            color: #666;
            font-size: 0.9rem;
        }

        .booking-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
        }

        .info-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            font-weight: 600;
            color: #333;
        }

        .total-amount {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .total-label {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .total-price {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.3s;
            border: none;
            font-size: 1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .important-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .important-info h4 {
            color: #856404;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .important-info ul {
            color: #856404;
            padding-left: 1.5rem;
        }

        .important-info li {
            margin-bottom: 0.5rem;
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
            .hotel-summary {
                flex-direction: column;
                text-align: center;
            }

            .hotel-image {
                align-self: center;
            }

            .success-title {
                font-size: 2rem;
            }

            .total-price {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
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

    <section class="confirmation-section">
        <div class="container">
            <div class="confirmation-card">
                <div class="success-header">
                    <div class="success-icon">✅</div>
                    <h1 class="success-title">Booking Confirmed!</h1>
                    <p class="success-subtitle">Your reservation has been successfully processed</p>
                    <div class="booking-reference">
                        Booking Reference: <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                    </div>
                </div>

                <div class="confirmation-details">
                    <div class="hotel-summary">
                        <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" alt="<?php echo htmlspecialchars($booking['hotel_name']); ?>" class="hotel-image">
                        <div class="hotel-info">
                            <h2><?php echo htmlspecialchars($booking['hotel_name']); ?></h2>
                            <div class="hotel-location">
                                📍 <?php echo htmlspecialchars($booking['location'] . ', ' . $booking['city'] . ', ' . $booking['country']); ?>
                            </div>
                            <div class="hotel-rating">
                                <span class="stars">
                                    <?php 
                                    $rating = $booking['rating'];
                                    for($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '⭐' : '☆';
                                    }
                                    ?>
                                </span>
                                <span class="rating-text"><?php echo $booking['rating']; ?> out of 5</span>
                            </div>
                        </div>
                    </div>

                    <div class="booking-info">
                        <div class="info-section">
                            <h3 class="info-title">📅 Stay Details</h3>
                            <div class="info-item">
                                <span class="info-label">Check-in:</span>
                                <span class="info-value"><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Check-out:</span>
                                <span class="info-value"><?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Duration:</span>
                                <span class="info-value"><?php echo $days; ?> night<?php echo $days > 1 ? 's' : ''; ?></span>
                            </div>
                        </div>

                        <div class="info-section">
                            <h3 class="info-title">👥 Guest Information</h3>
                            <div class="info-item">
                                <span class="info-label">Guest Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($booking['guest_email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Guests:</span>
                                <span class="info-value"><?php echo $booking['guests']; ?> guest<?php echo $booking['guests'] > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Rooms:</span>
                                <span class="info-value"><?php echo $booking['rooms']; ?> room<?php echo $booking['rooms'] > 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="total-amount">
                        <div class="total-label">Total Amount Paid</div>
                        <div class="total-price">$<?php echo number_format($booking['total_amount'], 2); ?></div>
                    </div>

                    <div class="action-buttons">
                        <a href="index.php" class="btn btn-primary">Book Another Hotel</a>
                        <button onclick="window.print()" class="btn btn-secondary">Print Confirmation</button>
                    </div>

                    <div class="important-info">
                        <h4>⚠️ Important Information</h4>
                        <ul>
                            <li>Please save your booking reference number: <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></li>
                            <li>A confirmation email has been sent to <?php echo htmlspecialchars($booking['guest_email']); ?></li>
                            <li>Check-in time is typically 3:00 PM and check-out is 11:00 AM</li>
                            <li>Please bring a valid ID and credit card for check-in</li>
                            <li>Contact the hotel directly for any special requests or changes</li>
                        </ul>
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
        // Auto-scroll to top on page load
        window.addEventListener('load', function() {
            window.scrollTo(0, 0);
        });

        // Add some celebration animation
        document.addEventListener('DOMContentLoaded', function() {
            const successIcon = document.querySelector('.success-icon');
            successIcon.style.animation = 'bounce 1s ease-in-out';
        });

        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounce {
                0%, 20%, 60%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-20px);
                }
                80% {
                    transform: translateY(-10px);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
