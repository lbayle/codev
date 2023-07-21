
-- this script is to be executed to update CodevTT DB v22 to v23 (v1.7.0 to v1.8.0).
SET SQL_MODE='ANSI';

CREATE TABLE IF NOT EXISTS "codev_custom_user_data_table" (
  "user_id" int(11) NOT NULL COMMENT 'MantisBT user id',
  "field_01" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini',
  "field_02" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini',
  "field_03" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini',
  "field_04" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini',
  "field_05" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini',
  "field_06" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini',
  "field_07" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini',
  "field_08" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini',
  "field_09" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini',
  "field_10" varchar(50) DEFAULT NULL COMMENT 'field-name is set in config.ini'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- enable new plugins
INSERT INTO "codev_plugin_table" ("name", "status", "domains", "categories", "version", "description") VALUES
('CustomUserData', 1, 'TeamAdmin,Team,User', 'Admin,Activity', '1.0.0', 'Allows to set some user specific data such as EmployeeId, userId in other DB/Softwares, phoneNumber, etc.<br>The initial goal is to ease the export of CodevTT data to other tools.');

-- tag version
UPDATE "codev_config_table" SET "value"='23' WHERE "config_id"='database_version';

