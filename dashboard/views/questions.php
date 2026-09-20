<?php
require_once __DIR__ . '/../app/QuestionManager.php';
require_once __DIR__ . '/../app/CourseManager.php';
require_once __DIR__ . '/../app/CSRF.php';

$questionManager = new QuestionManager();
$courseManager = new CourseManager();
$error = '';
$success = '';

$courseIdFilter = $_GET['course_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'];

        if ($action === 'create' || $action === 'update') {
            $courseId = $_POST['course_id'] ?? null;
            $questionText = trim($_POST['question_text'] ?? '');
            $answerType = $_POST['answer_type'] ?? 'single_choice';
            $status = $_POST['status'] ?? 'active';
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            $options = $_POST['options'] ?? [];
            $correctOptions = $_POST['correct_options'] ?? [];

            if (empty($courseId) || empty($questionText)) {
                $error = "Course and Question text are required.";
            } else {
                try {
                    $db = Database::getInstance();
                    $db->beginTransaction();

                    // Validate options
                    $validOptionsCount = 0;
                    $hasCorrect = false;
                    foreach ($options as $key => $optText) {
                        if (trim($optText) !== '') {
                            $validOptionsCount++;
                            if (in_array($key, $correctOptions)) {
                                $hasCorrect = true;
                            }
                        }
                    }

                    if ($validOptionsCount < 2 && in_array($answerType, ['single_choice', 'multiple_choice', 'true_false'])) {
                        throw new Exception("At least two valid options are required for this question type.");
                    }

                    if (!$hasCorrect && in_array($answerType, ['single_choice', 'multiple_choice', 'true_false'])) {
                        throw new Exception("At least one correct answer must be defined.");
                    }

                    if ($action === 'create') {
                        $qId = $questionManager->createQuestion($courseId, $questionText, $answerType, $status, $sortOrder);
                        $success = "Question created successfully.";
                    } else {
                        $qId = $_POST['id'] ?? 0;
                        $questionManager->updateQuestion($qId, $questionText, $answerType, $status, $sortOrder);
                        $questionManager->deleteOptions($qId);
                        $success = "Question updated successfully.";
                    }

                    // Add options
                    if (!empty($options)) {
                        $order = 0;
                        foreach ($options as $key => $optText) {
                            if (trim($optText) === '') continue;
                            $isCorrect = in_array($key, $correctOptions);
                            $questionManager->addOption($qId, $key, trim($optText), $isCorrect, $order);
                            $order++;
                        }
                    }

                    $db->commit();
                } catch (Exception $e) {
                    if (isset($db)) $db->rollBack();
                    $error = "Error saving question: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? 0;
            $questionManager->deleteQuestion($id);
            $success = "Question deleted successfully.";
        } elseif ($action === 'import_preview') {
            $courseId = $_POST['import_course_id'] ?? null;
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                try {
                    $previewData = $questionManager->previewCSV($_FILES['csv_file']['tmp_name']);
                    // Keep file content to actually import
                    $csvContent = base64_encode(file_get_contents($_FILES['csv_file']['tmp_name']));
                    $previewCourseId = $courseId;
                } catch (Exception $e) {
                    $error = "Preview failed: " . $e->getMessage();
                }
            } else {
                $error = "File upload failed.";
            }
        } elseif ($action === 'import_confirm') {
            $courseId = $_POST['import_course_id'] ?? null;
            $csvContent = base64_decode($_POST['csv_content'] ?? '');

            if ($courseId && $csvContent) {
                // Create temporary file
                $tmpName = tempnam(sys_get_temp_dir(), 'csv');
                file_put_contents($tmpName, $csvContent);
                try {
                    $questionManager->importCSV($courseId, $tmpName);
                    $success = "Questions imported successfully.";
                } catch (Exception $e) {
                    $error = "Import failed: " . $e->getMessage();
                }
                unlink($tmpName);
            } else {
                $error = "Missing data for import.";
            }
        }
    } else {
        $error = "Invalid CSRF token.";
    }
}

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$questions = $questionManager->listQuestions($courseIdFilter, $search, $statusFilter);
$courses = $courseManager->listCourses();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions - Automation Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .options-list input[type="radio"], .options-list input[type="checkbox"] {
            transform: scale(1.2);
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
        <h1 class="text-xl font-bold">Automation Platform</h1>
        <div class="flex space-x-4 items-center">
            <a href="/index.php" class="hover:underline">Dashboard</a>
            <a href="/index.php/courses" class="hover:underline">Courses</a>
            <a href="/index.php/questions" class="font-bold underline">Questions & Answers</a>
            <span class="ml-4"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
            <a href="/index.php/logout.php" class="bg-blue-800 hover:bg-blue-900 px-3 py-1 rounded">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto p-4 mt-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Questions</h2>
            <div class="space-x-2">
                <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                    Import CSV
                </button>
                <button onclick="openQuestionModal()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Add Question
                </button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded shadow mb-6">
            <form method="GET" action="/index.php/questions" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Course</label>
                    <select name="course_id" class="mt-1 block w-full border rounded px-3 py-2">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $courseIdFilter == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Text or normalized text">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 block w-full border rounded px-3 py-2">
                        <option value="">All</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Filter</button>
                <a href="/index.php/questions" class="text-blue-500 hover:underline px-4 py-2">Clear</a>
            </form>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Order</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Question</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Course</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Type</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Correct Answer(s)</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Status</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php foreach ($questions as $q): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4"><?php echo (int)$q['sort_order']; ?></td>
                        <td class="py-2 px-4">
                            <div class="font-medium"><?php echo htmlspecialchars(substr($q['question_text'], 0, 100)) . (strlen($q['question_text']) > 100 ? '...' : ''); ?></div>
                        </td>
                        <td class="py-2 px-4"><?php echo htmlspecialchars($q['course_name'] ?? 'Unknown'); ?></td>
                        <td class="py-2 px-4"><?php echo htmlspecialchars($q['answer_type']); ?></td>
                        <td class="py-2 px-4 text-sm">
                            <?php
                            $corrects = array_filter($q['options'], function($opt) { return $opt['is_correct']; });
                            $correctKeys = array_map(function($opt) { return $opt['option_key']; }, $corrects);
                            echo htmlspecialchars(implode(', ', $correctKeys));
                            if (empty($correctKeys)) echo '<span class="text-red-500">None set</span>';
                            ?>
                        </td>
                        <td class="py-2 px-4">
                            <span class="px-2 py-1 rounded text-xs text-white <?php echo $q['status'] === 'active' ? 'bg-green-500' : 'bg-gray-500'; ?>">
                                <?php echo htmlspecialchars($q['status']); ?>
                            </span>
                        </td>
                        <td class="py-2 px-4">
                            <button onclick="editQuestion(<?php echo htmlspecialchars(json_encode($q)); ?>)" class="text-blue-500 hover:underline mr-2">Edit</button>

                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                <button type="submit" class="text-red-500 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($questions)): ?>
                    <tr>
                        <td colspan="7" class="py-4 text-center text-gray-500">No questions found for this course.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Question Modal -->
    <div id="questionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-10 mx-auto p-5 border w-3/4 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add Question</h3>
                <form id="questionForm" method="POST" action="/index.php/questions<?php echo $courseIdFilter ? '?course_id='.$courseIdFilter : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="questionId" value="">

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Course *</label>
                            <select name="course_id" id="questionCourse" required class="mt-1 block w-full border rounded px-3 py-2">
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Answer Type</label>
                            <select name="answer_type" id="questionType" class="mt-1 block w-full border rounded px-3 py-2" onchange="updateOptionsUI()">
                                <option value="single_choice">Single Choice</option>
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="dropdown">Dropdown</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Question Text *</label>
                        <textarea name="question_text" id="questionText" required rows="3" class="mt-1 block w-full border rounded px-3 py-2"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                            <input type="number" name="sort_order" id="questionOrder" value="0" class="mt-1 block w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="questionStatus" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4 p-4 border rounded bg-gray-50">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-medium text-gray-800">Options</h4>
                            <button type="button" onclick="addOptionRow()" class="text-sm bg-blue-100 text-blue-700 px-2 py-1 rounded">Add Option</button>
                        </div>
                        <div id="optionsContainer" class="space-y-2 options-list">
                            <!-- Options rendered via JS -->
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-5">
                        <button type="button" onclick="document.getElementById('questionModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Import Questions (CSV)</h3>
                <form method="POST" action="/index.php/questions" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                    <input type="hidden" name="action" value="import_preview">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Target Course *</label>
                        <select name="import_course_id" required class="mt-1 block w-full border rounded px-3 py-2">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $courseIdFilter == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">CSV File *</label>
                        <input type="file" name="csv_file" accept=".csv" required class="mt-1 block w-full border rounded px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Columns: question,answer_type,option_a,option_b,option_c,option_d,correct_answer</p>
                    </div>

                    <div class="flex justify-end space-x-3 mt-5">
                        <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Preview</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <?php if (isset($previewData)): ?>
    <div id="previewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-10 mx-auto p-5 border w-3/4 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Import Preview</h3>
                <div class="overflow-x-auto max-h-96">
                    <table class="min-w-full bg-white text-sm text-left">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2">Row</th>
                                <th class="px-4 py-2">Question</th>
                                <th class="px-4 py-2">Valid?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($previewData as $p): ?>
                                <tr class="border-b <?php echo $p['is_valid'] ? '' : 'bg-red-50'; ?>">
                                    <td class="px-4 py-2"><?php echo $p['row']; ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($p['question']); ?></td>
                                    <td class="px-4 py-2">
                                        <?php if ($p['is_valid']): ?>
                                            <span class="text-green-600 font-bold">Yes</span>
                                        <?php else: ?>
                                            <span class="text-red-600 font-bold">No: <?php echo implode(', ', $p['errors']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="/index.php/questions" class="mt-4 flex justify-end space-x-3">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                    <input type="hidden" name="action" value="import_confirm">
                    <input type="hidden" name="import_course_id" value="<?php echo htmlspecialchars($previewCourseId); ?>">
                    <input type="hidden" name="csv_content" value="<?php echo htmlspecialchars($csvContent); ?>">

                    <button type="button" onclick="document.getElementById('previewModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
                    <?php
                        $hasInvalid = count(array_filter($previewData, function($p) { return !$p['is_valid']; })) > 0;
                        if (!$hasInvalid && !empty($previewData)):
                    ?>
                        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Confirm Import</button>
                    <?php else: ?>
                        <p class="text-red-500 text-sm flex items-center px-4">Please fix errors in CSV before importing.</p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openQuestionModal() {
            document.getElementById('modalTitle').innerText = 'Add Question';
            document.getElementById('formAction').value = 'create';
            document.getElementById('questionForm').reset();

            // Set default course from filter if exists
            const urlParams = new URLSearchParams(window.location.search);
            const courseId = urlParams.get('course_id');
            if(courseId) document.getElementById('questionCourse').value = courseId;

            document.getElementById('optionsContainer').innerHTML = '';
            addOptionRow('A');
            addOptionRow('B');
            addOptionRow('C');
            addOptionRow('D');
            updateOptionsUI();

            document.getElementById('questionModal').classList.remove('hidden');
        }

        function editQuestion(q) {
            document.getElementById('modalTitle').innerText = 'Edit Question';
            document.getElementById('formAction').value = 'update';
            document.getElementById('questionId').value = q.id;

            document.getElementById('questionCourse').value = q.course_id;
            document.getElementById('questionType').value = q.answer_type;
            document.getElementById('questionText').value = q.question_text;
            document.getElementById('questionOrder').value = q.sort_order;
            document.getElementById('questionStatus').value = q.status;

            document.getElementById('optionsContainer').innerHTML = '';

            if (q.options && q.options.length > 0) {
                q.options.forEach(opt => {
                    addOptionRow(opt.option_key, opt.option_text, opt.is_correct == 1);
                });
            } else {
                addOptionRow('A'); addOptionRow('B');
            }

            updateOptionsUI();
            document.getElementById('questionModal').classList.remove('hidden');
        }

        function addOptionRow(key = '', text = '', isCorrect = false) {
            if (!key) {
                const keys = ['A','B','C','D','E','F','G','H'];
                const existing = document.querySelectorAll('.opt-key');
                key = keys[existing.length] || 'X';
            }

            const type = document.getElementById('questionType').value === 'multiple_choice' ? 'checkbox' : 'radio';
            const name = type === 'radio' ? 'correct_options[]' : 'correct_options[]';
            const checkedStr = isCorrect ? 'checked' : '';

            const html = `
                <div class="flex items-center space-x-2 option-row">
                    <input type="${type}" name="${name}" value="${key}" ${checkedStr} class="opt-correct">
                    <input type="text" name="option_keys[]" value="${key}" class="w-12 border rounded px-2 py-1 text-center font-bold opt-key" readonly>
                    <input type="text" name="options[${key}]" value="${text.replace(/"/g, '&quot;')}" class="flex-1 border rounded px-3 py-1" placeholder="Option text...">
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 px-2 font-bold">&times;</button>
                </div>
            `;
            document.getElementById('optionsContainer').insertAdjacentHTML('beforeend', html);
        }

        function updateOptionsUI() {
            const type = document.getElementById('questionType').value;
            const inputs = document.querySelectorAll('.opt-correct');
            inputs.forEach(input => {
                input.type = (type === 'multiple_choice') ? 'checkbox' : 'radio';
            });
        }
    </script>
</body>
</html>
