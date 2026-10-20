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

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON."]);
    exit;
}

$vehicle   = trim($input["vehicle"] ?? "");
$guardrail = trim($input["guardrail"] ?? "");
$severity  = ucfirst(strtolower(trim($input["severity"] ?? "")));
$speed     = filter_var($input["speedStudsPerSecond"] ?? 0, FILTER_VALIDATE_INT);
$position  = substr(trim($input["position"] ?? ""), 0, 255);

$allowedSeverity = ["Low", "Medium", "High"];

if (
    $vehicle === "" ||
    $guardrail === "" ||
    !in_array($severity, $allowedSeverity, true) ||
    $speed === false ||
    $speed < 0
) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid accident data."]);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO accidents
    (vehicle, guardrail, accident_time, speed_studs_per_second, severity, position)
    VALUES (?, ?, UTC_TIMESTAMP(), ?, ?, ?)"
);
$stmt->bind_param("ssiss", $vehicle, $guardrail, $speed, $severity, $position);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        "message" => "Accident saved.",
        "id" => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Could not save accident."]);
}

$stmt->close();
$conn->close();
