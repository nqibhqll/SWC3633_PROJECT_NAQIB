<?php

function logApiRequest($statusCode) {
    $logDirectory = __DIR__ . "/../logs";
    $logFile = $logDirectory . "/api_requests.log";

    if (!is_dir($logDirectory)) {
        if (!mkdir($logDirectory, 0755, true)) {
            error_log("Unable to create API log directory");
            return;
        }
    }

    $timestamp = date("Y-m-d H:i:s");

    $method = $_SERVER["REQUEST_METHOD"] ?? "UNKNOWN";

    $requestUri = parse_url(
        $_SERVER["REQUEST_URI"] ?? "/",
        PHP_URL_PATH
    );

    $requestUri = $requestUri ?: "/";

    $method = preg_replace(
        "/[^A-Z]/",
        "",
        strtoupper($method)
    );

    $requestUri = str_replace(
        ["\r", "\n"],
        "",
        $requestUri
    );

    $logEntry = sprintf(
        "[%s] %s %s - Status: %d%s",
        $timestamp,
        $method,
        $requestUri,
        $statusCode,
        PHP_EOL
    );

    if (file_put_contents(
        $logFile,
        $logEntry,
        FILE_APPEND | LOCK_EX
    ) === false) {
        error_log("Unable to write API request log");
    }
}
?>
