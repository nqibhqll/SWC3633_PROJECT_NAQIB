
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

// JSON response
function respond($status, $success, $message, $extra = []) {
    global $pdo;

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code($status);
    logApiRequest($status);

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Current authenticated user
function currentBookingUser($pdo) {
    if (empty($_SESSION["user_id"])) {
        respond(401, false, "Authentication required. Please login.");
    }

    $stmt = $pdo->prepare(
        "SELECT user_id, role FROM users WHERE user_id = ? LIMIT 1"
    );
    $stmt->execute([(int) $_SESSION["user_id"]]);
    $user = $stmt->fetch();

    if (!$user) {
        respond(401, false, "Session user not found. Please login.");
    }

    if (!in_array($user["role"], ["admin", "organiser", "customer"], true)) {
        respond(403, false, "Access denied.");
    }

    return [
        "id" => (int) $user["user_id"],
        "role" => $user["role"]
    ];
}

function requireBookingRole($user, $roles) {
    if (!in_array($user["role"], $roles, true)) {
        respond(403, false, "Access denied. Insufficient permissions.");
    }
}

function getId() {
    $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);

    if ($id === false || $id === null || $id <= 0) {
        respond(400, false, "Valid booking ID is required");
    }

    return $id;
}

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

function positiveInt($value) {
    if (
        !is_int($value) &&
        !(is_string($value) && ctype_digit($value))
    ) {
        return false;
    }

    $number = filter_var($value, FILTER_VALIDATE_INT);

    return ($number !== false && $number > 0)
        ? $number
        : false;
}

function validateBooking($data) {
    foreach (
        ["customer_id", "event_id", "quantity", "booking_status"]
        as $field
    ) {
        if (!array_key_exists($field, $data)) {
            respond(400, false, "$field is required");
        }
    }

    $customerId = positiveInt($data["customer_id"]);
    $eventId = positiveInt($data["event_id"]);
    $quantity = positiveInt($data["quantity"]);

    if ($customerId === false) {
        respond(400, false, "Invalid customer ID");
    }

    if ($eventId === false) {
        respond(400, false, "Invalid event ID");
    }

    if ($quantity === false) {
        respond(400, false, "Quantity must be greater than zero");
    }

    if (
        !is_string($data["booking_status"]) ||
        !in_array(
            $data["booking_status"],
            ["pending", "confirmed", "cancelled"],
            true
        )
    ) {
        respond(400, false, "Invalid booking status");
    }

    return [
        "customer_id" => $customerId,
        "event_id" => $eventId,
        "quantity" => $quantity,
        "booking_status" => $data["booking_status"]
    ];
}

