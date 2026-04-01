<?php
define('CLAUT_ACCESS', true);
require_once __DIR__ . '/build/config/session-config.php';
SessionConfig::init();

header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE
]);
