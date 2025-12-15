<?php
include 'db_connect.php';

header('Content-Type: application/json');

$from = $_POST['from'] ?? '';
$to = $_POST['to'] ?? '';
$date = $_POST['date'] ?? '';

// If no search criteria, return all flights
if(empty($from) && empty($to) && empty($date)) {
    $sql = "SELECT f.*, a.airline_name 
            FROM flights f 
            JOIN airlines a ON f.airline_id = a.airline_id 
            WHERE f.available_seats > 0
            ORDER BY f.departure_date, f.departure_time
            LIMIT 50";
    $result = $conn->query($sql);
} else {
    // Flexible search - match any part of city name
    $sql = "SELECT f.*, a.airline_name 
            FROM flights f 
            JOIN airlines a ON f.airline_id = a.airline_id 
            WHERE f.available_seats > 0";
    
    $params = [];
    $types = '';
    
    if(!empty($from)) {
        $sql .= " AND f.departure_city LIKE ?";
        $params[] = "%$from%";
        $types .= 's';
    }
    
    if(!empty($to)) {
        $sql .= " AND f.arrival_city LIKE ?";
        $params[] = "%$to%";
        $types .= 's';
    }
    
    if(!empty($date)) {
        $sql .= " AND f.departure_date = ?";
        $params[] = $date;
        $types .= 's';
    }
    
    $sql .= " ORDER BY f.departure_time";
    
    $stmt = $conn->prepare($sql);
    if(!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
}

$flights = [];
while($row = $result->fetch_assoc()) {
    $flights[] = $row;
}

echo json_encode($flights);

$conn->close();
?>
