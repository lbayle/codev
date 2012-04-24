-- Serveur: localhost
-- genere le : 22 Oct 2011
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

DROP FUNCTION IF EXISTS get_project_resolved_status_threshold;
DROP FUNCTION IF EXISTS get_issue_resolved_status_threshold;

-- ------------------------

DROP TABLE IF EXISTS `codev_config_table`;
DROP TABLE IF EXISTS `codev_holidays_table`;
DROP TABLE IF EXISTS `codev_job_table`;
DROP TABLE IF EXISTS `codev_project_job_table`;
DROP TABLE IF EXISTS `codev_sidetasks_category_table`;
DROP TABLE IF EXISTS `codev_team_project_table`;
DROP TABLE IF EXISTS `codev_team_table`;
DROP TABLE IF EXISTS `codev_team_user_table`;
DROP TABLE IF EXISTS `codev_timetracking_table`;
DROP TABLE IF EXISTS `codev_blog_table`;
DROP TABLE IF EXISTS `codev_blog_activity_table`;



-- /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
-- /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
-- /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
