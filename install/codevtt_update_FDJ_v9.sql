
-- this script is to be executed to update CodevTT DB v3 to v9.

-- DB v1 is for CodevTT vFDJ_1.0.0 released on 2010
-- DB v9 is for CodevTT v0.99.18 released on 2012-09-22


--
-- Structure de la table `codev_blog_activity_table`
--



ALTER TABLE `codev_config_table` ADD `project_id` int(11) NOT NULL DEFAULT '0' AFTER `user_id`;
ALTER TABLE `codev_config_table` ADD `team_id` int(11) NOT NULL DEFAULT '0' AFTER `project_id`;
ALTER TABLE codev_config_table DROP PRIMARY KEY;
ALTER TABLE codev_config_table ADD PRIMARY KEY (`config_id`,`team_id`,`project_id`);

ALTER TABLE `codev_config_table` CHANGE `desc` `description` longtext;

UPDATE `codev_config_table` SET `config_id`='customField_backlog' WHERE `config_id`='customField_remaining';
UPDATE `codev_config_table` SET `config_id`='customField_ExtId' WHERE `config_id`='customField_TC';
UPDATE `codev_config_table` SET `config_id`='client_teamid' WHERE `config_id`='FDJ_teamid';

INSERT INTO `codev_config_table` (`config_id`, `value`, `type`) VALUES ('blogCategories', '1:General,2:Imputations', 3);


