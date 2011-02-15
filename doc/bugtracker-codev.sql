-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Mar 08 Février 2011 à 15:26
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

--
-- Structure de la table `codev_job_table`
--

DROP TABLE IF EXISTS `codev_job_table`;
CREATE TABLE IF NOT EXISTS `codev_job_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `type` int(10) NOT NULL DEFAULT '0',
  `color` varchar(7) DEFAULT '#000000',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=21 ;

--
-- Contenu de la table `codev_job_table`
--

INSERT INTO `codev_job_table` (`id`, `name`, `type`, `color`) VALUES
(1, 'Etude d impact', 0, '#FFCD85'),
(2, 'Analyse de l''existant', 0, '#FFF494'),
(3, 'Developpement', 0, '#C2DFFF'),
(4, 'Tests et Corrections', 0, '#92C5FC'),
(10, 'N/A', 1, '#A8FFBD'),
(18, 'Documentation', 0, '#E0F57A');

-- --------------------------------------------------------

--
-- Structure de la table `codev_project_job_table`
--

DROP TABLE IF EXISTS `codev_project_job_table`;
CREATE TABLE IF NOT EXISTS `codev_project_job_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `project_id` int(10) NOT NULL,
  `job_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Contenu de la table `codev_project_job_table`
--

INSERT INTO `codev_project_job_table` (`id`, `project_id`, `job_id`) VALUES
(1, 11, 10);

-- --------------------------------------------------------

--
-- Structure de la table `codev_team_project_table`
--

DROP TABLE IF EXISTS `codev_team_project_table`;
CREATE TABLE IF NOT EXISTS `codev_team_project_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `project_id` int(10) NOT NULL,
  `team_id` int(10) NOT NULL,
  `type` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=105 ;

--
-- Contenu de la table `codev_team_project_table`
--

INSERT INTO `codev_team_project_table` (`id`, `project_id`, `team_id`, `type`) VALUES
(1, 1, 1, 0),
(2, 2, 1, 0),
(3, 3, 1, 0),
(4, 4, 1, 0),
(5, 5, 1, 0),
(6, 6, 1, 0),
(7, 12, 1, 0),
(8, 15, 1, 0),
(9, 11, 1, 1),
(102, 6, 6, 0),
(101, 19, 6, 0),
(14, 16, 1, 0),
(36, 18, 6, 0),
(35, 18, 1, 0),
(103, 20, 6, 0),
(25, 11, 6, 1),
(24, 17, 6, 0),
(104, 5, 6, 0),
(51, 11, 21, 1),
(66, 11, 23, 1),
(34, 11, 3, 1),
(67, 1, 23, 0),
(68, 2, 23, 0),
(69, 12, 23, 0),
(70, 15, 23, 0),
(71, 4, 23, 0),
(72, 6, 23, 0),
(73, 3, 23, 0),
(74, 5, 23, 0),
(75, 16, 23, 0),
(76, 11, 24, 1),
(77, 11, 25, 1),
(78, 2, 6, 0),
(79, 1, 6, 0),
(80, 16, 6, 0),
(81, 11, 26, 1),
(82, 3, 6, 0),
(86, 2, 26, 0),
(85, 1, 26, 0),
(87, 12, 26, 0),
(88, 15, 26, 0),
(89, 4, 26, 0),
(90, 6, 26, 0),
(91, 3, 26, 0),
(92, 5, 26, 0),
(93, 16, 26, 0),
(94, 17, 26, 0),
(98, 20, 1, 0),
(99, 20, 26, 0),
(100, 19, 26, 0);

-- --------------------------------------------------------

--
-- Structure de la table `codev_team_table`
--

DROP TABLE IF EXISTS `codev_team_table`;
CREATE TABLE IF NOT EXISTS `codev_team_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(15) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `leader_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=29 ;

--
-- Contenu de la table `codev_team_table`
--

