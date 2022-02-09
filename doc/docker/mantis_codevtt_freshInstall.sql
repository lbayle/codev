-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le :  mer. 09 fév. 2022 à 15:11
-- Version du serveur :  10.4.11-MariaDB
-- Version de PHP :  7.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `bugtracker`
--

DELIMITER $$
--
-- Fonctions
--
DROP FUNCTION IF EXISTS `get_issue_resolved_status_threshold`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `get_issue_resolved_status_threshold` (`bug_id` INT) RETURNS INT(11) BEGIN
   DECLARE proj_id INT DEFAULT NULL;
   
   SELECT project_id INTO proj_id FROM mantis_bug_table
             WHERE id = bug_id
             LIMIT 1;
   
   RETURN get_project_resolved_status_threshold(proj_id);
END$$

DROP FUNCTION IF EXISTS `get_project_resolved_status_threshold`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `get_project_resolved_status_threshold` (`proj_id` INT) RETURNS INT(11) BEGIN
   DECLARE status INT DEFAULT NULL;
   
   SELECT value INTO status FROM mantis_config_table 
          WHERE config_id = 'bug_resolved_status_threshold' 
          AND project_id = proj_id
          LIMIT 1;
   
   IF status <=> NULL THEN
      SELECT value INTO status FROM codev_config_table
             WHERE config_id = 'bug_resolved_status_threshold' 
             AND project_id = 0
             LIMIT 1;
   END IF;
   
   RETURN status;
END$$

DROP FUNCTION IF EXISTS `is_issue_in_team_commands`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `is_issue_in_team_commands` (`bugid` INT, `teamid` INT) RETURNS INT(11) BEGIN
   DECLARE is_found INT DEFAULT NULL;

   SELECT COUNT(codev_command_bug_table.bug_id) INTO is_found FROM codev_command_bug_table, codev_command_table
          WHERE codev_command_table.id = codev_command_bug_table.command_id
          AND   codev_command_table.team_id = teamid
          AND   codev_command_bug_table.bug_id = bugid
          LIMIT 1;

   RETURN is_found;
END$$

DROP FUNCTION IF EXISTS `is_project_in_team`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `is_project_in_team` (`projid` INT, `teamid` INT) RETURNS INT(11) BEGIN
   DECLARE is_found INT DEFAULT NULL;



   SELECT COUNT(team_id) INTO is_found FROM codev_team_project_table
          WHERE team_id = teamid
          AND   project_id = projid
          LIMIT 1;

   RETURN is_found;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_blog_activity_table`
--

DROP TABLE IF EXISTS `codev_blog_activity_table`;
CREATE TABLE IF NOT EXISTS `codev_blog_activity_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key1` (`blog_id`,`user_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Wall activity';

-- --------------------------------------------------------

--
-- Structure de la table `codev_blog_table`
--

