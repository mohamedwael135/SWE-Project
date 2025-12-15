<?php
include 'db_connect.php';

header('Content-Type: application/json');

$booking_id = $_POST['booking_id'] ?? 0;
$luggage_type = $_POST['luggage_type'] ?? '';
$weight = $_POST['weight'] ?? 0;
$price = $_POST['price'] ?? 0;

if(empty($booking_id) || empty($luggage_type)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$conn->begin_transaction();

try {
    // Check if booking exists and is confirmed
    $stmt = $conn->prepare("SELECT status, total_amount FROM bookings WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 0) {
        throw new Exception("Booking not found");
    }
    
    $booking = $result->fetch_assoc();
    
    if($booking['status'] !== 'confirmed') {
        throw new Exception("Cannot add luggage to cancelled booking");
    }
    
    // Add luggage
    $stmt = $conn->prepare("INSERT INTO luggage (booking_id, luggage_type, weight, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isdd", $booking_id, $luggage_type, $weight, $price);
    $stmt->execute();
    
    // Update total amount
    $new_total = $booking['total_amount'] + $price;
    $stmt = $conn->prepare("UPDATE bookings SET total_amount = ? WHERE booking_id = ?");
    $stmt->bind_param("di", $new_total, $booking_id);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Luggage added successfully',
        'new_total' => $new_total
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
