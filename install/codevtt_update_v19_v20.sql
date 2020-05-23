
-- this script is to be executed to update CodevTT DB v19 to v20.
SET SQL_MODE='ANSI';

-- Declare new customField, for SalesPricePerPeriod plugin
INSERT INTO `codev_config_table`
(`config_id`, `value`, `type`, `user_id`, `project_id`, `team_id`, `servicecontract_id`, `commandset_id`, `command_id`, `access_reqd`, `description`)  VALUES
('customField_dailyPrice',(SELECT id FROM `mantis_custom_field_table` WHERE `name` = 'CodevTT_DailyPrice'),1,0,0,0,0,0,0,0,0);

-- Activate new plugins
UPDATE "codev_plugin_table" SET "status"='1' WHERE "name"='AdminTools';
UPDATE "codev_plugin_table" SET "status"='1' WHERE "name"='OngoingTasks';
UPDATE "codev_plugin_table" SET "status"='1' WHERE "name"='SellingPriceForPeriod';

-- tag version
UPDATE "codev_config_table" SET "value"='20' WHERE "config_id"='database_version';

