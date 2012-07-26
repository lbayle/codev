
-- this script is to be executed to update CodevTT DB v8 to v9.

-- DB v8 is for CodevTT v0.99.17

-- -----------------

ALTER table codev_command_table modify cost int(11);
ALTER table codev_command_table modify budget_dev int(11);
ALTER table codev_command_table modify budget_mngt int(11);
ALTER table codev_command_table modify budget_garantie int(11);
ALTER table codev_command_table modify average_daily_rate int(11);

ALTER table codev_commandset_table modify budget int(11);


ALTER TABLE `codev_command_table`         ADD UNIQUE (`name`);
ALTER TABLE `codev_commandset_table`      ADD UNIQUE (`name`);
ALTER TABLE `codev_servicecontract_table` ADD UNIQUE (`name`);

UPDATE `codev_config_table` SET `value`='9' WHERE `config_id`='database_version';


ALTER TABLE `codev_team_table` ADD `enabled` tinyint(4) NOT NULL DEFAULT '1' AFTER `leader_id`;
ALTER TABLE `codev_team_table` ADD `lock_timetracks_date` int(11) DEFAULT NULL AFTER `date`;

-- -----------------

DELIMITER |
CREATE FUNCTION is_project_in_team(projid INT, teamid INT)
RETURNS INT
DETERMINISTIC
BEGIN
   DECLARE is_found INT DEFAULT NULL;



   SELECT COUNT(team_id) INTO is_found FROM `codev_team_project_table`
          WHERE team_id = teamid
          AND   project_id = projid
          LIMIT 1;

   RETURN is_found;
END|
DELIMITER ;

-- -----------------------------------------------------
-- Check if an issue is already referenced,in a command
-- -----------------------------------------------------

DELIMITER |
CREATE FUNCTION is_issue_in_team_commands(bugid INT, teamid INT)
RETURNS INT
DETERMINISTIC
BEGIN
   DECLARE is_found INT DEFAULT NULL;

   SELECT COUNT(codev_command_bug_table.bug_id) INTO is_found FROM `codev_command_bug_table`, `codev_command_table`
          WHERE codev_command_table.id = codev_command_bug_table.command_id
          AND   codev_command_table.team_id = teamid
          AND   codev_command_bug_table.bug_id = bugid
          LIMIT 1;

   RETURN is_found;
END|
DELIMITER ;

