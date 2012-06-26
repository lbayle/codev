
-- this script is to be executed to update CodevTT DB v7 to v8.

-- DB v7 is for CodevTT v0.99.16 released on 2012-04-12
-- DB v8 is for CodevTT v0.99.17

-- -----------------
RENAME TABLE `codev_command_table` To `codev_servicecontract_table`; 

ALTER TABLE `codev_servicecontract_table` ADD `state` int(11)  AFTER `team_id`;
ALTER TABLE `codev_servicecontract_table` ADD `reference` varchar(64)  AFTER `state`;
ALTER TABLE `codev_servicecontract_table` ADD `version` varchar(64)  AFTER `reference`;
ALTER TABLE `codev_servicecontract_table` ADD `reporter` varchar(64)  AFTER `version`;
ALTER TABLE `codev_servicecontract_table` ADD `start_date` int(11)  AFTER `reporter`;
ALTER TABLE `codev_servicecontract_table` ADD `end_date` int(11)  AFTER `start_date`;


-- -----------------
RENAME TABLE `codev_command_srv_table` To `codev_servicecontract_cmdset_table`; 

ALTER TABLE `codev_servicecontract_cmdset_table` CHANGE `command_id` `servicecontract_id` int(11);
ALTER TABLE `codev_servicecontract_cmdset_table` CHANGE `service_id` `commandset_id` int(11); 


-- -----------------
CREATE TABLE IF NOT EXISTS `codev_servicecontract_stproj_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `servicecontract_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- -----------------
RENAME TABLE `codev_service_table` To `codev_commandset_table`; 

ALTER TABLE `codev_commandset_table` ADD `state` int(11)  AFTER `team_id`;
ALTER TABLE `codev_commandset_table` ADD `reference` varchar(64)  AFTER `state`;
ALTER TABLE `codev_commandset_table` ADD `budget` float  AFTER `state`;
ALTER TABLE `codev_commandset_table` ADD `budget_days` int(11)  AFTER `budget`;
ALTER TABLE `codev_commandset_table` ADD `currency` varchar(3) default 'EUR' AFTER `budget_days`;

-- -----------------
RENAME TABLE `codev_service_eng_table` To `codev_commandset_cmd_table`; 

ALTER TABLE `codev_commandset_cmd_table` CHANGE `service_id`    `commandset_id` int(11);
ALTER TABLE `codev_commandset_cmd_table` CHANGE `engagement_id` `command_id` int(11);

-- -----------------
RENAME TABLE `codev_engagement_table` To `codev_command_table`; 

ALTER TABLE `codev_command_table` ADD `reference` varchar(64)  AFTER `name`;
ALTER TABLE `codev_command_table` ADD `version` varchar(64)  AFTER `reference`;
ALTER TABLE `codev_command_table` ADD `reporter` varchar(64)  AFTER `version`;
ALTER TABLE `codev_command_table` ADD `cost` float  AFTER `state`;
ALTER TABLE `codev_command_table` ADD `currency` varchar(3) default 'EUR' AFTER `cost`;

-- -----------------
RENAME TABLE `codev_engagement_bug_table` To `codev_command_bug_table`; 

ALTER TABLE `codev_command_bug_table` CHANGE `engagement_id` `command_id` int(11);

-- -----------------

CREATE TABLE IF NOT EXISTS `codev_project_category_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`,`project_id`,`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;




-- -----------------
UPDATE `codev_config_table` SET `value`='8' WHERE `config_id`='database_version';
ALTER TABLE `codev_config_table` CHANGE `desc` `description` longtext;