INSERT INTO `codev_team_table` (`id`, `name`, `description`, `leader_id`) VALUES
(1, 'atos', 'Equipe Atos Aix', 2),
(3, 'admin', 'Administrateurs CoDev', 7),
(21, 'FDJ', 'FDJ', 12),
(23, 'OLTP', 'Atos CODEV OLTP', 2),
(6, 'atos_tests', 'Equipe Atos compementaire', 21),
(24, 'Stats', 'Atos CODEV Statistiques', 9),
(25, 'GEMS', 'Atos CODEV GEMS', 10),
(26, 'CoDev_Atos', 'Equipe CoDev ATOS', 22);

-- --------------------------------------------------------

--
-- Structure de la table `codev_team_user_table`
--

DROP TABLE IF EXISTS `codev_team_user_table`;
CREATE TABLE IF NOT EXISTS `codev_team_user_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `team_id` int(10) NOT NULL,
  `access_level` int(10) unsigned NOT NULL DEFAULT '10',
  `arrival_date` int(10) unsigned NOT NULL,
  `departure_date` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=107 ;

--
-- Contenu de la table `codev_team_user_table`
--

INSERT INTO `codev_team_user_table` (`id`, `user_id`, `team_id`, `access_level`, `arrival_date`, `departure_date`) VALUES
(1, 2, 1, 10, 1275256800, 0),
(2, 5, 1, 10, 1275256800, 0),
(3, 6, 1, 10, 1275256800, 1286488800),
(4, 7, 1, 10, 1275256800, 0),
(34, 8, 1, 10, 1275256800, 1285279200),
(6, 9, 1, 10, 1275256800, 0),
(12, 7, 3, 10, 1275256800, 0),
(8, 11, 1, 10, 1275256800, 0),
(41, 3, 21, 10, 1275256800, 0),
(40, 4, 21, 10, 1275256800, 0),
(39, 14, 21, 10, 1275256800, 0),
(38, 17, 21, 10, 1275256800, 0),
(37, 15, 21, 10, 1275256800, 0),
(42, 12, 21, 10, 1275256800, 0),
(21, 16, 6, 10, 1278540000, 0),
(22, 1, 3, 10, 1278540000, 0),
(31, 19, 1, 10, 1284328800, 0),
(33, 18, 1, 10, 1284328800, 1295564400),
(44, 12, 1, 20, 1275256800, 0),
(47, 20, 1, 10, 1288652400, 0),
(105, 7, 6, 20, 1297119600, 0),
(50, 21, 6, 10, 1290034800, 0),
(56, 22, 1, 30, 1289862000, 0),
(52, 22, 6, 30, 1289862000, 0),
(53, 10, 1, 10, 1291590000, 0),
(57, 7, 23, 10, 1291590000, 0),
(58, 5, 23, 10, 1291590000, 0),
(59, 19, 23, 10, 1291590000, 0),
(60, 18, 23, 10, 1291590000, 1295564400),
(61, 20, 23, 10, 1291590000, 0),
(62, 9, 24, 10, 1291590000, 0),
(63, 2, 23, 10, 1291590000, 0),
(64, 10, 25, 10, 1291590000, 0),
(65, 11, 25, 10, 1291590000, 0),
(66, 9, 25, 10, 1291590000, 0),
(67, 22, 25, 30, 1291590000, 0),
(68, 22, 23, 30, 1291590000, 0),
(69, 22, 24, 30, 1291590000, 0),
(84, 9, 26, 10, 1275256800, 0),
(95, 16, 26, 10, 1278540000, 0),
(85, 19, 26, 10, 1284328800, 0),
(86, 18, 26, 10, 1284328800, 1295564400),
(87, 20, 26, 10, 1288652400, 0),
(88, 7, 26, 10, 1275256800, 0),
(96, 22, 26, 30, 1289862000, 0),
(90, 10, 26, 10, 1291590000, 0),
(91, 2, 26, 10, 1275256800, 0),
(92, 11, 26, 10, 1275256800, 0),
(93, 5, 26, 10, 1275256800, 0),
(94, 21, 26, 10, 1290034800, 0),
(106, 2, 3, 10, 1297119600, 0),
(99, 8, 26, 10, 1275256800, 1285279200),
(100, 6, 26, 10, 1275256800, 1286488800),
(101, 12, 26, 20, 1275256800, 0);

-- --------------------------------------------------------

--
-- Structure de la table `codev_timetracking_table`
--

DROP TABLE IF EXISTS `codev_timetracking_table`;
CREATE TABLE IF NOT EXISTS `codev_timetracking_table` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userid` int(10) NOT NULL,
  `bugid` int(10) NOT NULL,
  `jobid` int(10) NOT NULL,
  `date` int(10) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3436 ;

