
-- this script is to be executed to update CodevTT DB v8 to v9.

-- DB v8 is for CodevTT v0.99.17
-- DB v9 is for CodevTT v0.99.18

-- -----------------

UPDATE `codev_config_table` SET `value`='9' WHERE `config_id`='database_version';

INSERT INTO `codev_config_table` (`config_id`, `value`, `type`) VALUES ('severityNames', '10:feature,20:trivial,30:text,40:tweak,50:minor,60:major,70:crash,80:block', 3);

UPDATE `codev_config_table` SET `config_id`='customField_backlog' WHERE `config_id`='customField_remaining';

ALTER TABLE codev_command_table MODIFY cost int(11);
ALTER TABLE codev_command_table MODIFY budget_dev int(11);
ALTER TABLE codev_command_table MODIFY budget_mngt int(11);
ALTER TABLE codev_command_table MODIFY budget_garantie int(11);
ALTER TABLE codev_command_table MODIFY average_daily_rate int(11);

ALTER TABLE codev_command_table ADD `enabled` tinyint(4) NOT NULL DEFAULT '1' AFTER `average_daily_rate`;

ALTER TABLE codev_commandset_table MODIFY budget int(11);


ALTER TABLE `codev_command_table`         ADD UNIQUE (`name`);
ALTER TABLE `codev_commandset_table`      ADD UNIQUE (`name`);
ALTER TABLE `codev_servicecontract_table` ADD UNIQUE (`name`);

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

