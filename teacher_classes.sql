-- Add teacher_classes table for timetable management
CREATE TABLE IF NOT EXISTS teacher_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    teacher_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    subject VARCHAR(50) NOT NULL,
    room VARCHAR(50) NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday',
    start_time TIME NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_schedule (teacher_id, day_of_week, start_time),
    INDEX idx_business_classes (business_id)
);