
-- this script is to be executed to update CodevTT DB v18 to v19.
SET SQL_MODE='ANSI';

-- CREATE INDEX idx_tt_bugid_date ON codev_timetracking_table ("bugid", "date");

-- BlogPlugin
CREATE INDEX key1 ON codev_blog_table ("dest_team_id", "dest_user_id", "date_submitted");
CREATE INDEX key1 ON codev_blog_activity_table ("blog_id", "user_id", "action");

INSERT INTO "codev_blog_table" ("id", "date_submitted", "src_user_id", "dest_user_id", "dest_project_id", "dest_team_id", "severity", "category", "summary", "content", "date_expire", "color") VALUES
(1, 1526228606, 0, 0, 0, 0, 2, '3', 'Exchange messages with your team !', 'Hi,\nthis plugins allows to share notifications within the teams.\n\nExemple:\n\"The Mantis/CodevTT server will be unavailable for maintenance on May 13th\"\n\nClick <img src=\"images/b_add.png\"/> button on your right to add a message\nClick <img src="images/b_markAsRead.png"/> to inform that you have read the message\nClick <img src="images/b_ghost.png"/> to hide the message', 0, '0');

-- leader_id is replaced by a list of administrators '123,543,98,43'
ALTER TABLE "codev_team_table" ADD "administrators" varchar(255) DEFAULT NULL AFTER "description";
UPDATE "codev_team_table" SET "administrators" = "leader_id";
-- ALTER TABLE "codev_team_table" DROP "leader_id";

-- increase size for message plugin
ALTER TABLE "codev_blog_table" MODIFY "content" VARCHAR(2000);

-- insert new plugins
-- INSERT INTO "codev_plugin_table" ("name", "status", "domains", "categories", "version", "description") VALUES
-- ('ManagementCosts', 1, 'ServiceContract', 'Financial', '1.0.0', 'Sum elapsed time on management sideTasks and compare to the sum of command provisions. Returns a result in man-days and costs'),
-- ('SubmittedResolvedHistoryIndicator', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the number of issues submitted/resolved in a period'),
-- ('TasksPivotTable', 1, 'Team,User,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Group tasks by adding multiple filters'),
-- ('TimetrackList', 1, 'Task,Command,CommandSet', 'Activity', '1.0.0', 'List and edit timetracks'),
-- ('WBSExport', 1, 'Command', 'Roadmap', '1.0.0', 'export WBS to CSV file');

UPDATE "codev_plugin_table" SET "status"='1' WHERE "name"='BlogPlugin';

-- tag version
UPDATE "codev_config_table" SET "value"='19' WHERE "config_id"='database_version';

