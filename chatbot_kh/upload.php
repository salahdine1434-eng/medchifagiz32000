<?php
$response = [];

if(isset($_FILES['image'])){
    $targetDir = "uploads/";

    if(!is_dir($targetDir)){
        mkdir($targetDir);
    }

    $fileName = time() . "_" . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        $response["image"] = $targetFile; // ✅ صححناها
    } else {
        $response["error"] = "Upload failed";
    }
}

if(isset($_POST['message'])){
    $response["message"] = $_POST['message'];
}

header("Content-Type: application/json");
echo json_encode($response);
?>