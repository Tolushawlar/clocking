-- Create missing tables
CREATE TABLE IF NOT EXISTS teacher_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    teacher_id INT NOT NULL,
    activity_name VARCHAR(200) NOT NULL,
    activity_date DATE NOT NULL,
    start_time TIME NOT NULL,
    duration INT NOT NULL,
    location VARCHAR(100) NOT NULL,
    grade_level VARCHAR(50),
    status ENUM('pending', 'completed') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS teacher_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    teacher_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    subject VARCHAR(50) NOT NULL,
    room VARCHAR(50) NOT NULL,
    day_of_week TINYINT NOT NULL,
    start_time TIME NOT NULL,
    duration INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO projects (business_id, name, description, status, created_by) VALUES
(1, 'Website Redesign', 'Complete redesign of company website', 'active', 1),
(1, 'Mobile App Development', 'New mobile application for customers', 'active', 1);

INSERT INTO teacher_activities (business_id, teacher_id, activity_name, activity_date, start_time, duration, location, grade_level, status) VALUES
(1, 1, 'Homeroom Registration', CURDATE(), '08:00:00', 45, 'Room 101', 'Grade 10B', 'completed'),
(1, 1, 'Mathematics - Calculus II', CURDATE(), '10:30:00', 60, 'Room 304', 'Grade 12C', 'pending'),
(1, 1, 'Staff Meeting', CURDATE(), '13:00:00', 60, 'Conference Room B', '', 'pending');