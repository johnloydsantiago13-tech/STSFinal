<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";
require_token();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["message" => "POST requests only."]);
    exit;
}

$accidentId = filter_var($_POST["accident_id"] ?? "", FILTER_VALIDATE_INT);

if ($accidentId === false || $accidentId < 1) {
    http_response_code(400);
    echo json_encode(["message" => "accident_id is required."]);
    exit;
}

if (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["message" => "No valid image was uploaded."]);
    exit;
}

$file = $_FILES["image"];

if ($file["size"] > 5 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(["message" => "Image must be 5 MB or smaller."]);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file["tmp_name"]);

$allowedTypes = [
    "image/png"  => "png",
    "image/jpeg" => "jpg"
];

if (!isset($allowedTypes[$mime])) {
    http_response_code(415);
    echo json_encode(["message" => "Only PNG and JPEG images are allowed."]);
    exit;
}

// Make sure the accident exists
$check = $conn->prepare("SELECT id FROM accidents WHERE id = ?");
$check->bind_param("i", $accidentId);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["message" => "Accident not found."]);
    exit;
}
$check->close();

$uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    http_response_code(500);
    echo json_encode(["message" => "Could not create upload directory."]);
    exit;
}

$fileName = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mime];
$destination = $uploadDir . $fileName;

if (!move_uploaded_file($file["tmp_name"], $destination)) {
    http_response_code(500);
    echo json_encode(["message" => "Could not save image."]);
    exit;
}

$imagePath = "uploads/" . $fileName;

$update = $conn->prepare("UPDATE accidents SET image_path = ? WHERE id = ?");
$update->bind_param("si", $imagePath, $accidentId);

if (!$update->execute()) {
    http_response_code(500);
    echo json_encode(["message" => "Image saved but database update failed."]);
    exit;
}

echo json_encode([
    "message"   => "Image uploaded successfully.",
    "accident_id" => $accidentId,
    "filename"  => $fileName,
    "imagePath" => $imagePath
]);

$update->close();
$conn->close();
