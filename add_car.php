<?php
// Start session to get user data
session_start();
 
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
 
$user_id = $_SESSION['user_id'];
 
// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
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
 
// Handle form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make'])) {
    $make = mysqli_real_escape_string($conn, $_POST['make']);
    $model = mysqli_real_escape_string($conn, $_POST['model']);
    $year = (int) $_POST['year'];
    $reg_number = mysqli_real_escape_string($conn, $_POST['reg_number']);
    $availability = mysqli_real_escape_string($conn, $_POST['availability']);
    $daily_rate = (float) $_POST['daily_rate'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);
 
    // Validation
    if ($year < 1900 || $year > date("Y")) {
        $error = "Invalid year.";
    } elseif ($daily_rate <= 0) {
        $error = "Daily rate must be positive.";
    } elseif (empty($make) || empty($model) || empty($reg_number) || empty($location)) {
        $error = "All fields are required.";
    } else {
        $image_path = null;
 
        // Upload image
        if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/cars/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
 
            $imageFileType = strtolower(pathinfo($_FILES["car_image"]["name"], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
 
            if (!in_array($imageFileType, $allowed_types)) {
                $error = "Invalid file type.";
            } else {
                $unique_name = uniqid('car_', true) . '.' . $imageFileType;
                $target_file = $target_dir . $unique_name;
 
                if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $target_file)) {
                    $image_path = $target_file;
                } else {
                    $error = "Error uploading image.";
                }
            }
        }
 
        if (!isset($error)) {
            // Insert into Cars
            $stmt = $conn->prepare("INSERT INTO Cars (OwnerID, Make, Model, Year, RegistrationNumber, Availability, DailyRate, Location, ImagePath) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssiss", $user_id, $make, $model, $year, $reg_number, $availability, $daily_rate, $location, $image_path);
 
            if ($stmt->execute()) {
                $car_id = $stmt->insert_id;
 
                // Insert into CarImages
                if ($image_path) {
                    $stmt_img = $conn->prepare("INSERT INTO CarImages (CarID, ImagePath) VALUES (?, ?)");
                    $stmt_img->bind_param("is", $car_id, $image_path);
                    $stmt_img->execute();
                    $stmt_img->close();
                }
 
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Database error: " . $stmt->error;
            }
 
            $stmt->close();
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Car</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
        body {
            font-family: Arial;
            background-color: #f4f4f4;
            margin: 0;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        .header-buttons {
            position: absolute;
            right: 20px;
            top: 20px;
        }
        .header-buttons form {
            display: inline-block;
        }
        .header-buttons button {
            background-color: white;
            color: #4CAF50;
            border: 1px solid #4CAF50;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .header-buttons button:hover {
            background-color: #4CAF50;
            color: white;
        }
        .container {
            max-width: 600px;
            background: white;
            margin: 40px auto;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button[type="submit"] {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .dashboard-links {
            margin-top: 20px;
        }
        .dashboard-links a {
            text-decoration: none;
            background-color: #4CAF50;
            padding: 10px 15px;
            color: white;
            border-radius: 5px;
            font-weight: bold;
        }
        .dashboard-links a:hover {
            background-color: #3e8e41;
        }
        footer {
            background: #333;
            color: white;
            padding: 10px;
            text-align: center;
            margin-top: 40px;
        }
</style>
</head>
<body>
 
<header>
<h1>Add a New Car</h1>
<div class="header-buttons">
<form method="POST" action="">
<button type="submit" name="logout">Logout</button>
</form>
</div>
</header>
 
<div class="container">
<h2>Car Details</h2>
 
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
 
    <form method="POST" enctype="multipart/form-data">
<input type="text" name="make" placeholder="Car Make" required>
<input type="text" name="model" placeholder="Car Model" required>
<input type="number" name="year" placeholder="Year" required>
<input type="text" name="reg_number" placeholder="Registration Number" required>
<select name="availability" required>
<option value="">Select Availability</option>
<option value="Available">Available</option>
<option value="Booked">Booked</option>
<option value="Unavailable">Unavailable</option>
</select>
<input type="number" step="0.01" name="daily_rate" placeholder="Daily Rate (ZAR)" required>
<input type="text" name="location" placeholder="Location" required>
<input type="file" name="car_image" accept="image/*" required>
 
        <button type="submit">Add Car</button>
</form>
 
    <div class="dashboard-links">
<a href="dashboard.php">Back to Dashboard</a>
</div>
</div>
 
<footer>
&copy; 2025 Car Rental Platform
</footer>
 
</body>
</html>
 
<?php
$conn->close();
?>