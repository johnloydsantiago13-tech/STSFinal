<?php
define("API_TOKEN", "jlx2027");

function require_token(): void
{
    $provided = $_SERVER["HTTP_X_API_TOKEN"] ?? "";

    if (!hash_equals(API_TOKEN, $provided)) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized."]);
        exit;
    }
}
