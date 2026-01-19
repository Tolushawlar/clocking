-- Team-based hierarchy schema
CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    team_leader_id INT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES business(id) ON DELETE CASCADE,
    FOREIGN KEY (team_leader_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_member (team_id, user_id)
);

-- Update projects table to link to teams
ALTER TABLE projects ADD COLUMN team_id INT NULL;
ALTER TABLE projects ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL;

-- Update tasks table
ALTER TABLE tasks ADD COLUMN deadline DATE NULL;
ALTER TABLE tasks ADD COLUMN completed_by INT NULL;
ALTER TABLE tasks ADD COLUMN completed_at TIMESTAMP NULL;
ALTER TABLE tasks ADD FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL;

-- Update users table for roles
ALTER TABLE users ADD COLUMN user_role ENUM('admin', 'team_leader', 'team_member') DEFAULT 'team_member';