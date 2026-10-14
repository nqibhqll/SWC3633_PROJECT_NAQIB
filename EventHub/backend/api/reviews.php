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

function reviewRespond($status, $success, $message, $extra = []) {
    http_response_code($status);
    logApiRequest($status);

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    exit;
}

function reviewCurrentUser($pdo) {
    if (empty($_SESSION["user_id"])) {
        reviewRespond(401, false, "Please login first.");
    }

    $stmt = $pdo->prepare(
        "SELECT user_id, role
         FROM users
         WHERE user_id = ?
         LIMIT 1"
    );

    $stmt->execute([(int) $_SESSION["user_id"]]);
    $user = $stmt->fetch();

    if (!$user) {
        reviewRespond(401, false, "User session not found.");
    }

    if (!in_array(
        $user["role"],
        ["admin", "organiser", "customer"],
        true
    )) {
        reviewRespond(403, false, "Access denied.");
    }

    return [
        "id" => (int) $user["user_id"],
        "role" => $user["role"]
    ];
}

function reviewGetId() {
    $id = filter_var(
        $_GET["id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($id === false || $id === null || $id <= 0) {
        reviewRespond(400, false, "Valid review ID is required.");
    }

    return $id;
}

function reviewGetBody() {
    $raw = file_get_contents("php://input");

    if (strlen($raw) > 16384) {
        reviewRespond(413, false, "Request body is too large.");
    }

    $body = json_decode($raw, true);

    if (
        json_last_error() !== JSON_ERROR_NONE ||
        !is_array($body) ||
        array_is_list($body)
    ) {
        reviewRespond(400, false, "Valid JSON object is required.");
    }

    return $body;
}

function reviewValidate($body, $requireEvent = true) {
    if ($requireEvent) {
        $eventId = filter_var(
            $body["event_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $eventId === false ||
            $eventId === null ||
            $eventId <= 0
        ) {
            reviewRespond(400, false, "Valid event ID is required.");
        }
    } else {
        $eventId = null;
    }

    $rating = filter_var(
        $body["rating"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $rating === false ||
        $rating === null ||
        $rating < 1 ||
        $rating > 5
    ) {
        reviewRespond(400, false, "Rating must be between 1 and 5.");
    }

    $comment = $body["comment"] ?? "";

    if (!is_string($comment)) {
        reviewRespond(400, false, "Comment must be text.");
    }

    $comment = trim($comment);

    if (mb_strlen($comment) > 2000) {
        reviewRespond(400, false, "Comment must not exceed 2000 characters.");
    }

    return [
        "event_id" => $eventId,
        "rating" => (int) $rating,
        "comment" => $comment
    ];
}

function reviewFind($pdo, $id) {
    $stmt = $pdo->prepare(
        "SELECT *
         FROM reviews
         WHERE review_id = ?
         LIMIT 1"
    );

    $stmt->execute([$id]);

    return $stmt->fetch();
}

function reviewCheckEligibility($pdo, $userId, $eventId) {
    $stmt = $pdo->prepare(
        "SELECT event_id, event_date
         FROM events
         WHERE event_id = ?
         LIMIT 1"
    );

    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        reviewRespond(404, false, "Event not found.");
    }

    if (strtotime($event["event_date"]) > time()) {
        reviewRespond(
            403,
            false,
            "You can review this event only after it has taken place."
        );
    }

    $stmt = $pdo->prepare(
        "SELECT booking_id
         FROM bookings
         WHERE customer_id = ?
           AND event_id = ?
           AND booking_status = 'confirmed'
         LIMIT 1"
    );

    $stmt->execute([$userId, $eventId]);

    if (!$stmt->fetch()) {
        reviewRespond(
            403,
            false,
            "Only customers with a confirmed booking can review this event."
        );
    }
}

$method = $_SERVER["REQUEST_METHOD"];

try {
    switch ($method) {

        // GET — PUBLIC REVIEW LIST
        case "GET":

            if (isset($_GET["id"])) {
                $stmt = $pdo->prepare(
                    "SELECT
                        r.review_id,
                        r.user_id,
                        u.name AS reviewer_name,
                        r.event_id,
                        e.event_name,
                        r.rating,
                        r.comment,
                        r.created_at
                     FROM reviews r
                     JOIN users u ON r.user_id = u.user_id
                     JOIN events e ON r.event_id = e.event_id
                     WHERE r.review_id = ?
                     LIMIT 1"
                );

                $stmt->execute([reviewGetId()]);
                $review = $stmt->fetch();

                if (!$review) {
                    reviewRespond(404, false, "Review not found.");
                }

                reviewRespond(200, true, "Review found.", [
                    "data" => $review
                ]);
            }

            $sql = "
                SELECT
                    r.review_id,
                    r.user_id,
                    u.name AS reviewer_name,
                    r.event_id,
                    e.event_name,
                    r.rating,
                    r.comment,
                    r.created_at
                FROM reviews r
                JOIN users u ON r.user_id = u.user_id
                JOIN events e ON r.event_id = e.event_id
            ";

            $params = [];

            if (isset($_GET["event_id"])) {
                $eventId = filter_var(
                    $_GET["event_id"],
                    FILTER_VALIDATE_INT
                );

                if (
                    $eventId === false ||
                    $eventId <= 0
                ) {
                    reviewRespond(400, false, "Invalid event ID.");
                }

                $sql .= " WHERE r.event_id = ?";
                $params[] = $eventId;
            }

            $sql .= " ORDER BY r.created_at DESC, r.review_id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $reviews = $stmt->fetchAll();

            $totalRating = 0;

            foreach ($reviews as $review) {
                $totalRating += (int) $review["rating"];
            }

            $averageRating = count($reviews) > 0
                ? round($totalRating / count($reviews), 2)
                : 0;

            reviewRespond(200, true, "Reviews retrieved.", [
                "count" => count($reviews),
                "average_rating" => $averageRating,
                "data" => $reviews
            ]);

        // POST — CUSTOMER SUBMIT REVIEW
        case "POST":

            $user = reviewCurrentUser($pdo);

            if ($user["role"] !== "customer") {
                reviewRespond(
                    403,
                    false,
                    "Only customers can submit reviews."
                );
            }

            $data = reviewValidate(reviewGetBody());

            reviewCheckEligibility(
                $pdo,
                $user["id"],
                $data["event_id"]
            );

            $stmt = $pdo->prepare(
                "SELECT review_id
                 FROM reviews
                 WHERE user_id = ?
                   AND event_id = ?
                 LIMIT 1"
            );

            $stmt->execute([
                $user["id"],
                $data["event_id"]
            ]);

            if ($stmt->fetch()) {
                reviewRespond(
                    409,
                    false,
                    "You have already reviewed this event."
                );
            }

            $stmt = $pdo->prepare(
                "INSERT INTO reviews
                    (user_id, event_id, rating, comment)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $user["id"],
                $data["event_id"],
                $data["rating"],
                $data["comment"]
            ]);

            $reviewId = (int) $pdo->lastInsertId();

            reviewRespond(201, true, "Review submitted successfully.", [
                "data" => reviewFind($pdo, $reviewId)
            ]);

        // PUT — CUSTOMER EDIT OWN REVIEW
        case "PUT":

            $user = reviewCurrentUser($pdo);
            $id = reviewGetId();
            $existing = reviewFind($pdo, $id);

            if (!$existing) {
                reviewRespond(404, false, "Review not found.");
            }

            if (
                $user["role"] !== "customer" ||
                (int) $existing["user_id"] !== $user["id"]
            ) {
                reviewRespond(
                    403,
                    false,
                    "You can only edit your own review."
                );
            }

            $data = reviewValidate(reviewGetBody(), false);

            $stmt = $pdo->prepare(
                "UPDATE reviews
                 SET rating = ?, comment = ?
                 WHERE review_id = ?"
            );

            $stmt->execute([
                $data["rating"],
                $data["comment"],
                $id
            ]);

            reviewRespond(200, true, "Review updated successfully.", [
                "data" => reviewFind($pdo, $id)
            ]);

        // DELETE — OWNER OR ADMIN
        case "DELETE":

            $user = reviewCurrentUser($pdo);
            $id = reviewGetId();
            $existing = reviewFind($pdo, $id);

            if (!$existing) {
                reviewRespond(404, false, "Review not found.");
            }

            if (
                $user["role"] !== "admin" &&
                !(
                    $user["role"] === "customer" &&
                    (int) $existing["user_id"] === $user["id"]
                )
            ) {
                reviewRespond(
                    403,
                    false,
                    "You do not have permission to delete this review."
                );
            }

            $stmt = $pdo->prepare(
                "DELETE FROM reviews WHERE review_id = ?"
            );

            $stmt->execute([$id]);

            reviewRespond(200, true, "Review deleted successfully.");

        default:
            header("Allow: GET, POST, PUT, DELETE, OPTIONS");
            reviewRespond(405, false, "Method not allowed.");
    }

} catch (PDOException $e) {
    error_log("Reviews API error: " . $e->getMessage());

    if ($e->getCode() === "23000") {
        reviewRespond(409, false, "Review already exists or conflicts with existing data.");
    }

    reviewRespond(500, false, "Database operation failed.");

} catch (Throwable $e) {
    error_log("Reviews API error: " . $e->getMessage());
    reviewRespond(500, false, "Server error.");
}
?>
