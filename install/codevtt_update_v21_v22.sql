
-- this script is to be executed to update CodevTT DB v21 to v22 (v1.6.0 to v1.7.0).
SET SQL_MODE='ANSI';

-- enable new plugins
-- INSERT INTO "codev_plugin_table" ("name", "status", "domains", "categories", "version", "description") VALUES
-- ('LoadPerCustomfieldValues', 1, 'Team,Project,User,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Choose a customfield, return the elapsed time for each customField value'),
-- ('ResetDashboard', 1, 'Admin', 'Admin', '1.0.0', 'Remove all plugins from a dashboard. This is usefull if a plugin crashes the page');

-- tag version
UPDATE "codev_config_table" SET "value"='22' WHERE "config_id"='database_version';

