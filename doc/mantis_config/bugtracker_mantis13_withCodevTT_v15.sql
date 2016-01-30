-- phpMyAdmin SQL Dump
-- version 4.2.7.1
-- http://www.phpmyadmin.net
--
-- Client :  127.0.0.1
-- Généré le :  Sam 30 Janvier 2016 à 20:43
-- Version du serveur :  5.6.20
-- Version de PHP :  5.5.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `mantis13`
--

DELIMITER $$
--
-- Fonctions
--
DROP FUNCTION IF EXISTS `get_issue_resolved_status_threshold`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `get_issue_resolved_status_threshold`(bug_id INT) RETURNS int(11)
    DETERMINISTIC
BEGIN
   DECLARE proj_id INT DEFAULT NULL;

   SELECT project_id INTO proj_id FROM `mantis_bug_table`
             WHERE id = bug_id
             LIMIT 1;

   RETURN get_project_resolved_status_threshold(proj_id);
END$$

DROP FUNCTION IF EXISTS `get_project_resolved_status_threshold`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `get_project_resolved_status_threshold`(proj_id INT) RETURNS int(11)
    DETERMINISTIC
BEGIN
   DECLARE status INT DEFAULT NULL;

   SELECT value INTO status FROM `mantis_config_table`
          WHERE config_id = 'bug_resolved_status_threshold'
          AND project_id = proj_id
          LIMIT 1;

   IF status <=> NULL THEN
      SELECT value INTO status FROM `codev_config_table`
             WHERE config_id = 'bug_resolved_status_threshold'
             AND project_id = 0
             LIMIT 1;
   END IF;

   RETURN status;
END$$

DROP FUNCTION IF EXISTS `is_issue_in_team_commands`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `is_issue_in_team_commands`(bugid INT, teamid INT) RETURNS int(11)
    DETERMINISTIC
BEGIN
   DECLARE is_found INT DEFAULT NULL;

   SELECT COUNT(codev_command_bug_table.bug_id) INTO is_found FROM `codev_command_bug_table`, `codev_command_table`
          WHERE codev_command_table.id = codev_command_bug_table.command_id
          AND   codev_command_table.team_id = teamid
          AND   codev_command_bug_table.bug_id = bugid
          LIMIT 1;

   RETURN is_found;
END$$

DROP FUNCTION IF EXISTS `is_project_in_team`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `is_project_in_team`(projid INT, teamid INT) RETURNS int(11)
    DETERMINISTIC
BEGIN
   DECLARE is_found INT DEFAULT NULL;



   SELECT COUNT(team_id) INTO is_found FROM `codev_team_project_table`
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
`id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `date` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Wall activity' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_blog_table`
--

