
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

function getId() {
    $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);

    if ($id === false || $id === null || $id <= 0) {
        respond(400, false, "Valid event ID is required");
    }

    return $id;
}

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

function validInteger($value, $allowZero = false) {
    if (
        !is_int($value) &&
        !(is_string($value) && ctype_digit($value))
    ) {
        return false;
    }

    $number = filter_var($value, FILTER_VALIDATE_INT);

    if ($number === false) {
        return false;
    }

    if ($allowZero) {
        return $number >= 0 ? $number : false;
    }

    return $number > 0 ? $number : false;
}

function validDateTime($date) {
    if (!is_string($date)) {
        return false;
    }

    $parsed = DateTimeImmutable::createFromFormat(
        "!Y-m-d H:i:s",
        $date
    );

    return (
        $parsed !== false &&
        $parsed->format("Y-m-d H:i:s") === $date
    );
}

function recordExists($pdo, $table, $column, $id) {
    $allowed = [
        "venues" => "venue_id",
        "users" => "user_id"
    ];

    if (
        !isset($allowed[$table]) ||
        $allowed[$table] !== $column
    ) {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM $table WHERE $column = ?"
    );
    $stmt->execute([$id]);

    return (int) $stmt->fetchColumn() > 0;
}

function findEvent($pdo, $id) {
    $stmt = $pdo->prepare(
        "SELECT * FROM events WHERE event_id = ?"
    );
    $stmt->execute([$id]);

    return $stmt->fetch();
}

// Verify session user against database.
// GET remains public; write methods require authentication.
function currentWriter($pdo) {
    if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
        respond(401, false, "Authentication required. Please login.");
    }

    $stmt = $pdo->prepare(
        "SELECT user_id, role FROM users WHERE user_id = ?"
    );
    $stmt->execute([(int) $_SESSION["user_id"]]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION = [];
        session_destroy();
        respond(401, false, "Session user no longer exists. Please login.");
    }

    if (!in_array($user["role"], ["admin", "organiser"], true)) {
        respond(403, false, "Access denied. Admin or organiser role required.");
    }

    return $user;
}

function validateEvent($data, $pdo, $writer) {
    $required = [
        "event_name",
        "venue_id",
        "event_date",
        "ticket_price",
        "total_tickets",
        "tickets_available"
    ];

    foreach ($required as $field) {
        if (!array_key_exists($field, $data)) {
            respond(400, false, "$field is required");
        }
    }

    if (
        !is_string($data["event_name"]) ||
        trim($data["event_name"]) === "" ||
        mb_strlen(trim($data["event_name"])) > 150
    ) {
        respond(400, false, "Valid event name is required");
    }

    $venueId = validInteger($data["venue_id"]);
    $totalTickets = validInteger($data["total_tickets"]);
    $ticketsAvailable = validInteger(
        $data["tickets_available"],
        true
    );

    if ($venueId === false) {
        respond(400, false, "Invalid venue ID");
    }

    if ($totalTickets === false) {
        respond(400, false, "Total tickets must be greater than zero");
    }

    if (
        $ticketsAvailable === false ||
        $ticketsAvailable > $totalTickets
    ) {
        respond(400, false, "Invalid tickets available");
    }

    if (!validDateTime($data["event_date"])) {
        respond(
            400,
            false,
            "Event date must use YYYY-MM-DD HH:MM:SS"
        );
    }

    if (
        !is_numeric($data["ticket_price"]) ||
        (float) $data["ticket_price"] < 0 ||
        (float) $data["ticket_price"] > 99999999.99
    ) {
        respond(400, false, "Invalid ticket price");
    }

    if (!recordExists($pdo, "venues", "venue_id", $venueId)) {
        respond(404, false, "Venue does not exist");
    }

    // Organiser ID comes from the session for organisers.
    // Admin may specify an organiser_id.
    if ($writer["role"] === "organiser") {
        $organiserId = (int) $writer["user_id"];
    } else {
        $organiserId = validInteger($data["organiser_id"] ?? null);

        if ($organiserId === false) {
            respond(400, false, "Valid organiser ID is required");
        }
    }

    $stmt = $pdo->prepare(
        "SELECT role FROM users WHERE user_id = ?"
    );
    $stmt->execute([$organiserId]);
    $organiser = $stmt->fetch();

    if (!$organiser || $organiser["role"] !== "organiser") {
        respond(400, false, "Valid organiser is required");
    }

    $description = $data["description"] ?? null;

    if (
        $description !== null &&
        !is_string($description)
    ) {
        respond(400, false, "Invalid event description");
    }

    return [
        "event_name" => trim($data["event_name"]),
        "description" => $description,
        "venue_id" => $venueId,
        "organiser_id" => $organiserId,
        "event_date" => $data["event_date"],
        "ticket_price" => number_format(
            (float) $data["ticket_price"],
            2,
            ".",
            ""
        ),
        "total_tickets" => $totalTickets,
        "tickets_available" => $ticketsAvailable
    ];
}

