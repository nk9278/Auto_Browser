<?php
require_once __DIR__ . '/../config/database.php';

class QuestionManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function normalizeQuestion($text) {
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = strtolower($text);
        $text = preg_replace('/[?!.,;:"\']/', '', $text);
        return $text;
    }

    public function createQuestion($courseId, $questionText, $answerType, $status, $sortOrder) {
        $normalized = $this->normalizeQuestion($questionText);
        $stmt = $this->db->prepare("INSERT INTO questions (course_id, question_text, normalized_question, answer_type, status, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$courseId, $questionText, $normalized, $answerType, $status, $sortOrder]);
        return $this->db->lastInsertId();
    }

    public function updateQuestion($id, $questionText, $answerType, $status, $sortOrder) {
        $normalized = $this->normalizeQuestion($questionText);
        $stmt = $this->db->prepare("UPDATE questions SET question_text = ?, normalized_question = ?, answer_type = ?, status = ?, sort_order = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$questionText, $normalized, $answerType, $status, $sortOrder, $id]);
    }

    public function deleteQuestion($id) {
        $this->deleteOptions($id);
        $stmt = $this->db->prepare("DELETE FROM questions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getQuestion($id) {
        $stmt = $this->db->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($question) {
            $question['options'] = $this->getOptions($id);
        }
        return $question;
    }

    public function listQuestions($courseId = null, $search = '', $status = '') {
        $query = "SELECT q.*, c.name as course_name FROM questions q LEFT JOIN courses c ON q.course_id = c.id WHERE 1=1";
        $params = [];

        if ($courseId) {
            $query .= " AND q.course_id = ?";
            $params[] = $courseId;
        }

        if (!empty($search)) {
            $normalizedSearch = $this->normalizeQuestion($search);
            $query .= " AND (q.question_text LIKE ? OR q.normalized_question LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$normalizedSearch%";
        }

        if (!empty($status)) {
            $query .= " AND q.status = ?";
            $params[] = $status;
        }

        $query .= " ORDER BY q.sort_order ASC, q.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($questions as &$q) {
            $q['options'] = $this->getOptions($q['id']);
        }

        return $questions;
    }

    public function addOption($questionId, $optionKey, $optionText, $isCorrect, $sortOrder) {
        $stmt = $this->db->prepare("INSERT INTO question_options (question_id, option_key, option_text, is_correct, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        return $stmt->execute([$questionId, $optionKey, $optionText, $isCorrect ? 1 : 0, $sortOrder]);
    }

    public function deleteOptions($questionId) {
        $stmt = $this->db->prepare("DELETE FROM question_options WHERE question_id = ?");
        return $stmt->execute([$questionId]);
    }

    public function getOptions($questionId) {
        $stmt = $this->db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function previewCSV($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found.");
        }

        $handle = fopen($filePath, "r");
        if ($handle === false) {
            throw new Exception("Could not read file.");
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception("Empty CSV file.");
        }

        $expectedHeaders = ['question', 'answer_type', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer'];
        $headerMap = [];
        foreach ($headers as $idx => $h) {
            $headerMap[strtolower(trim($h))] = $idx;
        }

        foreach (['question', 'correct_answer'] as $req) {
            if (!isset($headerMap[$req])) {
                fclose($handle);
                throw new Exception("Missing required column: " . $req);
            }
        }

        $preview = [];
        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) continue;

            $qText = isset($headerMap['question']) ? trim($row[$headerMap['question']]) : '';
            $aType = isset($headerMap['answer_type']) ? trim($row[$headerMap['answer_type']]) : 'single_choice';
            $correct = isset($headerMap['correct_answer']) ? trim($row[$headerMap['correct_answer']]) : '';

            $isValid = true;
            $errors = [];

            if (empty($qText)) {
                $isValid = false;
                $errors[] = "Empty question";
            }
            if (empty($correct)) {
                $isValid = false;
                $errors[] = "Missing correct answer";
            }

            $opts = ['a', 'b', 'c', 'd'];
            $optionsData = [];
            foreach ($opts as $optLetter) {
                $colName = "option_$optLetter";
                if (isset($headerMap[$colName])) {
                    $optText = trim($row[$headerMap[$colName]]);
                    if (!empty($optText)) {
                        $optionsData[$optLetter] = $optText;
                    }
                }
            }

            if (empty($optionsData) && in_array($aType, ['single_choice', 'multiple_choice'])) {
                 $isValid = false;
                 $errors[] = "Missing options";
            }

            $preview[] = [
                'row' => $rowNum,
                'question' => $qText,
                'type' => $aType,
                'options' => $optionsData,
                'correct' => $correct,
                'is_valid' => $isValid,
                'errors' => $errors
            ];
        }

        fclose($handle);
        return $preview;
    }

    public function importCSV($courseId, $filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found.");
        }

        $handle = fopen($filePath, "r");
        if ($handle === false) {
            throw new Exception("Could not read file.");
        }

        $stmt = $this->db->prepare("SELECT id FROM courses WHERE id = ?");
        $stmt->execute([$courseId]);
        if (!$stmt->fetch()) {
            fclose($handle);
            throw new Exception("Invalid course ID.");
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception("Empty CSV file.");
        }

        $headerMap = [];
        foreach ($headers as $idx => $h) {
            $headerMap[strtolower(trim($h))] = $idx;
        }

        foreach (['question', 'correct_answer'] as $req) {
            if (!isset($headerMap[$req])) {
                fclose($handle);
                throw new Exception("Missing required column: " . $req);
            }
        }

        $this->db->beginTransaction();

        try {
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) continue;

                $qText = isset($headerMap['question']) ? trim($row[$headerMap['question']]) : '';
                $aType = isset($headerMap['answer_type']) ? trim($row[$headerMap['answer_type']]) : 'single_choice';
                $correct = isset($headerMap['correct_answer']) ? trim($row[$headerMap['correct_answer']]) : '';

                if (empty($qText)) {
                    throw new Exception("Empty question on row $rowNum");
                }

                $qId = $this->createQuestion($courseId, $qText, $aType, 'active', 0);

                $correctKeys = array_map('trim', explode(',', $correct));

                $opts = ['a', 'b', 'c', 'd'];
                $sortOrder = 0;
                foreach ($opts as $optLetter) {
                    $colName = "option_$optLetter";
                    if (isset($headerMap[$colName])) {
                        $optText = trim($row[$headerMap[$colName]]);
                        if (!empty($optText)) {
                            $optKey = strtoupper($optLetter);
                            $isCorrect = in_array($optKey, $correctKeys) || in_array($optLetter, $correctKeys);
                            $this->addOption($qId, $optKey, $optText, $isCorrect, $sortOrder++);
                        }
                    }
                }
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);
        return true;
    }
}
