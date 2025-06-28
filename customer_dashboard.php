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
<a href="index.html" style="background-color: #4CAF50; color: white; border: none; padding: 10px 20px; margin-right: 10px; text-decoration: none; border-radius: 4px; font-size: 16px;">Home</a>
<a href="logout.php" style="background-color: #4CAF50; color: white; border: none; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-size: 16px;">Logout</a>
</header>
<!-- Logout functionality is handled in logout.php -->
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
    <button onclick='showRatingModal(<?= json_encode($row['OwnerID']) ?>, <?= json_encode($row['CarID']) ?>)'>Rate Owner</button>
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
<div id="ratingModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:30px 40px; border-radius:8px; text-align:center; max-width:350px; margin:auto;">
        <h3>Rate Car Owner</h3>
        <form id="rateOwnerForm" method="POST" action="rate_owner.php">
            <input type="hidden" name="owner_id" id="owner_id">
            <input type="hidden" name="car_id" id="car_id">
            <label for="rating">Rating:</label>
            <select name="rating" id="rating" required>
                <option value="">Select</option>
                <option value="1">1 - Poor</option>
                <option value="2">2</option>
                <option value="3">3 - Average</option>
                <option value="4">4</option>
                <option value="5">5 - Excellent</option>
            </select>
            <br><br>
            <label for="comment">Comment (optional):</label><br>
            <textarea name="comment" id="comment" rows="3" style="width:90%;"></textarea>
            <br><br>
            <button type="submit" style="padding:8px 20px; background:#4CAF50; color:#fff; border:none; border-radius:4px; cursor:pointer;">Submit Rating</button>
            <button type="button" onclick="closeRatingModal()" style="padding:8px 20px; background:#ccc; color:#333; border:none; border-radius:4px; cursor:pointer; margin-left:10px;">Cancel</button>
        </form>
    </div>
</div>
<script>
function showRatingModal(ownerId, carId) {
    document.getElementById('owner_id').value = ownerId;
    document.getElementById('car_id').value = carId;
    document.getElementById('ratingModal').style.display = 'flex';
}
function closeRatingModal() {
    document.getElementById('ratingModal').style.display = 'none';
}
function bookCar(carId) {
    window.location.href = `book_car.php?Car_ID=${carId}`;
}
</script>

</body>
</html>