
-- this script is to be executed to update CodevTT DB v11 to v12.

-- DB v11 is for CodevTT v0.99.20/21 (Apr 2013)
-- DB v12 is for CodevTT v0.99.22

-- -----------------

-- create WBS tables

-- TODO


-- prepare team users to have an averageDaylyRate (TJM)
ALTER TABLE `codev_team_user_table` ADD `average_daily_rate` int(11) DEFAULT NULL AFTER `departure_date`;

-- tag version
UPDATE `codev_config_table` SET `value`='12' WHERE `config_id`='database_version';
