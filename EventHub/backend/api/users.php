
<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . "/api_logger.php";

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    logApiRequest(204);
    exit;
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/auth.php";

// RESPONSE FUNCTION
function respond($status, $success, $message, $extra = []) {
    http_response_code($status);
    logApiRequest($status);

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    exit;
}

// AUTHENTICATION AND RBAC
// Only logged-in admins can access this API.
if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    respond(401, false, "Authentication required. Please login.");
}

try {
    $authStmt = $pdo->prepare(
        "SELECT role FROM users WHERE user_id = ? LIMIT 1"
    );
    $authStmt->execute([(int) $_SESSION["user_id"]]);
    $currentUser = $authStmt->fetch();

    if (!$currentUser) {
        $_SESSION = [];
        session_destroy();
        respond(401, false, "Session user no longer exists. Please login.");
    }

    if ($currentUser["role"] !== "admin") {
        respond(403, false, "Access denied. Admin role required.");
    }
} catch (PDOException $e) {
    error_log("Users authentication error: " . $e->getMessage());
    respond(500, false, "Authentication check failed");
}

// GET USER ID
function getId() {
    $id = filter_var(
        $_GET["id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($id === false || $id === null || $id <= 0) {
        respond(400, false, "Valid user ID is required");
    }

    return $id;
}

// GET JSON BODY
function getBody() {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        json_last_error() !== JSON_ERROR_NONE ||
        !is_array($data) ||
        array_is_list($data)
    ) {
        respond(400, false, "Valid JSON object is required");
    }

    return $data;
}

// VALIDATE USER DATA
function validateUser($data, $requirePassword = false) {
    foreach (["name", "email", "role"] as $field) {
        if (!array_key_exists($field, $data)) {
            respond(400, false, "$field is required");
        }
    }

    if (
        !is_string($data["name"]) ||
        trim($data["name"]) === "" ||
        strlen(trim($data["name"])) > 150
    ) {
        respond(400, false, "Valid name is required");
    }

    if (
        !is_string($data["email"]) ||
        !filter_var(trim($data["email"]), FILTER_VALIDATE_EMAIL)
    ) {
        respond(400, false, "Valid email is required");
    }

    if (
        !is_string($data["role"]) ||
        !in_array(
            $data["role"],
            ["admin", "organiser", "customer"],
            true
        )
    ) {
        respond(400, false, "Invalid user role");
    }

    $result = [
        "name" => trim($data["name"]),
        "email" => strtolower(trim($data["email"])),
        "role" => $data["role"]
    ];

    if ($requirePassword || array_key_exists("password", $data)) {
        if (
            !isset($data["password"]) ||
            !is_string($data["password"]) ||
            strlen($data["password"]) < 8 ||
            strlen($data["password"]) > 72
        ) {
            respond(400, false, "Password must be 8 to 72 characters");
        }

        $result["password_hash"] = password_hash(
            $data["password"],
            PASSWORD_BCRYPT
        );
    }

    return $result;
}

// FIND USER
function findUser($pdo, $id) {
    $stmt = $pdo->prepare(
        "SELECT user_id, name, email, role, created_at
         FROM users
         WHERE user_id = ?"
    );

    $stmt->execute([$id]);

    return $stmt->fetch();
}

// CHECK DUPLICATE EMAIL
function emailExists($pdo, $email, $excludeId = null) {
    $sql = "SELECT user_id FROM users WHERE email = ?";
    $params = [$email];

    if ($excludeId !== null) {
        $sql .= " AND user_id != ?";
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetch();
}

$method = $_SERVER["REQUEST_METHOD"];

try {
    switch ($method) {

        // GET USERS
        case "GET":

            if (isset($_GET["id"])) {
                $user = findUser($pdo, getId());

                if (!$user) {
                    respond(404, false, "User not found");
                }

                respond(200, true, "User found", [
                    "data" => $user
                ]);
            }

            $stmt = $pdo->query(
                "SELECT user_id, name, email, role, created_at
                 FROM users
                 ORDER BY user_id ASC"
            );

            $users = $stmt->fetchAll();

            respond(200, true, "Users retrieved", [
                "count" => count($users),
                "data" => $users
            ]);

        // CREATE USER
        case "POST":

            $data = validateUser(getBody(), true);

            if (emailExists($pdo, $data["email"])) {
                respond(409, false, "Email already exists");
            }

            $stmt = $pdo->prepare(
                "INSERT INTO users
                 (name, email, password_hash, role, created_at)
                 VALUES
                 (:name, :email, :password_hash, :role, NOW())"
            );

            $stmt->execute($data);

            $user = findUser(
                $pdo,
                (int) $pdo->lastInsertId()
            );

            respond(201, true, "User created", [
                "data" => $user
            ]);

        // UPDATE USER
        case "PUT":

            $id = getId();

            if (!findUser($pdo, $id)) {
                respond(404, false, "User not found");
            }

            $data = validateUser(getBody());

            if (emailExists($pdo, $data["email"], $id)) {
                respond(409, false, "Email already exists");
            }

            $sql = "UPDATE users
                    SET name = :name,
                        email = :email,
                        role = :role";

            if (isset($data["password_hash"])) {
                $sql .= ", password_hash = :password_hash";
            }

            $sql .= " WHERE user_id = :user_id";

            $data["user_id"] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            respond(200, true, "User updated", [
                "data" => findUser($pdo, $id)
            ]);

        // DELETE USER
        case "DELETE":

            $id = getId();

            if (!findUser($pdo, $id)) {
                respond(404, false, "User not found");
            }

            // Prevent admin from deleting their own account.
            if ($id === (int) $_SESSION["user_id"]) {
                respond(
                    403,
                    false,
                    "You cannot delete your own admin account"
                );
            }

            $stmt = $pdo->prepare(
                "DELETE FROM users WHERE user_id = ?"
            );

            $stmt->execute([$id]);

            respond(200, true, "User deleted");

        // INVALID METHOD
        default:

            header("Allow: GET, POST, PUT, DELETE, OPTIONS");
            respond(405, false, "Method not allowed");
    }

} catch (PDOException $e) {
    error_log("Users API error: " . $e->getMessage());

    if ($e->getCode() === "23000") {
        respond(
            409,
            false,
            "Duplicate email or user is referenced by existing records"
        );
    }

    respond(500, false, "Database operation failed");
}
?>