-- Create externalTasksProject
INSERT INTO `mantis_project_table` (`name`, `status`, `enabled`, `view_state`, `access_min`, `file_path`, `description`, `category_id`, `inherit_global`) VALUES
('CodevTT Taches Externes', 50, 1, 50, 10, '', '', 1, 1);
INSERT INTO `codev_config_table` (`config_id`, `value`, `type`) VALUES ('externalTasksProject', (SELECT id FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes'), 1);
INSERT INTO `codev_project_job_table` (`project_id`, `job_id`) VALUES ((SELECT `id` FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes'), 1);

INSERT INTO `codev_team_project_table` (`team_id`, `project_id`, `type`) VALUES (3,(SELECT id FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes'),3);
INSERT INTO `codev_team_project_table` (`team_id`, `project_id`, `type`) VALUES (21,(SELECT id FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes'),3);
INSERT INTO `codev_team_project_table` (`team_id`, `project_id`, `type`) VALUES (6,(SELECT id FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes'),3);
INSERT INTO `codev_team_project_table` (`team_id`, `project_id`, `type`) VALUES (26,(SELECT id FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes'),3);
INSERT INTO `codev_team_project_table` (`team_id`, `project_id`, `type`) VALUES (34,(SELECT id FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes'),3);
INSERT INTO `codev_team_project_table` (`team_id`, `project_id`, `type`) VALUES (35,(SELECT id FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes'),3);


-- CUSTOM -- Please replace '60' with the bugid of your 'Leave' (absence) task
INSERT INTO `codev_config_table` (`config_id`, `value`, `type`) VALUES ('externalTask_leave', '60', 1);


-- CUSTOM -- timetracks on "Absence Prosys" & "Absence MMedia" -> externalTask_leave
UPDATE `codev_timetracking_table` SET `bugid`=(SELECT `value` FROM `codev_config_table` where `config_id` = 'externalTask_leave') WHERE `bugid` IN (726, 727);

-- CUSTOM -- move ExternalTasks from SuiviOp to ExternalTaskProject
UPDATE `mantis_bug_table` SET `project_id`= (SELECT id FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes') WHERE `id` IN (59,287,339,586);

-- CUSTOM -- 'ExtId' field is not mandatory anymore
UPDATE `mantis_custom_field_table` SET `require_report` = '0' WHERE `id`= '1';
UPDATE `mantis_custom_field_table` SET `require_update` = '0' WHERE `id`= '1';

-- Move 'Leave' task from SideTaskProject (SuiviOp) to ExternalTaskProject
UPDATE `mantis_bug_table` SET `project_id`= (SELECT id FROM `mantis_project_table` WHERE `name` = 'CodevTT Taches Externes') WHERE `id`=(SELECT `value` FROM `codev_config_table` where `config_id` = 'externalTask_leave');


ALTER TABLE `codev_team_table` ADD `enabled` tinyint(4) NOT NULL DEFAULT '1' AFTER `leader_id`;
ALTER TABLE `codev_team_table` ADD `lock_timetracks_date` int(11) DEFAULT NULL AFTER `date`;
ALTER TABLE `codev_team_table` MODIFY `name` varchar(50) NOT NULL;

INSERT INTO `codev_config_table` (`config_id`, `value`, `type`) VALUES ('database_version', '9', 1);


-- cleanup
DELETE FROM  `codev_config_table` WHERE config_id = 'periodStatsExcludedProjectList';
DELETE FROM  `codev_config_table` WHERE config_id = 'defaultSideTaskProject';
DELETE FROM  `codev_config_table` WHERE config_id = 'bug_resolved_status_threshold';

-- CodevTT_ExtId (RC) field is not mandatory anymore, so fields with '0' can be removed
DELETE FROM `mantis_custom_field_string_table` WHERE `field_id` =1 AND (`value` = '' OR `value` = '0');

--
-- Structure de la table `codev_project_category_table`
--

CREATE TABLE IF NOT EXISTS `codev_project_category_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`,`project_id`,`category_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `codev_blog_activity_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_blog_table`
--

CREATE TABLE IF NOT EXISTS `codev_blog_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_submitted` int(11) NOT NULL,
  `src_user_id` int(11) unsigned NOT NULL,
  `dest_user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `dest_project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `dest_team_id` int(11) unsigned NOT NULL DEFAULT '0',
  `severity` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `summary` varchar(100) NOT NULL,
  `content` varchar(500) DEFAULT NULL,
  `date_expire` int(11) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date_submitted`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Blog posts' ;


CREATE TABLE IF NOT EXISTS `codev_commandset_cmd_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `commandset_id` int(11) NOT NULL,
  `command_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_commandset_table`
--

CREATE TABLE IF NOT EXISTS `codev_commandset_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `date` int(11) unsigned DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `state` int(11) DEFAULT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `budget` int(11) DEFAULT NULL,
  `budget_days` int(11) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_command_bug_table`
--

CREATE TABLE IF NOT EXISTS `codev_command_bug_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `command_id` int(11) DEFAULT NULL,
  `bug_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_command_table`
--

CREATE TABLE IF NOT EXISTS `codev_command_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `version` varchar(64) DEFAULT NULL,
  `reporter` varchar(64) DEFAULT NULL,
  `start_date` int(11) unsigned DEFAULT NULL,
  `deadline` int(11) DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `state` int(11) DEFAULT NULL,
  `cost` int(11) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `budget_dev` int(11) DEFAULT NULL,
  `budget_mngt` int(11) DEFAULT NULL,
  `budget_garantie` int(11) DEFAULT NULL,
  `average_daily_rate` int(11) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

--
-- Structure de la table `codev_servicecontract_cmdset_table`
--

CREATE TABLE IF NOT EXISTS `codev_servicecontract_cmdset_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `servicecontract_id` int(11) NOT NULL,
  `commandset_id` int(11) NOT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_stproj_table`
--

CREATE TABLE IF NOT EXISTS `codev_servicecontract_stproj_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `servicecontract_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_table`
--

CREATE TABLE IF NOT EXISTS `codev_servicecontract_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `team_id` int(11) NOT NULL,
  `state` int(11) DEFAULT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `version` varchar(64) DEFAULT NULL,
  `reporter` varchar(64) DEFAULT NULL,
  `start_date` int(11) DEFAULT NULL,
  `end_date` int(11) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;






-- --------------------------------------------------------

-- do not forget to execute tools/move_st_cat_tuples.php
-- do not forget to execute tools/eta_to_mee.php

-- do not forget to execute codevtt_procedures.sql
-- do not forget to execute codevtt_update_v9_v10.sql



