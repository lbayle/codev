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


/*
 * 
 * 
 * create MySQL 'codev' user with access SELECT, INSERT, UPDATE, DELETE, CREATE
 * 
 * Step 1
 * 
 * - [user] create DB config file & test connection        OK
 * - [auto] create DB tables (from SQL file)               OK
 * - [auto] create Mantis codev user (if necessary ?)
 * - [auto] create admin team & add to codev_config_table  OK
 * 
 * Step 2
 * 
 * - [auto] create custom fields & add to codev_config_table  OK
 * - [auto] create CodevMetaProject (optional ?)
 * - [user] update codev_config_table with user prefs
 * - [user] 
 * 
 * - [user] create CommonSideTasks Project                 OK
 * - [auto] asign N/A job to commonSideTasks               OK
 * - [user] create default side tasks
 * - [user] config astreintes

 * Step 3
 * - [user] create jobs
 * - [user] config support job
 * - [user] add custom fields to existing projects
 */




   include_once 'install.class.php'; 
   
   $sqlFile = "./bugtracker_install.sql";
   
   $db_mantis_host     = 'localhost';
   $db_mantis_user     = 'codev';
   $db_mantis_pass     = '';
   $db_mantis_database = 'bugtracker';   

   $install = new Install();
                                         
   $msg = $install->checkDBConnection($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
   
   if ($msg) {
   	echo $msg;
   	exit;
   } else {
   	echo "DB connection OK<br/>";
   }
   
   
   $install->createMysqlConfigFile($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
   
   echo "DEBUG execSQLscript<br/>";
   $install->execSQLscript($sqlFile);
   
   echo "DEBUG createCustomFields<br/>";
   $install->createCustomFields();

   echo "DEBUG createCommonSideTasksProject<br/>";
   $stproj_id = $install->createCommonSideTasksProject(T_("SideTasks"), T_("CoDev commonSideTasks Project"));

   # TODO select Admin teamLeader from mantis user table
   
   $adminTeamName = T_("admin");
   $adminTeamLeader = 0;
   echo "DEBUG createAdminTeam  $adminTeamName  $adminTeamLeader<br/>";
   $install->createAdminTeam($adminTeamName, $adminTeamLeader);   
   

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