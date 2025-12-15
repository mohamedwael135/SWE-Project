<?php
include 'db_connect.php';

header('Content-Type: application/json');

$booking_ref = $_POST['booking_ref'] ?? '';
$email = $_POST['email'] ?? '';

if(empty($booking_ref) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$conn->begin_transaction();

try {
    // Get booking details
    $stmt = $conn->prepare("SELECT b.booking_id, b.flight_id, b.status 
                           FROM bookings b 
                           JOIN users u ON b.user_id = u.user_id 
                           WHERE b.booking_ref = ? AND u.email = ?");
    $stmt->bind_param("ss", $booking_ref, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 0) {
        throw new Exception("Booking not found");
    }
    
    $booking = $result->fetch_assoc();
    
    if($booking['status'] == 'cancelled') {
        throw new Exception("Booking already cancelled");
    }
    
    // Update booking status
    $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_ref = ?");
    $stmt->bind_param("s", $booking_ref);
    $stmt->execute();
    
    // Return seat to available pool
    $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats + 1 WHERE flight_id = ?");
    $stmt->bind_param("i", $booking['flight_id']);
    $stmt->execute();
    
    // Update payment status
    $stmt = $conn->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?");
    $stmt->bind_param("i", $booking['booking_id']);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
