<?php
// Called by kurbada_capture.py. Returns the oldest recent accident
// that does not have a screenshot yet.
header("Content-Type: application/json; charset=UTF-8");

require_once "config.php";
require_token();
require_once "db.php";

$result = $conn->query(
    "SELECT id, vehicle, severity
     FROM accidents
     WHERE image_path IS NULL
       AND severity IN ('Medium', 'High')
       AND accident_time > (UTC_TIMESTAMP() - INTERVAL 2 MINUTE)
     ORDER BY id ASC
     LIMIT 1"
);

$row = $result ? $result->fetch_assoc() : null;

echo json_encode([
    "pending" => $row ? true : false,
    "accident" => $row
]);

$conn->close();
