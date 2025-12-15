<?php
include 'db_connect.php';

header('Content-Type: application/json');

$sql = "SELECT * FROM luggage_prices ORDER BY price ASC";
$result = $conn->query($sql);

$prices = [];
while($row = $result->fetch_assoc()) {
    $prices[] = $row;
}

echo json_encode($prices);

$conn->close();
?>
