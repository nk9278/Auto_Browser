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
require_once __DIR__ . '/../app/CourseManager.php';
require_once __DIR__ . '/../app/QuestionManager.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/' || $uri === '/index.php' || $uri === '') {
    if (!Auth::isLoggedIn()) {
        header('Location: /index.php/login.php');
        exit;
    }
    require_once __DIR__ . '/../views/dashboard.php';
} elseif ($uri === '/login.php' || $uri === '/index.php/login.php') {
    require_once __DIR__ . '/../app/login.php';
} elseif ($uri === '/logout.php' || $uri === '/index.php/logout.php') {
    require_once __DIR__ . '/../app/logout.php';
} elseif ($uri === '/courses' || $uri === '/index.php/courses') {
    if (!Auth::isLoggedIn()) {
        header('Location: /index.php/login.php');
        exit;
    }
    require_once __DIR__ . '/../views/courses.php';
} elseif ($uri === '/questions' || $uri === '/index.php/questions') {
    if (!Auth::isLoggedIn()) {
        header('Location: /index.php/login.php');
        exit;
    }
    require_once __DIR__ . '/../views/questions.php';
} else {
    http_response_code(404);
    echo "404 Not Found";
}