<?php
require_once "helpers.php";

$filter = $_GET["severity"] ?? "";
$allowed = ["Low", "Medium", "High"];
$where = "";
$params = [];

if (in_array($filter, $allowed, true)) {
    $where = "WHERE severity = ?";
    $params[] = $filter;
}

$sql = "SELECT id, vehicle, guardrail, accident_time, speed_studs_per_second,
               severity, position, image_path
        FROM accidents $where
        ORDER BY id DESC
        LIMIT 60";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param("s", $params[0]);
}
$stmt->execute();
$accidents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stats = $conn->query(
    "SELECT COUNT(*) AS total,
            SUM(severity = 'High') AS high,
            SUM(severity = 'Medium') AS medium,
            MAX(speed_studs_per_second) AS top_speed
     FROM accidents"
)->fetch_assoc();

// Partial mode: used by the auto-refresh script
$partial = isset($_GET["partial"]);

ob_start();
?>
<?php if (!$accidents): ?>
    <div class="empty">No accidents recorded yet. Crash a car into a guardrail in Roblox Studio.</div>
<?php else: ?>
    <div class="grid">
    <?php foreach ($accidents as $a): $img = image_url($a["image_path"]); ?>
        <a class="card" href="accident.php?id=<?= (int)$a["id"] ?>">
            <div class="thumb">
                <?php if ($img): ?>
                    <img src="<?= e($img) ?>" alt="Accident #<?= (int)$a["id"] ?>" loading="lazy">
                <?php else: ?>
                    <span class="noimg">No screenshot</span>
                <?php endif; ?>
                <span class="badge <?= severity_class($a["severity"]) ?>"><?= e($a["severity"]) ?></span>
            </div>
            <div class="body">
                <h3>#<?= (int)$a["id"] ?> &middot; <?= e($a["vehicle"]) ?></h3>
                <p class="muted"><?= e(format_time($a["accident_time"])) ?></p>
                <p>Speed: <strong><?= (int)$a["speed_studs_per_second"] ?></strong> studs/s</p>
                <p class="muted">Guardrail: <?= e($a["guardrail"]) ?></p>
            </div>
        </a>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
$gridHtml = ob_get_clean();

if ($partial) {
    echo $gridHtml;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kurbada Accident Monitor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="top">
    <div class="wrap">
        <h1>Kurbada</h1>
        <p class="muted">Guardrail accident monitor</p>
    </div>
</header>

<main class="wrap">
    <section class="stats">
        <div class="stat"><span><?= (int)$stats["total"] ?></span>Total accidents</div>
        <div class="stat"><span><?= (int)$stats["high"] ?></span>High severity</div>
        <div class="stat"><span><?= (int)$stats["medium"] ?></span>Medium severity</div>
        <div class="stat"><span><?= (int)$stats["top_speed"] ?></span>Top speed (studs/s)</div>
    </section>

    <nav class="filters">
        <a href="index.php" class="<?= $filter === "" ? "on" : "" ?>">All</a>
        <?php foreach ($allowed as $s): ?>
            <a href="index.php?severity=<?= e($s) ?>" class="<?= $filter === $s ? "on" : "" ?>"><?= e($s) ?></a>
        <?php endforeach; ?>
        <span class="muted live">Auto-refreshing every 5s</span>
    </nav>

    <div id="list"><?= $gridHtml ?></div>
</main>

<script>
const params = new URLSearchParams(window.location.search);
params.set("partial", "1");

setInterval(async () => {
    try {
        const res = await fetch("index.php?" + params.toString(), { cache: "no-store" });
        if (res.ok) {
            document.getElementById("list").innerHTML = await res.text();
        }
    } catch (e) { /* ignore, try again next tick */ }
}, 5000);
</script>
</body>
</html>
