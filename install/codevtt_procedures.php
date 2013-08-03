<?php

/*
 * same queries as in codevtt_procedures.sql
 * but the SqlParser cannot handle DELIMITER commands in files.
 * so the queries must be set as variables
 */

$codevtt_sqlProcedures = array();
$codevtt_sqlProcedures[] = "DROP FUNCTION IF EXISTS get_project_resolved_status_threshold;";
$codevtt_sqlProcedures[] = "DROP FUNCTION IF EXISTS get_issue_resolved_status_threshold;";
$codevtt_sqlProcedures[] = "DROP FUNCTION IF EXISTS is_project_in_team;";
$codevtt_sqlProcedures[] = "DROP FUNCTION IF EXISTS is_issue_in_team_commands;";

#-- -----------------------------------------------------
#-- Get project_id bug_resolved_status_threshold value from Mantis config
#-- or (if not found) the default value defined in CodevTT config
#-- -----------------------------------------------------

$codevtt_sqlProcedures[] = "
CREATE FUNCTION get_project_resolved_status_threshold(proj_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
   DECLARE status INT DEFAULT NULL;

   SELECT value INTO status FROM `mantis_config_table`
          WHERE config_id = 'bug_resolved_status_threshold'
          AND project_id = proj_id
          LIMIT 1;

   IF status <=> NULL THEN
      SELECT value INTO status FROM `codev_config_table`
             WHERE config_id = 'bug_resolved_status_threshold'
             AND project_id = 0
             LIMIT 1;
   END IF;

   RETURN status;
END";

#-- -----------------------------------------------------
#-- Get project_id bug_resolved_status_threshold value from Mantis config
#-- or (if not found) the default value defined in CodevTT config
#-- -----------------------------------------------------

$codevtt_sqlProcedures[] = "
CREATE FUNCTION get_issue_resolved_status_threshold(bug_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
   DECLARE proj_id INT DEFAULT NULL;

   SELECT project_id INTO proj_id FROM `mantis_bug_table`
             WHERE id = bug_id
             LIMIT 1;

   RETURN get_project_resolved_status_threshold(proj_id);
END";



#-- -----------------------------------------------------
#-- This procedure is used by the CodevTT mantis plugin
#-- -----------------------------------------------------

$codevtt_sqlProcedures[] = "
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
END";

#-- -----------------------------------------------------
#-- Check if an issue is already referenced,in a command
#-- -----------------------------------------------------

$codevtt_sqlProcedures[] = "
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
END";

?>