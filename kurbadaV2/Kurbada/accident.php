<?php
require_once "helpers.php";

$id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);

if ($id === false || $id < 1) {
    http_response_code(400);
    exit("Invalid accident ID.");
}

$stmt = $conn->prepare("SELECT * FROM accidents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$a = $stmt->get_result()->fetch_assoc();

if (!$a) {
    http_response_code(404);
    exit("Accident not found.");
}

$img = image_url($a["image_path"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accident #<?= (int)$a["id"] ?> - Kurbada</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top">
    <div class="wrap">
        <h1>Kurbada</h1>
        <p class="muted"><a href="index.php">&larr; Back to all accidents</a></p>
    </div>
</header>

<main class="wrap detail">
    <div class="shot">
        <?php if ($img): ?>
            <a href="<?= e($img) ?>" target="_blank" rel="noopener">
                <img src="<?= e($img) ?>" alt="Accident #<?= (int)$a["id"] ?> screenshot">
            </a>
        <?php else: ?>
            <div class="empty">No screenshot was captured for this accident.</div>
        <?php endif; ?>
    </div>

    <aside class="info">
        <h2>Accident #<?= (int)$a["id"] ?>
            <span class="badge <?= severity_class($a["severity"]) ?>"><?= e($a["severity"]) ?></span>
        </h2>
        <dl>
            <dt>Vehicle</dt><dd><?= e($a["vehicle"]) ?></dd>
            <dt>Guardrail</dt><dd><?= e($a["guardrail"]) ?></dd>
            <dt>Date &amp; time (PH)</dt><dd><?= e(format_time($a["accident_time"])) ?></dd>
            <dt>Speed</dt><dd><?= (int)$a["speed_studs_per_second"] ?> studs/second</dd>
            <dt>Position</dt><dd><?= e($a["position"]) ?></dd>
        </dl>
    </aside>
</main>
</body>
</html>
