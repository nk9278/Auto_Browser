<?php
// router.php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// if static file exists, serve it
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Emulate Nginx logic for PHP built-in server
// Set SCRIPT_NAME to /index.php for the front controller
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Also rewrite $_SERVER['REQUEST_URI'] slightly if it is just a plain PHP script request that we want index.php to handle? No, index.php uses REQUEST_URI directly.

require_once __DIR__ . '/index.php';
