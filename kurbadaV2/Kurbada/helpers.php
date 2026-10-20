<?php
// Shared helpers for the website pages.
require_once __DIR__ . "/api/db.php";

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// accident_time is stored in UTC. Show it in Philippine time.
function format_time(string $utc): string
{
    $dt = new DateTime($utc, new DateTimeZone("UTC"));
    $dt->setTimezone(new DateTimeZone("Asia/Manila"));
    return $dt->format("M d, Y h:i:s A");
}

function severity_class(string $severity): string
{
    return "sev-" . strtolower($severity);
}

function image_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }
    // Only allow files from the uploads folder
    return preg_match('#^uploads/[a-f0-9]{32}\.(png|jpg)$#', $path) ? $path : null;
}
