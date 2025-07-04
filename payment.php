<?php
session_start();
$conn = new mysqli("localhost", "root", "", "car_rental");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF token validation
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}

// Ensure booking ID and total price are available
if (!isset($_POST['booking_id']) || !isset($_POST['total_price'])) {
    die("Booking details are missing.");
}

$booking_id = $_POST['booking_id'];
$total_price = $_POST['total_price'];

// Fetch booking details
$sql = "SELECT Bookings.*, Cars.Make, Cars.Model, Cars.RegistrationNumber, Cars.DailyRate, Users.Name AS OwnerName 
        FROM Bookings 
        JOIN Cars ON Bookings.CarID = Cars.CarID
        JOIN Users ON Cars.OwnerID = Users.UserID
        WHERE Bookings.BookingID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking_result = $stmt->get_result();
$booking = $booking_result->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}

// Calculate the total rental days
$date1 = new DateTime($booking['StartDate']);
$date2 = new DateTime($booking['EndDate']);
$days = $date1->diff($date2)->days;

// Calculate the total price by multiplying the number of days by the daily rate
$total_price = $days * $booking['DailyRate'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2c3e50;
            text-align: center;
        }

        p {
            font-size: 16px;
            line-height: 1.5;
        }

        .details {
            margin-top: 20px;
            border-top: 2px solid #ecf0f1;
            padding-top: 20px;
        }

        .details p {
            margin: 10px 0;
        }

        .details strong {
            color: #2980b9;
        }

        .price {
            font-size: 18px;
            color: #27ae60;
            font-weight: bold;
            margin-top: 10px;
        }

        .form-container {
            margin-top: 30px;
        }

        input[type="text"], input[type="date"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .back-link {
            display: inline-block;
            margin-top: 10px;
            font-size: 14px;
            color: #3498db;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Payment Details</h2>
    <p>Complete the form below to proceed with payment.</p>

    <div class="details">
        <h3>Booking Details</h3>
        <p><strong>Car:</strong> <?php echo htmlspecialchars($booking['Make'] . " " . $booking['Model']); ?></p>
        <p><strong>Owner:</strong> <?php echo htmlspecialchars($booking['OwnerName']); ?></p>
        <p><strong>Start Date:</strong> <?php echo htmlspecialchars($booking['StartDate']); ?></p>
        <p><strong>End Date:</strong> <?php echo htmlspecialchars($booking['EndDate']); ?></p>
        <p class="price"><strong>Total Price:</strong> ZAR <?php echo number_format($total_price, 2); ?></p>
    </div>

    <form method="POST" action="payment_success.php">
        <div class="form-container">
            <h3>Enter Card Information</h3>
            <label for="card_number">Card Number</label>
            <input type="text" name="card_number" id="card_number" placeholder="Enter card number" required>

            <label for="expiry_date">Expiry Date</label>
            <input type="text" name="expiry_date" id="expiry_date" placeholder="MM/YY" required>

            <label for="cvv">CVV</label>
            <input type="number" name="cvv" id="cvv" placeholder="CVV" required>

            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <button type="submit" class="btn">Submit Payment</button>
        </div>
    </form>

    <a href="view_cars.php" class="back-link">Back to Available Cars</a>
</div>

</body>
</html>

<?php
$conn->close();
?>