function findBooking($pdo, $id, $lock = false) {
    $sql = "
        SELECT b.*, e.organiser_id
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id
        WHERE b.booking_id = ?
    ";

    if ($lock) {
        $sql .= " FOR UPDATE";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function cleanBooking($booking) {
    if (!$booking) {
        return $booking;
    }

    unset($booking["organiser_id"]);
    return $booking;
}

function checkCustomer($pdo, $id) {
    $stmt = $pdo->prepare(
        "SELECT role FROM users WHERE user_id = ?"
    );
    $stmt->execute([$id]);

    $user = $stmt->fetch();

    if (!$user) {
        respond(404, false, "Customer not found");
    }

    if ($user["role"] !== "customer") {
        respond(400, false, "User must have customer role");
    }
}

function checkBookingAccess($booking, $user) {
    if ($user["role"] === "admin") {
        return;
    }

    if (
        $user["role"] === "customer" &&
        (int) $booking["customer_id"] === $user["id"]
    ) {
        return;
    }

    if (
        $user["role"] === "organiser" &&
        (int) $booking["organiser_id"] === $user["id"]
    ) {
        return;
    }

    respond(403, false, "Access denied to this booking.");
}

function lockEvents($pdo, $ids) {
    $ids = array_values(array_unique(array_map("intval", $ids)));
    sort($ids);

    $events = [];

    foreach ($ids as $id) {
        $stmt = $pdo->prepare(
            "SELECT event_id, tickets_available, total_tickets
             FROM events
             WHERE event_id = ?
             FOR UPDATE"
        );
        $stmt->execute([$id]);

        $event = $stmt->fetch();

        if (!$event) {
            respond(404, false, "Event not found");
        }

        $events[$id] = $event;
    }

    return $events;
}

function reservedQuantity($booking) {
    return $booking["booking_status"] === "cancelled"
        ? 0
        : (int) $booking["quantity"];
}

function updateInventory($pdo, $events, $changes) {
    foreach ($changes as $eventId => $change) {
        if ($change === 0) {
            continue;
        }

        $event = $events[$eventId];

        $available =
            (int) $event["tickets_available"] + $change;

        if ($available < 0) {
            respond(409, false, "Not enough tickets available");
        }

        if ($available > (int) $event["total_tickets"]) {
            respond(409, false, "Ticket inventory exceeds capacity");
        }

        $stmt = $pdo->prepare(
            "UPDATE events
             SET tickets_available = ?
             WHERE event_id = ?"
        );
        $stmt->execute([$available, $eventId]);
    }
}

// MAIN API
$method = $_SERVER["REQUEST_METHOD"];

try {
    $user = currentBookingUser($pdo);

    switch ($method) {

        // GET - VIEW BOOKINGS
        case "GET":

            if (isset($_GET["id"])) {
                $booking = findBooking($pdo, getId());

                if (!$booking) {
                    respond(404, false, "Booking not found");
                }

                checkBookingAccess($booking, $user);

                respond(200, true, "Booking found", [
                    "data" => cleanBooking($booking)
                ]);
            }

            if ($user["role"] === "admin") {
                $stmt = $pdo->query(
                    "SELECT b.*
                     FROM bookings b
                     ORDER BY b.booking_id ASC"
                );

            } elseif ($user["role"] === "organiser") {
                $stmt = $pdo->prepare(
                    "SELECT b.*
                     FROM bookings b
                     JOIN events e ON b.event_id = e.event_id
                     WHERE e.organiser_id = ?
                     ORDER BY b.booking_id ASC"
                );
                $stmt->execute([$user["id"]]);

            } else {
                $stmt = $pdo->prepare(
                    "SELECT b.*
                     FROM bookings b
                     WHERE b.customer_id = ?
                     ORDER BY b.booking_id ASC"
                );
                $stmt->execute([$user["id"]]);
            }

            $bookings = $stmt->fetchAll();

            respond(200, true, "Bookings retrieved", [
                "count" => count($bookings),
                "data" => $bookings
            ]);

        // POST - CREATE BOOKING
        case "POST":

            requireBookingRole($user, ["admin", "customer"]);
            $data = validateBooking(getBody());

            if ($user["role"] === "customer") {
                if ($data["customer_id"] !== $user["id"]) {
                    respond(
                        403,
                        false,
                        "Cannot create booking for another customer"
                    );
                }

                if ($data["booking_status"] !== "pending") {
                    respond(
                        403,
                        false,
                        "Customer bookings must start as pending"
                    );
                }
            }

            checkCustomer($pdo, $data["customer_id"]);

            $pdo->beginTransaction();

            $events = lockEvents($pdo, [$data["event_id"]]);

            $reserved = $data["booking_status"] === "cancelled"
                ? 0
                : $data["quantity"];

            updateInventory($pdo, $events, [
                $data["event_id"] => -$reserved
            ]);

            $stmt = $pdo->prepare(
                "INSERT INTO bookings
                 (customer_id, event_id, quantity,
                  booking_status, booked_at)
                 VALUES (?, ?, ?, ?, NOW())"
            );

            $stmt->execute([
                $data["customer_id"],
                $data["event_id"],
                $data["quantity"],
                $data["booking_status"]
            ]);

            $bookingId = (int) $pdo->lastInsertId();
            $pdo->commit();

            $booking = findBooking($pdo, $bookingId);

            respond(201, true, "Booking created", [
                "data" => cleanBooking($booking)
            ]);

        // PUT - UPDATE BOOKING
        case "PUT":

            $id = getId();
            $body = getBody();
            $existing = findBooking($pdo, $id);

            if (!$existing) {
                respond(404, false, "Booking not found");
            }

            checkBookingAccess($existing, $user);

            if ($user["role"] === "customer") {

                if (
                    !isset($body["booking_status"]) ||
                    $body["booking_status"] !== "cancelled"
                ) {
                    respond(
                        403,
                        false,
                        "Customers can only cancel their bookings"
                    );
                }

                if ($existing["booking_status"] === "cancelled") {
                    respond(409, false, "Booking already cancelled");
                }

                foreach (
                    ["customer_id", "event_id", "quantity"]
                    as $field
                ) {
                    if (
                        array_key_exists($field, $body) &&
                        (int) $body[$field] !== (int) $existing[$field]
                    ) {
                        respond(
                            403,
                            false,
                            "Customers cannot modify booking details"
                        );
                    }
                }

                $data = [
                    "customer_id" => (int) $existing["customer_id"],
                    "event_id" => (int) $existing["event_id"],
                    "quantity" => (int) $existing["quantity"],
                    "booking_status" => "cancelled"
                ];

            } elseif ($user["role"] === "organiser") {

                foreach (
                    ["customer_id", "event_id", "quantity"]
                    as $field
                ) {
                    if (
                        array_key_exists($field, $body) &&
                        (int) $body[$field] !== (int) $existing[$field]
                    ) {
                        respond(
                            403,
                            false,
                            "Organiser can only update booking status"
                        );
                    }
                }

                if (
                    !isset($body["booking_status"]) ||
                    !in_array(
                        $body["booking_status"],
                        ["confirmed", "cancelled"],
                        true
                    )
                ) {
                    respond(
                        400,
                        false,
                        "Status must be confirmed or cancelled"
                    );
                }

                if ($existing["booking_status"] === "cancelled") {
                    respond(
                        409,
                        false,
                        "Cancelled booking cannot be updated"
                    );
                }

                $data = [
                    "customer_id" => (int) $existing["customer_id"],
                    "event_id" => (int) $existing["event_id"],
                    "quantity" => (int) $existing["quantity"],
                    "booking_status" => $body["booking_status"]
                ];

            } else {

                $data = validateBooking($body);
                checkCustomer($pdo, $data["customer_id"]);
            }

            $pdo->beginTransaction();

            $old = findBooking($pdo, $id, true);

            if (!$old) {
                respond(404, false, "Booking not found");
            }

            checkBookingAccess($old, $user);

            if ($user["role"] !== "admin") {
                if (
                    $old["customer_id"] != $existing["customer_id"] ||
                    $old["event_id"] != $existing["event_id"] ||
                    $old["quantity"] != $existing["quantity"] ||
                    $old["booking_status"] !== $existing["booking_status"]
                ) {
                    respond(
                        409,
                        false,
                        "Booking changed. Please try again."
                    );
                }
            }

            $events = lockEvents($pdo, [
                $old["event_id"],
                $data["event_id"]
            ]);

            $changes = [
                (int) $old["event_id"] => reservedQuantity($old)
            ];

            $newEventId = $data["event_id"];

            $changes[$newEventId] =
                ($changes[$newEventId] ?? 0) -
                ($data["booking_status"] === "cancelled"
                    ? 0
                    : $data["quantity"]);

            updateInventory($pdo, $events, $changes);

            $stmt = $pdo->prepare(
                "UPDATE bookings
                 SET customer_id = ?,
                     event_id = ?,
                     quantity = ?,
                     booking_status = ?
                 WHERE booking_id = ?"
            );

            $stmt->execute([
                $data["customer_id"],
                $data["event_id"],
                $data["quantity"],
                $data["booking_status"],
                $id
            ]);

            $pdo->commit();

            $booking = findBooking($pdo, $id);

            respond(200, true, "Booking updated", [
                "data" => cleanBooking($booking)
            ]);

        // DELETE - ADMIN ONLY
        case "DELETE":

            requireBookingRole($user, ["admin"]);

            $id = getId();

            $pdo->beginTransaction();

            $booking = findBooking($pdo, $id, true);

            if (!$booking) {
                respond(404, false, "Booking not found");
            }

            $events = lockEvents($pdo, [$booking["event_id"]]);

            updateInventory($pdo, $events, [
                (int) $booking["event_id"] =>
                    reservedQuantity($booking)
            ]);

            $stmt = $pdo->prepare(
                "DELETE FROM bookings WHERE booking_id = ?"
            );
            $stmt->execute([$id]);

            $pdo->commit();

            respond(200, true, "Booking deleted");

        default:
            header("Allow: GET, POST, PUT, DELETE, OPTIONS");
            respond(405, false, "Method not allowed");
    }

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Bookings API error: " . $e->getMessage());

    if ($e->getCode() === "23000") {
        respond(
            409,
            false,
            "Booking is referenced by another record"
        );
    }

    respond(500, false, "Database operation failed");

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Bookings API error: " . $e->getMessage());

    respond(500, false, "Server error");
}
?>
