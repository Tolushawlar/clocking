-- Insert sample data for demonstration
INSERT IGNORE INTO projects (id, business_id, name, description, status, created_by) VALUES
(1, 1, 'Website Redesign', 'Complete redesign of company website', 'active', 1),
(2, 1, 'Mobile App Development', 'New mobile application for customers', 'active', 1);

INSERT IGNORE INTO teacher_activities (business_id, teacher_id, activity_name, activity_date, start_time, duration, location, grade_level, status) VALUES
(1, 1, 'Homeroom Registration', CURDATE(), '08:00:00', 45, 'Room 101', 'Grade 10B', 'completed'),
(1, 1, 'Mathematics - Calculus II', CURDATE(), '10:30:00', 60, 'Room 304', 'Grade 12C', 'pending'),
(1, 1, 'Staff Meeting', CURDATE(), '13:00:00', 60, 'Conference Room B', '', 'pending');