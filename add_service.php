<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include './config/db.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    // FIX 1: Change the array key lookup from 'price' to 'starting_rating' 
    // to match the hidden input field name in staff_dashboard.php.
    // The variable name $price is kept for the database binding (line 33).
    if (isset($_POST['starting_rating'])) {
        $price = $_POST['starting_rating'];
    } else {
        // Fallback or error handling if the rating somehow isn't set, 
        // though the field is 'required' in the HTML.
        // Since 'price' cannot be null, we must set a default or exit.
        // Let's assume 0.0 is the fallback for the star rating value.
        $price = 0.0; 
    }
    
    // The previous line 17: $price = $_POST['price']; 
    // is now resolved by the logic above.
    
    $staff_id = $_SESSION['user']['id'];
    
    $image_data = null; 
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_data = file_get_contents($_FILES['image']['tmp_name']);
    }

    // UPDATED SQL to include price and image_data
    $sql = "INSERT INTO services (title, description, price, image_data, uploaded_by_staff_id, status) VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters, using PDO::PARAM_LOB for image_data
    $stmt->bindParam(1, $title);
    $stmt->bindParam(2, $description);
    // Bind $price (which now holds the star rating from 'starting_rating')
    $stmt->bindParam(3, $price); 
    $stmt->bindParam(4, $image_data, PDO::PARAM_LOB); 
    $stmt->bindParam(5, $staff_id);
    
    // FIX 2: This is line 36 where the Fatal error was thrown. 
    // It is now fixed because $price is guaranteed to be set.
    $stmt->execute();

    header('Location: staff_dashboard.php?message=Service submitted for approval.');
    exit;
}
?>