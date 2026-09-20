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
require_once __DIR__ . '/../../dashboard/config/database.php';

echo "Testing DB Connection...\n";

try {
    $db = Database::getInstance();
    $stmt = $db->query("SHOW TABLES;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('users', $tables)) {
        echo "PASS: Users table exists.\n";
    } else {
        echo "FAIL: Users table missing.\n";
    }
} catch (Exception $e) {
    echo "FAIL: DB connection error: " . $e->getMessage() . "\n";
}
