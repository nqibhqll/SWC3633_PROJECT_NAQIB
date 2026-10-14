<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/../config/database.php";

function respond($status, $success, $message, $extra = []) {
    http_response_code($status);

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    respond(405, false, "Method not allowed.");
}

if (isset($_SESSION["user_id"])) {
    respond(403, false, "Please logout before registering a new account.");
}

if (strlen(file_get_contents("php://input")) > 16384) {
    respond(413, false, "Request body is too large.");
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    respond(400, false, "Invalid JSON request.");
}

$name = trim($input["name"] ?? "");
$email = strtolower(trim($input["email"] ?? ""));
$password = $input["password"] ?? "";
$confirmPassword = $input["confirm_password"] ?? "";

if (
    !is_string($name) ||
    !is_string($email) ||
    !is_string($password) ||
    !is_string($confirmPassword)
) {
    respond(400, false, "Invalid registration data.");
}

if (
    $name === "" ||
    $email === "" ||
    $password === "" ||
    $confirmPassword === ""
) {
    respond(400, false, "All fields are required.");
}

if (mb_strlen($name) > 100) {
    respond(400, false, "Name must not exceed 100 characters.");
}

if (
    strlen($email) > 254 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    respond(400, false, "Please enter a valid email address.");
}

if (strlen($password) < 8) {
    respond(400, false, "Password must contain at least 8 characters.");
}

if (strlen($password) > 72) {
    respond(400, false, "Password must not exceed 72 bytes.");
}

if ($password !== $confirmPassword) {
    respond(400, false, "Passwords do not match.");
}

try {
    /*
     * This code expects config/database.php to expose
     * a PDO connection in $pdo.
     */

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        respond(
            500,
            false,
            "Database connection configuration is unavailable."
        );
    }

    $check = $pdo->prepare(
        "SELECT user_id FROM users WHERE email = ? LIMIT 1"
    );

    $check->execute([$email]);

    if ($check->fetch(PDO::FETCH_ASSOC)) {
        respond(409, false, "This email address is already registered.");
    }

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($passwordHash === false) {
        respond(500, false, "Unable to process password.");
    }

    $insert = $pdo->prepare(
        "INSERT INTO users (name, email, password_hash, role)
         VALUES (?, ?, ?, 'customer')"
    );

    $insert->execute([
        $name,
        $email,
        $passwordHash
    ]);

    respond(201, true, "Registration successful. You can now sign in.");

} catch (PDOException $error) {
    if ($error->getCode() === "23000") {
        respond(409, false, "This email address is already registered.");
    }

    error_log("EventHub registration error: " . $error->getMessage());

    respond(500, false, "Registration failed. Please try again.");
}
?>
