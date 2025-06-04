<?php
session_start();

// Check if the user is logged in and is an Owner
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Owner') {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_rental";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch bookings for the owner
$ownerId = $_SESSION['user_id'];
$bookingsQuery = "SELECT b.BookingID, c.Make AS carName, u.Name AS customerName, b.StartDate AS bookingDate
                  FROM Bookings b
                  JOIN Cars c ON b.CarID = c.CarID
                  JOIN Users u ON b.RenterID = u.UserID
                  WHERE c.OwnerID = ?";
$bookingsStmt = $conn->prepare($bookingsQuery);
$bookingsStmt->bind_param("i", $ownerId);
$bookingsStmt->execute();
$bookings = $bookingsStmt->get_result();

// Fetch cars added by the owner
$listingsQuery = "SELECT CarID, Make, Model, Year, RegistrationNumber, DailyRate, Availability
                  FROM Cars
                  WHERE OwnerID = ?";
$listingsStmt = $conn->prepare($listingsQuery);
$listingsStmt->bind_param("i", $ownerId);
$listingsStmt->execute();
$listings = $listingsStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        header { background-color: #4CAF50; color: white; padding: 15px; text-align: center; }
        .container { padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; }
        input, button { padding: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 10px; text-align: left; }
        .home-button { margin-bottom: 20px; display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
        .home-button:hover { background-color: #45a049; }
    </style>
</head>
<body>
<header>
    <h1>Welcome, Car Owner</h1>
    <a href="index.html" class="home-button">Home</a>
</header>
<div class="container">
    <h2>Add a New Car</h2>
    <form method="POST" action="add_car.php">
        <button type="submit">Add Car</button>
    </form>

    <h2>My Listings</h2>
    <table>
        <thead>
            <tr>
                <th>Car ID</th>
                <th>Make</th>
                <th>Model</th>
                <th>Year</th>
                <th>Registration Number</th>
                <th>Daily Rate</th>
                <th>Availability</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $listings->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['CarID']) ?></td>
                    <td><?= htmlspecialchars($row['Make']) ?></td>
                    <td><?= htmlspecialchars($row['Model']) ?></td>
                    <td><?= htmlspecialchars($row['Year']) ?></td>
                    <td><?= htmlspecialchars($row['RegistrationNumber']) ?></td>
                    <td><?= htmlspecialchars($row['DailyRate']) ?></td>
                    <td><?= htmlspecialchars($row['Availability']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Bookings</h2>
    <table>
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Car Name</th>
                <th>Customer Name</th>
                <th>Booking Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $bookings->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['BookingID']) ?></td>
                    <td><?= htmlspecialchars($row['carName']) ?></td>
                    <td><?= htmlspecialchars($row['customerName']) ?></td>
                    <td><?= htmlspecialchars($row['bookingDate']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

<?php
$bookingsStmt->close();
$listingsStmt->close();
$conn->close();
?>