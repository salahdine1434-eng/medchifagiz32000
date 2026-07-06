<?php
$conn = new mysqli("localhost", "root", "", "medchifagiz");

$sql = "SELECT id, name_ar FROM specialties";
$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>