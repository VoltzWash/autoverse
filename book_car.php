<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$conn = new mysqli("localhost", "root", "", "car_rental");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// To Ensure a car is selected
if ( !isset($_GET['Car_ID'])) {
    die("Car ID is required.");
}

// To Retrieve Car ID from GET or POST
$Car_ID = $_GET['Car_ID'];

// To Fetch car details
$sql = "SELECT Cars.*, Users.Name AS OwnerName FROM Cars 
        JOIN Users ON Cars.OwnerID = Users.UserID 
        WHERE Cars.CarID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $Car_ID);
$stmt->execute();
$car_result = $stmt->get_result();
$car = $car_result->fetch_assoc();

if (!$car) {
    die("Car not found.");
}
//echo"Car: $car[Make] $car[Model]";
// To Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// To Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve and sanitize form inputs
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

    // To Validate input
    $errors = [];
    if (empty($start_date)) $errors[] = "Start date is required.";
    if (empty($end_date)) $errors[] = "End date is required.";
    if (!empty($start_date) && !empty($end_date)) {
        $date1 = new DateTime($start_date);
        $date2 = new DateTime($end_date);
        if ($date1 > $date2) {
            $errors[] = "End date must be after start date.";
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
        exit;       
    }

    // To Check if customer already exists
    $customer_sql = "SELECT UserID FROM Users WHERE UserID = ?";
    $stmt = $conn->prepare($customer_sql);
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $customer_result = $stmt->get_result();
    $customer = $customer_result->fetch_assoc();

    if ($_SESSION['user_id']) {
        $_SESSION['user_id'] = $customer['UserID'];
    }
    else{
            // Clear the session and redirect to register.php
            session_unset();
            session_destroy();
            header("Location: register.php");
            exit;
    }
       
    

    // To Calculate total price
    $date1 = new DateTime($start_date);
    $date2 = new DateTime($end_date);
    $days = $date1->diff($date2)->days;
    $total_price = $days * $car['DailyRate'];

    // to Insert booking
    $insert_booking_sql = "INSERT INTO Bookings (RenterID, CarID, StartDate, EndDate, TotalPrice) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_booking_sql);
    $stmt->bind_param("iissd", $_SESSION['user_id'], $Car_ID, $start_date, $end_date, $total_price);

    if ($stmt->execute()) {
        // to store the booking ID in the session
        $_SESSION['booking_id'] = $stmt->insert_id;  // to sore the new booking ID

        header("Location: booking_confirmation.php");
        exit;
    } else {
        die("Error processing booking.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Car - Car Rental Platform</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        header .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header .header-container h1 {
            margin: 0;
        }
        header .header-container a button {
            background-color: #008CBA;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        header .header-container a button:hover {
            background-color: #006f8e;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px;
            position: relative;
            bottom: 0;
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <h1>Book Car - <?php echo htmlspecialchars($car['Make'] . " " . $car['Model']); ?></h1>
        <a href="view_cars.php">
            <button>View Cars</button>
        </a>
    </div>
</header>

<div class="container">
    <h3>Car Details</h3>
    <table>
        <tr>
            <th>Owner</th>
            <td><?php echo htmlspecialchars($car['OwnerName']); ?></td>
        </tr>
        <tr>
            <th>Make</th>
            <td><?php echo htmlspecialchars($car['Make']); ?></td>
        </tr>
        <tr>
            <th>Model</th>
            <td><?php echo htmlspecialchars($car['Model']); ?></td>
        </tr>
        <tr>
            <th>Year</th>
            <td><?php echo htmlspecialchars($car['Year']); ?></td>
        </tr>
        <tr>
            <th>Registration</th>
            <td><?php echo htmlspecialchars($car['RegistrationNumber']); ?></td>
        </tr>
        <tr>
            <th>Daily Rate</th>
            <td>ZAR <?php echo number_format($car['DailyRate'], 2); ?></td>
        </tr>
        <tr>
            <th>Location</th>
            <td><?php echo htmlspecialchars($car['Location']); ?></td>
        </tr>
    </table>

    <h3>Enter Your Details</h3>
    <form method="POST" action="">
    <!-- Hidden input to pass Car_ID -->
    <input type="hidden" name="Car_ID" value="<?= htmlspecialchars($Car_ID) ?>">

    <!-- Other form fields -->   

    <label for="start_date">Start Date:</label>
    <input type="date" id="start_date" name="start_date" required><br><br>

    <label for="end_date">End Date:</label>
    <input type="date" id="end_date" name="end_date" required><br><br>

    <!-- CSRF token -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <button type="submit">Submit</button>
</form>
</div>

<footer>
    <p>&copy; 2025 Car Rental Platform. All rights reserved.</p>
</footer>

</body>
</html>

<?php
$conn->close();
?>
