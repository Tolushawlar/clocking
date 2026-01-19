-- Add teacher_activities table for daily activity tracking
CREATE TABLE IF NOT EXISTS teacher_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    teacher_id INT NOT NULL,
    activity_name VARCHAR(200) NOT NULL,
    activity_date DATE NOT NULL,
    start_time TIME NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    location VARCHAR(100) NOT NULL,
    grade_level VARCHAR(50),
    tags TEXT,
    icon VARCHAR(50) DEFAULT 'meeting_room',
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    completion_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_date (teacher_id, activity_date),
    INDEX idx_business_activities (business_id)
);

-- Insert sample data for demonstration
INSERT INTO teacher_activities (business_id, teacher_id, activity_name, activity_date, start_time, duration, location, grade_level, tags, icon, status, completed_at) VALUES
(1, 1, 'Homeroom Registration', CURDATE(), '08:00:00', 45, 'Room 101', 'Grade 10B', '', 'meeting_room', 'completed', NOW() - INTERVAL 2 HOUR),
(1, 1, 'Physics Lab - Mechanics', CURDATE(), '09:00:00', 90, 'Lab 3', 'Grade 11A', '', 'science', 'completed', NOW() - INTERVAL 1 HOUR),
(1, 1, 'Mathematics - Calculus II', CURDATE(), '10:30:00', 60, 'Room 304', 'Grade 12C', 'Chapter 4,Exam Prep', 'meeting_room', 'pending', NULL),
(1, 1, 'Staff Meeting', CURDATE(), '13:00:00', 60, 'Conference Room B', '', '', 'group', 'pending', NULL),
(1, 1, 'History - World War II', CURDATE(), '14:30:00', 60, 'Room 202', 'Grade 10A', '', 'history_edu', 'pending', NULL);