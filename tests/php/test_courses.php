<?php
require_once __DIR__ . '/../../dashboard/app/CourseManager.php';
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

echo "Testing CourseManager...\n";

$db = Database::getInstance();
$db->exec("DELETE FROM courses WHERE name = 'Test Course 101'");

$cm = new CourseManager();
$courseId = $cm->createCourse('Test Course 101', 'http://example.com/course', 'ext_123', 'desc', 'active');
if ($courseId) {
    echo "PASS: Course created.\n";
} else {
    echo "FAIL: Course not created.\n";
}

$course = $cm->getCourse($courseId);
if ($course['name'] === 'Test Course 101') {
    echo "PASS: Course fetched successfully.\n";
} else {
    echo "FAIL: Course fetch mismatch.\n";
}

$cm->updateCourse($courseId, 'Test Course 102', 'http://example.com/course', 'ext_123', 'desc updated', 'inactive');
$updated = $cm->getCourse($courseId);
if ($updated['name'] === 'Test Course 102' && $updated['status'] === 'inactive') {
    echo "PASS: Course updated successfully.\n";
} else {
    echo "FAIL: Course update failed.\n";
}

$courses = $cm->listCourses();
if (count($courses) > 0) {
    echo "PASS: listCourses works.\n";
} else {
    echo "FAIL: listCourses failed.\n";
}
