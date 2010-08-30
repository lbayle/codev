-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Jeu 17 Juin 2010 à 14:22
-- Version du serveur: 5.1.41
-- Version de PHP: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `bugtracker`
--

-- --------------------------------------------------------
-- Structure de la table `codev_timetracking_table`

CREATE TABLE IF NOT EXISTS `codev_timetracking_table` (
  `id`       int(10) NOT NULL AUTO_INCREMENT,
  `userid`   int(10) NOT NULL,
  `bugid`    int(10) NOT NULL,
  `jobid`    int(10) NOT NULL,
  `date`     int(10) DEFAULT NULL,
  `duration` float   DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- --------------------------------------------------------
-- Structure de la table `codev_job_table`

CREATE TABLE IF NOT EXISTS `codev_job_table` (
  `id`        int(10)      NOT NULL AUTO_INCREMENT,
  `name`      varchar(30)  NOT NULL,
  `projectid` int(10)      DEFAULT NULL,
  PRIMARY KEY (`id`)
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- --------------------------------------------------------
-- Structure de la table `codev_team_table`

CREATE TABLE IF NOT EXISTS `codev_team_table` (
  `id`          int(10)        NOT NULL AUTO_INCREMENT,
  `name`        varchar(15)    NOT NULL,
  `description` varchar(255)   DEFAULT NULL,
  `leader_id`   int(10)        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------
-- Structure de la table `codev_user_team_table`

CREATE TABLE IF NOT EXISTS `codev_team_user_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `team_id` int(10) NOT NULL,
  `arrival_date` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------
-- Structure de la table `codev_team_project_table`

CREATE TABLE IF NOT EXISTS `codev_team_project_table` (
  `id`         int(10) NOT NULL AUTO_INCREMENT,
  `project_id` int(10) NOT NULL,
  `team_id`    int(10) NOT NULL,
  `type`       int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `codev_team_project_table` (`id`, `project_id`, `team_id`, `type`) VALUES
(1, 1, 1, 0),
(2, 2, 1, 0),
(3, 3, 1, 0),
(4, 4, 1, 0),
(5, 5, 1, 0),
(6, 6, 1, 0),
(7, 12, 1, 0),
(8, 15, 1, 0),
(9, 11, 1, 1);



-- --------------------------------------------------------
-- Create Project Type (used in codev_team_project_table)
CREATE TABLE IF NOT EXISTS `codev_team_project_type_table` (
  `id`      int(10) NOT NULL AUTO_INCREMENT,
  `name`    varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;


INSERT INTO `codev_team_project_type_table` (`id`, `name`) VALUES
(0, 'Project'),
(1, 'SideTasks');

-- --------------------------------------------------------
-- Create Mantis CoDev user
INSERT INTO `mantis_user_table` (`id`, `username`, `realname`, `access_level`) VALUES
(13, 'codev', 'CoDev', 90);

-- --------------------------------------------------------
-- Create Mantis project for TimeTracking
INSERT INTO `mantis_project_table` (`id`, `name`, `status`, `enabled`, `view_state`, `access_min`, `file_path`, `description`, `category_id`, `inherit_global`) VALUES
(11, 'Suivi Op.', 50, 1, 10, 10, '', 'Suivi opérationel.', 1, 0);

-- --------------------------------------------------------
-- Create Mantis Issues for TimeTracking tasks
-- REM: handler_id '11' is user codev.

INSERT INTO `mantis_bug_table` (`summary`, `project_id`, `category_id`, `handler_id`, `status`) VALUES 
('Reunion',                '11', '15', '13', '90'), 
('Pilotage / Meeting',     '11', '15', '13', '90'),
('Formation',              '11', '16', '13', '90'),
('Absence',                '11', '17', '13', '90'),
('Chomage Technique',      '11', '17', '13', '90'),
('Administration Systeme', '11', '18', '13', '90'),
('Incident',               '11', '19', '13', '90');

-- --------------------------------------------------------
INSERT INTO `mantis_category_table` (`id`, `project_id`, `user_id`, `name`, `status`) VALUES
(15, 11, 0, 'Pilotage', 0),
(16, 11, 0, 'Capitalisation', 0),
(17, 11, 0, 'Inactivité', 0),
(18, 11, 0, 'Outillage', 0),
(19, 11, 0, 'Incidents', 0);

-- --------------------------------------------------------
-- Contenu de la table `codev_job_table`
-- REM: project_id '11' is SuiviOp.
 
INSERT INTO `codev_job_table` (`name`, `projectid`) VALUES
('Etude d impact',         NULL),
('Analyse de l''existant', NULL),
('Developpement',          NULL),
('Tests et Corrections',   NULL),
('N/A',                    11);



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