DROP TABLE IF EXISTS `codev_blog_table`;
CREATE TABLE IF NOT EXISTS `codev_blog_table` (
`id` int(11) NOT NULL,
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
  `color` varchar(7) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Wall posts' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_commandset_cmd_table`
--

DROP TABLE IF EXISTS `codev_commandset_cmd_table`;
CREATE TABLE IF NOT EXISTS `codev_commandset_cmd_table` (
`id` int(11) unsigned NOT NULL,
  `commandset_id` int(11) NOT NULL,
  `command_id` int(11) NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_commandset_table`
--

DROP TABLE IF EXISTS `codev_commandset_table`;
CREATE TABLE IF NOT EXISTS `codev_commandset_table` (
`id` int(11) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `date` int(11) unsigned DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `state` int(11) unsigned DEFAULT NULL,
  `budget` int(11) DEFAULT NULL,
  `budget_days` int(11) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `description` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_command_bug_table`
--

DROP TABLE IF EXISTS `codev_command_bug_table`;
CREATE TABLE IF NOT EXISTS `codev_command_bug_table` (
`id` int(11) unsigned NOT NULL,
  `command_id` int(11) NOT NULL,
  `bug_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_command_provision_table`
--

DROP TABLE IF EXISTS `codev_command_provision_table`;
CREATE TABLE IF NOT EXISTS `codev_command_provision_table` (
`id` int(11) unsigned NOT NULL,
  `date` int(11) unsigned NOT NULL,
  `command_id` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `summary` varchar(128) NOT NULL,
  `budget_days` int(11) DEFAULT NULL,
  `budget` int(11) DEFAULT NULL,
  `average_daily_rate` int(11) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `is_in_check_budget` tinyint(4) NOT NULL DEFAULT '0',
  `description` longtext
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_command_table`
--

DROP TABLE IF EXISTS `codev_command_table`;
CREATE TABLE IF NOT EXISTS `codev_command_table` (
`id` int(11) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `version` varchar(64) DEFAULT NULL,
  `reporter` varchar(64) DEFAULT NULL,
  `start_date` int(11) unsigned DEFAULT NULL,
  `deadline` int(11) DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `wbs_id` int(11) unsigned NOT NULL,
  `state` int(11) unsigned DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `total_days` int(11) DEFAULT NULL,
  `average_daily_rate` int(11) DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `description` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_config_table`
--

DROP TABLE IF EXISTS `codev_config_table`;
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
  `description` longtext
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `codev_config_table`
--

INSERT INTO `codev_config_table` (`config_id`, `value`, `type`, `user_id`, `project_id`, `team_id`, `servicecontract_id`, `commandset_id`, `command_id`, `access_reqd`, `description`) VALUES
('database_version', '15', 1, 0, 0, 0, 0, 0, 0, NULL, NULL),
('blogCategories', '1:General,2:Imputations', 3, 0, 0, 0, 0, 0, 0, NULL, NULL),
('bug_resolved_status_threshold', '80', 1, 0, 0, 0, 0, 0, 0, NULL, 'bug resolved threshold as defined in Mantis (g_bug_resolved_status_threshold)'),
('customField_ExtId', '1', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('customField_effortEstim', '2', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('customField_type', '3', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('customField_MgrEffortEstim', '4', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('customField_deadLine', '5', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('customField_addEffort', '6', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('customField_backlog', '7', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('customField_deliveryDate', '8', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('externalTasksProject', '3', 1, 0, 0, 0, 0, 0, 0, NULL, 'CodevTT Projet de taches externes'),
('externalTasksCat_leave', '2', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('externalTasksCat_otherInternal', '3', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('adminTeamId', '1', 1, 0, 0, 0, 0, 0, 0, NULL, ''),
('issue_tooltip_fields', 'a:7:{i:0;s:10:"project_id";i:1;s:11:"category_id";i:2;s:8:"custom_3";i:3;s:6:"status";i:4;s:15:"codevtt_elapsed";i:5;s:8:"custom_7";i:6;s:13:"codevtt_drift";}', 2, 0, 0, 0, 0, 0, 0, NULL, 'fields to be displayed in issue tooltip'),
('defaultTeamId', '0', 1, 2, 0, 0, 0, 0, 0, NULL, 'prefered team on login');

-- --------------------------------------------------------

--
-- Structure de la table `codev_holidays_table`
--

DROP TABLE IF EXISTS `codev_holidays_table`;
CREATE TABLE IF NOT EXISTS `codev_holidays_table` (
`id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT 'D8D8D8'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Fixed Holidays (national, religious, etc.)' AUTO_INCREMENT=36 ;

--
-- Contenu de la table `codev_holidays_table`
--

INSERT INTO `codev_holidays_table` (`id`, `date`, `description`, `color`) VALUES
(14, 1293836400, 'Reveillon', 'D8D8D8'),
(25, 1335823200, 'fete du travail', '58CC77'),
(24, 1333922400, 'lundi de paques', '58CC77'),
(20, 1279058400, 'fete nationale', '58CC77'),
(21, 1288566000, 'toussaints', '58CC77'),
(22, 1289430000, 'armistice', '58CC77'),
(23, 1293231600, 'noel', 'D8D8D8'),
(27, 1304805600, 'victoire 1945', 'D8D8D8'),
(28, 1337205600, 'ascension', '58CC77'),
(29, 1307916000, 'pentecote', '58CC77'),
(30, 1342216800, 'fete nationale', 'D8D8D8'),
(31, 1344981600, 'assomption', '58CC77'),
(32, 1313359200, 'assomption', '58CC77'),
(33, 1351724400, 'toussaint', '58CC77'),
(34, 1352588400, 'armistice', 'D8D8D8'),
(35, 1356390000, 'noel', '58CC77');

-- --------------------------------------------------------

--
-- Structure de la table `codev_job_table`
--

DROP TABLE IF EXISTS `codev_job_table`;
CREATE TABLE IF NOT EXISTS `codev_job_table` (
`id` int(11) NOT NULL,
  `name` varchar(30) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `color` varchar(7) DEFAULT '000000'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Contenu de la table `codev_job_table`
--

INSERT INTO `codev_job_table` (`id`, `name`, `type`, `color`) VALUES
(1, 'N/A', 1, 'A8FFBD'),
(2, 'Support', 0, 'A8FFBD'),
(3, 'Analyse', 0, 'FFCD85'),
(4, 'Dévelopement', 0, 'C2DFFF'),
(5, 'Tests', 0, '92C5FC'),
(6, 'Documentation', 0, 'E0F57A');

-- --------------------------------------------------------

--
-- Structure de la table `codev_plugin_table`
--

DROP TABLE IF EXISTS `codev_plugin_table`;
CREATE TABLE IF NOT EXISTS `codev_plugin_table` (
  `name` varchar(64) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `domains` varchar(250) NOT NULL,
  `categories` varchar(250) NOT NULL,
  `version` varchar(10) NOT NULL,
  `description` varchar(250) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_project_category_table`
--

DROP TABLE IF EXISTS `codev_project_category_table`;
CREATE TABLE IF NOT EXISTS `codev_project_category_table` (
`id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_project_job_table`
--

DROP TABLE IF EXISTS `codev_project_job_table`;
CREATE TABLE IF NOT EXISTS `codev_project_job_table` (
`id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `codev_project_job_table`
--

INSERT INTO `codev_project_job_table` (`id`, `project_id`, `job_id`) VALUES
(1, 3, 1);

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_cmdset_table`
--

DROP TABLE IF EXISTS `codev_servicecontract_cmdset_table`;
CREATE TABLE IF NOT EXISTS `codev_servicecontract_cmdset_table` (
`id` int(11) unsigned NOT NULL,
  `servicecontract_id` int(11) NOT NULL,
  `commandset_id` int(11) NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_stproj_table`
--

DROP TABLE IF EXISTS `codev_servicecontract_stproj_table`;
CREATE TABLE IF NOT EXISTS `codev_servicecontract_stproj_table` (
`id` int(11) unsigned NOT NULL,
  `servicecontract_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_servicecontract_table`
--

DROP TABLE IF EXISTS `codev_servicecontract_table`;
CREATE TABLE IF NOT EXISTS `codev_servicecontract_table` (
`id` int(11) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `state` int(11) unsigned DEFAULT NULL,
  `version` varchar(64) DEFAULT NULL,
  `reporter` varchar(64) DEFAULT NULL,
  `start_date` int(11) unsigned DEFAULT NULL,
  `end_date` int(11) unsigned DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_sidetasks_category_table`
--

DROP TABLE IF EXISTS `codev_sidetasks_category_table`;
CREATE TABLE IF NOT EXISTS `codev_sidetasks_category_table` (
`id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `cat_management` int(11) DEFAULT NULL,
  `cat_incident` int(11) DEFAULT NULL,
  `cat_inactivity` int(11) DEFAULT NULL,
  `cat_tools` int(11) DEFAULT NULL,
  `cat_workshop` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_team_project_table`
--

DROP TABLE IF EXISTS `codev_team_project_table`;
CREATE TABLE IF NOT EXISTS `codev_team_project_table` (
`id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `codev_team_project_table`
--

INSERT INTO `codev_team_project_table` (`id`, `project_id`, `team_id`, `type`) VALUES
(1, 3, 1, 3);

-- --------------------------------------------------------

--
-- Structure de la table `codev_team_table`
--

DROP TABLE IF EXISTS `codev_team_table`;
CREATE TABLE IF NOT EXISTS `codev_team_table` (
`id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `leader_id` int(11) DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `commands_enabled` tinyint(4) NOT NULL DEFAULT '1',
  `date` int(11) NOT NULL,
  `lock_timetracks_date` int(11) DEFAULT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `codev_team_table`
--

INSERT INTO `codev_team_table` (`id`, `name`, `description`, `leader_id`, `enabled`, `commands_enabled`, `date`, `lock_timetracks_date`) VALUES
(1, 'CodevTT admin', 'Equipe d''administration CodevTT', 2, 0, 1, 1454108400, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `codev_team_user_table`
--

DROP TABLE IF EXISTS `codev_team_user_table`;
CREATE TABLE IF NOT EXISTS `codev_team_user_table` (
`id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `access_level` int(11) unsigned NOT NULL DEFAULT '10',
  `arrival_date` int(11) unsigned NOT NULL,
  `departure_date` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `codev_team_user_table`
--

INSERT INTO `codev_team_user_table` (`id`, `user_id`, `team_id`, `access_level`, `arrival_date`, `departure_date`) VALUES
(1, 2, 1, 10, 1454108400, 0);

-- --------------------------------------------------------

--
-- Structure de la table `codev_timetracking_table`
--

DROP TABLE IF EXISTS `codev_timetracking_table`;
CREATE TABLE IF NOT EXISTS `codev_timetracking_table` (
`id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `bugid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  `date` int(11) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `committer_id` int(11) DEFAULT NULL,
  `commit_date` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `codev_wbs_table`
--

DROP TABLE IF EXISTS `codev_wbs_table`;
CREATE TABLE IF NOT EXISTS `codev_wbs_table` (
`id` int(11) unsigned NOT NULL,
  `root_id` int(11) unsigned DEFAULT NULL,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `order` int(11) NOT NULL,
  `bug_id` int(11) DEFAULT NULL,
  `expand` tinyint(4) NOT NULL DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `icon` varchar(64) DEFAULT NULL,
  `font` varchar(64) DEFAULT NULL,
  `color` varchar(64) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_api_token_table`
--

DROP TABLE IF EXISTS `mantis_api_token_table`;
CREATE TABLE IF NOT EXISTS `mantis_api_token_table` (
`id` int(10) unsigned NOT NULL,
  `user_id` int(11) DEFAULT '0',
  `name` varchar(128) NOT NULL,
  `hash` varchar(128) NOT NULL,
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `date_used` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bugnote_table`
--

DROP TABLE IF EXISTS `mantis_bugnote_table`;
CREATE TABLE IF NOT EXISTS `mantis_bugnote_table` (
`id` int(10) unsigned NOT NULL,
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `reporter_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bugnote_text_id` int(10) unsigned NOT NULL DEFAULT '0',
  `view_state` smallint(6) NOT NULL DEFAULT '10',
  `note_type` int(11) DEFAULT '0',
  `note_attr` varchar(250) DEFAULT '',
  `time_tracking` int(10) unsigned NOT NULL DEFAULT '0',
  `last_modified` int(10) unsigned NOT NULL DEFAULT '1',
  `date_submitted` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bugnote_text_table`
--

DROP TABLE IF EXISTS `mantis_bugnote_text_table`;
CREATE TABLE IF NOT EXISTS `mantis_bugnote_text_table` (
`id` int(10) unsigned NOT NULL,
  `note` longtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_file_table`
--

DROP TABLE IF EXISTS `mantis_bug_file_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_file_table` (
`id` int(10) unsigned NOT NULL,
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `description` varchar(250) NOT NULL DEFAULT '',
  `diskfile` varchar(250) NOT NULL DEFAULT '',
  `filename` varchar(250) NOT NULL DEFAULT '',
  `folder` varchar(250) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL DEFAULT '0',
  `file_type` varchar(250) NOT NULL DEFAULT '',
  `content` longblob,
  `date_added` int(10) unsigned NOT NULL DEFAULT '1',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_history_table`
--

DROP TABLE IF EXISTS `mantis_bug_history_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_history_table` (
`id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_name` varchar(64) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `type` smallint(6) NOT NULL DEFAULT '0',
  `date_modified` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_monitor_table`
--

DROP TABLE IF EXISTS `mantis_bug_monitor_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_monitor_table` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_relationship_table`
--

DROP TABLE IF EXISTS `mantis_bug_relationship_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_relationship_table` (
`id` int(10) unsigned NOT NULL,
  `source_bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `destination_bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `relationship_type` smallint(6) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_revision_table`
--

DROP TABLE IF EXISTS `mantis_bug_revision_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_revision_table` (
`id` int(10) unsigned NOT NULL,
  `bug_id` int(10) unsigned NOT NULL,
  `bugnote_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL,
  `type` int(10) unsigned NOT NULL,
  `value` longtext NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_table`
--

DROP TABLE IF EXISTS `mantis_bug_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_table` (
`id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `reporter_id` int(10) unsigned NOT NULL DEFAULT '0',
  `handler_id` int(10) unsigned NOT NULL DEFAULT '0',
  `duplicate_id` int(10) unsigned NOT NULL DEFAULT '0',
  `priority` smallint(6) NOT NULL DEFAULT '30',
  `severity` smallint(6) NOT NULL DEFAULT '50',
  `reproducibility` smallint(6) NOT NULL DEFAULT '10',
  `status` smallint(6) NOT NULL DEFAULT '10',
  `resolution` smallint(6) NOT NULL DEFAULT '10',
  `projection` smallint(6) NOT NULL DEFAULT '10',
  `eta` smallint(6) NOT NULL DEFAULT '10',
  `bug_text_id` int(10) unsigned NOT NULL DEFAULT '0',
  `os` varchar(32) NOT NULL DEFAULT '',
  `os_build` varchar(32) NOT NULL DEFAULT '',
  `platform` varchar(32) NOT NULL DEFAULT '',
  `version` varchar(64) NOT NULL DEFAULT '',
  `fixed_in_version` varchar(64) NOT NULL DEFAULT '',
  `build` varchar(32) NOT NULL DEFAULT '',
  `profile_id` int(10) unsigned NOT NULL DEFAULT '0',
  `view_state` smallint(6) NOT NULL DEFAULT '10',
  `summary` varchar(128) NOT NULL DEFAULT '',
  `sponsorship_total` int(11) NOT NULL DEFAULT '0',
  `sticky` tinyint(4) NOT NULL DEFAULT '0',
  `target_version` varchar(64) NOT NULL DEFAULT '',
  `category_id` int(10) unsigned NOT NULL DEFAULT '1',
  `date_submitted` int(10) unsigned NOT NULL DEFAULT '1',
  `due_date` int(10) unsigned NOT NULL DEFAULT '1',
  `last_updated` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `mantis_bug_table`
--

INSERT INTO `mantis_bug_table` (`id`, `project_id`, `reporter_id`, `handler_id`, `duplicate_id`, `priority`, `severity`, `reproducibility`, `status`, `resolution`, `projection`, `eta`, `bug_text_id`, `os`, `os_build`, `platform`, `version`, `fixed_in_version`, `build`, `profile_id`, `view_state`, `summary`, `sponsorship_total`, `sticky`, `target_version`, `category_id`, `date_submitted`, `due_date`, `last_updated`) VALUES
(1, 3, 0, 0, 0, 10, 50, 100, 90, 10, 10, 10, 1, '', '', '', '', '', '', 0, 10, 'autre activité', 0, 0, '', 3, 1454108400, 1, 1454108400),
(2, 3, 0, 0, 0, 10, 50, 100, 90, 10, 10, 10, 2, '', '', '', '', '', '', 0, 10, 'Absence', 0, 0, '', 2, 1454108400, 1, 1454108400),
(3, 3, 0, 0, 0, 10, 50, 100, 90, 10, 10, 10, 3, '', '', '', '', '', '', 0, 10, 'Maladie', 0, 0, '', 2, 1454108400, 1, 1454108400);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_tag_table`
--

DROP TABLE IF EXISTS `mantis_bug_tag_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_tag_table` (
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `tag_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_attached` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_bug_text_table`
--

DROP TABLE IF EXISTS `mantis_bug_text_table`;
CREATE TABLE IF NOT EXISTS `mantis_bug_text_table` (
`id` int(10) unsigned NOT NULL,
  `description` longtext NOT NULL,
  `steps_to_reproduce` longtext NOT NULL,
  `additional_information` longtext NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `mantis_bug_text_table`
--

INSERT INTO `mantis_bug_text_table` (`id`, `description`, `steps_to_reproduce`, `additional_information`) VALUES
(1, 'Toutes activités non référencées dans un projet mantis', '', ''),
(2, 'Congé, Maladie, ...', '', ''),
(3, 'Maladie', '', '');

-- --------------------------------------------------------

--
-- Structure de la table `mantis_category_table`
--

DROP TABLE IF EXISTS `mantis_category_table`;
CREATE TABLE IF NOT EXISTS `mantis_category_table` (
`id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `status` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `mantis_category_table`
--

INSERT INTO `mantis_category_table` (`id`, `project_id`, `user_id`, `name`, `status`) VALUES
(1, 0, 0, 'General', 0),
(2, 3, 0, 'Leave', 0),
(3, 3, 0, 'Other activity', 0);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_config_table`
--

DROP TABLE IF EXISTS `mantis_config_table`;
CREATE TABLE IF NOT EXISTS `mantis_config_table` (
  `config_id` varchar(64) NOT NULL,
  `project_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `access_reqd` int(11) DEFAULT '0',
  `type` int(11) DEFAULT '90',
  `value` longtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `mantis_config_table`
--

INSERT INTO `mantis_config_table` (`config_id`, `project_id`, `user_id`, `access_reqd`, `type`, `value`) VALUES
('database_version', 0, 0, 90, 1, '201'),
('bug_submit_status', 3, 0, 90, 1, '90'),
('bug_assigned_status', 3, 0, 90, 1, '90'),
('plugin_FilterBugList_schema', 0, 0, 90, 1, '-1'),
('status_enum_workflow', 1, 0, 90, 3, '{"10":"20:feedback,30:acknowledged,40:analyzed,50:open,80:resolved,90:closed","20":"30:acknowledged,40:analyzed,50:open,80:resolved","30":"20:feedback,40:analyzed,50:open,80:resolved","40":"20:feedback,50:open,80:resolved","50":"20:feedback,80:resolved","80":"20:feedback,82:validated,85:delivered,90:closed","82":"20:feedback,85:delivered,90:closed","85":"20:feedback,90:closed","90":"20:feedback"}'),
('status_enum_workflow', 0, 0, 90, 3, '{"10":"20:feedback,30:acknowledged,40:analyzed,50:open,80:resolved,90:closed","20":"30:acknowledged,40:analyzed,50:open,80:resolved","30":"20:feedback,40:analyzed,50:open,80:resolved","40":"20:feedback,50:open,80:resolved","50":"20:feedback,80:resolved","80":"20:feedback,82:validated,85:delivered,90:closed","82":"20:feedback,85:delivered,90:closed","85":"20:feedback,90:closed","90":"20:feedback"}');

-- --------------------------------------------------------

--
-- Structure de la table `mantis_custom_field_project_table`
--

DROP TABLE IF EXISTS `mantis_custom_field_project_table`;
CREATE TABLE IF NOT EXISTS `mantis_custom_field_project_table` (
  `field_id` int(11) NOT NULL DEFAULT '0',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sequence` smallint(6) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `mantis_custom_field_project_table`
--

INSERT INTO `mantis_custom_field_project_table` (`field_id`, `project_id`, `sequence`) VALUES
(3, 1, 101),
(1, 2, 0),
(1, 1, 102),
(4, 1, 103),
(2, 1, 104),
(6, 1, 105),
(7, 1, 106),
(5, 1, 107),
(8, 1, 108),
(3, 2, 101),
(4, 2, 103),
(2, 2, 104),
(6, 2, 105),
(7, 2, 106),
(5, 2, 107),
(8, 2, 108);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_custom_field_string_table`
--

DROP TABLE IF EXISTS `mantis_custom_field_string_table`;
CREATE TABLE IF NOT EXISTS `mantis_custom_field_string_table` (
  `field_id` int(11) NOT NULL DEFAULT '0',
  `bug_id` int(11) NOT NULL DEFAULT '0',
  `value` varchar(255) NOT NULL DEFAULT '',
  `text` longtext
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_custom_field_table`
--

DROP TABLE IF EXISTS `mantis_custom_field_table`;
CREATE TABLE IF NOT EXISTS `mantis_custom_field_table` (
`id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `type` smallint(6) NOT NULL DEFAULT '0',
  `possible_values` text,
  `default_value` varchar(255) NOT NULL DEFAULT '',
  `valid_regexp` varchar(255) NOT NULL DEFAULT '',
  `access_level_r` smallint(6) NOT NULL DEFAULT '0',
  `access_level_rw` smallint(6) NOT NULL DEFAULT '0',
  `length_min` int(11) NOT NULL DEFAULT '0',
  `length_max` int(11) NOT NULL DEFAULT '0',
  `require_report` tinyint(4) NOT NULL DEFAULT '0',
  `require_update` tinyint(4) NOT NULL DEFAULT '0',
  `display_report` tinyint(4) NOT NULL DEFAULT '0',
  `display_update` tinyint(4) NOT NULL DEFAULT '1',
  `require_resolved` tinyint(4) NOT NULL DEFAULT '0',
  `display_resolved` tinyint(4) NOT NULL DEFAULT '0',
  `display_closed` tinyint(4) NOT NULL DEFAULT '0',
  `require_closed` tinyint(4) NOT NULL DEFAULT '0',
  `filter_by` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Contenu de la table `mantis_custom_field_table`
--

INSERT INTO `mantis_custom_field_table` (`id`, `name`, `type`, `possible_values`, `default_value`, `valid_regexp`, `access_level_r`, `access_level_rw`, `length_min`, `length_max`, `require_report`, `require_update`, `display_report`, `display_update`, `require_resolved`, `display_resolved`, `display_closed`, `require_closed`, `filter_by`) VALUES
(1, 'HP-ALM id', 0, '', '', '', 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(2, 'CodevTT_Charge Initiale', 1, '', '1', '', 10, 25, 0, 0, 1, 0, 1, 1, 0, 0, 0, 0, 1),
(3, 'CodevTT_Type', 6, 'Bug|Task', '', '', 10, 25, 0, 0, 1, 0, 1, 1, 0, 0, 0, 0, 1),
(4, 'CodevTT_Charge Manager', 1, '', '0', '', 70, 70, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1),
(5, 'CodevTT_Deadline', 8, '', '', '', 10, 25, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1),
(6, 'CodevTT_Charge Additionnelle', 1, '', '', '', 10, 25, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(7, 'CodevTT_RAF', 1, '', '', '', 10, 25, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1),
(8, 'CodevTT_Date Livraison', 8, '', '', '', 10, 25, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_email_table`
--

DROP TABLE IF EXISTS `mantis_email_table`;
CREATE TABLE IF NOT EXISTS `mantis_email_table` (
`email_id` int(10) unsigned NOT NULL,
  `email` varchar(64) NOT NULL DEFAULT '',
  `subject` varchar(250) NOT NULL DEFAULT '',
  `metadata` longtext NOT NULL,
  `body` longtext NOT NULL,
  `submitted` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_filters_table`
--

DROP TABLE IF EXISTS `mantis_filters_table`;
CREATE TABLE IF NOT EXISTS `mantis_filters_table` (
`id` int(10) unsigned NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `project_id` int(11) NOT NULL DEFAULT '0',
  `is_public` tinyint(4) DEFAULT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `filter_string` longtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_news_table`
--

DROP TABLE IF EXISTS `mantis_news_table`;
CREATE TABLE IF NOT EXISTS `mantis_news_table` (
`id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `poster_id` int(10) unsigned NOT NULL DEFAULT '0',
  `view_state` smallint(6) NOT NULL DEFAULT '10',
  `announcement` tinyint(4) NOT NULL DEFAULT '0',
  `headline` varchar(64) NOT NULL DEFAULT '',
  `body` longtext NOT NULL,
  `last_modified` int(10) unsigned NOT NULL DEFAULT '1',
  `date_posted` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_plugin_table`
--

DROP TABLE IF EXISTS `mantis_plugin_table`;
CREATE TABLE IF NOT EXISTS `mantis_plugin_table` (
  `basename` varchar(40) NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '0',
  `protected` tinyint(4) NOT NULL DEFAULT '0',
  `priority` int(10) unsigned NOT NULL DEFAULT '3'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `mantis_plugin_table`
--

INSERT INTO `mantis_plugin_table` (`basename`, `enabled`, `protected`, `priority`) VALUES
('MantisCoreFormatting', 1, 0, 3),
('CodevTT', 1, 0, 3),
('FilterBugList', 1, 0, 3);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_file_table`
--

DROP TABLE IF EXISTS `mantis_project_file_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_file_table` (
`id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `description` varchar(250) NOT NULL DEFAULT '',
  `diskfile` varchar(250) NOT NULL DEFAULT '',
  `filename` varchar(250) NOT NULL DEFAULT '',
  `folder` varchar(250) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL DEFAULT '0',
  `file_type` varchar(250) NOT NULL DEFAULT '',
  `content` longblob,
  `date_added` int(10) unsigned NOT NULL DEFAULT '1',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_hierarchy_table`
--

DROP TABLE IF EXISTS `mantis_project_hierarchy_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_hierarchy_table` (
  `child_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `inherit_parent` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_table`
--

DROP TABLE IF EXISTS `mantis_project_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_table` (
`id` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `status` smallint(6) NOT NULL DEFAULT '10',
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `view_state` smallint(6) NOT NULL DEFAULT '10',
  `access_min` smallint(6) NOT NULL DEFAULT '10',
  `file_path` varchar(250) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `category_id` int(10) unsigned NOT NULL DEFAULT '1',
  `inherit_global` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `mantis_project_table`
--

INSERT INTO `mantis_project_table` (`id`, `name`, `status`, `enabled`, `view_state`, `access_min`, `file_path`, `description`, `category_id`, `inherit_global`) VALUES
(1, 'project1', 10, 1, 10, 10, '', '', 1, 1),
(2, 'project2', 10, 1, 50, 10, '', '', 1, 1),
(3, 'CodevTT Taches Externes', 50, 1, 50, 10, '', 'CodevTT Projet de taches externes', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_user_list_table`
--

DROP TABLE IF EXISTS `mantis_project_user_list_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_user_list_table` (
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `access_level` smallint(6) NOT NULL DEFAULT '10'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `mantis_project_user_list_table`
--

INSERT INTO `mantis_project_user_list_table` (`project_id`, `user_id`, `access_level`) VALUES
(2, 2, 90),
(2, 3, 55),
(2, 4, 55);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_version_table`
--

DROP TABLE IF EXISTS `mantis_project_version_table`;
CREATE TABLE IF NOT EXISTS `mantis_project_version_table` (
`id` int(11) NOT NULL,
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `version` varchar(64) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `released` tinyint(4) NOT NULL DEFAULT '1',
  `obsolete` tinyint(4) NOT NULL DEFAULT '0',
  `date_order` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_sponsorship_table`
--

DROP TABLE IF EXISTS `mantis_sponsorship_table`;
CREATE TABLE IF NOT EXISTS `mantis_sponsorship_table` (
`id` int(11) NOT NULL,
  `bug_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `amount` int(11) NOT NULL DEFAULT '0',
  `logo` varchar(128) NOT NULL DEFAULT '',
  `url` varchar(128) NOT NULL DEFAULT '',
  `paid` tinyint(4) NOT NULL DEFAULT '0',
  `date_submitted` int(10) unsigned NOT NULL DEFAULT '1',
  `last_updated` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_tag_table`
--

DROP TABLE IF EXISTS `mantis_tag_table`;
CREATE TABLE IF NOT EXISTS `mantis_tag_table` (
`id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `date_created` int(10) unsigned NOT NULL DEFAULT '1',
  `date_updated` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_tokens_table`
--

DROP TABLE IF EXISTS `mantis_tokens_table`;
CREATE TABLE IF NOT EXISTS `mantis_tokens_table` (
`id` int(11) NOT NULL,
  `owner` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `value` longtext NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '1',
  `expiry` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Contenu de la table `mantis_tokens_table`
--

INSERT INTO `mantis_tokens_table` (`id`, `owner`, `type`, `value`, `timestamp`, `expiry`) VALUES
(2, 1, 4, '1', 1454182691, 1454183160);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_user_pref_table`
--

DROP TABLE IF EXISTS `mantis_user_pref_table`;
CREATE TABLE IF NOT EXISTS `mantis_user_pref_table` (
`id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `default_profile` int(10) unsigned NOT NULL DEFAULT '0',
  `default_project` int(10) unsigned NOT NULL DEFAULT '0',
  `refresh_delay` int(11) NOT NULL DEFAULT '0',
  `redirect_delay` int(11) NOT NULL DEFAULT '0',
  `bugnote_order` varchar(4) NOT NULL DEFAULT 'ASC',
  `email_on_new` tinyint(4) NOT NULL DEFAULT '0',
  `email_on_assigned` tinyint(4) NOT NULL DEFAULT '0',
  `email_on_feedback` tinyint(4) NOT NULL DEFAULT '0',
  `email_on_resolved` tinyint(4) NOT NULL DEFAULT '0',
  `email_on_closed` tinyint(4) NOT NULL DEFAULT '0',
  `email_on_reopened` tinyint(4) NOT NULL DEFAULT '0',
  `email_on_bugnote` tinyint(4) NOT NULL DEFAULT '0',
  `email_on_status` tinyint(4) DEFAULT '0',
  `email_on_priority` tinyint(4) DEFAULT '0',
  `email_on_priority_min_severity` smallint(6) NOT NULL DEFAULT '10',
  `email_on_status_min_severity` smallint(6) NOT NULL DEFAULT '10',
  `email_on_bugnote_min_severity` smallint(6) NOT NULL DEFAULT '10',
  `email_on_reopened_min_severity` smallint(6) NOT NULL DEFAULT '10',
  `email_on_closed_min_severity` smallint(6) NOT NULL DEFAULT '10',
  `email_on_resolved_min_severity` smallint(6) NOT NULL DEFAULT '10',
  `email_on_feedback_min_severity` smallint(6) NOT NULL DEFAULT '10',
  `email_on_assigned_min_severity` smallint(6) NOT NULL DEFAULT '10',
  `email_on_new_min_severity` smallint(6) NOT NULL DEFAULT '10',
  `email_bugnote_limit` smallint(6) NOT NULL DEFAULT '0',
  `language` varchar(32) NOT NULL DEFAULT 'english',
  `timezone` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `mantis_user_pref_table`
--

INSERT INTO `mantis_user_pref_table` (`id`, `user_id`, `project_id`, `default_profile`, `default_project`, `refresh_delay`, `redirect_delay`, `bugnote_order`, `email_on_new`, `email_on_assigned`, `email_on_feedback`, `email_on_resolved`, `email_on_closed`, `email_on_reopened`, `email_on_bugnote`, `email_on_status`, `email_on_priority`, `email_on_priority_min_severity`, `email_on_status_min_severity`, `email_on_bugnote_min_severity`, `email_on_reopened_min_severity`, `email_on_closed_min_severity`, `email_on_resolved_min_severity`, `email_on_feedback_min_severity`, `email_on_assigned_min_severity`, `email_on_new_min_severity`, `email_bugnote_limit`, `language`, `timezone`) VALUES
(1, 1, 0, 0, 1, 30, 2, 'ASC', 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'auto', 'Europe/Berlin');

-- --------------------------------------------------------

--
-- Structure de la table `mantis_user_print_pref_table`
--

DROP TABLE IF EXISTS `mantis_user_print_pref_table`;
CREATE TABLE IF NOT EXISTS `mantis_user_print_pref_table` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `print_pref` varchar(64) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_user_profile_table`
--

DROP TABLE IF EXISTS `mantis_user_profile_table`;
CREATE TABLE IF NOT EXISTS `mantis_user_profile_table` (
`id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `platform` varchar(32) NOT NULL DEFAULT '',
  `os` varchar(32) NOT NULL DEFAULT '',
  `os_build` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `mantis_user_table`
--

DROP TABLE IF EXISTS `mantis_user_table`;
CREATE TABLE IF NOT EXISTS `mantis_user_table` (
`id` int(10) unsigned NOT NULL,
  `username` varchar(255) NOT NULL DEFAULT '',
  `realname` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `protected` tinyint(4) NOT NULL DEFAULT '0',
  `access_level` smallint(6) NOT NULL DEFAULT '10',
  `login_count` int(11) NOT NULL DEFAULT '0',
  `lost_password_request_count` smallint(6) NOT NULL DEFAULT '0',
  `failed_login_count` smallint(6) NOT NULL DEFAULT '0',
  `cookie_string` varchar(64) NOT NULL DEFAULT '',
  `last_visit` int(10) unsigned NOT NULL DEFAULT '1',
  `date_created` int(10) unsigned NOT NULL DEFAULT '1'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Contenu de la table `mantis_user_table`
--

INSERT INTO `mantis_user_table` (`id`, `username`, `realname`, `email`, `password`, `enabled`, `protected`, `access_level`, `login_count`, `lost_password_request_count`, `failed_login_count`, `cookie_string`, `last_visit`, `date_created`) VALUES
(1, 'administrator', '', 'root@localhost', '63a9f0ea7bb98050796b649e85481845', 1, 0, 90, 5, 0, 0, 'cccccf7e14e5e35874e645b088db1876468319631beaabddc923b346249e02a7', 1454182907, 1454181772),
(2, 'lbayle', 'Louis BAYLE', '', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 25, 0, 0, 0, 'HNDE4fwr3tkZKe4ZZVcqTbRIaNjQkimliy6Oc9WdvEpME5Ud5XwTH4eqeQWq_cvI', 1454181864, 1454181864),
(3, 'user1', 'user ONE', '', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 25, 0, 0, 0, '9yYwYsuBuwfEg_p4fZvxTMBsJ2LB0aelMBes-5C9KfrTKct_4qnJXifSOGn6f6bH', 1454181889, 1454181889),
(4, 'user2', 'user TWO', '', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 25, 0, 0, 0, 'P2n7qvNQB2U--lAngo_NfUXOtIMNh7THtvFBe2eXLWXKXBjMkuT0TvkCtimyUdBC', 1454181910, 1454181910);

--
-- Index pour les tables exportées
--

--
-- Index pour la table `codev_blog_activity_table`
--
ALTER TABLE `codev_blog_activity_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `codev_blog_table`
--
ALTER TABLE `codev_blog_table`
 ADD PRIMARY KEY (`id`), ADD KEY `date` (`date_submitted`);

--
-- Index pour la table `codev_commandset_cmd_table`
--
ALTER TABLE `codev_commandset_cmd_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `codev_commandset_table`
--
ALTER TABLE `codev_commandset_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `codev_command_bug_table`
--
ALTER TABLE `codev_command_bug_table`
 ADD PRIMARY KEY (`id`), ADD KEY `command_id` (`command_id`), ADD KEY `bug_id` (`bug_id`);

--
-- Index pour la table `codev_command_provision_table`
--
ALTER TABLE `codev_command_provision_table`
 ADD PRIMARY KEY (`id`), ADD KEY `command_id` (`command_id`);

--
-- Index pour la table `codev_command_table`
--
ALTER TABLE `codev_command_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `codev_config_table`
--
ALTER TABLE `codev_config_table`
 ADD PRIMARY KEY (`config_id`,`team_id`,`project_id`,`user_id`,`servicecontract_id`,`commandset_id`,`command_id`);

--
-- Index pour la table `codev_holidays_table`
--
ALTER TABLE `codev_holidays_table`
 ADD PRIMARY KEY (`id`), ADD KEY `date` (`date`);

--
-- Index pour la table `codev_job_table`
--
ALTER TABLE `codev_job_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `codev_plugin_table`
--
ALTER TABLE `codev_plugin_table`
 ADD PRIMARY KEY (`name`);

--
-- Index pour la table `codev_project_category_table`
--
ALTER TABLE `codev_project_category_table`
 ADD PRIMARY KEY (`id`,`project_id`,`category_id`);

--
-- Index pour la table `codev_project_job_table`
--
ALTER TABLE `codev_project_job_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `codev_servicecontract_cmdset_table`
--
ALTER TABLE `codev_servicecontract_cmdset_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `codev_servicecontract_stproj_table`
--
ALTER TABLE `codev_servicecontract_stproj_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `codev_servicecontract_table`
--
ALTER TABLE `codev_servicecontract_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `codev_sidetasks_category_table`
--
ALTER TABLE `codev_sidetasks_category_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `project_id` (`project_id`), ADD KEY `project_id_2` (`project_id`);

--
-- Index pour la table `codev_team_project_table`
--
ALTER TABLE `codev_team_project_table`
 ADD PRIMARY KEY (`id`), ADD KEY `project_id` (`project_id`), ADD KEY `team_id` (`team_id`);

--
-- Index pour la table `codev_team_table`
--
ALTER TABLE `codev_team_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `codev_team_user_table`
--
ALTER TABLE `codev_team_user_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `codev_timetracking_table`
--
ALTER TABLE `codev_timetracking_table`
 ADD PRIMARY KEY (`id`), ADD KEY `bugid` (`bugid`), ADD KEY `userid` (`userid`), ADD KEY `date` (`date`);

--
-- Index pour la table `codev_wbs_table`
--
ALTER TABLE `codev_wbs_table`
 ADD PRIMARY KEY (`id`), ADD KEY `bug_id` (`bug_id`), ADD KEY `parent_id` (`parent_id`), ADD KEY `order` (`order`);

--
-- Index pour la table `mantis_api_token_table`
--
ALTER TABLE `mantis_api_token_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_user_id_name` (`user_id`,`name`);

--
-- Index pour la table `mantis_bugnote_table`
--
ALTER TABLE `mantis_bugnote_table`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_bug` (`bug_id`), ADD KEY `idx_last_mod` (`last_modified`);

--
-- Index pour la table `mantis_bugnote_text_table`
--
ALTER TABLE `mantis_bugnote_text_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mantis_bug_file_table`
--
ALTER TABLE `mantis_bug_file_table`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_bug_file_bug_id` (`bug_id`), ADD KEY `idx_diskfile` (`diskfile`);

--
-- Index pour la table `mantis_bug_history_table`
--
ALTER TABLE `mantis_bug_history_table`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_bug_history_bug_id` (`bug_id`), ADD KEY `idx_history_user_id` (`user_id`), ADD KEY `idx_bug_history_date_modified` (`date_modified`);

--
-- Index pour la table `mantis_bug_monitor_table`
--
ALTER TABLE `mantis_bug_monitor_table`
 ADD PRIMARY KEY (`user_id`,`bug_id`), ADD KEY `idx_bug_id` (`bug_id`);

--
-- Index pour la table `mantis_bug_relationship_table`
--
ALTER TABLE `mantis_bug_relationship_table`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_relationship_source` (`source_bug_id`), ADD KEY `idx_relationship_destination` (`destination_bug_id`);

--
-- Index pour la table `mantis_bug_revision_table`
--
ALTER TABLE `mantis_bug_revision_table`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_bug_rev_type` (`type`), ADD KEY `idx_bug_rev_id_time` (`bug_id`,`timestamp`);

--
-- Index pour la table `mantis_bug_table`
--
ALTER TABLE `mantis_bug_table`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_bug_sponsorship_total` (`sponsorship_total`), ADD KEY `idx_bug_fixed_in_version` (`fixed_in_version`), ADD KEY `idx_bug_status` (`status`), ADD KEY `idx_project` (`project_id`), ADD KEY `handler_id` (`handler_id`);

--
-- Index pour la table `mantis_bug_tag_table`
--
ALTER TABLE `mantis_bug_tag_table`
 ADD PRIMARY KEY (`bug_id`,`tag_id`), ADD KEY `idx_bug_tag_tag_id` (`tag_id`);

--
-- Index pour la table `mantis_bug_text_table`
--
ALTER TABLE `mantis_bug_text_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mantis_category_table`
--
ALTER TABLE `mantis_category_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_category_project_name` (`project_id`,`name`);

--
-- Index pour la table `mantis_config_table`
--
ALTER TABLE `mantis_config_table`
 ADD PRIMARY KEY (`config_id`,`project_id`,`user_id`);

--
-- Index pour la table `mantis_custom_field_project_table`
--
ALTER TABLE `mantis_custom_field_project_table`
 ADD PRIMARY KEY (`field_id`,`project_id`);

--
-- Index pour la table `mantis_custom_field_string_table`
--
ALTER TABLE `mantis_custom_field_string_table`
 ADD PRIMARY KEY (`field_id`,`bug_id`), ADD KEY `idx_custom_field_bug` (`bug_id`);

--
-- Index pour la table `mantis_custom_field_table`
--
ALTER TABLE `mantis_custom_field_table`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_custom_field_name` (`name`);

--
-- Index pour la table `mantis_email_table`
--
ALTER TABLE `mantis_email_table`
 ADD PRIMARY KEY (`email_id`);

--
-- Index pour la table `mantis_filters_table`
--
ALTER TABLE `mantis_filters_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mantis_news_table`
--
ALTER TABLE `mantis_news_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mantis_plugin_table`
--
ALTER TABLE `mantis_plugin_table`
 ADD PRIMARY KEY (`basename`);

--
-- Index pour la table `mantis_project_file_table`
--
ALTER TABLE `mantis_project_file_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mantis_project_hierarchy_table`
--
ALTER TABLE `mantis_project_hierarchy_table`
 ADD UNIQUE KEY `idx_project_hierarchy` (`child_id`,`parent_id`), ADD KEY `idx_project_hierarchy_child_id` (`child_id`), ADD KEY `idx_project_hierarchy_parent_id` (`parent_id`);

--
-- Index pour la table `mantis_project_table`
--
ALTER TABLE `mantis_project_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_project_name` (`name`), ADD KEY `idx_project_view` (`view_state`);

--
-- Index pour la table `mantis_project_user_list_table`
--
ALTER TABLE `mantis_project_user_list_table`
 ADD PRIMARY KEY (`project_id`,`user_id`), ADD KEY `idx_project_user` (`user_id`);

--
-- Index pour la table `mantis_project_version_table`
--
ALTER TABLE `mantis_project_version_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_project_version` (`project_id`,`version`);

--
-- Index pour la table `mantis_sponsorship_table`
--
ALTER TABLE `mantis_sponsorship_table`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_sponsorship_bug_id` (`bug_id`), ADD KEY `idx_sponsorship_user_id` (`user_id`);

--
-- Index pour la table `mantis_tag_table`
--
ALTER TABLE `mantis_tag_table`
 ADD PRIMARY KEY (`id`,`name`), ADD KEY `idx_tag_name` (`name`);

--
-- Index pour la table `mantis_tokens_table`
--
ALTER TABLE `mantis_tokens_table`
 ADD PRIMARY KEY (`id`), ADD KEY `idx_typeowner` (`type`,`owner`);

--
-- Index pour la table `mantis_user_pref_table`
--
ALTER TABLE `mantis_user_pref_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mantis_user_print_pref_table`
--
ALTER TABLE `mantis_user_print_pref_table`
 ADD PRIMARY KEY (`user_id`);

--
-- Index pour la table `mantis_user_profile_table`
--
ALTER TABLE `mantis_user_profile_table`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mantis_user_table`
--
ALTER TABLE `mantis_user_table`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `idx_user_cookie_string` (`cookie_string`), ADD UNIQUE KEY `idx_user_username` (`username`), ADD KEY `idx_enable` (`enabled`), ADD KEY `idx_access` (`access_level`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `codev_blog_activity_table`
--
ALTER TABLE `codev_blog_activity_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_blog_table`
--
ALTER TABLE `codev_blog_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_commandset_cmd_table`
--
ALTER TABLE `codev_commandset_cmd_table`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_commandset_table`
--
ALTER TABLE `codev_commandset_table`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_command_bug_table`
--
ALTER TABLE `codev_command_bug_table`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_command_provision_table`
--
ALTER TABLE `codev_command_provision_table`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_command_table`
--
ALTER TABLE `codev_command_table`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_holidays_table`
--
ALTER TABLE `codev_holidays_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=36;
--
-- AUTO_INCREMENT pour la table `codev_job_table`
--
ALTER TABLE `codev_job_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pour la table `codev_project_category_table`
--
ALTER TABLE `codev_project_category_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_project_job_table`
--
ALTER TABLE `codev_project_job_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `codev_servicecontract_cmdset_table`
--
ALTER TABLE `codev_servicecontract_cmdset_table`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_servicecontract_stproj_table`
--
ALTER TABLE `codev_servicecontract_stproj_table`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_servicecontract_table`
--
ALTER TABLE `codev_servicecontract_table`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_sidetasks_category_table`
--
ALTER TABLE `codev_sidetasks_category_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_team_project_table`
--
ALTER TABLE `codev_team_project_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `codev_team_table`
--
ALTER TABLE `codev_team_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `codev_team_user_table`
--
ALTER TABLE `codev_team_user_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `codev_timetracking_table`
--
ALTER TABLE `codev_timetracking_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `codev_wbs_table`
--
ALTER TABLE `codev_wbs_table`
MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_api_token_table`
--
ALTER TABLE `mantis_api_token_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_bugnote_table`
--
ALTER TABLE `mantis_bugnote_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_bugnote_text_table`
--
ALTER TABLE `mantis_bugnote_text_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_bug_file_table`
--
ALTER TABLE `mantis_bug_file_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_bug_history_table`
--
ALTER TABLE `mantis_bug_history_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_bug_relationship_table`
--
ALTER TABLE `mantis_bug_relationship_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_bug_revision_table`
--
ALTER TABLE `mantis_bug_revision_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_bug_table`
--
ALTER TABLE `mantis_bug_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pour la table `mantis_bug_text_table`
--
ALTER TABLE `mantis_bug_text_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pour la table `mantis_category_table`
--
ALTER TABLE `mantis_category_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pour la table `mantis_custom_field_table`
--
ALTER TABLE `mantis_custom_field_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT pour la table `mantis_email_table`
--
ALTER TABLE `mantis_email_table`
MODIFY `email_id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_filters_table`
--
ALTER TABLE `mantis_filters_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_news_table`
--
ALTER TABLE `mantis_news_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_project_file_table`
--
ALTER TABLE `mantis_project_file_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_project_table`
--
ALTER TABLE `mantis_project_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pour la table `mantis_project_version_table`
--
ALTER TABLE `mantis_project_version_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_sponsorship_table`
--
ALTER TABLE `mantis_sponsorship_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_tag_table`
--
ALTER TABLE `mantis_tag_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_tokens_table`
--
ALTER TABLE `mantis_tokens_table`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pour la table `mantis_user_pref_table`
--
ALTER TABLE `mantis_user_pref_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `mantis_user_profile_table`
--
ALTER TABLE `mantis_user_profile_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `mantis_user_table`
--
ALTER TABLE `mantis_user_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
