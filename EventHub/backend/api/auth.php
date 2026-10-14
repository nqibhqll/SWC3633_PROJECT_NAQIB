<?php
// EventHub Session Authentication & RBAC

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Authentication required. Please login."
        ]);

        exit;
    }

    return (int) $_SESSION["user_id"];
}

function requireRole($allowedRoles) {
    requireLogin();

    if (!in_array($_SESSION["role"], $allowedRoles, true)) {
        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" => "Access denied. Insufficient permissions."
        ]);

        exit;
    }
}
?>
