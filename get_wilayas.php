<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "medchifagiz");

$sql = "SELECT id, name_fr, name_ar FROM wilayas";
$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>