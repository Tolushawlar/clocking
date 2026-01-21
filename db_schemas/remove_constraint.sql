ALTER TABLE `teams` 
  DROP FOREIGN KEY `teams_ibfk_1`,
  DROP FOREIGN KEY `teams_ibfk_2`,
  DROP FOREIGN KEY `teams_ibfk_3`;

ALTER TABLE `team_members` 
  DROP FOREIGN KEY `team_members_ibfk_1`,
  DROP FOREIGN KEY `team_members_ibfk_2`,
  DROP FOREIGN KEY `team_members_ibfk_3`;


ALTER TABLE `projects`
  DROP FOREIGN KEY `projects_ibfk_1`,
  DROP FOREIGN KEY `projects_ibfk_2`,
  DROP FOREIGN KEY `projects_ibfk_3`;


ALTER TABLE `project_members`
  DROP FOREIGN KEY `project_members_ibfk_1`,
  DROP FOREIGN KEY `project_members_ibfk_2`,
  DROP FOREIGN KEY `project_members_ibfk_3`;


ALTER TABLE `tasks`
  DROP FOREIGN KEY `tasks_ibfk_4`;