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

require_once __DIR__ . '/../../dashboard/app/Auth.php';
require_once __DIR__ . '/../../dashboard/app/CSRF.php';
require_once __DIR__ . '/../../dashboard/config/database.php';

echo "Testing Auth & CSRF...\n";

// Seed user for test
$db = Database::getInstance();
$db->exec("DELETE FROM users WHERE email = 'test@example.com'");
$hash = password_hash('password123', PASSWORD_BCRYPT);
$db->exec("INSERT INTO users (name, email, password_hash) VALUES ('Test User', 'test@example.com', '$hash')");

// Test Login
if (Auth::login('test@example.com', 'password123')) {
    echo "PASS: Login successful with correct credentials.\n";
} else {
    echo "FAIL: Login failed with correct credentials.\n";
}

if (!Auth::login('test@example.com', 'wrongpassword')) {
    echo "PASS: Login correctly failed with wrong credentials.\n";
} else {
    echo "FAIL: Login succeeded with wrong credentials.\n";
}

// Test CSRF
$token = CSRF::generateToken();
if (CSRF::validateToken($token)) {
    echo "PASS: Valid CSRF token accepted.\n";
} else {
    echo "FAIL: Valid CSRF token rejected.\n";
}

if (!CSRF::validateToken('invalid_token')) {
    echo "PASS: Invalid CSRF token correctly rejected.\n";
} else {
    echo "FAIL: Invalid CSRF token accepted.\n";
}
