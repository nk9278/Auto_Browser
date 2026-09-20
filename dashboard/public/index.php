<?php

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../../.env');

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/CSRF.php';
require_once __DIR__ . '/../app/JobManager.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/' || $uri === '/index.php') {
    if (!Auth::isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
    require_once __DIR__ . '/../views/dashboard.php';
} elseif ($uri === '/login.php') {
    require_once __DIR__ . '/../app/login.php';
} elseif ($uri === '/logout.php') {
    require_once __DIR__ . '/../app/logout.php';
} else {
    http_response_code(404);
    echo "404 Not Found";
}
