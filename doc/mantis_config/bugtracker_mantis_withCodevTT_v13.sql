-- MySQL dump 10.13  Distrib 5.6.20, for Win32 (x86)
--
-- Host: localhost    Database: bugtracker
-- ------------------------------------------------------
-- Server version	5.6.20

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `codev_blog_activity_table`
--

DROP TABLE IF EXISTS `codev_blog_activity_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_blog_activity_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Wall activity';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_blog_activity_table`
--

LOCK TABLES `codev_blog_activity_table` WRITE;
/*!40000 ALTER TABLE `codev_blog_activity_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_blog_activity_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_blog_table`
--

DROP TABLE IF EXISTS `codev_blog_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_blog_table` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Wall posts';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_blog_table`
--

LOCK TABLES `codev_blog_table` WRITE;
/*!40000 ALTER TABLE `codev_blog_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_blog_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_command_bug_table`
--

DROP TABLE IF EXISTS `codev_command_bug_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_command_bug_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `command_id` int(11) NOT NULL,
  `bug_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `command_id` (`command_id`),
  KEY `bug_id` (`bug_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_command_bug_table`
--

LOCK TABLES `codev_command_bug_table` WRITE;
/*!40000 ALTER TABLE `codev_command_bug_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_command_bug_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_command_provision_table`
--

DROP TABLE IF EXISTS `codev_command_provision_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_command_provision_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(11) unsigned NOT NULL,
  `command_id` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `summary` varchar(128) NOT NULL,
  `budget_days` int(11) DEFAULT NULL,
  `budget` int(11) DEFAULT NULL,
  `average_daily_rate` int(11) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `is_in_check_budget` tinyint(4) NOT NULL DEFAULT '0',
  `description` longtext,
  PRIMARY KEY (`id`),
  KEY `command_id` (`command_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_command_provision_table`
--

LOCK TABLES `codev_command_provision_table` WRITE;
/*!40000 ALTER TABLE `codev_command_provision_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_command_provision_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_command_table`
--

DROP TABLE IF EXISTS `codev_command_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_command_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
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
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_command_table`
--

LOCK TABLES `codev_command_table` WRITE;
/*!40000 ALTER TABLE `codev_command_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_command_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_commandset_cmd_table`
--

DROP TABLE IF EXISTS `codev_commandset_cmd_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_commandset_cmd_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `commandset_id` int(11) NOT NULL,
  `command_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_commandset_cmd_table`
--

LOCK TABLES `codev_commandset_cmd_table` WRITE;
/*!40000 ALTER TABLE `codev_commandset_cmd_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_commandset_cmd_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_commandset_table`
--

DROP TABLE IF EXISTS `codev_commandset_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_commandset_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `date` int(11) unsigned DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `state` int(11) unsigned DEFAULT NULL,
  `budget` int(11) DEFAULT NULL,
  `budget_days` int(11) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_commandset_table`
--

LOCK TABLES `codev_commandset_table` WRITE;
/*!40000 ALTER TABLE `codev_commandset_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_commandset_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_config_table`
--

DROP TABLE IF EXISTS `codev_config_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_config_table` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_config_table`
--

LOCK TABLES `codev_config_table` WRITE;
/*!40000 ALTER TABLE `codev_config_table` DISABLE KEYS */;
INSERT INTO `codev_config_table` VALUES ('database_version','13',1,0,0,0,0,0,0,NULL,NULL),('blogCategories','1:General,2:Imputations',3,0,0,0,0,0,0,NULL,NULL),('bug_resolved_status_threshold','80',1,0,0,0,0,0,0,NULL,'bug resolved threshold as defined in Mantis (g_bug_resolved_status_threshold)'),('client_teamid','',1,0,0,0,0,0,0,NULL,'teamId du client'),('customField_ExtId','1',1,0,0,0,0,0,0,NULL,''),('customField_effortEstim','2',1,0,0,0,0,0,0,NULL,''),('customField_type','3',1,0,0,0,0,0,0,NULL,''),('customField_MgrEffortEstim','4',1,0,0,0,0,0,0,NULL,''),('customField_deadLine','5',1,0,0,0,0,0,0,NULL,''),('customField_addEffort','6',1,0,0,0,0,0,0,NULL,''),('customField_backlog','7',1,0,0,0,0,0,0,NULL,''),('customField_deliveryDate','8',1,0,0,0,0,0,0,NULL,''),('externalTasksProject','4',1,0,0,0,0,0,0,NULL,'CodevTT Projet de taches externes'),('externalTasksCat_leave','5',1,0,0,0,0,0,0,NULL,''),('externalTasksCat_otherInternal','6',1,0,0,0,0,0,0,NULL,''),('adminTeamId','1',1,0,0,0,0,0,0,NULL,''),('issue_tooltip_fields','a:6:{i:0;s:10:\"project_id\";i:1;s:11:\"category_id\";i:2;s:8:\"custom_3\";i:3;s:15:\"codevtt_elapsed\";i:4;s:8:\"custom_7\";i:5;s:13:\"codevtt_drift\";}',2,0,0,0,0,0,0,NULL,'fields to be displayed in issue tooltip');
/*!40000 ALTER TABLE `codev_config_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_holidays_table`
--

DROP TABLE IF EXISTS `codev_holidays_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_holidays_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` int(11) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT 'D8D8D8',
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=MyISAM AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COMMENT='Fixed Holidays (national, religious, etc.)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_holidays_table`
--

LOCK TABLES `codev_holidays_table` WRITE;
/*!40000 ALTER TABLE `codev_holidays_table` DISABLE KEYS */;
INSERT INTO `codev_holidays_table` VALUES (14,1293836400,'Reveillon','D8D8D8'),(25,1335823200,'fete du travail','58CC77'),(24,1333922400,'lundi de paques','58CC77'),(20,1279058400,'fete nationale','58CC77'),(21,1288566000,'toussaints','58CC77'),(22,1289430000,'armistice','58CC77'),(23,1293231600,'noel','D8D8D8'),(27,1304805600,'victoire 1945','D8D8D8'),(28,1337205600,'ascension','58CC77'),(29,1307916000,'pentecote','58CC77'),(30,1342216800,'fete nationale','D8D8D8'),(31,1344981600,'assomption','58CC77'),(32,1313359200,'assomption','58CC77'),(33,1351724400,'toussaint','58CC77'),(34,1352588400,'armistice','D8D8D8'),(35,1356390000,'noel','58CC77');
/*!40000 ALTER TABLE `codev_holidays_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_job_table`
--

DROP TABLE IF EXISTS `codev_job_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_job_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `color` varchar(7) DEFAULT '000000',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_job_table`
--

LOCK TABLES `codev_job_table` WRITE;
/*!40000 ALTER TABLE `codev_job_table` DISABLE KEYS */;
INSERT INTO `codev_job_table` VALUES (1,'N/A',1,'A8FFBD'),(2,'Support',0,'A8FFBD'),(3,'Analyse',0,'FFCD85'),(4,'Dévelopement',0,'C2DFFF'),(5,'Tests',0,'92C5FC'),(6,'Documentation',0,'E0F57A');
/*!40000 ALTER TABLE `codev_job_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_project_category_table`
--

DROP TABLE IF EXISTS `codev_project_category_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_project_category_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`,`project_id`,`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_project_category_table`
--

LOCK TABLES `codev_project_category_table` WRITE;
/*!40000 ALTER TABLE `codev_project_category_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_project_category_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_project_job_table`
--

DROP TABLE IF EXISTS `codev_project_job_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_project_job_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_project_job_table`
--

LOCK TABLES `codev_project_job_table` WRITE;
/*!40000 ALTER TABLE `codev_project_job_table` DISABLE KEYS */;
INSERT INTO `codev_project_job_table` VALUES (1,4,1);
/*!40000 ALTER TABLE `codev_project_job_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_servicecontract_cmdset_table`
--

DROP TABLE IF EXISTS `codev_servicecontract_cmdset_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_servicecontract_cmdset_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `servicecontract_id` int(11) NOT NULL,
  `commandset_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_servicecontract_cmdset_table`
--

LOCK TABLES `codev_servicecontract_cmdset_table` WRITE;
/*!40000 ALTER TABLE `codev_servicecontract_cmdset_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_servicecontract_cmdset_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_servicecontract_stproj_table`
--

DROP TABLE IF EXISTS `codev_servicecontract_stproj_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_servicecontract_stproj_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `servicecontract_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_servicecontract_stproj_table`
--

LOCK TABLES `codev_servicecontract_stproj_table` WRITE;
/*!40000 ALTER TABLE `codev_servicecontract_stproj_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_servicecontract_stproj_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_servicecontract_table`
--

DROP TABLE IF EXISTS `codev_servicecontract_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_servicecontract_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `reference` varchar(64) DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  `state` int(11) unsigned DEFAULT NULL,
  `version` varchar(64) DEFAULT NULL,
  `reporter` varchar(64) DEFAULT NULL,
  `start_date` int(11) unsigned DEFAULT NULL,
  `end_date` int(11) unsigned DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_servicecontract_table`
--

LOCK TABLES `codev_servicecontract_table` WRITE;
/*!40000 ALTER TABLE `codev_servicecontract_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_servicecontract_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_sidetasks_category_table`
--

DROP TABLE IF EXISTS `codev_sidetasks_category_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_sidetasks_category_table` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_sidetasks_category_table`
--

LOCK TABLES `codev_sidetasks_category_table` WRITE;
/*!40000 ALTER TABLE `codev_sidetasks_category_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_sidetasks_category_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_team_project_table`
--

DROP TABLE IF EXISTS `codev_team_project_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_team_project_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `team_id` (`team_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_team_project_table`
--

LOCK TABLES `codev_team_project_table` WRITE;
/*!40000 ALTER TABLE `codev_team_project_table` DISABLE KEYS */;
INSERT INTO `codev_team_project_table` VALUES (1,4,1,3);
/*!40000 ALTER TABLE `codev_team_project_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_team_table`
--

DROP TABLE IF EXISTS `codev_team_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_team_table` (
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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_team_table`
--

LOCK TABLES `codev_team_table` WRITE;
/*!40000 ALTER TABLE `codev_team_table` DISABLE KEYS */;
INSERT INTO `codev_team_table` VALUES (1,'CodevTT admin','Equipe d\'administration CodevTT',1,0,1,1438984800,NULL);
/*!40000 ALTER TABLE `codev_team_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_team_user_table`
--

DROP TABLE IF EXISTS `codev_team_user_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_team_user_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `access_level` int(11) unsigned NOT NULL DEFAULT '10',
  `arrival_date` int(11) unsigned NOT NULL,
  `departure_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_team_user_table`
--

LOCK TABLES `codev_team_user_table` WRITE;
/*!40000 ALTER TABLE `codev_team_user_table` DISABLE KEYS */;
INSERT INTO `codev_team_user_table` VALUES (1,1,1,10,1438984800,0);
/*!40000 ALTER TABLE `codev_team_user_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_timetracking_table`
--

DROP TABLE IF EXISTS `codev_timetracking_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_timetracking_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `bugid` int(11) NOT NULL,
  `jobid` int(11) NOT NULL,
  `date` int(11) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bugid` (`bugid`),
  KEY `userid` (`userid`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_timetracking_table`
--

LOCK TABLES `codev_timetracking_table` WRITE;
/*!40000 ALTER TABLE `codev_timetracking_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_timetracking_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `codev_wbs_table`
--

DROP TABLE IF EXISTS `codev_wbs_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codev_wbs_table` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `root_id` int(11) unsigned DEFAULT NULL,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `order` int(11) NOT NULL,
  `bug_id` int(11) DEFAULT NULL,
  `expand` tinyint(4) NOT NULL DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `icon` varchar(64) DEFAULT NULL,
  `font` varchar(64) DEFAULT NULL,
  `color` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bug_id` (`bug_id`),
  KEY `parent_id` (`parent_id`),
  KEY `order` (`order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `codev_wbs_table`
--

LOCK TABLES `codev_wbs_table` WRITE;
/*!40000 ALTER TABLE `codev_wbs_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `codev_wbs_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bug_file_table`
--

DROP TABLE IF EXISTS `mantis_bug_file_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bug_file_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `description` varchar(250) NOT NULL DEFAULT '',
  `diskfile` varchar(250) NOT NULL DEFAULT '',
  `filename` varchar(250) NOT NULL DEFAULT '',
  `folder` varchar(250) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL DEFAULT '0',
  `file_type` varchar(250) NOT NULL DEFAULT '',
  `content` longblob NOT NULL,
  `date_added` int(10) unsigned NOT NULL DEFAULT '1',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_bug_file_bug_id` (`bug_id`),
  KEY `idx_diskfile` (`diskfile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bug_file_table`
--

LOCK TABLES `mantis_bug_file_table` WRITE;
/*!40000 ALTER TABLE `mantis_bug_file_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_bug_file_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bug_history_table`
--

DROP TABLE IF EXISTS `mantis_bug_history_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bug_history_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_name` varchar(64) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `type` smallint(6) NOT NULL DEFAULT '0',
  `date_modified` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_bug_history_bug_id` (`bug_id`),
  KEY `idx_history_user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bug_history_table`
--

LOCK TABLES `mantis_bug_history_table` WRITE;
/*!40000 ALTER TABLE `mantis_bug_history_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_bug_history_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bug_monitor_table`
--

DROP TABLE IF EXISTS `mantis_bug_monitor_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bug_monitor_table` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`bug_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bug_monitor_table`
--

LOCK TABLES `mantis_bug_monitor_table` WRITE;
/*!40000 ALTER TABLE `mantis_bug_monitor_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_bug_monitor_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bug_relationship_table`
--

DROP TABLE IF EXISTS `mantis_bug_relationship_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bug_relationship_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `destination_bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `relationship_type` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_relationship_source` (`source_bug_id`),
  KEY `idx_relationship_destination` (`destination_bug_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bug_relationship_table`
--

LOCK TABLES `mantis_bug_relationship_table` WRITE;
/*!40000 ALTER TABLE `mantis_bug_relationship_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_bug_relationship_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bug_revision_table`
--

DROP TABLE IF EXISTS `mantis_bug_revision_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bug_revision_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bug_id` int(10) unsigned NOT NULL,
  `bugnote_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL,
  `type` int(10) unsigned NOT NULL,
  `value` longtext NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_bug_rev_type` (`type`),
  KEY `idx_bug_rev_id_time` (`bug_id`,`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bug_revision_table`
--

LOCK TABLES `mantis_bug_revision_table` WRITE;
/*!40000 ALTER TABLE `mantis_bug_revision_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_bug_revision_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bug_table`
--

DROP TABLE IF EXISTS `mantis_bug_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bug_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `last_updated` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_bug_sponsorship_total` (`sponsorship_total`),
  KEY `idx_bug_fixed_in_version` (`fixed_in_version`),
  KEY `idx_bug_status` (`status`),
  KEY `idx_project` (`project_id`),
  KEY `handler_id` (`handler_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bug_table`
--

LOCK TABLES `mantis_bug_table` WRITE;
/*!40000 ALTER TABLE `mantis_bug_table` DISABLE KEYS */;
INSERT INTO `mantis_bug_table` VALUES (1,4,0,0,0,10,50,100,90,10,10,10,1,'','','','','','',0,10,'autre activité',0,0,'',6,1438984800,1,1438984800),(2,4,0,0,0,10,50,100,90,10,10,10,2,'','','','','','',0,10,'Absence',0,0,'',5,1438984800,1,1438984800),(3,4,0,0,0,10,50,100,90,10,10,10,3,'','','','','','',0,10,'Maladie',0,0,'',5,1438984800,1,1438984800);
/*!40000 ALTER TABLE `mantis_bug_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bug_tag_table`
--

DROP TABLE IF EXISTS `mantis_bug_tag_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bug_tag_table` (
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `tag_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_attached` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`bug_id`,`tag_id`),
  KEY `idx_bug_tag_tag_id` (`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bug_tag_table`
--

LOCK TABLES `mantis_bug_tag_table` WRITE;
/*!40000 ALTER TABLE `mantis_bug_tag_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_bug_tag_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bug_text_table`
--

DROP TABLE IF EXISTS `mantis_bug_text_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bug_text_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `description` longtext NOT NULL,
  `steps_to_reproduce` longtext NOT NULL,
  `additional_information` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bug_text_table`
--

LOCK TABLES `mantis_bug_text_table` WRITE;
/*!40000 ALTER TABLE `mantis_bug_text_table` DISABLE KEYS */;
INSERT INTO `mantis_bug_text_table` VALUES (1,'Toutes activités non référencées dans un projet mantis','',''),(2,'Congé, Maladie, ...','',''),(3,'Maladie','','');
/*!40000 ALTER TABLE `mantis_bug_text_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bugnote_table`
--

DROP TABLE IF EXISTS `mantis_bugnote_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bugnote_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bug_id` int(10) unsigned NOT NULL DEFAULT '0',
  `reporter_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bugnote_text_id` int(10) unsigned NOT NULL DEFAULT '0',
  `view_state` smallint(6) NOT NULL DEFAULT '10',
  `note_type` int(11) DEFAULT '0',
  `note_attr` varchar(250) DEFAULT '',
  `time_tracking` int(10) unsigned NOT NULL DEFAULT '0',
  `last_modified` int(10) unsigned NOT NULL DEFAULT '1',
  `date_submitted` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_bug` (`bug_id`),
  KEY `idx_last_mod` (`last_modified`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bugnote_table`
--

LOCK TABLES `mantis_bugnote_table` WRITE;
/*!40000 ALTER TABLE `mantis_bugnote_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_bugnote_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_bugnote_text_table`
--

DROP TABLE IF EXISTS `mantis_bugnote_text_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_bugnote_text_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `note` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_bugnote_text_table`
--

LOCK TABLES `mantis_bugnote_text_table` WRITE;
/*!40000 ALTER TABLE `mantis_bugnote_text_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_bugnote_text_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_category_table`
--

DROP TABLE IF EXISTS `mantis_category_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_category_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `status` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_category_project_name` (`project_id`,`name`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_category_table`
--

LOCK TABLES `mantis_category_table` WRITE;
/*!40000 ALTER TABLE `mantis_category_table` DISABLE KEYS */;
INSERT INTO `mantis_category_table` VALUES (1,0,0,'General',0),(2,3,0,'CAT3',0),(3,2,0,'MISM',0),(4,1,0,'TCCM',0),(5,4,0,'Leave',0),(6,4,0,'Other activity',0);
/*!40000 ALTER TABLE `mantis_category_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_config_table`
--

DROP TABLE IF EXISTS `mantis_config_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_config_table` (
  `config_id` varchar(64) NOT NULL,
  `project_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `access_reqd` int(11) DEFAULT '0',
  `type` int(11) DEFAULT '90',
  `value` longtext NOT NULL,
  PRIMARY KEY (`config_id`,`project_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_config_table`
--

LOCK TABLES `mantis_config_table` WRITE;
/*!40000 ALTER TABLE `mantis_config_table` DISABLE KEYS */;
INSERT INTO `mantis_config_table` VALUES ('database_version',0,0,90,1,'183'),('status_enum_workflow',0,0,90,3,'a:9:{i:10;s:59:\"30:acknowledged,20:feedback,40:analyzed,50:open,80:resolved\";i:20;s:47:\"30:acknowledged,40:analyzed,50:open,80:resolved\";i:30;s:43:\"40:analyzed,20:feedback,50:open,80:resolved\";i:40;s:31:\"50:open,20:feedback,80:resolved\";i:50;s:23:\"80:resolved,20:feedback\";i:80;s:47:\"90:closed,20:feedback,82:validated,85:delivered\";i:82;s:34:\"85:delivered,20:feedback,90:closed\";i:85;s:21:\"90:closed,20:feedback\";i:90;s:11:\"20:feedback\";}'),('main_menu_custom_options',0,0,90,3,'a:1:{i:0;a:3:{i:0;s:7:\"CodevTT\";i:1;s:2:\"10\";i:2;s:20:\"../codevtt/index.php\";}}'),('bug_submit_status',4,0,90,1,'90'),('bug_assigned_status',4,0,90,1,'90'),('plugin_FilterBugList_schema',0,0,90,1,'-1');
/*!40000 ALTER TABLE `mantis_config_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_custom_field_project_table`
--

DROP TABLE IF EXISTS `mantis_custom_field_project_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_custom_field_project_table` (
  `field_id` int(11) NOT NULL DEFAULT '0',
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sequence` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`field_id`,`project_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_custom_field_project_table`
--

LOCK TABLES `mantis_custom_field_project_table` WRITE;
/*!40000 ALTER TABLE `mantis_custom_field_project_table` DISABLE KEYS */;
INSERT INTO `mantis_custom_field_project_table` VALUES (1,1,0),(1,2,0),(1,3,0),(3,1,101),(4,1,103),(2,1,104),(6,1,105),(7,1,106),(5,1,107),(8,1,108),(3,2,101),(4,2,103),(2,2,104),(6,2,105),(7,2,106),(5,2,107),(8,2,108),(3,3,101),(4,3,103),(2,3,104),(6,3,105),(7,3,106),(5,3,107),(8,3,108);
/*!40000 ALTER TABLE `mantis_custom_field_project_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_custom_field_string_table`
--

DROP TABLE IF EXISTS `mantis_custom_field_string_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_custom_field_string_table` (
  `field_id` int(11) NOT NULL DEFAULT '0',
  `bug_id` int(11) NOT NULL DEFAULT '0',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`field_id`,`bug_id`),
  KEY `idx_custom_field_bug` (`bug_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_custom_field_string_table`
--

LOCK TABLES `mantis_custom_field_string_table` WRITE;
/*!40000 ALTER TABLE `mantis_custom_field_string_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_custom_field_string_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_custom_field_table`
--

DROP TABLE IF EXISTS `mantis_custom_field_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_custom_field_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `type` smallint(6) NOT NULL DEFAULT '0',
  `possible_values` text NOT NULL,
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
  `filter_by` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_custom_field_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_custom_field_table`
--

LOCK TABLES `mantis_custom_field_table` WRITE;
/*!40000 ALTER TABLE `mantis_custom_field_table` DISABLE KEYS */;
INSERT INTO `mantis_custom_field_table` VALUES (1,'CustomerRef',0,'','','',10,55,0,0,0,0,0,1,0,0,0,0,1),(2,'CodevTT_Charge Initiale',1,'','1','',10,25,0,0,1,0,1,1,0,0,0,0,1),(3,'CodevTT_Type',6,'Bug|Task','','',10,25,0,0,1,0,1,1,0,0,0,0,1),(4,'CodevTT_Charge Manager',1,'','0','',70,70,0,0,0,0,1,1,0,0,0,0,1),(5,'CodevTT_Deadline',8,'','','',10,25,0,0,0,0,1,1,0,0,0,0,1),(6,'CodevTT_Charge Additionnelle',1,'','','',10,25,0,0,0,0,0,1,0,0,0,0,1),(7,'CodevTT_RAF',1,'','','',10,25,0,0,0,0,0,1,0,1,1,0,1),(8,'CodevTT_Date Livraison',8,'','','',10,25,0,0,0,0,0,1,0,1,1,0,1);
/*!40000 ALTER TABLE `mantis_custom_field_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_email_table`
--

DROP TABLE IF EXISTS `mantis_email_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_email_table` (
  `email_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(64) NOT NULL DEFAULT '',
  `subject` varchar(250) NOT NULL DEFAULT '',
  `metadata` longtext NOT NULL,
  `body` longtext NOT NULL,
  `submitted` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`email_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_email_table`
--

LOCK TABLES `mantis_email_table` WRITE;
/*!40000 ALTER TABLE `mantis_email_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_email_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_filters_table`
--

DROP TABLE IF EXISTS `mantis_filters_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_filters_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `project_id` int(11) NOT NULL DEFAULT '0',
  `is_public` tinyint(4) DEFAULT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `filter_string` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_filters_table`
--

LOCK TABLES `mantis_filters_table` WRITE;
/*!40000 ALTER TABLE `mantis_filters_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_filters_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_news_table`
--

DROP TABLE IF EXISTS `mantis_news_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_news_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `poster_id` int(10) unsigned NOT NULL DEFAULT '0',
  `view_state` smallint(6) NOT NULL DEFAULT '10',
  `announcement` tinyint(4) NOT NULL DEFAULT '0',
  `headline` varchar(64) NOT NULL DEFAULT '',
  `body` longtext NOT NULL,
  `last_modified` int(10) unsigned NOT NULL DEFAULT '1',
  `date_posted` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_news_table`
--

LOCK TABLES `mantis_news_table` WRITE;
/*!40000 ALTER TABLE `mantis_news_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_news_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_plugin_table`
--

DROP TABLE IF EXISTS `mantis_plugin_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_plugin_table` (
  `basename` varchar(40) NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '0',
  `protected` tinyint(4) NOT NULL DEFAULT '0',
  `priority` int(10) unsigned NOT NULL DEFAULT '3',
  PRIMARY KEY (`basename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_plugin_table`
--

LOCK TABLES `mantis_plugin_table` WRITE;
/*!40000 ALTER TABLE `mantis_plugin_table` DISABLE KEYS */;
INSERT INTO `mantis_plugin_table` VALUES ('MantisCoreFormatting',1,0,3),('CodevTT',1,0,3),('FilterBugList',1,0,3);
/*!40000 ALTER TABLE `mantis_plugin_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_project_file_table`
--

DROP TABLE IF EXISTS `mantis_project_file_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_project_file_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `description` varchar(250) NOT NULL DEFAULT '',
  `diskfile` varchar(250) NOT NULL DEFAULT '',
  `filename` varchar(250) NOT NULL DEFAULT '',
  `folder` varchar(250) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL DEFAULT '0',
  `file_type` varchar(250) NOT NULL DEFAULT '',
  `content` longblob NOT NULL,
  `date_added` int(10) unsigned NOT NULL DEFAULT '1',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_project_file_table`
--

LOCK TABLES `mantis_project_file_table` WRITE;
/*!40000 ALTER TABLE `mantis_project_file_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_project_file_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_project_hierarchy_table`
--

DROP TABLE IF EXISTS `mantis_project_hierarchy_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_project_hierarchy_table` (
  `child_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `inherit_parent` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `idx_project_hierarchy_child_id` (`child_id`),
  KEY `idx_project_hierarchy_parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_project_hierarchy_table`
--

LOCK TABLES `mantis_project_hierarchy_table` WRITE;
/*!40000 ALTER TABLE `mantis_project_hierarchy_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_project_hierarchy_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_project_table`
--

DROP TABLE IF EXISTS `mantis_project_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_project_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `status` smallint(6) NOT NULL DEFAULT '10',
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `view_state` smallint(6) NOT NULL DEFAULT '10',
  `access_min` smallint(6) NOT NULL DEFAULT '10',
  `file_path` varchar(250) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `category_id` int(10) unsigned NOT NULL DEFAULT '1',
  `inherit_global` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_project_name` (`name`),
  KEY `idx_project_view` (`view_state`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_project_table`
--

LOCK TABLES `mantis_project_table` WRITE;
/*!40000 ALTER TABLE `mantis_project_table` DISABLE KEYS */;
INSERT INTO `mantis_project_table` VALUES (1,'Project1',10,1,50,10,'','',1,1),(2,'Project2',10,1,10,10,'','',1,1),(3,'Project3',10,1,10,10,'','',1,1),(4,'CodevTT Taches Externes',50,1,50,10,'','CodevTT Projet de taches externes',1,1);
/*!40000 ALTER TABLE `mantis_project_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_project_user_list_table`
--

DROP TABLE IF EXISTS `mantis_project_user_list_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_project_user_list_table` (
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `access_level` smallint(6) NOT NULL DEFAULT '10',
  PRIMARY KEY (`project_id`,`user_id`),
  KEY `idx_project_user` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_project_user_list_table`
--

LOCK TABLES `mantis_project_user_list_table` WRITE;
/*!40000 ALTER TABLE `mantis_project_user_list_table` DISABLE KEYS */;
INSERT INTO `mantis_project_user_list_table` VALUES (1,3,55),(1,4,55),(1,2,70),(2,3,55),(2,4,55),(2,2,70),(3,3,55),(3,4,55),(3,2,70);
/*!40000 ALTER TABLE `mantis_project_user_list_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_project_version_table`
--

DROP TABLE IF EXISTS `mantis_project_version_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_project_version_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL DEFAULT '0',
  `version` varchar(64) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `released` tinyint(4) NOT NULL DEFAULT '1',
  `obsolete` tinyint(4) NOT NULL DEFAULT '0',
  `date_order` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_project_version` (`project_id`,`version`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_project_version_table`
--

LOCK TABLES `mantis_project_version_table` WRITE;
/*!40000 ALTER TABLE `mantis_project_version_table` DISABLE KEYS */;
INSERT INTO `mantis_project_version_table` VALUES (1,3,'0.1.0','',0,0,1383563288),(2,2,'0.2.0','',0,0,1383563303),(3,1,'1.0.0','',0,0,1383563320);
/*!40000 ALTER TABLE `mantis_project_version_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_sponsorship_table`
--

DROP TABLE IF EXISTS `mantis_sponsorship_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_sponsorship_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `amount` int(11) NOT NULL DEFAULT '0',
  `logo` varchar(128) NOT NULL DEFAULT '',
  `url` varchar(128) NOT NULL DEFAULT '',
  `paid` tinyint(4) NOT NULL DEFAULT '0',
  `date_submitted` int(10) unsigned NOT NULL DEFAULT '1',
  `last_updated` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_sponsorship_bug_id` (`bug_id`),
  KEY `idx_sponsorship_user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_sponsorship_table`
--

LOCK TABLES `mantis_sponsorship_table` WRITE;
/*!40000 ALTER TABLE `mantis_sponsorship_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_sponsorship_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_tag_table`
--

DROP TABLE IF EXISTS `mantis_tag_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_tag_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `date_created` int(10) unsigned NOT NULL DEFAULT '1',
  `date_updated` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`,`name`),
  KEY `idx_tag_name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_tag_table`
--

LOCK TABLES `mantis_tag_table` WRITE;
/*!40000 ALTER TABLE `mantis_tag_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_tag_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_tokens_table`
--

DROP TABLE IF EXISTS `mantis_tokens_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_tokens_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `value` longtext NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '1',
  `expiry` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_typeowner` (`type`,`owner`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_tokens_table`
--

LOCK TABLES `mantis_tokens_table` WRITE;
/*!40000 ALTER TABLE `mantis_tokens_table` DISABLE KEYS */;
INSERT INTO `mantis_tokens_table` VALUES (3,1,4,'1',1439062552,1439062856);
/*!40000 ALTER TABLE `mantis_tokens_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_user_pref_table`
--

DROP TABLE IF EXISTS `mantis_user_pref_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_user_pref_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `email_on_status` tinyint(4) NOT NULL DEFAULT '0',
  `email_on_priority` tinyint(4) NOT NULL DEFAULT '0',
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
  `timezone` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_user_pref_table`
--

LOCK TABLES `mantis_user_pref_table` WRITE;
/*!40000 ALTER TABLE `mantis_user_pref_table` DISABLE KEYS */;
INSERT INTO `mantis_user_pref_table` VALUES (1,1,0,0,1,30,2,'ASC',1,1,1,1,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,'english','UTC');
/*!40000 ALTER TABLE `mantis_user_pref_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_user_print_pref_table`
--

DROP TABLE IF EXISTS `mantis_user_print_pref_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_user_print_pref_table` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `print_pref` varchar(64) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_user_print_pref_table`
--

LOCK TABLES `mantis_user_print_pref_table` WRITE;
/*!40000 ALTER TABLE `mantis_user_print_pref_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_user_print_pref_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_user_profile_table`
--

DROP TABLE IF EXISTS `mantis_user_profile_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_user_profile_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `platform` varchar(32) NOT NULL DEFAULT '',
  `os` varchar(32) NOT NULL DEFAULT '',
  `os_build` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_user_profile_table`
--

LOCK TABLES `mantis_user_profile_table` WRITE;
/*!40000 ALTER TABLE `mantis_user_profile_table` DISABLE KEYS */;
/*!40000 ALTER TABLE `mantis_user_profile_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantis_user_table`
--

DROP TABLE IF EXISTS `mantis_user_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mantis_user_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL DEFAULT '',
  `realname` varchar(64) NOT NULL DEFAULT '',
  `email` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `protected` tinyint(4) NOT NULL DEFAULT '0',
  `access_level` smallint(6) NOT NULL DEFAULT '10',
  `login_count` int(11) NOT NULL DEFAULT '0',
  `lost_password_request_count` smallint(6) NOT NULL DEFAULT '0',
  `failed_login_count` smallint(6) NOT NULL DEFAULT '0',
  `cookie_string` varchar(64) NOT NULL DEFAULT '',
  `last_visit` int(10) unsigned NOT NULL DEFAULT '1',
  `date_created` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_cookie_string` (`cookie_string`),
  UNIQUE KEY `idx_user_username` (`username`),
  KEY `idx_enable` (`enabled`),
  KEY `idx_access` (`access_level`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantis_user_table`
--

LOCK TABLES `mantis_user_table` WRITE;
/*!40000 ALTER TABLE `mantis_user_table` DISABLE KEYS */;
INSERT INTO `mantis_user_table` VALUES (1,'administrator','','root@localhost','63a9f0ea7bb98050796b649e85481845',1,0,90,6,0,0,'5b1f45d24d8096fd8581b1dd49bb36c9a51a1cb4c32e9cfe2cf5d73d25a384e3',1439062556,1383562226),(2,'lbayle','Louis BAYLE','louis.bayle@atos.net','d41d8cd98f00b204e9800998ecf8427e',1,0,25,0,0,0,'8dbc80ac9b24f15555247f08c463a3a352d461e337154b76369971d8654da1fc',1383563113,1383563113),(3,'user1','User ONE','toto@titi.fr','d41d8cd98f00b204e9800998ecf8427e',1,0,25,0,0,0,'a7c944c3da2e6c33a5dbc39e552beb09d7dc9171a39a78397ed9f871db39b5eb',1383563160,1383563160),(4,'user2','User TWO','toto@titi.tt','d41d8cd98f00b204e9800998ecf8427e',1,0,25,0,0,0,'eb08fbe6c146a430b10f9f21ca26a46d586cbec84cf02e7f8d2bc6081b68eb0c',1383563179,1383563179);
/*!40000 ALTER TABLE `mantis_user_table` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-08-08 21:36:34
