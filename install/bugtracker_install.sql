-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- genere le : Mar 03 Mai 2011 a  09:45
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
-- Structure de la table `codev_config_table`
--

CREATE TABLE IF NOT EXISTS `codev_config_table` (
  `config_id` varchar(50) NOT NULL,
  `value` longtext NOT NULL,
  `type` int(10) DEFAULT NULL,
  `user_id` int(10) DEFAULT NULL,
  `desc` longtext,
  PRIMARY KEY (`config_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `codev_holidays_table`
--

CREATE TABLE IF NOT EXISTS `codev_holidays_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` int(10) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#D8D8D8',
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Fixed Holidays (national, religious, etc.)' AUTO_INCREMENT=36 ;

--
-- Contenu de la table `codev_holidays_table`
--

INSERT INTO `codev_holidays_table` (`id`, `date`, `description`, `color`) VALUES
(14, 1293836400, 'Reveillon', '#D8D8D8'),
(25, 1335823200, 'fete du travail', '#58CC77'),
(24, 1333922400, 'lundi de paques', '#58CC77'),
(20, 1279058400, '	fete nationale', '#58CC77'),
(21, 1288566000, 'toussaints', '#58CC77'),
(22, 1289430000, 'armistice', '#58CC77'),
(23, 1293231600, 'noel', '#D8D8D8'),
(27, 1304805600, 'victoire 1945', '#D8D8D8'),
(28, 1337205600, 'ascension', '#58CC77'),
(29, 1307916000, 'pentecote', '#58CC77'),
(30, 1342216800, 'fete nationale', '#D8D8D8'),
(31, 1344981600, 'assomption', '#58CC77'),
(32, 1313359200, 'assomption', '#58CC77'),
(33, 1351724400, 'toussaint', '#58CC77'),
(34, 1352588400, 'armistice', '#D8D8D8'),
(35, 1356390000, 'noel', '#58CC77');

-- --------------------------------------------------------

--
-- Structure de la table `codev_job_table`
--

CREATE TABLE IF NOT EXISTS `codev_job_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `type` int(10) NOT NULL DEFAULT '0',
  `color` varchar(7) CHARACTER SET utf8 DEFAULT '#000000',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `codev_job_table`
--

INSERT INTO `codev_job_table` (`id`, `name`, `type`, `color`) VALUES
(1, 'N/A', 1, '#A8FFBD');
-- --------------------------------------------------------

--
-- Structure de la table `codev_project_job_table`
--

CREATE TABLE IF NOT EXISTS `codev_project_job_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `project_id` int(10) NOT NULL,
  `job_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_sidetasks_category_table`
--

CREATE TABLE IF NOT EXISTS `codev_sidetasks_category_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `project_id` int(10) NOT NULL,
  `cat_management` int(10) DEFAULT NULL,
  `cat_incident` int(10) DEFAULT NULL,
  `cat_absence` int(10) DEFAULT NULL,
  `cat_tools` int(11) DEFAULT NULL,
  `cat_workshop` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id` (`project_id`),
  KEY `project_id_2` (`project_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_team_project_table`
--

CREATE TABLE IF NOT EXISTS `codev_team_project_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `project_id` int(10) NOT NULL,
  `team_id` int(10) NOT NULL,
  `type` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_team_table`
--

CREATE TABLE IF NOT EXISTS `codev_team_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(15) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `leader_id` int(10) DEFAULT NULL,
  `date` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_team_user_table`
--

CREATE TABLE IF NOT EXISTS `codev_team_user_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `team_id` int(10) NOT NULL,
  `access_level` int(10) unsigned NOT NULL DEFAULT '10',
  `arrival_date` int(10) unsigned NOT NULL,
  `departure_date` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Structure de la table `codev_timetracking_table`
--

CREATE TABLE IF NOT EXISTS `codev_timetracking_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userid` int(10) NOT NULL,
  `bugid` int(10) NOT NULL,
  `jobid` int(10) NOT NULL,
  `date` int(10) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bugid` (`bugid`),
  KEY `userid` (`userid`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
-- /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
-- /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
