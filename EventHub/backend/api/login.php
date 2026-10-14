
<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . "/api_logger.php";

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    logApiRequest(204);
    exit;
}

function respond($status, $success, $message, $extra = []) {
    http_response_code($status);
    logApiRequest($status);

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST, OPTIONS");
    respond(405, false, "Method not allowed");
}

require_once __DIR__ . "/../config/database.php";

session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data) || array_is_list($data)) {
    respond(400, false, "Valid JSON object is required");
}

$email = $data["email"] ?? null;
$password = $data["password"] ?? null;

if (!is_string($email) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !is_string($password) ||
    $password === "") {
    respond(400, false, "Valid email and password are required");
}

try {
    $stmt = $pdo->prepare(
        "SELECT user_id, name, email, password_hash, role
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user["password_hash"])) {
        respond(401, false, "Invalid email or password");
    }

    session_regenerate_id(true);

    $_SESSION["user_id"] = (int)$user["user_id"];
    $_SESSION["role"] = $user["role"];

    respond(200, true, "Login successful", [
        "data" => [
            "user_id" => (int)$user["user_id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "role" => $user["role"]
        ]
    ]);

} catch (PDOException $e) {
    error_log("Login API error: " . $e->getMessage());
    respond(500, false, "Login failed due to server error");
}
?>
