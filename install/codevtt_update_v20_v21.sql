
-- this script is to be executed to update CodevTT DB v19 to v20.
SET SQL_MODE='ANSI';

-- some issues with i18n : desc may be much bigger in UTF8 if not english
ALTER TABLE "codev_plugin_table" MODIFY "description" varchar(1000) default NULL;

-- tag version
UPDATE "codev_config_table" SET "value"='21' WHERE "config_id"='database_version';