--
-- Contenu de la table `codev_timetracking_table`
--

-- INSERT INTO `codev_timetracking_table` (`id`, `userid`, `bugid`, `jobid`, `date`, `duration`) VALUES
-- (26, 7, 52, 10, 1275429600, 1),
-- (3434, 5, 62, 10, 1297119600, 0.2);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_custom_field_table`
--

-- DROP TABLE IF EXISTS `mantis_custom_field_table`;
-- CREATE TABLE IF NOT EXISTS `mantis_custom_field_table` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `name` varchar(64) NOT NULL DEFAULT '',
--   `type` smallint(6) NOT NULL DEFAULT '0',
--   `possible_values` text NOT NULL,
--   `default_value` varchar(255) NOT NULL DEFAULT '',
--   `valid_regexp` varchar(255) NOT NULL DEFAULT '',
--   `access_level_r` smallint(6) NOT NULL DEFAULT '0',
--   `access_level_rw` smallint(6) NOT NULL DEFAULT '0',
--   `length_min` int(11) NOT NULL DEFAULT '0',
--   `length_max` int(11) NOT NULL DEFAULT '0',
--   `require_report` tinyint(4) NOT NULL DEFAULT '0',
--   `require_update` tinyint(4) NOT NULL DEFAULT '0',
--   `display_report` tinyint(4) NOT NULL DEFAULT '0',
--   `display_update` tinyint(4) NOT NULL DEFAULT '1',
--   `require_resolved` tinyint(4) NOT NULL DEFAULT '0',
--   `display_resolved` tinyint(4) NOT NULL DEFAULT '0',
--   `display_closed` tinyint(4) NOT NULL DEFAULT '0',
--   `require_closed` tinyint(4) NOT NULL DEFAULT '0',
--   `filter_by` tinyint(4) NOT NULL DEFAULT '1',
--   PRIMARY KEY (`id`),
--   KEY `idx_custom_field_name` (`name`)
-- ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

--
-- Contenu de la table `mantis_custom_field_table`
--

INSERT INTO `mantis_custom_field_table` (`id`, `name`, `type`, `possible_values`, `default_value`, `valid_regexp`, `access_level_r`, `access_level_rw`, `length_min`, `length_max`, `require_report`, `require_update`, `display_report`, `display_update`, `require_resolved`, `display_resolved`, `display_closed`, `require_closed`, `filter_by`) VALUES
(1, 'TC', 0, '', '', '(tcp1b[12]_)?[0-9]{1,5}', 10, 25, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 1),
(2, 'Release', 3, '(select)|GEMS/1.5.1|GEMS/1.6|GEMS/1.7|P1012_GEMS/1.8|P1103_GEMS/1.9|P1106_GEMS/1.9.2|P1106_GEMS/1.10|OLTP/3.5.0|OLTP/3.6.0|OLTP/3.7.0|P1012_OLTP/3.8.0|P1106_OLTP/3.9.0|P1103_OLTP/3.9.1|P1106_OLTP/3.9.2|P1109_OLTP/3.10.0|JTR_SSCC/1.10|OLTP_Prod', '(select)', '', 10, 25, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1),
(3, 'Est. Effort (BI)', 1, '', '', '', 10, 40, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 1),
(4, 'Remaining (RAE)', 1, '', '', '', 10, 40, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 1),
(8, 'Dead Line', 8, '', '', '', 10, 55, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1),
(9, 'FDL', 1, '', '', '', 10, 55, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1),
(10, 'Budget supp. (BS)', 1, '', '', '', 10, 55, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1),
(11, 'Liv. Date', 8, '', '', '', 10, 55, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_table`
--

