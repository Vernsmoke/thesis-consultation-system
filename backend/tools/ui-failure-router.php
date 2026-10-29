<?php

// Temporary read-only loopback stand-in for browser failure-state evidence.
// Never runs Laravel, accesses a database, accepts writes, or logs request bodies.
if (($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') {
    http_response_code(403);
    exit;
}
header('Access-Control-Allow-Origin: http://127.0.0.1:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Accept, Content-Type, X-XSRF-TOKEN');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = getenv('UI_FAILURE_MODE');
    if ($mode === 'slow') {
        sleep(3);
    } elseif ($mode === 'timeout') {
        sleep(12);
    }
}
http_response_code(503);
echo json_encode(['code' => 'controlled_ui_service_unavailable']);
