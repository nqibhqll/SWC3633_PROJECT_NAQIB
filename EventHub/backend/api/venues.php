
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

    echo json_encode(
        array_merge([
            "success" => $success,
            "message" => $message
        ], $extra),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );

    exit;
}

// ADMIN-ONLY ACCESS FOR WRITE OPERATIONS
function requireAdminAccess($pdo) {
    if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
        respond(401, false, "Authentication required. Please login.");
    }

    $stmt = $pdo->prepare(
        "SELECT role FROM users WHERE user_id = ? LIMIT 1"
    );

    $stmt->execute([(int) $_SESSION["user_id"]]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION = [];
        session_destroy();

        respond(
            401,
            false,
            "Session user no longer exists. Please login."
        );
    }

    if ($user["role"] !== "admin") {
        respond(403, false, "Access denied. Admin role required.");
    }

    return true;
}

// GET VENUE ID
function getId() {
    $id = filter_var(
        $_GET["id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($id === false || $id === null || $id <= 0) {
        respond(400, false, "Valid venue ID is required");
    }

    return $id;
}

// GET JSON BODY
function getBody() {
    $body = json_decode(file_get_contents("php://input"), true);

    if (
        json_last_error() !== JSON_ERROR_NONE ||
        !is_array($body) ||
        array_is_list($body)
    ) {
        respond(400, false, "Valid JSON object is required");
    }

    return $body;
}

// VALIDATE VENUE DATA
function validateVenue($data) {
    foreach (["venue_name", "address", "capacity"] as $field) {
        if (!array_key_exists($field, $data)) {
            respond(400, false, "$field is required");
        }
    }

    if (
        !is_string($data["venue_name"]) ||
        trim($data["venue_name"]) === "" ||
        strlen(trim($data["venue_name"])) > 150
    ) {
        respond(400, false, "Valid venue name is required");
    }

    if (
        !is_string($data["address"]) ||
        trim($data["address"]) === ""
    ) {
        respond(400, false, "Valid address is required");
    }

    $capacity = filter_var(
        $data["capacity"],
        FILTER_VALIDATE_INT
    );

    if ($capacity === false || $capacity <= 0) {
        respond(400, false, "Capacity must be greater than zero");
    }

    return [
        "venue_name" => trim($data["venue_name"]),
        "address" => trim($data["address"]),
        "capacity" => $capacity
    ];
}

// FIND VENUE
function findVenue($pdo, $id) {
    $stmt = $pdo->prepare(
        "SELECT * FROM venues WHERE venue_id = ?"
    );

    $stmt->execute([$id]);

    return $stmt->fetch();
}

$method = $_SERVER["REQUEST_METHOD"];

try {
    switch ($method) {

        // GET VENUES - PUBLIC ACCESS
        case "GET":

            if (isset($_GET["id"])) {
                $venue = findVenue($pdo, getId());

                if (!$venue) {
                    respond(404, false, "Venue not found");
                }

                respond(200, true, "Venue found", [
                    "data" => $venue
                ]);
            }

            $stmt = $pdo->query(
                "SELECT * FROM venues ORDER BY venue_id ASC"
            );

            $venues = $stmt->fetchAll();

            respond(200, true, "Venues retrieved", [
                "count" => count($venues),
                "data" => $venues
            ]);

        // CREATE VENUE - ADMIN ONLY
        case "POST":

            requireAdminAccess($pdo);

            $data = validateVenue(getBody());

            $stmt = $pdo->prepare(
                "INSERT INTO venues
                 (venue_name, address, capacity)
                 VALUES
                 (:venue_name, :address, :capacity)"
            );

            $stmt->execute($data);

            $venue = findVenue(
                $pdo,
                (int) $pdo->lastInsertId()
            );

            respond(201, true, "Venue created", [
                "data" => $venue
            ]);

        // UPDATE VENUE - ADMIN ONLY
        case "PUT":

            requireAdminAccess($pdo);

            $id = getId();

            if (!findVenue($pdo, $id)) {
                respond(404, false, "Venue not found");
            }

            $data = validateVenue(getBody());

            $stmt = $pdo->prepare(
                "UPDATE venues
                 SET venue_name = :venue_name,
                     address = :address,
                     capacity = :capacity
                 WHERE venue_id = :venue_id"
            );

            $data["venue_id"] = $id;
            $stmt->execute($data);

            respond(200, true, "Venue updated", [
                "data" => findVenue($pdo, $id)
            ]);

        // DELETE VENUE - ADMIN ONLY
        case "DELETE":

            requireAdminAccess($pdo);

            $id = getId();

            if (!findVenue($pdo, $id)) {
                respond(404, false, "Venue not found");
            }

            $stmt = $pdo->prepare(
                "DELETE FROM venues WHERE venue_id = ?"
            );

            $stmt->execute([$id]);

            respond(200, true, "Venue deleted");

        // INVALID HTTP METHOD
        default:

            header("Allow: GET, POST, PUT, DELETE, OPTIONS");
            respond(405, false, "Method not allowed");
    }

} catch (PDOException $e) {
    error_log("Venues API error: " . $e->getMessage());

    if ($e->getCode() === "23000") {
        respond(
            409,
            false,
            "Venue is referenced by existing records"
        );
    }

    respond(500, false, "Database operation failed");
}
?>
