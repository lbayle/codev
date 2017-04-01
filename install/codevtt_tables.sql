-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- genere le : Mar 03 Mai 2011 a 09:45
-- Version du serveur: 5.1.41
-- Version de PHP: 5.3.1

-- SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


-- /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
-- /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
-- /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
-- /*!40101 SET NAMES utf8 */;

--
-- Base de donnees: `bugtracker`
--

-- --------------------------------------------------------


--
-- tune MantisBT tables for CodevTT needs
--
-- CREATE INDEX `handler_id` ON `mantis_bug_table` (`handler_id`);


--
-- Structure de la table `codev_config_table`
--
CREATE TABLE IF NOT EXISTS `codev_config_table` (
  `config_id` varchar(64) NOT NULL,
  `value` longtext NOT NULL,
  `type` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `project_id` int(11) NOT NULL DEFAULT '0',
  `team_id` int(11) NOT NULL DEFAULT '0',
  `servicecontract_id` int(11) NOT NULL DEFAULT '0',
  `commandset_id` int(11) NOT NULL DEFAULT '0',
  `command_id` int(11) NOT NULL DEFAULT '0',
  `access_reqd` int(11) DEFAULT NULL,
  `description` longtext,
  PRIMARY KEY (`config_id`,`team_id`,`project_id`,`user_id`,`servicecontract_id`,`commandset_id`,`command_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


INSERT INTO `codev_config_table` (`config_id`, `value`, `type`) VALUES
('database_version', 17, 1),
('blogCategories', '1:General,2:Timetracking,3:Admin', 3);


-- --------------------------------------------------------

--
-- Structure de la table `codev_holidays_table`
--
CREATE TABLE IF NOT EXISTS `codev_holidays_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` int(11) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT 'D8D8D8',
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Fixed Holidays (national, religious, etc.)' AUTO_INCREMENT=36 ;

--
-- Contenu de la table `codev_holidays_table`
--

INSERT INTO `codev_holidays_table` (`date`, `description`, `color`) VALUES
(1492380000, 'lundi de paques', '58CC77'),
(1493589600, 'fete du travail', '58CC77'),
(1494194400, 'victoire 1945', '58CC77'),
(1495663200, 'ascension', '58CC77'),
(1496613600, 'pentecote', '58CC77'),
(1499983200, 'fete nationale', '58CC77'),
(1502748000, 'assomption', '58CC77'),
(1509490800, 'toussaint', '58CC77'),
(1514156400, 'noel', '58CC77'),
(1514761200, 'reveillon', '58CC77');

-- --------------------------------------------------------

--
-- Structure de la table `codev_job_table`
--

CREATE TABLE IF NOT EXISTS `codev_job_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `color` varchar(7) CHARACTER SET utf8 DEFAULT '000000',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `codev_job_table`
--

INSERT INTO `codev_job_table` (`id`, `name`, `type`, `color`) VALUES
(1, 'N/A', 1, 'A8FFBD'),
(2, 'Support', 0, 'A8FFBD');
-- --------------------------------------------------------

--
-- Structure de la table `codev_project_job_table`
--

CREATE TABLE IF NOT EXISTS `codev_project_job_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_sidetasks_category_table`
--

CREATE TABLE IF NOT EXISTS `codev_sidetasks_category_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `cat_management` int(11) DEFAULT NULL,
  `cat_incident` int(11) DEFAULT NULL,
  `cat_inactivity` int(11) DEFAULT NULL,
  `cat_tools` int(11) DEFAULT NULL,
  `cat_workshop` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id` (`project_id`),
  KEY `project_id_2` (`project_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;


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
-- Structure de la table `codev_team_project_table`
--

CREATE TABLE IF NOT EXISTS `codev_team_project_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `team_id` (`team_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_team_table`
--

CREATE TABLE IF NOT EXISTS `codev_team_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `leader_id` int(11) DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `commands_enabled` tinyint(4) NOT NULL DEFAULT '1',
  `date` int(11) NOT NULL,
  `lock_timetracks_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_team_user_table`
--

CREATE TABLE IF NOT EXISTS `codev_team_user_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `access_level` int(11) unsigned NOT NULL DEFAULT '10',
  `arrival_date` int(11) unsigned NOT NULL,
  `departure_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_timetracking_table`
--

CREATE TABLE IF NOT EXISTS `codev_timetracking_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `bugid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  `date` int(11) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `committer_id` int(11) DEFAULT NULL,
  `commit_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bugid` (`bugid`),
  KEY `userid` (`userid`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_blog_table`
--

CREATE TABLE IF NOT EXISTS `codev_blog_table` (
  `id` int(11) NOT NULL auto_increment,
  `date_submitted` int(11) NOT NULL,
  `src_user_id` int(11) unsigned NOT NULL,
  `dest_user_id` int(11) unsigned NOT NULL default '0',
  `dest_project_id` int(11) unsigned NOT NULL default '0',
  `dest_team_id` int(11) unsigned NOT NULL default '0',
  `severity` int(11) NOT NULL,
  `category` varchar(50) default NULL,
  `summary` varchar(100) NOT NULL,
  `content` varchar(500) default NULL,
  `date_expire` int(11) default NULL,
  `color` varchar(7) default NULL,
  PRIMARY KEY  (`id`),
  KEY `date` (`date_submitted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Wall posts' AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_blog_activity_table`
--

CREATE TABLE IF NOT EXISTS `codev_blog_activity_table` (
  `id` int(11) NOT NULL auto_increment,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1  COMMENT='Wall activity';


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
  `wbs_id` int(11) unsigned NOT NULL,
  `state` int(11) unsigned default NULL,
  `currency` varchar(3) default 'EUR',
  `total_days` int(11) default NULL,
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
  PRIMARY KEY  (`id`),
  KEY `command_id` (`command_id`),
  KEY `bug_id` (`bug_id`)

) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------
-- 20121021 Command Provision
CREATE TABLE IF NOT EXISTS `codev_command_provision_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `date` int(11) unsigned NOT NULL,
  `command_id` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `summary` varchar(128) NOT NULL,
  `budget_days` int(11) default NULL,
  `budget` int(11) default NULL,
  `average_daily_rate` int(11) default NULL,
  `currency` varchar(3) default 'EUR',
  `is_in_check_budget` tinyint(4) NOT NULL DEFAULT '0',
  `description` longtext default NULL,
  PRIMARY KEY  (`id`),
  KEY `command_id` (`command_id`)

) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_wbs_table`
--

CREATE TABLE IF NOT EXISTS `codev_wbs_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `root_id` int(11) unsigned default NULL,
  `parent_id` int(11) unsigned default NULL,
  `order` int(11) NOT NULL,
  `bug_id` int(11) default NULL,
  `expand` tinyint(4) NOT NULL DEFAULT '0',
  `title` varchar(255) default NULL,
  `icon` varchar(64) default NULL,
  `font` varchar(64) default NULL,
  `color` varchar(64) default NULL,
  PRIMARY KEY  (`id`),
  KEY `bug_id` (`bug_id`),
  KEY `parent_id` (`parent_id`),
  KEY `order` (`order`)

) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_plugin_table`
--

CREATE TABLE IF NOT EXISTS `codev_plugin_table` (
  `name` varchar(64) NOT NULL,
  `status` int(11) NOT NULL default 0,
  `domains` varchar(250) NOT NULL,
  `categories` varchar(250) NOT NULL,
  `version` varchar(10) NOT NULL,
  `description` varchar(250) default NULL,
  PRIMARY KEY  (`name`)

) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

INSERT INTO `codev_plugin_table` (`name`, `status`, `domains`, `categories`, `version`, `description`) VALUES
('AvailableWorkforceIndicator', 1, 'Team', 'Planning', '1.0.0', 'Man-days available in period, except leaves and external tasks'),
('BacklogPerUserIndicator', 1, 'Team,User,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the tasks and return the backlog per User'),
('BlogPlugin', 0, 'Homepage', 'Internal', '1.0.0', 'Display messages on the homepage'),
('BudgetDriftHistoryIndicator2', 1, 'Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the budget history'),
('DeadlineAlertIndicator', 1, 'User,Team,Project,Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display unresolved tasks that should have been delivered'),
('DriftAlertIndicator', 1, 'Homepage,User,Team,Project,Command,CommandSet,ServiceContract', 'Risk', '1.0.0', 'Display tasks where the elapsed time is greater than the estimated effort'),
('EffortEstimReliabilityIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Display the EffortEstim reliability rate history<br>rate = EffortEstim / elapsed (on resolved tasks only)'),
('HelloWorldIndicator', 0, 'Homepage,Team,User', 'Quality', '1.0.0', 'A simple HelloWorld plugin'),
('ImportIssueCsv', 1, 'Import_Export', 'Import', '1.0.0', 'Import a list of issues to MantisBT / CodevTT from a CSV file'),
('ImportUsers', 1, 'Import_Export', 'Import', '1.0.0', 'Import a list of users to MantisBT / CodevTT'),
('IssueBacklogVariationIndicator', 1, 'Task', 'Roadmap', '1.0.0', 'Display task backlog updates since the task creation'),
('IssueConsistencyCheck', 1, 'Homepage', 'Risk', '1.0.0', 'Check for errors in issues'),
('LoadPerJobIndicator2', 1, 'Task,Team,User,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Check all the timetracks of the period and return their repartition per Job'),
('LoadPerProjCategoryIndicator', 1, 'Team,Project,User,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the timetracks of the period and return their repartition per project categories'),
('LoadPerProjectIndicator', 1, 'User,Team,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the timetracks of the period and return their repartition per Project'),
('LoadPerUserIndicator', 1, 'Task,Team,User,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the timetracks of the period and return their repartition per User'),
('ManagementLoadHistoryIndicator', 1, 'ServiceContract', 'Activity', '1.0.0', 'Compares the elapsed time on management sideTasks to the management provisions'),
('MoveIssueTimetracks', 1, 'Admin,TeamAdmin', 'Admin', '1.0.0', 'Move timetracks from one issue to another'),
('ProgressHistoryIndicator2', 1, 'Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the progress history'),
('ReopenedRateIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Display the bug reopened rate history'),
('StatusHistoryIndicator2', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Display Issue Status history'),
('TimePerStatusIndicator', 1, 'Task', 'Roadmap', '1.0.0', 'Time allocation by status'),
('TimetrackDetailsIndicator', 1, 'Admin', 'Admin', '1.0.0', 'Display additional info on timetracks'),
('UserTeamList', 1, 'Admin', 'Admin', '1.0.0', 'Display a history of all the teams for a given user');

-- --------------------------------------------------------

--
-- Table structure for table `codev_timetrack_note_table`
--

CREATE TABLE IF NOT EXISTS `codev_timetrack_note_table` (
  `timetrackid` int(11) NOT NULL,
  `noteid` int(11) NOT NULL,
  PRIMARY KEY (`timetrackid`, `noteid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;






-- /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
-- /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
-- /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
