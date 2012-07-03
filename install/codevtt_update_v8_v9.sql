
-- this script is to be executed to update CodevTT DB v8 to v9.

-- DB v8 is for CodevTT v0.99.17

-- -----------------

ALTER table codev_command_table modify cost int(11);
ALTER table codev_command_table modify budget_dev int(11);
ALTER table codev_command_table modify budget_mngt int(11);
ALTER table codev_command_table modify budget_garantie int(11);
ALTER table codev_command_table modify average_daily_rate int(11);

ALTER table codev_commandset_table modify budget int(11);


ALTER TABLE `codev_team_table`            ADD UNIQUE (`name`);
ALTER TABLE `codev_command_table`         ADD UNIQUE (`name`);
ALTER TABLE `codev_commandset_table`      ADD UNIQUE (`name`);
ALTER TABLE `codev_servicecontract_table` ADD UNIQUE (`name`);

UPDATE `codev_config_table` SET `value`='9' WHERE `config_id`='database_version';



