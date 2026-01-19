-- Add team assignment to projects
ALTER TABLE projects ADD COLUMN team_id INT NULL;
ALTER TABLE projects ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL;