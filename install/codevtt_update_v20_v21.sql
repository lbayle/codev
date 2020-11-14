
-- this script is to be executed to update CodevTT DB v20 to v21 (v1.5.0 to v1.6.0).
SET SQL_MODE='ANSI';

-- some issues with i18n : desc may be much bigger in UTF8 if not english
ALTER TABLE "codev_plugin_table" MODIFY "description" varchar(1000) default NULL;

ALTER TABLE "codev_project_job_table" ADD "team_id" int(11) NOT NULL DEFAULT '0' AFTER "job_id";

-- tag version
UPDATE "codev_config_table" SET "value"='21' WHERE "config_id"='database_version';