-- DROP TABLE IF EXISTS `mantis_project_table`;
-- CREATE TABLE IF NOT EXISTS `mantis_project_table` (
--   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `name` varchar(128) NOT NULL DEFAULT '',
--   `status` smallint(6) NOT NULL DEFAULT '10',
--   `enabled` tinyint(4) NOT NULL DEFAULT '1',
--   `view_state` smallint(6) NOT NULL DEFAULT '10',
--   `access_min` smallint(6) NOT NULL DEFAULT '10',
--   `file_path` varchar(250) NOT NULL DEFAULT '',
--   `description` longtext NOT NULL,
--   `category_id` int(10) unsigned NOT NULL DEFAULT '1',
--   `inherit_global` int(10) unsigned NOT NULL DEFAULT '0',
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `idx_project_name` (`name`),
--   KEY `idx_project_view` (`view_state`)
-- ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

--
-- Contenu de la table `mantis_project_table`
--

INSERT INTO `mantis_project_table` (`id`, `name`, `status`, `enabled`, `view_state`, `access_min`, `file_path`, `description`, `category_id`, `inherit_global`) VALUES
(1, 'ARIANE', 10, 1, 10, 10, '', 'Carte joueur.', 1, 1),
(2, 'Cappuccino', 10, 1, 10, 10, '', 'Nouveau Rapido+ (Amigo).', 1, 1),
(3, 'PeterPan', 10, 1, 10, 10, '', 'Nouveau Joker+.', 1, 1),
(4, 'LotoHum', 10, 0, 10, 10, '', 'Loto de la solidarité.', 1, 1),
(5, 'Promulgation', 10, 1, 10, 10, '', 'Standardisation Diffusion Promulgation.', 1, 1),
(6, 'Maintenance', 10, 1, 10, 10, '', 'Evolutions, Maintenance SJP.', 1, 1),
(17, 'Test', 30, 1, 10, 10, '', 'Ensemble des activitées liées aux tests.', 1, 1),
(18, 'FDL', 30, 0, 10, 10, '', 'Fiches de livraisons.', 1, 1),
(11, 'Suivi Op.', 50, 1, 10, 10, '', 'Ensemble des activitées liées au suivi opérationel.', 1, 0),
(12, 'CAYMAN', 10, 0, 10, 10, '', 'Afficheur Point de Vente.', 1, 1),
(16, 'ROMA', 10, 1, 10, 10, '', 'Evolutions Euromillions.', 1, 1),
(15, 'LotoFoot', 10, 1, 10, 10, '', 'Suppression N Mise LF7&15.', 1, 1),
(19, 'Amigo', 10, 1, 10, 10, '', 'Cappuccino multiple.', 1, 1),
(20, 'Promo', 10, 1, 10, 10, '', 'Mécanismes promotionnels temps réels.', 1, 1),
(21, 'Sport', 10, 1, 10, 10, '', 'Extension offre sport réseau.', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_project_user_list_table`
--

-- DROP TABLE IF EXISTS `mantis_project_user_list_table`;
-- CREATE TABLE IF NOT EXISTS `mantis_project_user_list_table` (
--   `project_id` int(10) unsigned NOT NULL DEFAULT '0',
--   `user_id` int(10) unsigned NOT NULL DEFAULT '0',
--   `access_level` smallint(6) NOT NULL DEFAULT '10',
--   PRIMARY KEY (`project_id`,`user_id`),
--   KEY `idx_project_user` (`user_id`)
-- ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Contenu de la table `mantis_project_user_list_table`
--

INSERT INTO `mantis_project_user_list_table` (`project_id`, `user_id`, `access_level`) VALUES
(1, 16, 55),
(2, 16, 55),
(12, 16, 55),
(15, 16, 55),
(4, 16, 55),
(6, 16, 55),
(3, 16, 55),
(5, 16, 55),
(11, 16, 55),
(1, 18, 55),
(2, 18, 55),
(12, 18, 55),
(18, 18, 55),
(15, 18, 55),
(4, 18, 55),
(6, 18, 55),
(3, 18, 55),
(5, 18, 55),
(16, 18, 55),
(11, 18, 55),
(1, 19, 55),
(2, 19, 55),
(12, 19, 55),
(18, 19, 55),
(15, 19, 55),
(4, 19, 55),
(6, 19, 55),
(3, 19, 55),
(5, 19, 55),
(16, 19, 55),
(11, 19, 55),
(1, 20, 55),
(2, 20, 55),
(12, 20, 55),
(18, 20, 55),
(15, 20, 55),
(4, 20, 55),
(6, 20, 55),
(3, 20, 55),
(5, 20, 55),
(16, 20, 55),
(11, 20, 55),
(1, 21, 55),
(2, 21, 55),
(12, 21, 55),
(15, 21, 55),
(4, 21, 55),
(6, 21, 55),
(3, 21, 55),
(5, 21, 55),
(16, 21, 55),
(11, 21, 55),
(17, 16, 55),
(17, 21, 55);

-- --------------------------------------------------------

--
-- Structure de la table `mantis_user_table`
--

-- DROP TABLE IF EXISTS `mantis_user_table`;
-- CREATE TABLE IF NOT EXISTS `mantis_user_table` (
--   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `username` varchar(32) NOT NULL DEFAULT '',
--   `realname` varchar(64) NOT NULL DEFAULT '',
--   `email` varchar(64) NOT NULL DEFAULT '',
--   `password` varchar(32) NOT NULL DEFAULT '',
--   `enabled` tinyint(4) NOT NULL DEFAULT '1',
--   `protected` tinyint(4) NOT NULL DEFAULT '0',
--   `access_level` smallint(6) NOT NULL DEFAULT '10',
--   `login_count` int(11) NOT NULL DEFAULT '0',
--   `lost_password_request_count` smallint(6) NOT NULL DEFAULT '0',
--   `failed_login_count` smallint(6) NOT NULL DEFAULT '0',
--   `cookie_string` varchar(64) NOT NULL DEFAULT '',
--   `last_visit` int(10) unsigned NOT NULL DEFAULT '1',
--   `date_created` int(10) unsigned NOT NULL DEFAULT '1',
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `idx_user_cookie_string` (`cookie_string`),
--   UNIQUE KEY `idx_user_username` (`username`),
--   KEY `idx_enable` (`enabled`),
--   KEY `idx_access` (`access_level`)
-- ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

--
-- Contenu de la table `mantis_user_table`
--

INSERT INTO `mantis_user_table` (`id`, `username`, `realname`, `email`, `password`, `enabled`, `protected`, `access_level`, `login_count`, `lost_password_request_count`, `failed_login_count`, `cookie_string`, `last_visit`, `date_created`) VALUES
(1, 'administrator', 'administrator', 'root@localhost', '63a9f0ea7bb98050796b649e85481845', 1, 0, 90, 57, 0, 0, '2eaf0f02c475b359318a87988342c3c32fc634ac03eb8dfaa24ec99ef8046214', 1297163850, 1272555040),
(2, 'mnavarro', 'Mikaël NAVARRO', 'mikael.navarro@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 70, 240, 0, 0, '11cabc1f68e9f33c5cc44be68ad7f31897bfe84d77dbfdd0d07d06007cee886f', 1297175150, 1272609634),
(3, 'qualif', 'Qualification', 'mosi@lfdj.fr', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 25, 130, 0, 0, 'e2d3d4a08d60c35c322ff71130a5e43743173f3de6979e2256878e7ae7aab689', 1296659939, 1272610523),
(4, 'preq', 'PreQual', 'preq@lfdj.fr', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 25, 25, 0, 0, 'c3de616cddf08ced072f09a0eab293408854d073c75eaa1f2dcadfc6e0a991f1', 1294306975, 1272610558),
(5, 'sberal', 'Sébastien BERAL', 'sebastien.beral@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 55, 8, 0, 0, '76aa39c680026f4c401e052699286bc5538f25fb5a752502f7ee016de25dad64', 1297173798, 1272610576),
(6, 'vcastelin', 'Vincent CASTELIN', 'vincent.castelin@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 55, 44, 0, 0, 'a51edcc9a9846fdea46fa06f5e8bef4033e4f4fa1f3162904a44afb2f2fab084', 1285597326, 1272610608),
(7, 'lbayle', 'Louis BAYLE', 'lbayle.work@gmail.com', 'caf671ca0104ebfca4cd4b6f4b345e8e', 1, 0, 55, 43, 0, 0, 'f10234a5a2a8eee74e4a44f82fec7a9c8461efea4c2f77030b4a9d08051b88d7', 1297163821, 1272610636),
(8, 'cmaruejols', 'Christophe MARUEJOLS', 'christophe.maruejols@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 55, 28, 0, 0, '26d616184948cf6d3530de8ba53fec739213325b38ed738d1f1e5fbf0c9bc8b1', 1284542987, 1272610681),
(9, 'afebvre', 'Anne FEBVRE', 'anne.febvre@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 70, 74, 0, 0, '464319d0c4e9a12a95836ac3335d94da0cb9a3d32b96175643983f83519325e3', 1297093598, 1272610824),
(10, 'mdoan', 'Marie DOAN', 'marie.doan@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 70, 10, 0, 0, '71a5b5f6087385d8c2053927e7c52ec61501f5540b4037358834c133717a5433', 1297173854, 1272610890),
(11, 'nladraa', 'Nadia LADRAA', 'nadia.ladraa@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 55, 50, 0, 0, '08db035be4a8389fabfd3cb0d2cd6ef693f7a3fd55c7e7563d0364cb4798fd08', 1297152704, 1272610921),
(12, 'golivier', 'Gisèle OLIVIER', 'golivier@lfdj.fr', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 25, 33, 0, 0, '5403f2e529dd027e20097d7940426851a3486f593c903952db77a94a2b61dbfa', 1296808929, 1272610952),
(13, 'codev', 'CoDev', 'codev-atos@lfdj.fr', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 90, 130, 0, 0, '1df9e6d3be53458cb27290265f204d93a1a6b233624860466a3975c11a292b22', 1297070405, 1272611005),
(14, 'ogueneau', 'Olivier GUENEAU', 'ogueneau@lfdj.fr', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 40, 11, 0, 0, 'e741de824bed998a1c47e4f12d71a151cb729f0256553ebc39af26631c729bbc', 1294648654, 1272613873),
(15, 'fdj', 'FdJ', 'fdj@lfdj.fr', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 40, 0, 0, 0, 'd209db61f82e9f412d606f823c81bb13b3679e465b81e6c32aad076fa893a07e', 1272619824, 1272619824),
(16, 'cpatin', 'Carole PATIN', 'carole.patin@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 55, 19, 0, 0, '9e6f44c13a312b84fc53ddca867ddfe1b0ba47405b73bee6173766ef04443bda', 1297156050, 1278570124),
(17, 'ktan', 'Karter TAN', 'ktan@lfdj.fr', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 25, 11, 0, 0, 'd7ac61539229d09f5d80aa7886c5de4d3e0933bfc7ceb37104fc9e9c43a9301d', 1294839655, 1280836945),
(18, 'jjulien', 'Jerôme JULIEN', 'jerome.julien@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 55, 54, 0, 0, 'bc0e96ed1f1cebf863f284ac53e59b147168aaed411d880d71e4551855ade549', 1295620236, 1284363725),
(19, 'jbaldaccini', 'Jessica BALDACCINI', 'jessica.baldaccini@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 55, 43, 0, 0, 'bba760e39d75ed9ea5484e94dbc537d92a7ece826bc810d861fbbaf4485cfef5', 1297156747, 1284363879),
(20, 'lachaibou', 'Lyna ACHAIBOU', 'lyna.achaibou@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 55, 50, 0, 0, '1f28b703895404d6eb00ff6d512ee13a91102671069215c21d61126803ca2dbb', 1297097781, 1284364052),
(21, 'tuzieblo', 'Tomasz UZIEBLO', 'tomasz.uzieblo@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 55, 29, 0, 0, 'cc2162a91a787c1998e720fdad3d051148b18227ae253ccc22e39ffadfebfdc6', 1297156240, 1290073340),
(22, 'mbastide', 'Marie BASTIDE', 'marie.bastide@atosorigin.com', 'd41d8cd98f00b204e9800998ecf8427e', 1, 0, 70, 38, 0, 0, '5b9d06a658c4dc21fac7b494b79b505155dd0bc8f87de8d0e3f0817d90df1bfe', 1297175040, 1290517904);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
