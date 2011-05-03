<?php if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>

<?php include_once '../path.inc.php'; ?>
<?php include_once 'i18n.inc.php'; ?>

<?php
   $_POST[page_name] = T_("Install"); 
   include 'install_header.inc.php';
   
   include 'install_menu.inc.php';
?>

<?php
   echo "DEBUG include_once install.class.php<br/>";

   include_once 'install.class.php'; 
   
   $sqlFile = "./bugtracker_install.sql";
   
   
   $db_mantis_host     = 'localhost';
   $db_mantis_user     = 'codev';
   $db_mantis_pass     = '';
   $db_mantis_database = 'bugtracker2';   

   echo "DEBUG new Install()<br/>";
   $install = new Install();
                                         
   echo "DEBUG checkDBConnection<br/>";
   $msg = $install->checkDBConnection($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
   
   if ($msg) {
   	echo $msg;
   	exit;
   } else {
   	echo "DB connection OK<br/>";
   }
   
   
   echo "DEBUG createMysqlConfigFile<br/>";
   $install->createMysqlConfigFile($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
   
   echo "DEBUG execSQLscript<br/>";
   $install->execSQLscript($sqlFile);
   
   echo "DEBUG createCustomFields<br/>";
   $install->createCustomFields();

   echo "DEBUG createCommonSideTasksProject<br/>";
   $stproj_id = $install->createCommonSideTasksProject(T_("SideTasks"));


/*   
         if ($stproj_id < 0) {
            die ("ERROR: CommonSideTaskProject creation FAILED.<br/>\n");
         } else {
            $stproj = ProjectCache::getInstance()->getProject($stproj_id);
         
            // 4) --- add SideTaskProject Categories
            $stproj->addCategoryProjManagement($cat_projManagement);
        
            if ($isCatIncident) {
               $stproj->addCategoryIncident($cat_incident);
            }
            if ($isCatTools) {
               $stproj->addCategoryTools($cat_tools);
            }
            if ($isCatOther) {
               $stproj->addCategoryOther($cat_workshop);
            }
        
            // 5) --- add SideTaskProject default SideTasks
            if ($isTaskProjManagement) {
               $stproj->addIssueProjManagement($task_projManagement);
            }
            if ($isTaskMeeting) {
               $stproj->addIssueProjManagement($task_meeting);
            }
            if ($isTaskIncident) {
               $stproj->addIssueIncident($task_incident);
            }
            if ($isTaskTools) {
               $stproj->addIssueTools($task_tools);
            }
            if ($isTaskOther) {
               $stproj->addIssueOther($task_workshop1);
            }
*/   
   
?>