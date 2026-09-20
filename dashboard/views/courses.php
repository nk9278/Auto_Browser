<?php
require_once __DIR__ . '/../app/CourseManager.php';
require_once __DIR__ . '/../app/CSRF.php';

$courseManager = new CourseManager();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'];
        if ($action === 'create' || $action === 'update') {
            $name = trim($_POST['name'] ?? '');
            $url = trim($_POST['course_url'] ?? '');
            $identifier = trim($_POST['external_identifier'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = $_POST['status'] ?? 'active';

            if (empty($name)) {
                $error = "Course name is required.";
            } else {
                if ($action === 'create') {
                    $courseManager->createCourse($name, $url, $identifier, $description, $status);
                    $success = "Course created successfully.";
                } else {
                    $id = $_POST['id'] ?? 0;
                    $courseManager->updateCourse($id, $name, $url, $identifier, $description, $status);
                    $success = "Course updated successfully.";
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? 0;
            $courseManager->deleteCourse($id);
            $success = "Course deleted successfully.";
        }
    } else {
        $error = "Invalid CSRF token.";
    }
}

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$courses = $courseManager->listCourses($search, $statusFilter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - Automation Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
        <h1 class="text-xl font-bold">Automation Platform</h1>
        <div class="flex space-x-4 items-center">
            <a href="/index.php" class="hover:underline">Dashboard</a>
            <a href="/index.php/courses" class="font-bold underline">Courses</a>
            <a href="/index.php/questions" class="hover:underline">Questions & Answers</a>
            <span class="ml-4"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
            <a href="/index.php/logout.php" class="bg-blue-800 hover:bg-blue-900 px-3 py-1 rounded">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto p-4 mt-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Courses</h2>
            <button onclick="document.getElementById('courseModal').classList.remove('hidden'); document.getElementById('modalTitle').innerText='Add Course'; document.getElementById('courseForm').reset(); document.getElementById('formAction').value='create';" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Add Course
            </button>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded shadow mb-6">
            <form method="GET" action="/index.php/courses" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Name or Identifier">
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
                <a href="/index.php/courses" class="text-blue-500 hover:underline px-4 py-2">Clear</a>
            </form>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Name</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Identifier</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">URL</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Status</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-center">Questions</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Created</th>
                        <th class="py-2 px-4 uppercase font-semibold text-sm text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php foreach ($courses as $course): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4 font-medium"><?php echo htmlspecialchars($course['name']); ?></td>
                        <td class="py-2 px-4"><?php echo htmlspecialchars($course['external_identifier'] ?? '-'); ?></td>
                        <td class="py-2 px-4">
                            <?php if ($course['course_url']): ?>
                                <a href="<?php echo htmlspecialchars($course['course_url']); ?>" target="_blank" class="text-blue-500 hover:underline">Link</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-4">
                            <span class="px-2 py-1 rounded text-xs text-white <?php echo $course['status'] === 'active' ? 'bg-green-500' : 'bg-gray-500'; ?>">
                                <?php echo htmlspecialchars($course['status']); ?>
                            </span>
                        </td>
                        <td class="py-2 px-4 text-center"><?php echo (int)$course['questions_count']; ?></td>
                        <td class="py-2 px-4 text-sm"><?php echo htmlspecialchars(substr($course['created_at'], 0, 10)); ?></td>
                        <td class="py-2 px-4">
                            <button onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)" class="text-blue-500 hover:underline mr-2">Edit</button>
                            <a href="/index.php/questions?course_id=<?php echo $course['id']; ?>" class="text-purple-500 hover:underline mr-2">Questions</a>

                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this course? All associated questions will also be deleted.');">
                                <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $course['id']; ?>">
                                <button type="submit" class="text-red-500 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="7" class="py-4 text-center text-gray-500">No courses found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Course Modal -->
    <div id="courseModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add Course</h3>
                <form id="courseForm" method="POST" action="/index.php/courses">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="courseId" value="">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Name *</label>
                        <input type="text" name="name" id="courseName" required class="mt-1 block w-full border rounded px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">URL</label>
                        <input type="url" name="course_url" id="courseUrl" class="mt-1 block w-full border rounded px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">External Identifier</label>
                        <input type="text" name="external_identifier" id="courseIdentifier" class="mt-1 block w-full border rounded px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="courseDescription" class="mt-1 block w-full border rounded px-3 py-2"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="courseStatus" class="mt-1 block w-full border rounded px-3 py-2">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3 mt-5">
                        <button type="button" onclick="document.getElementById('courseModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editCourse(course) {
            document.getElementById('modalTitle').innerText = 'Edit Course';
            document.getElementById('formAction').value = 'update';
            document.getElementById('courseId').value = course.id;
            document.getElementById('courseName').value = course.name;
            document.getElementById('courseUrl').value = course.course_url || '';
            document.getElementById('courseIdentifier').value = course.external_identifier || '';
            document.getElementById('courseDescription').value = course.description || '';
            document.getElementById('courseStatus').value = course.status;
            document.getElementById('courseModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
