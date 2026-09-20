<?php

require_once __DIR__ . '/../config/database.php';

class JobManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createJob($jobType, $accountId = null) {
        $stmt = $this->db->prepare("INSERT INTO automation_jobs (account_id, job_type, status, created_at, updated_at) VALUES (?, ?, 'pending', NOW(), NOW())");
        $stmt->execute([$accountId, $jobType]);
        return $this->db->lastInsertId();
    }

    public function getJobs($limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM automation_jobs ORDER BY created_at DESC LIMIT ?");
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getJobLogs($jobId) {
        $stmt = $this->db->prepare("SELECT * FROM automation_logs WHERE job_id = ? ORDER BY created_at ASC");
        $stmt->execute([$jobId]);
        return $stmt->fetchAll();
    }
}
