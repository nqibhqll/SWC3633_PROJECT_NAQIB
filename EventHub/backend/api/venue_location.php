<?php
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/api_logger.php";

function locationResponse($status, $success, $message, $extra = []) {
    http_response_code($status);
    logApiRequest($status);

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    logApiRequest(204);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Allow: GET, OPTIONS");
    locationResponse(405, false, "Method not allowed");
}

$id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id <= 0) {
    locationResponse(400, false, "Valid venue ID is required");
}

require_once __DIR__ . "/../config/database.php";

try {
    $stmt = $pdo->prepare(
        "SELECT venue_id, venue_name, address
         FROM venues
         WHERE venue_id = ?
         LIMIT 1"
    );
    $stmt->execute([$id]);

    $venue = $stmt->fetch();

    if (!$venue) {
        locationResponse(404, false, "Venue not found");
    }

    $address = trim($venue["address"]);

    if ($address === "") {
        locationResponse(422, false, "Venue address is empty");
    }

    // Shared local cache to respect Nominatim usage limits.
    $cacheDir = __DIR__ . "/../data/location_cache";

    if (!is_dir($cacheDir) &&
        !mkdir($cacheDir, 0775, true) &&
        !is_dir($cacheDir)) {
        locationResponse(500, false, "Unable to create cache directory");
    }

    $cacheFile = $cacheDir . "/" . hash("sha256", $address) . ".json";
    $cacheTtl = 30 * 24 * 60 * 60;

    if (
        is_file($cacheFile) &&
        (time() - filemtime($cacheFile)) < $cacheTtl
    ) {
        $cached = json_decode(file_get_contents($cacheFile), true);

        if (is_array($cached) && isset($cached["latitude"], $cached["longitude"])) {
            locationResponse(200, true, "Venue location retrieved", [
                "data" => [
                    "venue_id" => (int) $venue["venue_id"],
                    "venue_name" => $venue["venue_name"],
                    "address" => $address,
                    "latitude" => $cached["latitude"],
                    "longitude" => $cached["longitude"],
                    "display_name" => $cached["display_name"],
                    "source" => "OpenStreetMap contributors",
                    "cached" => true
                ]
            ]);
        }
    }

    // Limit external calls to one request per second.
    $lockFile = $cacheDir . "/nominatim_rate.lock";
    $lock = fopen($lockFile, "c+");

    if (!$lock || !flock($lock, LOCK_EX)) {
        locationResponse(503, false, "Location service temporarily unavailable");
    }

    // Recheck cache after obtaining lock.
    if (
        is_file($cacheFile) &&
        (time() - filemtime($cacheFile)) < $cacheTtl
    ) {
        $cached = json_decode(file_get_contents($cacheFile), true);

        if (is_array($cached) && isset($cached["latitude"], $cached["longitude"])) {
            flock($lock, LOCK_UN);
            fclose($lock);

            locationResponse(200, true, "Venue location retrieved", [
                "data" => [
                    "venue_id" => (int) $venue["venue_id"],
                    "venue_name" => $venue["venue_name"],
                    "address" => $address,
                    "latitude" => $cached["latitude"],
                    "longitude" => $cached["longitude"],
                    "display_name" => $cached["display_name"],
                    "source" => "OpenStreetMap contributors",
                    "cached" => true
                ]
            ]);
        }
    }

    rewind($lock);
    $lastRequest = (float) trim(stream_get_contents($lock));

    $wait = 1.1 - (microtime(true) - $lastRequest);

    if ($wait > 0) {
        usleep((int) ($wait * 1000000));
    }

    $url = "https://nominatim.openstreetmap.org/search?" .
        http_build_query([
            "q" => $address,
            "format" => "jsonv2",
            "limit" => 1
        ]);

    $ch = curl_init($url);

    if ($ch === false) {
        flock($lock, LOCK_UN);
        fclose($lock);
        locationResponse(503, false, "Unable to initialize location service");
    }

    // IMPORTANT: Replace with a genuine contact email.
    $userAgent = "EventHubStudentProject/1.0 (contact: naqibardnan@gmail.com)";

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "User-Agent: " . $userAgent
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => false
    ]);

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    rewind($lock);
    ftruncate($lock, 0);
    fwrite($lock, (string) microtime(true));
    fflush($lock);

    flock($lock, LOCK_UN);
    fclose($lock);

    if ($result === false || $status !== 200) {
        locationResponse(502, false, "External location service failed");
    }

    $places = json_decode($result, true);

    if (!is_array($places) || empty($places)) {
        locationResponse(404, false, "Location not found for venue address");
    }

    $place = $places[0];

    if (!isset($place["lat"], $place["lon"])) {
        locationResponse(502, false, "Invalid location service response");
    }

    $location = [
        "latitude" => (float) $place["lat"],
        "longitude" => (float) $place["lon"],
        "display_name" => $place["display_name"] ?? $address
    ];

    file_put_contents(
        $cacheFile,
        json_encode($location, JSON_PRETTY_PRINT),
        LOCK_EX
    );

    locationResponse(200, true, "Venue location retrieved", [
        "data" => [
            "venue_id" => (int) $venue["venue_id"],
            "venue_name" => $venue["venue_name"],
            "address" => $address,
            "latitude" => $location["latitude"],
            "longitude" => $location["longitude"],
            "display_name" => $location["display_name"],
            "source" => "OpenStreetMap contributors",
            "cached" => false
        ]
    ]);

} catch (Throwable $e) {
    error_log("Venue Location API error: " . $e->getMessage());

    locationResponse(500, false, "Server error");
}
?>
