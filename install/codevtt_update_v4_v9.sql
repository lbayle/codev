
-- this script is to be executed to update CodevTT DB v3 to v9.

-- DB v4 is for CodevTT v0.99.?? released on 2012-??
-- DB v9 is for CodevTT v0.99.18 released on 2012-09-22

ALTER TABLE `codev_team_table` ADD `enabled` tinyint(4) NOT NULL DEFAULT '1' AFTER `leader_id`;
ALTER TABLE `codev_team_table` ADD `lock_timetracks_date` int(11) DEFAULT NULL AFTER `date`;
ALTER TABLE `codev_team_table` MODIFY `name` varchar(64) NOT NULL;

INSERT INTO `codev_config_table` (`config_id`, `value`, `type`) VALUES ('blogCategories', '1:General,2:Imputations', 3);

UPDATE `codev_config_table` SET `value`='9' WHERE `config_id`='database_version';
ALTER TABLE `codev_config_table` CHANGE `desc` `description` longtext;

update `codev_config_table` SET `config_id`='customField_backlog' WHERE `config_id`='customField_remaining';

--
-- Table structure for table `codev_project_category_table`
--

CREATE TABLE IF NOT EXISTS `codev_project_category_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`,`project_id`,`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_table`
--

CREATE TABLE IF NOT EXISTS `codev_servicecontract_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64),
  `team_id` int(11) NOT NULL,
  `state` int(11) unsigned default NULL,
  `version` varchar(64) default NULL,
  `reporter` varchar(64) default NULL,
  `start_date` int(11) unsigned default NULL,
  `end_date` int(11) unsigned default NULL,
  `description` varchar(500) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_commandset_table`
--

CREATE TABLE IF NOT EXISTS `codev_servicecontract_cmdset_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `servicecontract_id` int(11) NOT NULL,
  `commandset_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_commandset_table`
--

CREATE TABLE IF NOT EXISTS `codev_servicecontract_stproj_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `servicecontract_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_commandset_table`
--

CREATE TABLE IF NOT EXISTS `codev_commandset_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) default NULL,
  `date` int(11) unsigned default NULL,
  `team_id` int(11) NOT NULL,
  `state` int(11) unsigned default NULL,
  `budget` int(11) default NULL,
  `budget_days` int(11) default NULL,
  `currency` varchar(3) default 'EUR',
  `description` varchar(500) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_commandset_eng_table`
--

CREATE TABLE IF NOT EXISTS `codev_commandset_cmd_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `commandset_id` int(11) NOT NULL,
  `command_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_engagement_table`
--

CREATE TABLE IF NOT EXISTS `codev_command_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) default NULL,
  `version` varchar(64) default NULL,
  `reporter` varchar(64) default NULL,
  `start_date` int(11) unsigned default NULL,
  `deadline` int(11) default NULL,
  `team_id` int(11) NOT NULL,
  `state` int(11) unsigned default NULL,
  `cost` int(11) default NULL,
  `currency` varchar(3) default 'EUR',
  `budget_dev` int(11) default NULL,
  `budget_mngt` int(11) default NULL,
  `budget_garantie` int(11) default NULL,
  `average_daily_rate` int(11) default NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `description` varchar(500) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_engagement_bug_table`
--

CREATE TABLE IF NOT EXISTS `codev_command_bug_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `command_id` int(11) NOT NULL,
  `bug_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- Please replace '1234' with the bugid of your 'Leave' (absence) task
-- INSERT INTO `codev_config_table` (`config_id`, `value`, `type`) VALUES ('externalTask_leave', '1234', 1);

-- Move Leave task from SideTaskProject to ExternalTaskProject
-- please replace 68 with ExternalTasksProject id and '1234' with the bugid of your 'Leave' (absence) task
-- UPDATE `mantis_bug_table` SET `project_id`='68' WHERE id = '1234';


-- --------------------------------------------------------

-- do not forget to execute tools/move_st_cat_tuples.php

-- do not forget to execute codevtt_procedures.sql
