<?php
session_start();
$conn = new mysqli("localhost", "root", "", "car_rental");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in as a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Customer')
    $_SESSION['user_id'] = $user['UserID'];
    $_SESSION['user_type'] = $user['Customer'];
 {
    die("You must be logged in as a customer to rate an owner.");
}

// Validate POST data
$owner_id = isset($_POST['owner_id']) ? intval($_POST['owner_id']) : 0;
$car_id = isset($_POST['car_id']) ? intval($_POST['car_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

$errors = [];
if ($owner_id <= 0) $errors[] = "Invalid owner.";
if ($car_id <= 0) $errors[] = "Invalid car.";
if ($rating < 1 || $rating > 5) $errors[] = "Rating must be between 1 and 5.";

// Prevent duplicate ratings
$check = $conn->prepare("SELECT * FROM OwnerRatings WHERE OwnerID=? AND CarID=? AND CustomerID=?");
$check->bind_param("iii", $owner_id, $car_id, $_SESSION['user_id']);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $errors[] = "You have already rated this owner for this car.";
}
$check->close();

if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    echo "<a href='customer_dashboard.php'>Back to Dashboard</a>";
    $conn->close();
    exit;
}

// Insert the rating (do NOT include RatingID)
$stmt = $conn->prepare("INSERT INTO OwnerRatings (OwnerID, CarID, CustomerID, Rating, Comment, RatedAt) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("iiiis", $owner_id, $car_id, $_SESSION['user_id'], $rating, $comment);

if ($stmt->execute()) {
    header("Location: customer_dashboard.php?msg=Rating+submitted+successfully");
    exit;
} else {
    error_log("Error submitting rating: " . $stmt->error); // Log the error for debugging
    echo "<p style='color:red;'>Error submitting rating. Please try again.</p>";
    echo "<a href='customer_dashboard.php'>Back to Dashboard</a>";
}
$conn->close();
?>