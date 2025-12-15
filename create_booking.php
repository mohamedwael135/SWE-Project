<?php
include 'db_connect.php';

header('Content-Type: application/json');

// Start session to store booking data
session_start();

// Get POST data
$flight_id = $_POST['flight_id'] ?? 0;
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$dob = $_POST['dob'] ?? '';
$passport = $_POST['passport'] ?? '';
$nationality = $_POST['nationality'] ?? '';
$card_number = $_POST['card_number'] ?? '';

// Validate inputs
if(empty($flight_id) || empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Check if user exists, if not create one
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
    } else {
        // Create new user
        $default_password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $email, $default_password, $first_name, $last_name, $phone);
        $stmt->execute();
        $user_id = $conn->insert_id;
    }
    
    // Get flight details
    $stmt = $conn->prepare("SELECT price, available_seats FROM flights WHERE flight_id = ?");
    $stmt->bind_param("i", $flight_id);
    $stmt->execute();
    $flight = $stmt->get_result()->fetch_assoc();
    
    if($flight['available_seats'] <= 0) {
        throw new Exception("No available seats");
    }
    
    $price = $flight['price'];
    
    // Generate booking reference
    $booking_ref = 'OBA' . strtoupper(substr(md5(uniqid()), 0, 6));
    
    // Create booking
    $stmt = $conn->prepare("INSERT INTO bookings (booking_ref, user_id, flight_id, total_amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siid", $booking_ref, $user_id, $flight_id, $price);
    $stmt->execute();
    $booking_id = $conn->insert_id;
    
    // Add passenger
    $stmt = $conn->prepare("INSERT INTO passengers (booking_id, first_name, last_name, date_of_birth, passport_number, nationality) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $booking_id, $first_name, $last_name, $dob, $passport, $nationality);
    $stmt->execute();
    
    // Create payment record
    $payment_method = 'Credit Card';
    $payment_status = 'completed';
    $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $booking_id, $price, $payment_method, $payment_status);
    $stmt->execute();
    
    // Update available seats
    $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats - 1 WHERE flight_id = ?");
    $stmt->bind_param("i", $flight_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Booking created successfully',
        'booking_ref' => $booking_ref,
        'booking_id' => $booking_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
