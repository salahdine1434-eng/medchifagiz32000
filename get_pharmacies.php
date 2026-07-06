<?php
$conn = new mysqli("localhost", "root", "", "medchifagiz");

$wilaya  = $_GET['wilaya'] ?? '';
$commune = $_GET['commune'] ?? '';

$sql = "SELECT * FROM pharmacies";

$where = [];

if ($wilaya != '') {
   $where[] = "LOWER(pharmacies.wilaya) LIKE LOWER('%$wilaya%')";
}

if ($commune != '') {
   $where[] = "LOWER(pharmacies.commune) LIKE LOWER('%$commune%')";
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>