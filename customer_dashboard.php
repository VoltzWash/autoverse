<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 
// Start session
session_start();
 
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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
 
// Fetch user details
$userId = $_SESSION['user_id'];
$userQuery = "SELECT * FROM Users WHERE UserID = '$userId'";
$userResult = $conn->query($userQuery);
 
if ($userResult && $userResult->num_rows > 0) {
    $user = $userResult->fetch_assoc();
} else {
    die("User not found or query failed: " . $conn->error);
}
 
// Fetch available cars
$sql = "SELECT * FROM Cars WHERE Availability = 'Available'";
$result = $conn->query($sql);
 
if (!$result) {
    die("Error fetching cars: " . $conn->error);
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard</title>
<style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        header { background-color: #4CAF50; color: white; padding: 15px; text-align: center; }
        .container { padding: 20px; }
        .car { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        .car button { background-color: #4CAF50; color: white; border: none; padding: 10px; cursor: pointer; }
        .car button:hover { background-color: #45a049; }
</style>
</head>
<body>
<header>
<h1>Welcome, <?php echo htmlspecialchars($user['Name']); ?></h1>
</header>
<div class="container">
<h2>Available Cars</h2>
<?php if ($result->num_rows > 0): ?>
<?php while ($row = $result->fetch_assoc()): ?>
<div class="car">
<p><strong>Car Make:</strong> <?= htmlspecialchars($row['Make']) ?></p>
<p><strong>Car Model:</strong> <?= htmlspecialchars($row['Model']) ?></p>
<p><strong>Car Year:</strong> <?= htmlspecialchars($row['Year']) ?></p>
<p><strong>Location:</strong> <?= htmlspecialchars($row['Location']) ?></p>
<p><strong>Price:</strong> <?= htmlspecialchars($row['DailyRate']) ?> per day</p>
<p><strong>Car ID:</strong> <?= htmlspecialchars($row['CarID']) ?></p>
<button onclick='bookCar(<?= json_encode($row['CarID']) ?>)'>Book Now</button>
</div>
<?php endwhile; ?>
<?php else: ?>
<p>No cars available at the moment.</p>
<?php endif; ?>
</div>
 
<script>
    function bookCar(carId) {
       
        window.location.href = `book_car.php?Car_ID=${carId}`;
    }
</script>
</body>
</html>