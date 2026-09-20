ALTER TABLE `courses`
ADD COLUMN `course_url` VARCHAR(255) NULL AFTER `name`,
ADD COLUMN `external_identifier` VARCHAR(255) NULL AFTER `course_url`,
ADD COLUMN `status` ENUM('active', 'inactive') DEFAULT 'active' AFTER `description`;

ALTER TABLE `questions`
ADD COLUMN `course_id` BIGINT UNSIGNED NULL AFTER `id`,
ADD COLUMN `normalized_question` TEXT NULL AFTER `question_text`,
ADD COLUMN `answer_type` VARCHAR(50) DEFAULT 'single_choice' AFTER `normalized_question`,
ADD COLUMN `status` ENUM('active', 'inactive') DEFAULT 'active' AFTER `answer_type`,
ADD COLUMN `sort_order` INT DEFAULT 0 AFTER `status`,
ADD CONSTRAINT `fk_question_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE;

ALTER TABLE `question_options`
ADD COLUMN `option_key` VARCHAR(50) NULL AFTER `question_id`,
ADD COLUMN `sort_order` INT DEFAULT 0 AFTER `is_correct`;