function requireEventOwnership($event, $writer) {
    if (
        $writer["role"] !== "admin" &&
        (int) $event["organiser_id"] !== (int) $writer["user_id"]
    ) {
        respond(403, false, "Access denied. You do not own this event.");
    }
}

$method = $_SERVER["REQUEST_METHOD"];

try {
    switch ($method) {

        case "GET":
            if (isset($_GET["id"])) {
                $event = findEvent($pdo, getId());

                if (!$event) {
                    respond(404, false, "Event not found");
                }

                respond(200, true, "Event found", [
                    "data" => $event
                ]);
            }

            $conditions = [];
            $params = [];

            if (isset($_GET["search"])) {
                if (!is_string($_GET["search"])) {
                    respond(400, false, "Invalid search parameter");
                }

                $search = trim($_GET["search"]);

                if ($search !== "") {
                    $conditions[] = "event_name LIKE ?";
                    $params[] = "%" . $search . "%";
                }
            }

            if (isset($_GET["venue_id"])) {
                $venueId = validInteger($_GET["venue_id"]);

                if ($venueId === false) {
                    respond(400, false, "Invalid venue filter");
                }

                $conditions[] = "venue_id = ?";
                $params[] = $venueId;
            }

            if (isset($_GET["event_date"])) {
                $eventDate = $_GET["event_date"];

                if (
                    !is_string($eventDate) ||
                    !($parsed = DateTimeImmutable::createFromFormat(
                        "!Y-m-d",
                        $eventDate
                    )) ||
                    $parsed->format("Y-m-d") !== $eventDate
                ) {
                    respond(400, false, "Invalid event date filter");
                }

                $conditions[] = "DATE(event_date) = ?";
                $params[] = $eventDate;
            }

            $sql = "SELECT * FROM events";

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " ORDER BY event_date ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $events = $stmt->fetchAll();

            respond(200, true, "Events retrieved", [
                "count" => count($events),
                "data" => $events
            ]);

        case "POST":
            $writer = currentWriter($pdo);
            $data = validateEvent(getBody(), $pdo, $writer);

            $stmt = $pdo->prepare(
                "INSERT INTO events
                (venue_id, organiser_id, event_name,
                 description, event_date, ticket_price,
                 total_tickets, tickets_available)
                 VALUES
                (:venue_id, :organiser_id, :event_name,
                 :description, :event_date, :ticket_price,
                 :total_tickets, :tickets_available)"
            );

            $stmt->execute($data);
            $id = (int) $pdo->lastInsertId();

            respond(201, true, "Event created", [
                "data" => findEvent($pdo, $id)
            ]);

        case "PUT":
            $writer = currentWriter($pdo);
            $id = getId();
            $event = findEvent($pdo, $id);

            if (!$event) {
                respond(404, false, "Event not found");
            }

            requireEventOwnership($event, $writer);

            $data = validateEvent(getBody(), $pdo, $writer);

            // Organisers cannot transfer ownership.
            if ($writer["role"] === "organiser") {
                $data["organiser_id"] = (int) $event["organiser_id"];
            }

            $data["event_id"] = $id;

            $stmt = $pdo->prepare(
                "UPDATE events SET
                    venue_id = :venue_id,
                    organiser_id = :organiser_id,
                    event_name = :event_name,
                    description = :description,
                    event_date = :event_date,
                    ticket_price = :ticket_price,
                    total_tickets = :total_tickets,
                    tickets_available = :tickets_available
                 WHERE event_id = :event_id"
            );

            $stmt->execute($data);

            respond(200, true, "Event updated", [
                "data" => findEvent($pdo, $id)
            ]);

        case "DELETE":
            $writer = currentWriter($pdo);
            $id = getId();
            $event = findEvent($pdo, $id);

            if (!$event) {
                respond(404, false, "Event not found");
            }

            requireEventOwnership($event, $writer);

            $stmt = $pdo->prepare(
                "DELETE FROM events WHERE event_id = ?"
            );
            $stmt->execute([$id]);

            respond(200, true, "Event deleted");

        default:
            header("Allow: GET, POST, PUT, DELETE, OPTIONS");
            respond(405, false, "Method not allowed");
    }

} catch (PDOException $e) {
    error_log("Events API database error: " . $e->getMessage());

    if ($e->getCode() === "23000") {
        respond(
            409,
            false,
            "Event cannot be modified because of related records"
        );
    }

    respond(500, false, "Database operation failed");
}
?>
