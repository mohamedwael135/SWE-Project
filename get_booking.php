<?php
include 'db_connect.php';

header('Content-Type: application/json');

$booking_ref = $_GET['booking_ref'] ?? '';
$email = $_GET['email'] ?? '';

if(empty($booking_ref) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Missing booking reference or email']);
    exit;
}

$sql = "SELECT b.*, f.*, a.airline_name, p.first_name as passenger_first, p.last_name as passenger_last, 
        p.passport_number, pay.payment_status, pay.payment_method
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN flights f ON b.flight_id = f.flight_id
        JOIN airlines a ON f.airline_id = a.airline_id
        LEFT JOIN passengers p ON b.booking_id = p.booking_id
        LEFT JOIN payments pay ON b.booking_id = pay.booking_id
        WHERE b.booking_ref = ? AND u.email = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $booking_ref, $email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    // Get luggage for this booking
    $luggage_sql = "SELECT * FROM luggage WHERE booking_id = ?";
    $luggage_stmt = $conn->prepare($luggage_sql);
    $luggage_stmt->bind_param("i", $booking['booking_id']);
    $luggage_stmt->execute();
    $luggage_result = $luggage_stmt->get_result();
    
    $luggage = [];
    while($row = $luggage_result->fetch_assoc()) {
        $luggage[] = $row;
    }
    
    $booking['luggage'] = $luggage;
    
    echo json_encode(['success' => true, 'booking' => $booking]);
    $luggage_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}

$stmt->close();
$conn->close();
?>
