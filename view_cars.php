<?php
session_start();
$conn = new mysqli("localhost", "root", "", "car_rental");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$isCustomer = isset($_SESSION['user_id']) && isset($_SESSION['name']);
// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch only available cars with owner details using prepared statement
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $search_param = "%{$search}%";
    $sql = "SELECT Cars.CarID, Cars.Make, Cars.Model, Cars.Year, Cars.RegistrationNumber, Cars.DailyRate, Cars.Location,Cars.ImagePath, Users.Name AS OwnerName 
            FROM Cars 
            JOIN Users ON Cars.OwnerID = Users.UserID 
            WHERE Cars.Availability = 'Available'
              AND (Cars.Make LIKE ? OR Cars.Model LIKE ? OR Cars.Location LIKE ?)
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);

    // For pagination count
    $total_sql = "SELECT COUNT(*) AS total FROM Cars WHERE Availability = 'Available'
                  AND (Make LIKE ? OR Model LIKE ? OR Location LIKE ?)";
    $total_stmt = $conn->prepare($total_sql);
    $total_stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_cars = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_cars / $limit);
} else {
    $sql = "SELECT Cars.CarID, Cars.Make, Cars.Model, Cars.Year, Cars.RegistrationNumber, Cars.DailyRate, Cars.Location,Cars.ImagePath, Users.Name AS OwnerName 
            FROM Cars 
            JOIN Users ON Cars.OwnerID = Users.UserID 
            WHERE Cars.Availability = 'Available' 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);

    // For pagination count
    $total_sql = "SELECT COUNT(*) AS total FROM Cars WHERE Availability = 'Available'";
    $total_result = $conn->query($total_sql);
    $total_cars = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total_cars / $limit);
}
$stmt->execute();
$cars_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Cars - Car Rental Platform</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; margin: 0; padding: 0; }
        header { background-color: #4CAF50; color: white; padding: 15px; text-align: center; position: relative; }
        .header-buttons { position: absolute; top: 50%; right: 20px; transform: translateY(-50%); }
        .header-buttons a { padding: 8px 15px; background-color: white; color: #4CAF50; border: 1px solid #4CAF50; border-radius: 5px; text-decoration: none; font-weight: bold; margin-left: 10px; }
        .header-buttons a:hover { background-color: #4CAF50; color: white; }
        .container { max-width: 1200px; margin: 50px auto; padding: 20px; display: flex; flex-wrap: wrap; justify-content: space-around; }
        .car-card { width: 280px; background-color: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); margin: 15px; padding: 20px; text-align: center; transition: transform 0.3s ease-in-out; }
        .car-card:hover { transform: translateY(-10px); }
        .car-card img { width: 100%; border-radius: 5px; height: 180px; object-fit: cover; }
        .car-card h3 { margin: 10px 0; color: #2c3e50; }
        .car-card p { font-size: 14px; color: #7f8c8d; }
        .price { font-size: 18px; color: #27ae60; font-weight: bold; margin: 10px 0; }
        .book-btn { padding: 8px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; text-decoration: none; font-size: 14px; cursor: pointer; }
        .book-btn:hover { background-color: #218838; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a { padding: 8px 12px; margin: 0 5px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
        .pagination a:hover { background-color: #45a049; }
        footer { background-color: #333; color: white; text-align: center; padding: 10px; margin-top: 20px; }
    </style>
</head>
<body>

<header>
    <h1>Available Cars</h1>
    <div class="header-buttons">
    <a href="index.html" class="home-button">Home</a>
    </div>
</header>
<form method="GET" action="" style="text-align:center; margin: 20px 0;">
    <input type="text" name="search" placeholder="Search by make, model, or location" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="padding:8px; width:250px;">
    <button type="submit" style="padding:8px 15px;">Search</button>
</form>
<div class="container">
    <?php if ($cars_result->num_rows > 0): ?>
        <?php while ($car = $cars_result->fetch_assoc()): ?>
            <div class="car-card">
                
                <?php
                // Display image
                if (!empty($car['ImagePath']) && file_exists($car['ImagePath'])) {
                    echo "<img src='" . htmlspecialchars($car['ImagePath']) . "' alt='Car Image'>";
                } else {
                    echo "<img src='images/caricon.png' alt='Car Image'>";
                }
                ?>
                <h3><?php echo htmlspecialchars($car['Make'] . " " . $car['Model']); ?></h3>
                <p><strong>Owner:</strong> <?php echo htmlspecialchars($car['OwnerName']); ?></p>
                <p><strong>Year:</strong> <?php echo htmlspecialchars($car['Year']); ?></p>
                <p><strong>Registration:</strong> <?php echo htmlspecialchars($car['RegistrationNumber']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($car['Location']); ?></p>
                <p class="price">R<?php echo number_format($car['DailyRate'], 2); ?>/day</p>
                <?php

if ($isCustomer): ?>
    <a href="book_car.php?Car_ID=<?php echo $car['CarID']; ?>" class="book-btn">Book</a>
<?php else: ?>
    <a href="#" class="book-btn" onclick="showLoginPopup(event)">Book</a>
<?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No available cars at the moment.</p>
    <?php endif; ?>
</div>

<div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="view_cars.php?page=<?php echo $i; ?>"> <?php echo $i; ?> </a>
    <?php endfor; ?>
</div>

<footer>
    <p>&copy; 2025 Car Rental Platform. All rights reserved.</p>
</footer>
<!-- Login Required Popup Modal -->
<div id="loginPopup" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:30px 40px; border-radius:8px; text-align:center; max-width:300px; margin:auto;">
        <p style="font-size:18px; margin-bottom:20px;">Log in First to Book a Car</p>
        <button onclick="closeLoginPopup()" style="padding:8px 20px; background:#4CAF50; color:#fff; border:none; border-radius:4px; cursor:pointer;">OK</button>
    </div>
</div>
<script>
function showLoginPopup(e) {
    e.preventDefault();
    document.getElementById('loginPopup').style.display = 'flex';
}
function closeLoginPopup() {
    window.location.href = 'index.html';
}
</script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
<!-- Helper PHP function to get car image path -->
<?php
function getCarImagePath($carId) {
    $uploadDir = __DIR__ . '/uploads/cars/';
    $webDir = 'uploads/cars/';
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ($allowedExtensions as $ext) {
        $filePath = $uploadDir . $carId . '.' . $ext;
        if (file_exists($filePath)) {
            return $webDir . $carId . '.' . $ext;
        }
    }
    return 'images/caricon.png';
}
?>
<script>
    // No JS needed for image display, handled in PHP above
</script>