DROP TABLE IF EXISTS `codev_blog_table`;
CREATE TABLE IF NOT EXISTS `codev_blog_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_submitted` int(11) NOT NULL,
  `src_user_id` int(10) UNSIGNED NOT NULL,
  `dest_user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `dest_project_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `dest_team_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `severity` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `summary` varchar(100) NOT NULL,
  `content` varchar(2000) DEFAULT NULL,
  `date_expire` int(11) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date_submitted`),
  KEY `key1` (`dest_team_id`,`dest_user_id`,`date_submitted`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Wall posts';

--
-- Déchargement des données de la table `codev_blog_table`
--

INSERT INTO `codev_blog_table` (`id`, `date_submitted`, `src_user_id`, `dest_user_id`, `dest_project_id`, `dest_team_id`, `severity`, `category`, `summary`, `content`, `date_expire`, `color`) VALUES
(1, 1526228606, 0, 0, 0, 0, 2, '3', 'Exchange messages with your team !', 'Hi,\nthis plugins allows to share notifications within the teams.\n\nExemple:\n\"The Mantis/CodevTT server will be unavailable for maintenance on May 13th\"\n\nClick <img src=\"images/b_add.png\"/> button on your right to add a message\nClick <img src=\"images/b_markAsRead.png\"/> to inform that you have read the message\nClick <img src=\"images/b_ghost.png\"/> to hide the message', 0, '0');

-- --------------------------------------------------------

--
-- Structure de la table `codev_commandset_cmd_table`
--

DROP TABLE IF EXISTS `codev_commandset_cmd_table`;
CREATE TABLE IF NOT EXISTS `codev_commandset_cmd_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `commandset_id` int(11) NOT NULL,
  `command_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_commandset_table`
--

DROP TABLE IF EXISTS `codev_commandset_table`;
CREATE TABLE IF NOT EXISTS `codev_commandset_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `date` int(10) UNSIGNED DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `state` int(10) UNSIGNED DEFAULT NULL,
  `budget` int(11) DEFAULT NULL,
  `budget_days` int(11) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_command_bug_table`
--

DROP TABLE IF EXISTS `codev_command_bug_table`;
CREATE TABLE IF NOT EXISTS `codev_command_bug_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `command_id` int(11) NOT NULL,
  `bug_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `command_id` (`command_id`),
  KEY `bug_id` (`bug_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_command_provision_table`
--

DROP TABLE IF EXISTS `codev_command_provision_table`;
CREATE TABLE IF NOT EXISTS `codev_command_provision_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` int(10) UNSIGNED NOT NULL,
  `command_id` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `summary` varchar(128) NOT NULL,
  `budget_days` int(11) DEFAULT NULL,
  `budget` int(11) DEFAULT NULL,
  `average_daily_rate` int(11) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `is_in_check_budget` tinyint(4) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `command_id` (`command_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_command_table`
--

DROP TABLE IF EXISTS `codev_command_table`;
CREATE TABLE IF NOT EXISTS `codev_command_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `version` varchar(64) DEFAULT NULL,
  `reporter` varchar(64) DEFAULT NULL,
  `start_date` int(10) UNSIGNED DEFAULT NULL,
  `deadline` int(11) DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `wbs_id` int(10) UNSIGNED NOT NULL,
  `state` int(10) UNSIGNED DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `total_days` int(11) DEFAULT NULL,
  `average_daily_rate` int(11) DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT 1,
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_config_table`
--

DROP TABLE IF EXISTS `codev_config_table`;
CREATE TABLE IF NOT EXISTS `codev_config_table` (
  `config_id` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `type` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `team_id` int(11) NOT NULL DEFAULT 0,
  `servicecontract_id` int(11) NOT NULL DEFAULT 0,
  `commandset_id` int(11) NOT NULL DEFAULT 0,
  `command_id` int(11) NOT NULL DEFAULT 0,
  `access_reqd` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`config_id`,`team_id`,`project_id`,`user_id`,`servicecontract_id`,`commandset_id`,`command_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `codev_config_table`
--

INSERT INTO `codev_config_table` (`config_id`, `value`, `type`, `user_id`, `project_id`, `team_id`, `servicecontract_id`, `commandset_id`, `command_id`, `access_reqd`, `description`) VALUES
('adminTeamId', '1', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('blogCategories', '1:General,2:Timetracking,3:Admin', 3, 0, 0, 0, 0, 0, 0, NULL, NULL),
('bug_resolved_status_threshold', '80', 1, 0, 0, 0, 0, 0, 0, NULL, 'bug resolved threshold as defined in Mantis (g_bug_resolved_status_threshold)'),
('customField_addEffort', '6', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('customField_backlog', '7', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('customField_dailyPrice', '9', 1, 0, 0, 0, 0, 0, 0, 0, '0'),
('customField_deadLine', '5', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('customField_deliveryDate', '8', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('customField_effortEstim', '1', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('customField_ExtId', '4', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('customField_MgrEffortEstim', '3', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('customField_type', '2', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('database_version', '22', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('defaultProjectId', '0', 1, 1, 0, 0, 0, 0, 0, NULL, 'prefered project on login'),
('defaultTeamId', '1', 1, 1, 0, 0, 0, 0, 0, NULL, 'prefered team on login'),
('externalTasksCat_leave', '2', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('externalTasksCat_otherInternal', '3', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('externalTasksProject', '2', 1, 0, 0, 0, 0, 0, 0, NULL, 'CodevTT ExternalTasks Project'),
('issue_tooltip_fields', 'a:7:{i:0;s:10:\"project_id\";i:1;s:11:\"category_id\";i:2;s:8:\"custom_2\";i:3;s:6:\"status\";i:4;s:15:\"codevtt_elapsed\";i:5;s:8:\"custom_7\";i:6;s:13:\"codevtt_drift\";}', 2, 0, 0, 0, 0, 0, 0, NULL, 'fields to be displayed in issue tooltip');

-- --------------------------------------------------------

--
-- Structure de la table `codev_currencies_table`
--

DROP TABLE IF EXISTS `codev_currencies_table`;
CREATE TABLE IF NOT EXISTS `codev_currencies_table` (
  `currency` varchar(3) NOT NULL,
  `coef` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `codev_currencies_table`
--

INSERT INTO `codev_currencies_table` (`currency`, `coef`) VALUES
('CNY', 134703),
('EUR', 1000000),
('GBP', 1153988),
('INR', 14125),
('USD', 930709);

-- --------------------------------------------------------

--
-- Structure de la table `codev_holidays_table`
--

DROP TABLE IF EXISTS `codev_holidays_table`;
CREATE TABLE IF NOT EXISTS `codev_holidays_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` int(11) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT 'D8D8D8',
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 COMMENT='Fixed Holidays (national, religious, etc.)';

--
-- Déchargement des données de la table `codev_holidays_table`
--

INSERT INTO `codev_holidays_table` (`id`, `date`, `description`, `color`) VALUES
(36, 1492380000, 'lundi de paques', '58CC77'),
(37, 1493589600, 'fete du travail', '58CC77'),
(38, 1494194400, 'victoire 1945', '58CC77'),
(39, 1495663200, 'ascension', '58CC77'),
(40, 1496613600, 'pentecote', '58CC77'),
(41, 1499983200, 'fete nationale', '58CC77'),
(42, 1502748000, 'assomption', '58CC77'),
(43, 1509490800, 'toussaint', '58CC77'),
(44, 1514156400, 'noel', '58CC77'),
(45, 1514761200, 'reveillon', '58CC77');

-- --------------------------------------------------------

--
-- Structure de la table `codev_job_table`
--

DROP TABLE IF EXISTS `codev_job_table`;
CREATE TABLE IF NOT EXISTS `codev_job_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `type` int(11) NOT NULL DEFAULT 0,
  `color` varchar(7) DEFAULT '000000',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `codev_job_table`
--

INSERT INTO `codev_job_table` (`id`, `name`, `type`, `color`) VALUES
(1, 'N/A', 1, 'A8FFBD'),
(2, 'Support', 0, 'A8FFBD'),
(3, 'Analyse', 0, 'FFCD85'),
(4, 'Development', 0, 'C2DFFF'),
(5, 'Tests', 0, '92C5FC'),
(6, 'Documentation', 0, 'E0F57A');

-- --------------------------------------------------------

--
-- Structure de la table `codev_plugin_table`
--

DROP TABLE IF EXISTS `codev_plugin_table`;
CREATE TABLE IF NOT EXISTS `codev_plugin_table` (
  `name` varchar(64) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `domains` varchar(250) NOT NULL,
  `categories` varchar(250) NOT NULL,
  `version` varchar(10) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `codev_plugin_table`
--

INSERT INTO `codev_plugin_table` (`name`, `status`, `domains`, `categories`, `version`, `description`) VALUES
('AdminTools', 1, 'Admin', 'Admin', '1.0.0', 'CodevTT administration tools'),
('AvailableWorkforceIndicator', 1, 'Team', 'Planning', '1.0.0', 'Man-days available in period, except leaves and external tasks'),
('BacklogPerUserIndicator', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the tasks and return the backlog per User'),
('BlogPlugin', 1, 'Homepage', 'Internal', '1.0.0', 'Display a message wall on the homepage<br>Allows Administrators & team members to send messages that will be displayed on other users\' wall'),
('BudgetDriftHistoryIndicator2', 1, 'Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the budget history'),
('BurnDownChart', 1, 'Project,Task,Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the backlog history'),
('CostsIndicator', 1, 'Task,Command,CommandSet,ServiceContract', 'Financial', '1.0.0', 'Compute costs, using the UserDailyCosts defined in team settings'),
('DeadlineAlertIndicator', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display unresolved tasks that should have been delivered'),
('DriftAlertIndicator', 1, 'Homepage,Team,Project,Command,CommandSet,ServiceContract', 'Risk', '1.0.0', 'Display tasks where the elapsed time is greater than the estimated effort'),
('EffortEstimReliabilityIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Display the EffortEstim reliability rate history<br>rate = EffortEstim / elapsed (on resolved tasks only)'),
('FillPeriodWithTimetracks', 1, 'TeamAdmin', 'Activity', '1.0.0', 'Add multiple timetracks at once'),
('ImportIssueCsv', 1, 'Import_Export', 'Import', '1.0.0', 'Import a list of issues to MantisBT / CodevTT from a CSV file'),
('ImportRelationshipTreeToCommand', 1, 'Import_Export,Command', 'Import', '1.0.0', 'Import a mantis parent-child relationship issue structure to a command WBS structure'),
('ImportUsers', 1, 'Import_Export,TeamAdmin', 'Import', '1.0.0', 'Import a list of users to MantisBT / CodevTT'),
('IssueBacklogVariationIndicator', 1, 'Task', 'Roadmap', '1.0.0', 'Display task backlog updates since the task creation'),
('IssueConsistencyCheck', 1, 'Homepage', 'Risk', '1.0.0', 'Check for errors in issues'),
('IssueSeniorityIndicator', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Statistics on the age of open tasks'),
('LoadHistoryIndicator', 1, 'Command,Team,Project,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the elapsed time in a period'),
('LoadPerCustomfieldValues', 1, 'Team,Project,User,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Choose a customfield, return the elapsed time for each customField value'),
('LoadPerJobIndicator2', 1, 'Task,Team,User,Project,Command,CommandSet,ServiceContract', 'Quality', '1.1.0', 'Check all the timetracks of the period and return their repartition per Job'),
('LoadPerProjCategoryIndicator', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the timetracks of the period and return their repartition per project categories'),
('LoadPerProjectIndicator', 1, 'User,Team,Command,CommandSet,ServiceContract', 'Activity', '1.1.0', 'Check all the timetracks of the period and return their repartition per Project'),
('LoadPerUserGroups', 1, 'Project,Team,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the timetracks of the period and return their repartition per User groups'),
('LoadPerUserIndicator', 1, 'Task,Team,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the timetracks of the period and return their repartition per User'),
('ManagementCosts', 1, 'ServiceContract', 'Financial', '1.0.0', 'Sum elapsed time on management sideTasks and compare to the sum of command provisions. Returns a result in man-days and costs'),
('ManagementLoadHistoryIndicator', 1, 'ServiceContract', 'Activity', '1.0.0', 'Compares the elapsed time on management sideTasks to the management provisions'),
('MoveIssueTimetracks', 1, 'Admin,TeamAdmin', 'Admin', '1.0.0', 'Move timetracks from one issue to another'),
('OngoingTasks', 1, 'Project,Team,User,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'List the active tasks on a given period'),
('ProgressHistoryIndicator2', 1, 'Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the progress history'),
('ReopenedRateIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Display the bug reopened rate history'),
('ResetDashboard', 1, 'Admin', 'Admin', '1.0.0', 'Remove all plugins from a dashboard. This is usefull if a plugin crashes the page'),
('SellingPriceForPeriod', 1, 'Task,Project,Team,User,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'If you sell daily services with a specific price for each task, this plugin will give you the price of your batch of tasks over a given period of time. For this plugin, you need to add the \"CodevTT_DailyPrice\" customField to your mantis projects and '),
('StatusHistoryIndicator2', 1, 'Command,Team,Project,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Display Issue Status history'),
('SubmittedResolvedHistoryIndicator', 1, 'Command,Team,Project,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Display the number of issues submitted/resolved in a period'),
('TasksPivotTable', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Group tasks by adding multiple filters'),
('TimePerStatusIndicator', 1, 'Task', 'Roadmap', '1.0.0', 'Time allocation by status'),
('TimetrackDetailsIndicator', 1, 'Admin', 'Admin', '1.0.0', 'Display additional info on timetracks'),
('TimetrackingAnalysis', 1, 'Team,Project', 'Risk', '1.0.0', 'Display the delay between the timetrack date and it\'s creation date'),
('TimetrackList', 1, 'Task,Project,Team,User,Command,CommandSet,ServiceContract', 'Activity', '1.1.0', 'List and edit timetracks'),
('UserTeamList', 1, 'Admin', 'Admin', '1.0.0', 'Display a history of all the teams for a given user'),
('WBSExport', 1, 'Command', 'Roadmap', '1.0.0', 'Export WBS to CSV file');

-- --------------------------------------------------------

--
-- Structure de la table `codev_project_category_table`
--

DROP TABLE IF EXISTS `codev_project_category_table`;
CREATE TABLE IF NOT EXISTS `codev_project_category_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`,`project_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_project_job_table`
--

DROP TABLE IF EXISTS `codev_project_job_table`;
CREATE TABLE IF NOT EXISTS `codev_project_job_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_job_team` (`project_id`,`job_id`,`team_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `codev_project_job_table`
--

INSERT INTO `codev_project_job_table` (`id`, `project_id`, `job_id`, `team_id`) VALUES
(2, 2, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_cmdset_table`
--

DROP TABLE IF EXISTS `codev_servicecontract_cmdset_table`;
CREATE TABLE IF NOT EXISTS `codev_servicecontract_cmdset_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `servicecontract_id` int(11) NOT NULL,
  `commandset_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_stproj_table`
--

DROP TABLE IF EXISTS `codev_servicecontract_stproj_table`;
CREATE TABLE IF NOT EXISTS `codev_servicecontract_stproj_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `servicecontract_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_table`
--

DROP TABLE IF EXISTS `codev_servicecontract_table`;
CREATE TABLE IF NOT EXISTS `codev_servicecontract_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `state` int(10) UNSIGNED DEFAULT NULL,
  `version` varchar(64) DEFAULT NULL,
  `reporter` varchar(64) DEFAULT NULL,
  `start_date` int(10) UNSIGNED DEFAULT NULL,
  `end_date` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_sidetasks_category_table`
--

DROP TABLE IF EXISTS `codev_sidetasks_category_table`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_team_project_table`
--

DROP TABLE IF EXISTS `codev_team_project_table`;
CREATE TABLE IF NOT EXISTS `codev_team_project_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 2,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `team_id` (`team_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `codev_team_project_table`
--

INSERT INTO `codev_team_project_table` (`id`, `project_id`, `team_id`, `type`) VALUES
(1, 2, 1, 3);

-- --------------------------------------------------------

--
-- Structure de la table `codev_team_table`
--

DROP TABLE IF EXISTS `codev_team_table`;
CREATE TABLE IF NOT EXISTS `codev_team_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `administrators` varchar(255) DEFAULT NULL,
  `leader_id` int(11) DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT 1,
  `commands_enabled` tinyint(4) NOT NULL DEFAULT 1,
  `date` int(11) NOT NULL,
  `average_daily_cost` int(11) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `lock_timetracks_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `codev_team_table`
--

INSERT INTO `codev_team_table` (`id`, `name`, `description`, `administrators`, `leader_id`, `enabled`, `commands_enabled`, `date`, `average_daily_cost`, `currency`, `lock_timetracks_date`) VALUES
(1, 'CodevTT admin', 'CodevTT Administrators team', '1', 1, 0, 1, 1521932400, NULL, 'EUR', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `codev_team_user_table`
--

DROP TABLE IF EXISTS `codev_team_user_table`;
CREATE TABLE IF NOT EXISTS `codev_team_user_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `access_level` int(10) UNSIGNED NOT NULL DEFAULT 10,
  `arrival_date` int(10) UNSIGNED NOT NULL,
  `departure_date` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `codev_team_user_table`
--

INSERT INTO `codev_team_user_table` (`id`, `user_id`, `team_id`, `access_level`, `arrival_date`, `departure_date`) VALUES
(1, 1, 1, 10, 1521932400, 0);

-- --------------------------------------------------------

--
-- Structure de la table `codev_timetracking_table`
--

DROP TABLE IF EXISTS `codev_timetracking_table`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_timetrack_note_table`
--

DROP TABLE IF EXISTS `codev_timetrack_note_table`;
CREATE TABLE IF NOT EXISTS `codev_timetrack_note_table` (
  `timetrackid` int(11) NOT NULL,
  `noteid` int(11) NOT NULL,
  PRIMARY KEY (`timetrackid`,`noteid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_userdailycost_table`
--

DROP TABLE IF EXISTS `codev_userdailycost_table`;
CREATE TABLE IF NOT EXISTS `codev_userdailycost_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `start_date` int(10) UNSIGNED NOT NULL,
  `daily_rate` int(10) UNSIGNED NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `description` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_team_date` (`user_id`,`team_id`,`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_wbs_table`
--

DROP TABLE IF EXISTS `codev_wbs_table`;
CREATE TABLE IF NOT EXISTS `codev_wbs_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `root_id` int(10) UNSIGNED DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `wbs_order` int(11) NOT NULL,
  `bug_id` int(11) DEFAULT NULL,
  `expand` tinyint(4) NOT NULL DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `icon` varchar(64) DEFAULT NULL,
  `font` varchar(64) DEFAULT NULL,
  `color` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bug_id` (`bug_id`),
  KEY `parent_id` (`parent_id`),
  KEY `wbs_order` (`wbs_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_api_token_table`
--

DROP TABLE IF EXISTS `mantis_api_token_table`;
CREATE TABLE IF NOT EXISTS `mantis_api_token_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL,
  `hash` varchar(128) NOT NULL,
  `date_created` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `date_used` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_id_name` (`user_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bugnote_table`
--

DROP TABLE IF EXISTS `mantis_bugnote_table`;
CREATE TABLE IF NOT EXISTS `mantis_bugnote_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bug_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `reporter_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bugnote_text_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `view_state` smallint(6) NOT NULL DEFAULT 10,
  `note_type` int(11) DEFAULT 0,
  `note_attr` varchar(250) DEFAULT '',
  `time_tracking` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_modified` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `date_submitted` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_bug` (`bug_id`),
  KEY `idx_last_mod` (`last_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bugnote_text_table`
--

DROP TABLE IF EXISTS `mantis_bugnote_text_table`;
CREATE TABLE IF NOT EXISTS `mantis_bugnote_text_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `note` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_file_table`
--

DROP TABLE IF EXISTS `mantis_bug_file_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_file_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bug_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `title` varchar(250) NOT NULL DEFAULT '',
  `description` varchar(250) NOT NULL DEFAULT '',
  `diskfile` varchar(250) NOT NULL DEFAULT '',
  `filename` varchar(250) NOT NULL DEFAULT '',
  `folder` varchar(250) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL DEFAULT 0,
  `file_type` varchar(250) NOT NULL DEFAULT '',
  `content` longblob DEFAULT NULL,
  `date_added` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bugnote_id` int(10) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_bug_file_bug_id` (`bug_id`),
  KEY `idx_diskfile` (`diskfile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_history_table`
--

DROP TABLE IF EXISTS `mantis_bug_history_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_history_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bug_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `field_name` varchar(64) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `type` smallint(6) NOT NULL DEFAULT 0,
  `date_modified` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_bug_history_bug_id` (`bug_id`),
  KEY `idx_history_user_id` (`user_id`),
  KEY `idx_bug_history_date_modified` (`date_modified`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_bug_history_table`
--

INSERT INTO `mantis_bug_history_table` (`id`, `user_id`, `bug_id`, `field_name`, `old_value`, `new_value`, `type`, `date_modified`) VALUES
(1, 1, 1, '', '', '', 1, 1521316255);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_monitor_table`
--

DROP TABLE IF EXISTS `mantis_bug_monitor_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_monitor_table` (
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bug_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`bug_id`),
  KEY `idx_bug_id` (`bug_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_relationship_table`
--

DROP TABLE IF EXISTS `mantis_bug_relationship_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_relationship_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_bug_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `destination_bug_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `relationship_type` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_relationship_source` (`source_bug_id`),
  KEY `idx_relationship_destination` (`destination_bug_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_revision_table`
--

DROP TABLE IF EXISTS `mantis_bug_revision_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_revision_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bug_id` int(10) UNSIGNED NOT NULL,
  `bugnote_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` int(10) UNSIGNED NOT NULL,
  `value` longtext NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_bug_rev_type` (`type`),
  KEY `idx_bug_rev_id_time` (`bug_id`,`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_table`
--

DROP TABLE IF EXISTS `mantis_bug_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `reporter_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `handler_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `duplicate_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `priority` smallint(6) NOT NULL DEFAULT 30,
  `severity` smallint(6) NOT NULL DEFAULT 50,
  `reproducibility` smallint(6) NOT NULL DEFAULT 10,
  `status` smallint(6) NOT NULL DEFAULT 10,
  `resolution` smallint(6) NOT NULL DEFAULT 10,
  `projection` smallint(6) NOT NULL DEFAULT 10,
  `eta` smallint(6) NOT NULL DEFAULT 10,
  `bug_text_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `os` varchar(32) NOT NULL DEFAULT '',
  `os_build` varchar(32) NOT NULL DEFAULT '',
  `platform` varchar(32) NOT NULL DEFAULT '',
  `version` varchar(64) NOT NULL DEFAULT '',
  `fixed_in_version` varchar(64) NOT NULL DEFAULT '',
  `build` varchar(32) NOT NULL DEFAULT '',
  `profile_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `view_state` smallint(6) NOT NULL DEFAULT 10,
  `summary` varchar(128) NOT NULL DEFAULT '',
  `sponsorship_total` int(11) NOT NULL DEFAULT 0,
  `sticky` tinyint(4) NOT NULL DEFAULT 0,
  `target_version` varchar(64) NOT NULL DEFAULT '',
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `date_submitted` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `due_date` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `last_updated` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_bug_sponsorship_total` (`sponsorship_total`),
  KEY `idx_bug_fixed_in_version` (`fixed_in_version`),
  KEY `idx_bug_status` (`status`),
  KEY `idx_project` (`project_id`),
  KEY `handler_id` (`handler_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_bug_table`
--

INSERT INTO `mantis_bug_table` (`id`, `project_id`, `reporter_id`, `handler_id`, `duplicate_id`, `priority`, `severity`, `reproducibility`, `status`, `resolution`, `projection`, `eta`, `bug_text_id`, `os`, `os_build`, `platform`, `version`, `fixed_in_version`, `build`, `profile_id`, `view_state`, `summary`, `sponsorship_total`, `sticky`, `target_version`, `category_id`, `date_submitted`, `due_date`, `last_updated`) VALUES
(1, 1, 1, 0, 0, 30, 50, 70, 10, 10, 10, 10, 1, '', '', '', '', '', '', 0, 10, 'bug1', 0, 0, '', 1, 1521316255, 1, 1521316255),
(2, 2, 0, 0, 0, 10, 50, 100, 90, 10, 10, 10, 2, '', '', '', '', '', '', 0, 10, 'Other external activity', 0, 0, '', 3, 1521932400, 1, 1521932400),
(3, 2, 0, 0, 0, 10, 50, 100, 90, 10, 10, 10, 3, '', '', '', '', '', '', 0, 10, 'Leave', 0, 0, '', 2, 1521932400, 1, 1521932400),
(4, 2, 0, 0, 0, 10, 50, 100, 90, 10, 10, 10, 4, '', '', '', '', '', '', 0, 10, 'Sick Leave', 0, 0, '', 2, 1521932400, 1, 1521932400);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_tag_table`
--

DROP TABLE IF EXISTS `mantis_bug_tag_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_tag_table` (
  `bug_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tag_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date_attached` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`bug_id`,`tag_id`),
  KEY `idx_bug_tag_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_text_table`
--

DROP TABLE IF EXISTS `mantis_bug_text_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_text_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `description` longtext NOT NULL,
  `steps_to_reproduce` longtext NOT NULL,
  `additional_information` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_bug_text_table`
--

INSERT INTO `mantis_bug_text_table` (`id`, `description`, `steps_to_reproduce`, `additional_information`) VALUES
(1, 'aaa', '', ''),
(2, 'Any external task, NOT referenced in any Mantis project', '', ''),
(3, 'On holiday, leave, ...', '', ''),
(4, 'Sick', '', '');

-- --------------------------------------------------------

--
-- Structure de la table `mantis_category_table`
--

DROP TABLE IF EXISTS `mantis_category_table`;
CREATE TABLE IF NOT EXISTS `mantis_category_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL DEFAULT '',
  `status` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_category_project_name` (`project_id`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_category_table`
--

INSERT INTO `mantis_category_table` (`id`, `project_id`, `user_id`, `name`, `status`) VALUES
(1, 0, 0, 'General', 0),
(2, 2, 0, 'Leave', 0),
(3, 2, 0, 'Other activity', 0);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_config_table`
--

DROP TABLE IF EXISTS `mantis_config_table`;
CREATE TABLE IF NOT EXISTS `mantis_config_table` (
  `config_id` varchar(64) NOT NULL,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `access_reqd` int(11) DEFAULT 0,
  `type` int(11) DEFAULT 90,
  `value` longtext NOT NULL,
  PRIMARY KEY (`config_id`,`project_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_config_table`
--

INSERT INTO `mantis_config_table` (`config_id`, `project_id`, `user_id`, `access_reqd`, `type`, `value`) VALUES
('bug_assigned_status', 2, 0, 90, 1, '90'),
('bug_submit_status', 2, 0, 90, 1, '90'),
('database_version', 0, 0, 90, 1, '211'),
('plugin_Gravatar_schema', 0, 0, 90, 1, '-1'),
('plugin_MantisGraph_schema', 0, 0, 90, 1, '-1'),
('plugin_SourceGitlab_schema', 0, 0, 90, 1, '-1'),
('plugin_Source_schema', 0, 0, 90, 1, '15');

-- --------------------------------------------------------

--
-- Structure de la table `mantis_custom_field_project_table`
--

DROP TABLE IF EXISTS `mantis_custom_field_project_table`;
CREATE TABLE IF NOT EXISTS `mantis_custom_field_project_table` (
  `field_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sequence` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`field_id`,`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_custom_field_string_table`
--

DROP TABLE IF EXISTS `mantis_custom_field_string_table`;
CREATE TABLE IF NOT EXISTS `mantis_custom_field_string_table` (
  `field_id` int(11) NOT NULL DEFAULT 0,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `value` varchar(255) NOT NULL DEFAULT '',
  `text` longtext DEFAULT NULL,
  PRIMARY KEY (`field_id`,`bug_id`),
  KEY `idx_custom_field_bug` (`bug_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_custom_field_table`
--

DROP TABLE IF EXISTS `mantis_custom_field_table`;
CREATE TABLE IF NOT EXISTS `mantis_custom_field_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `type` smallint(6) NOT NULL DEFAULT 0,
  `possible_values` text DEFAULT NULL,
  `default_value` varchar(255) NOT NULL DEFAULT '',
  `valid_regexp` varchar(255) NOT NULL DEFAULT '',
  `access_level_r` smallint(6) NOT NULL DEFAULT 0,
  `access_level_rw` smallint(6) NOT NULL DEFAULT 0,
  `length_min` int(11) NOT NULL DEFAULT 0,
  `length_max` int(11) NOT NULL DEFAULT 0,
  `require_report` tinyint(4) NOT NULL DEFAULT 0,
  `require_update` tinyint(4) NOT NULL DEFAULT 0,
  `display_report` tinyint(4) NOT NULL DEFAULT 0,
  `display_update` tinyint(4) NOT NULL DEFAULT 1,
  `require_resolved` tinyint(4) NOT NULL DEFAULT 0,
  `display_resolved` tinyint(4) NOT NULL DEFAULT 0,
  `display_closed` tinyint(4) NOT NULL DEFAULT 0,
  `require_closed` tinyint(4) NOT NULL DEFAULT 0,
  `filter_by` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_custom_field_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_custom_field_table`
--

INSERT INTO `mantis_custom_field_table` (`id`, `name`, `type`, `possible_values`, `default_value`, `valid_regexp`, `access_level_r`, `access_level_rw`, `length_min`, `length_max`, `require_report`, `require_update`, `display_report`, `display_update`, `require_resolved`, `display_resolved`, `display_closed`, `require_closed`, `filter_by`) VALUES
(1, 'CodevTT_EffortEstim', 2, '', '1', '', 10, 25, 0, 0, 1, 0, 1, 1, 0, 0, 0, 0, 1),
(2, 'CodevTT_Type', 6, 'Bug|Task', '', '', 10, 25, 0, 0, 1, 0, 1, 1, 0, 0, 0, 0, 1),
(3, 'CodevTT_Manager EffortEstim', 2, '', '0', '', 70, 70, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1),
(4, 'CodevTT_External ID', 0, '', '', '', 10, 25, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1),
(5, 'CodevTT_Deadline', 8, '', '', '', 10, 25, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1),
(6, 'CodevTT_Additional Effort', 2, '', '', '', 10, 25, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(7, 'CodevTT_Backlog', 2, '', '', '', 10, 25, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1),
(8, 'CodevTT_Delivery Date', 8, '', '', '', 10, 25, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1),
(9, 'CodevTT_DailyPrice', 2, NULL, '0', '', 70, 70, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_email_table`
--

DROP TABLE IF EXISTS `mantis_email_table`;
CREATE TABLE IF NOT EXISTS `mantis_email_table` (
  `email_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL DEFAULT '',
  `subject` varchar(250) NOT NULL DEFAULT '',
  `metadata` longtext NOT NULL,
  `body` longtext NOT NULL,
  `submitted` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`email_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_filters_table`
--

DROP TABLE IF EXISTS `mantis_filters_table`;
CREATE TABLE IF NOT EXISTS `mantis_filters_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `is_public` tinyint(4) DEFAULT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `filter_string` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_news_table`
--

DROP TABLE IF EXISTS `mantis_news_table`;
CREATE TABLE IF NOT EXISTS `mantis_news_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `poster_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `view_state` smallint(6) NOT NULL DEFAULT 10,
  `announcement` tinyint(4) NOT NULL DEFAULT 0,
  `headline` varchar(64) NOT NULL DEFAULT '',
  `body` longtext NOT NULL,
  `last_modified` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `date_posted` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_plugin_table`
--

DROP TABLE IF EXISTS `mantis_plugin_table`;
CREATE TABLE IF NOT EXISTS `mantis_plugin_table` (
  `basename` varchar(40) NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT 0,
  `protected` tinyint(4) NOT NULL DEFAULT 0,
  `priority` int(10) UNSIGNED NOT NULL DEFAULT 3,
  PRIMARY KEY (`basename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_plugin_table`
--

INSERT INTO `mantis_plugin_table` (`basename`, `enabled`, `protected`, `priority`) VALUES
('CodevTT', 1, 0, 3),
('FilterBugList', 1, 0, 3),
('Gravatar', 1, 0, 3),
('MantisCoreFormatting', 1, 0, 3),
('MantisGraph', 1, 0, 3),
('Source', 1, 0, 3),
('SourceGitlab', 1, 0, 3);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_file_table`
--

DROP TABLE IF EXISTS `mantis_project_file_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_file_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `title` varchar(250) NOT NULL DEFAULT '',
  `description` varchar(250) NOT NULL DEFAULT '',
  `diskfile` varchar(250) NOT NULL DEFAULT '',
  `filename` varchar(250) NOT NULL DEFAULT '',
  `folder` varchar(250) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL DEFAULT 0,
  `file_type` varchar(250) NOT NULL DEFAULT '',
  `content` longblob DEFAULT NULL,
  `date_added` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_hierarchy_table`
--

DROP TABLE IF EXISTS `mantis_project_hierarchy_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_hierarchy_table` (
  `child_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED NOT NULL,
  `inherit_parent` tinyint(4) NOT NULL DEFAULT 0,
  UNIQUE KEY `idx_project_hierarchy` (`child_id`,`parent_id`),
  KEY `idx_project_hierarchy_child_id` (`child_id`),
  KEY `idx_project_hierarchy_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_table`
--

DROP TABLE IF EXISTS `mantis_project_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `status` smallint(6) NOT NULL DEFAULT 10,
  `enabled` tinyint(4) NOT NULL DEFAULT 1,
  `view_state` smallint(6) NOT NULL DEFAULT 10,
  `access_min` smallint(6) NOT NULL DEFAULT 10,
  `file_path` varchar(250) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `inherit_global` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_project_name` (`name`),
  KEY `idx_project_view` (`view_state`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_project_table`
--

INSERT INTO `mantis_project_table` (`id`, `name`, `status`, `enabled`, `view_state`, `access_min`, `file_path`, `description`, `category_id`, `inherit_global`) VALUES
(1, 'prj1', 10, 1, 10, 10, '', '', 1, 1),
(2, 'CodevTT_ExternalTasks', 50, 1, 50, 10, '', 'CodevTT ExternalTasks Project', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_user_list_table`
--

DROP TABLE IF EXISTS `mantis_project_user_list_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_user_list_table` (
  `project_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `access_level` smallint(6) NOT NULL DEFAULT 10,
  PRIMARY KEY (`project_id`,`user_id`),
  KEY `idx_project_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_version_table`
--

DROP TABLE IF EXISTS `mantis_project_version_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_version_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `version` varchar(64) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `released` tinyint(4) NOT NULL DEFAULT 1,
  `obsolete` tinyint(4) NOT NULL DEFAULT 0,
  `date_order` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_project_version` (`project_id`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_sponsorship_table`
--

DROP TABLE IF EXISTS `mantis_sponsorship_table`;
CREATE TABLE IF NOT EXISTS `mantis_sponsorship_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `amount` int(11) NOT NULL DEFAULT 0,
  `logo` varchar(128) NOT NULL DEFAULT '',
  `url` varchar(128) NOT NULL DEFAULT '',
  `paid` tinyint(4) NOT NULL DEFAULT 0,
  `date_submitted` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `last_updated` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_sponsorship_bug_id` (`bug_id`),
  KEY `idx_sponsorship_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_tag_table`
--

DROP TABLE IF EXISTS `mantis_tag_table`;
CREATE TABLE IF NOT EXISTS `mantis_tag_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `date_created` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `date_updated` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`,`name`),
  KEY `idx_tag_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_tokens_table`
--

DROP TABLE IF EXISTS `mantis_tokens_table`;
CREATE TABLE IF NOT EXISTS `mantis_tokens_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `value` longtext NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `expiry` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_typeowner` (`type`,`owner`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_user_pref_table`
--

DROP TABLE IF EXISTS `mantis_user_pref_table`;
CREATE TABLE IF NOT EXISTS `mantis_user_pref_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `project_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `default_profile` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `default_project` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `refresh_delay` int(11) NOT NULL DEFAULT 0,
  `redirect_delay` int(11) NOT NULL DEFAULT 0,
  `bugnote_order` varchar(4) NOT NULL DEFAULT 'ASC',
  `email_on_new` tinyint(4) NOT NULL DEFAULT 0,
  `email_on_assigned` tinyint(4) NOT NULL DEFAULT 0,
  `email_on_feedback` tinyint(4) NOT NULL DEFAULT 0,
  `email_on_resolved` tinyint(4) NOT NULL DEFAULT 0,
  `email_on_closed` tinyint(4) NOT NULL DEFAULT 0,
  `email_on_reopened` tinyint(4) NOT NULL DEFAULT 0,
  `email_on_bugnote` tinyint(4) NOT NULL DEFAULT 0,
  `email_on_status` tinyint(4) NOT NULL DEFAULT 0,
  `email_on_priority` tinyint(4) NOT NULL DEFAULT 0,
  `email_on_priority_min_severity` smallint(6) NOT NULL DEFAULT 10,
  `email_on_status_min_severity` smallint(6) NOT NULL DEFAULT 10,
  `email_on_bugnote_min_severity` smallint(6) NOT NULL DEFAULT 10,
  `email_on_reopened_min_severity` smallint(6) NOT NULL DEFAULT 10,
  `email_on_closed_min_severity` smallint(6) NOT NULL DEFAULT 10,
  `email_on_resolved_min_severity` smallint(6) NOT NULL DEFAULT 10,
  `email_on_feedback_min_severity` smallint(6) NOT NULL DEFAULT 10,
  `email_on_assigned_min_severity` smallint(6) NOT NULL DEFAULT 10,
  `email_on_new_min_severity` smallint(6) NOT NULL DEFAULT 10,
  `email_bugnote_limit` smallint(6) NOT NULL DEFAULT 0,
  `language` varchar(32) NOT NULL DEFAULT 'english',
  `timezone` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_user_pref_table`
--

INSERT INTO `mantis_user_pref_table` (`id`, `user_id`, `project_id`, `default_profile`, `default_project`, `refresh_delay`, `redirect_delay`, `bugnote_order`, `email_on_new`, `email_on_assigned`, `email_on_feedback`, `email_on_resolved`, `email_on_closed`, `email_on_reopened`, `email_on_bugnote`, `email_on_status`, `email_on_priority`, `email_on_priority_min_severity`, `email_on_status_min_severity`, `email_on_bugnote_min_severity`, `email_on_reopened_min_severity`, `email_on_closed_min_severity`, `email_on_resolved_min_severity`, `email_on_feedback_min_severity`, `email_on_assigned_min_severity`, `email_on_new_min_severity`, `email_bugnote_limit`, `language`, `timezone`) VALUES
(1, 1, 0, 0, 1, 30, 2, 'ASC', 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'auto', 'Europe/Paris');

-- --------------------------------------------------------

--
-- Structure de la table `mantis_user_print_pref_table`
--

DROP TABLE IF EXISTS `mantis_user_print_pref_table`;
CREATE TABLE IF NOT EXISTS `mantis_user_print_pref_table` (
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `print_pref` varchar(64) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_user_profile_table`
--

DROP TABLE IF EXISTS `mantis_user_profile_table`;
CREATE TABLE IF NOT EXISTS `mantis_user_profile_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `platform` varchar(32) NOT NULL DEFAULT '',
  `os` varchar(32) NOT NULL DEFAULT '',
  `os_build` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_user_table`
--

DROP TABLE IF EXISTS `mantis_user_table`;
CREATE TABLE IF NOT EXISTS `mantis_user_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(191) NOT NULL DEFAULT '',
  `realname` varchar(191) NOT NULL DEFAULT '',
  `email` varchar(191) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `enabled` tinyint(4) NOT NULL DEFAULT 1,
  `protected` tinyint(4) NOT NULL DEFAULT 0,
  `access_level` smallint(6) NOT NULL DEFAULT 10,
  `login_count` int(11) NOT NULL DEFAULT 0,
  `lost_password_request_count` smallint(6) NOT NULL DEFAULT 0,
  `failed_login_count` smallint(6) NOT NULL DEFAULT 0,
  `cookie_string` varchar(64) NOT NULL DEFAULT '',
  `last_visit` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `date_created` int(10) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_cookie_string` (`cookie_string`),
  UNIQUE KEY `idx_user_username` (`username`),
  KEY `idx_enable` (`enabled`),
  KEY `idx_access` (`access_level`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `mantis_user_table`
--

INSERT INTO `mantis_user_table` (`id`, `username`, `realname`, `email`, `password`, `enabled`, `protected`, `access_level`, `login_count`, `lost_password_request_count`, `failed_login_count`, `cookie_string`, `last_visit`, `date_created`) VALUES
(1, 'administrator', '', 'root@localhost', '63a9f0ea7bb98050796b649e85481845', 1, 0, 90, 7, 0, 0, '732aac42ba8df328cc8e19febef5910e2b4289473bc523f1d973862efb7380ad', 1636636190, 1521316138);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
