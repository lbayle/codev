
-- this script is to be executed to update CodevTT DB v20 to v21 (v1.5.0 to v1.6.0).
SET SQL_MODE='ANSI';

-- some issues with i18n : desc may be much bigger in UTF8 if not english
ALTER TABLE "codev_plugin_table" MODIFY "description" varchar(1000) default NULL;

ALTER TABLE "codev_project_job_table" ADD "team_id" int(11) NOT NULL DEFAULT '0' AFTER "job_id";

ALTER TABLE "codev_project_job_table" ADD UNIQUE KEY "project_job_team" ("project_id","job_id","team_id");

-- enable new plugins
-- INSERT INTO "codev_plugin_table" ("name", "status", "domains", "categories", "version", "description") VALUES
-- ('BurnDownChart', 1, 'Project,Task,Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the backlog history'),
-- ('FillPeriodWithTimetracks', 1, 'TeamAdmin', 'Activity', '1.0.0', 'Add multiple timetracks at once'),
-- ('ImportRelationshipTreeToCommand', 1, 'Import_Export,Command', 'Import', '1.0.0', 'Import a mantis parent-child relationship issue structure to a command WBS structure'),
-- ('IssueSeniorityIndicator', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Statistics on the age of open tasks'),
-- ('LoadHistoryIndicator', 1, 'Command,Team,Project,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the elapsed time in a period'),
-- ('LoadPerUserGroups', 1, 'Project,Team,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the timetracks of the period and return their repartition per User groups'),
-- ('TimetrackingAnalysis', 1, 'Team,Project', 'Risk', '1.0.0', 'Display the delay between the timetrack date and it\'s creation date');

-- tag version
UPDATE "codev_config_table" SET "value"='21' WHERE "config_id"='database_version';

