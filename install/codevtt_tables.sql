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
('database_version', 16, 1),
('blogCategories', '1:General,2:Imputations', 3);


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
  `type` int(11) NOT NULL DEFAULT '0',
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
('BacklogPerUserIndicator', 1, 'Team,User,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Répartition du RAF des tâches de la sélection par utilisateur'),
('BudgetDriftHistoryIndicator2', 1, 'Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Affiche la dérive Budget'),
('DeadlineAlertIndicator', 1, 'User,Team,Project,Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Affiche les tâches ayant du être livrées (date jalon dépassée)'),
('DriftAlertIndicator', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Risk', '1.0.0', 'Tâches dont le consommé est supérieur à la charge initiale'),
('EffortEstimReliabilityIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Taux de fiabilité de la Charge estimée (ChargeMgr, ChargeInit)<br>Taux = charge estimée / consommé (tâches résolues uniquement)'),
('HelloWorldIndicator', 0, 'Command,Team,User,Project,CommandSet,ServiceContract,Admin', 'Quality', '1.0.0', 'un plugin exemple pour développeurs'),
('IssueBacklogVariationIndicator', 1, 'Task', 'Roadmap', '1.0.0', 'Affiche l''évolution du RAF dans le temps (burndown chart)'),
('LoadPerJobIndicator2', 1, 'Task,Team,User,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Affiche le consommé sur la période, réparti par poste'),
('LoadPerProjCategoryIndicator', 1, 'Team,Project,User,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Affiche le consommé sur la période, réparti par catégorie projet'),
('LoadPerProjectIndicator', 1, 'User,Team,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Affiche le consommé sur la période, réparti par projet'),
('LoadPerUserIndicator', 1, 'Task,Team,User,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Affiche le consommé sur la période, réparti par utilisateur'),
('ProgressHistoryIndicator2', 1, 'Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Affiche la progression de l''avancement dans le temps'),
('ReopenedRateIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Historique du nombre de fiches ayant été réouvertes'),
('StatusHistoryIndicator2', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Affiche l''évolution de la répartition des tâches par statut'),
('TimePerStatusIndicator', 1, 'Task', 'Roadmap', '1.0.0', 'Répartition du temps par status'),
('TimetrackDetailsIndicator', 1, 'Admin', 'Admin', '1.0.0', 'Affiche des informations suplémentaires sur les imputations');


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
