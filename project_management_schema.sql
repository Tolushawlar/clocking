-- Project Management Extension for TimeTrack Pro
-- This schema extends the existing clocking system with project management capabilities

-- Projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    client_name VARCHAR(255),
    project_code VARCHAR(50),
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    start_date DATE,
    end_date DATE,
    budget_hours INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_business_status (business_id, status),
    INDEX idx_project_code (project_code)
);

-- Project phases table
CREATE TABLE IF NOT EXISTS project_phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    phase_order INT DEFAULT 1,
    status ENUM('pending', 'in_progress', 'completed', 'blocked') DEFAULT 'pending',
    start_date DATE,
    end_date DATE,
    estimated_hours INT DEFAULT 0,
    actual_hours INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_phase (project_id, phase_order)
);

-- Tasks table (enhanced)
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    phase_id INT,
    parent_task_id INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    task_type ENUM('task', 'deliverable', 'milestone') DEFAULT 'task',
    status ENUM('pending', 'in_progress', 'review', 'completed', 'blocked') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    assigned_to INT,
    created_by INT NOT NULL,
    start_date DATE,
    due_date DATE,
    estimated_hours DECIMAL(5,2) DEFAULT 0,
    actual_hours DECIMAL(5,2) DEFAULT 0,
    completion_percentage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (phase_id) REFERENCES project_phases(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_tasks (project_id, status),
    INDEX idx_assigned_tasks (assigned_to, status),
    INDEX idx_phase_tasks (phase_id, status)
);

-- Project team members
CREATE TABLE IF NOT EXISTS project_team (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'manager', 'contributor', 'viewer') DEFAULT 'contributor',
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_user (project_id, user_id)
);

-- Daily schedules (for individual and team planning)
CREATE TABLE IF NOT EXISTS daily_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT,
    project_id INT,
    schedule_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    schedule_type ENUM('task', 'meeting', 'personal', 'break') DEFAULT 'task',
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_schedule (user_id, schedule_date),
    INDEX idx_task_schedule (task_id, schedule_date)
);

-- Task reports (daily progress reports)
CREATE TABLE IF NOT EXISTS task_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    report_date DATE NOT NULL,
    hours_worked DECIMAL(5,2) DEFAULT 0,
    progress_percentage INT DEFAULT 0,
    status_flag ENUM('on_track', 'at_risk', 'blocked') DEFAULT 'on_track',
    notes TEXT,
    blockers TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_task_user_date (task_id, user_id, report_date)
);

-- Timetables (for teachers/structured schedules)
CREATE TABLE IF NOT EXISTS timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE
);

-- Timetable slots
CREATE TABLE IF NOT EXISTS timetable_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    activity_title VARCHAR(255) NOT NULL,
    activity_description TEXT,
    location VARCHAR(255),
    activity_type ENUM('class', 'meeting', 'break', 'planning', 'other') DEFAULT 'class',
    color_code VARCHAR(7) DEFAULT '#135bec',
    is_recurring BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (timetable_id) REFERENCES timetables(id) ON DELETE CASCADE,
    INDEX idx_timetable_day (timetable_id, day_of_week)
);

-- Activity fulfillment tracking
CREATE TABLE IF NOT EXISTS activity_fulfillments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_slot_id INT NOT NULL,
    user_id INT NOT NULL,
    fulfillment_date DATE NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'missed') DEFAULT 'scheduled',
    notes TEXT,
    marked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (timetable_slot_id) REFERENCES timetable_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slot_user_date (timetable_slot_id, user_id, fulfillment_date)
);

-- Add new columns to existing users table for enhanced permissions
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS can_manage_projects BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS can_create_schedules BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS is_team_leader BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS is_supervisor BOOLEAN DEFAULT FALSE;

-- Update existing admin users to have project management permissions
UPDATE users SET 
    can_manage_projects = TRUE,
    can_create_schedules = TRUE,
    is_team_leader = TRUE,
    is_supervisor = TRUE
WHERE category = 'admin';