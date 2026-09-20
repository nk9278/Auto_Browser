<?php
require_once __DIR__ . '/../../dashboard/app/QuestionManager.php';
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

echo "Testing QuestionManager...\n";
$db = Database::getInstance();
$qm = new QuestionManager();
$cm = new CourseManager();

// Setup course
$courseId = $cm->createCourse('Temp Course for Qs', '', '', '', 'active');

// Test Normalization
$norm = $qm->normalizeQuestion(" What   is  AI? ");
if ($norm === "what is ai") {
    echo "PASS: Normalization works.\n";
} else {
    echo "FAIL: Normalization failed. Got: '$norm'\n";
}

// Test Question Creation
$qId = $qm->createQuestion($courseId, " What   is  AI? ", 'single_choice', 'active', 0);
if ($qId) {
    echo "PASS: Question created.\n";
} else {
    echo "FAIL: Question creation failed.\n";
}

// Test Option Adding
$qm->addOption($qId, 'A', 'Hardware', false, 0);
$qm->addOption($qId, 'B', 'Software', true, 1);

$q = $qm->getQuestion($qId);
if (count($q['options']) === 2 && $q['options'][1]['is_correct'] == 1) {
    echo "PASS: Options created and correct answer set.\n";
} else {
    echo "FAIL: Options failed.\n";
}

// Clean up
$cm->deleteCourse($courseId); // Should cascade delete questions
$qCheck = $qm->getQuestion($qId);
if (!$qCheck) {
    echo "PASS: Cascade delete works.\n";
} else {
    echo "FAIL: Cascade delete failed.\n";
}

// Test CSV Import
$courseId2 = $cm->createCourse('Import Course', '', '', '', 'active');
try {
    $preview = $qm->previewCSV(__DIR__ . '/../../storage/sample_questions.csv');
    if (count($preview) === 3) {
        echo "PASS: CSV Preview works.\n";
    } else {
        echo "FAIL: CSV Preview count mismatch.\n";
    }

    $qm->importCSV($courseId2, __DIR__ . '/../../storage/sample_questions.csv');
    $qs = $qm->listQuestions($courseId2);
    if (count($qs) === 3) {
        echo "PASS: CSV Import works.\n";
    } else {
        echo "FAIL: CSV Import count mismatch (got " . count($qs) . ").\n";
    }
} catch (Exception $e) {
    echo "FAIL: CSV Import threw error - " . $e->getMessage() . "\n";
}

$cm->deleteCourse($courseId2);
