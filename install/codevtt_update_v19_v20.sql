
-- this script is to be executed to update CodevTT DB v18 to v19.
SET SQL_MODE='ANSI';

-- SalesPricePerPeriod plugin

-- TODO create custom field


INSERT INTO `codev_config_table`
(`config_id`, `value`, `type`, `user_id`, `project_id`, `team_id`, `servicecontract_id`, `commandset_id`, `command_id`, `access_reqd`, `description`)  VALUES
('customField_dailyPrice',(SELECT id FROM `mantis_custom_field_table` WHERE `name` = 'CodevTT_DailyPrice'),1,0,0,0,0,0,0,0,0);

-- tag version
UPDATE "codev_config_table" SET "value"='20' WHERE "config_id"='database_version';

