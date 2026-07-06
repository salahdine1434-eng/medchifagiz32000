<?php
header('Content-Type: application/json; charset=utf-8');

$conn = new mysqli("localhost", "root", "", "medchifagiz");
$conn->set_charset("utf8mb4");

$wilaya = $_GET['wilaya'] ?? '';

$sql = "SELECT c.name_fr, c.name_ar
        FROM communes c
        JOIN wilayas w ON c.wilaya_id = w.id
        WHERE w.name_ar = '$wilaya'";

$result = $conn->query($sql);

$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>