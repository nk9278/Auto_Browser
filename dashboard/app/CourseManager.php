<?php
require_once __DIR__ . '/../config/database.php';

class CourseManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createCourse($name, $courseUrl, $externalIdentifier, $description, $status) {
        $stmt = $this->db->prepare("INSERT INTO courses (name, course_url, external_identifier, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$name, $courseUrl, $externalIdentifier, $description, $status]);
        return $this->db->lastInsertId();
    }

    public function updateCourse($id, $name, $courseUrl, $externalIdentifier, $description, $status) {
        $stmt = $this->db->prepare("UPDATE courses SET name = ?, course_url = ?, external_identifier = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$name, $courseUrl, $externalIdentifier, $description, $status, $id]);
    }

    public function deleteCourse($id) {
        $stmt = $this->db->prepare("DELETE FROM courses WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getCourse($id) {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listCourses($search = '', $status = '') {
        $query = "SELECT c.*, (SELECT COUNT(*) FROM questions q WHERE q.course_id = c.id) as questions_count FROM courses c WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (c.name LIKE ? OR c.external_identifier LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($status)) {
            $query .= " AND c.status = ?";
            $params[] = $status;
        }

        $query .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
