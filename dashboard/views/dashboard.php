<?php
require_once __DIR__ . '/../app/JobManager.php';
require_once __DIR__ . '/../app/CSRF.php';
$jobManager = new JobManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_job') {
    if (CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $jobType = $_POST['job_type'] ?? 'test_job';
        $jobManager->createJob($jobType);
        header('Location: /');
        exit;
    }
}

$jobs = $jobManager->getJobs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Automation Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
        <h1 class="text-xl font-bold">Automation Platform</h1>
        <div>
            <span class="mr-4"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
            <a href="/logout.php" class="bg-blue-800 hover:bg-blue-900 px-3 py-1 rounded">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto p-4 mt-4">
        <div class="bg-white p-6 rounded shadow mb-6">
            <h2 class="text-2xl font-bold mb-4">Create Job</h2>
            <form method="POST" action="/" class="flex items-center space-x-4">
                <input type="hidden" name="action" value="create_job">
                <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                <select name="job_type" class="border rounded px-3 py-2">
                    <option value="test_browser">Test Browser</option>
                    <option value="fetch_reviews">Fetch Reviews</option>
                </select>
                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Start Job
                </button>
            </form>
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-2xl font-bold mb-4">Recent Jobs</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="w-1/6 py-2 px-4 uppercase font-semibold text-sm">ID</th>
                            <th class="w-1/6 py-2 px-4 uppercase font-semibold text-sm">Type</th>
                            <th class="w-1/6 py-2 px-4 uppercase font-semibold text-sm">Status</th>
                            <th class="w-1/6 py-2 px-4 uppercase font-semibold text-sm">Step</th>
                            <th class="w-1/6 py-2 px-4 uppercase font-semibold text-sm">Created</th>
                            <th class="w-1/6 py-2 px-4 uppercase font-semibold text-sm">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php foreach ($jobs as $job): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4"><?php echo htmlspecialchars($job['id']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($job['job_type']); ?></td>
                            <td class="py-2 px-4">
                                <span class="px-2 py-1 rounded text-xs text-white
                                    <?php
                                        echo $job['status'] === 'completed' ? 'bg-green-500' :
                                            ($job['status'] === 'running' ? 'bg-blue-500' :
                                            ($job['status'] === 'failed' ? 'bg-red-500' : 'bg-gray-500'));
                                    ?>">
                                    <?php echo htmlspecialchars($job['status']); ?>
                                </span>
                            </td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($job['current_step'] ?? '-'); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($job['created_at']); ?></td>
                            <td class="py-2 px-4">
                                <a href="/?job=<?php echo $job['id']; ?>" class="text-blue-500 hover:underline">View Logs</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($jobs)): ?>
                        <tr>
                            <td colspan="6" class="py-4 text-center">No jobs found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (isset($_GET['job'])):
            $jobId = $_GET['job'];
            $logs = $jobManager->getJobLogs($jobId);
        ?>
        <div class="bg-white p-6 rounded shadow mt-6">
            <h2 class="text-2xl font-bold mb-4">Logs for Job #<?php echo htmlspecialchars($jobId); ?></h2>
            <div class="bg-gray-900 text-green-400 p-4 rounded font-mono text-sm overflow-y-auto max-h-96">
                <?php foreach ($logs as $log): ?>
                    <div class="mb-1">
                        <span class="text-gray-500">[<?php echo htmlspecialchars($log['created_at']); ?>]</span>
                        <span class="<?php echo $log['level'] === 'error' ? 'text-red-500' : ($log['level'] === 'warning' ? 'text-yellow-500' : ''); ?>">
                            [<?php echo htmlspecialchars(strtoupper($log['level'])); ?>]
                        </span>
                        <?php if ($log['step']): ?>
                            <span class="text-blue-300">[<?php echo htmlspecialchars($log['step']); ?>]</span>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($log['message']); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <div class="text-gray-500">No logs for this job yet.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
