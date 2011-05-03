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
   include 'header.inc.php'; 
?>

<?php include 'menu.inc.php'; ?>


<?php
   include_once 'install.class.php'; 

   $install = new Install();
   
   $msg = $install->checkDBConnection();
   
   if ($msg) {
   	echo $msg;
   	exit;
   } else {
   	echo "DB connection OK<br/>";
   }
   
   
   $install->createMysqlConfigFile();
   $install->createCustomFields();
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