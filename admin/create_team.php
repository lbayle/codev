<?php
require('../include/session.inc.php');

/*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

require('include/super_header.inc.php');

require('smarty_tools.php');

require('classes/smarty_helper.class.php');

include_once('classes/project_cache.class.php');
include_once('classes/user_cache.class.php');
include_once('classes/team.class.php');
include_once('classes/team_cache.class.php');

$logger = Logger::getLogger("create_team");

// ========== MAIN ===========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'CoDev Administration : Team Creation');

if(isset($_SESSION['userid'])) {
   $is_modified = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";

   // Form user selections
   $team_name = getSecurePOSTStringValue('team_name',"");

   // 'is_modified' is used because it's not possible to make a difference
   // between an unchecked checkBox and an unset checkbox variable
   if ("false" == $is_modified) {
      $isCreateSTProj = true;
      $isCatInactivity = true;
      $isCatIncident = true;
      $isCatTools = true;
      $isCatOther = true;
      $isTaskProjManagement = true;
      $isTaskOnDuty = false;
      $isTaskMeeting = true;
      $isTaskIncident = false;
      $isTaskTools = false;
      $isTaskOther = false;
      $stproj_name = T_("SideTasks")." my_team";
   } else {
      $isCreateSTProj = $_POST['cb_createSideTaskProj'];
      $isCatInactivity = $_POST['cb_catInactivity'];
      $isCatIncident = $_POST['cb_catIncident'];
      $isCatTools = $_POST['cb_catTools'];
      $isCatOther = $_POST['cb_catOther'];
      $isTaskOnDuty = $_POST['cb_taskOnDuty'];
      $isTaskProjManagement = $_POST['cb_taskProjManagement'];
      $isTaskMeeting = $_POST['cb_taskMeeting'];
      $isTaskIncident = $_POST['cb_taskIncident'];
      $isTaskTools = $_POST['cb_taskTools'];
      $isTaskOther = $_POST['cb_taskOther'];
      $stproj_name = ("" == $team_name) ? $teamSideTaskProjectName : T_("SideTasks")." $team_name";
   }

   $team_desc = getSecurePOSTStringValue('team_desc',"");
   $teamleader_id = getSecurePOSTStringValue('teamleader_id',"");

   $task_projManagement = getSecurePOSTStringValue('task_projManagement',T_("(generic) Project Management"));
   $task_meeting = getSecurePOSTStringValue('task_meeting',T_("(generic) Meeting"));
   $task_incident = getSecurePOSTStringValue('task_incident',T_("(generic) Network is down"));
   $task_tools = getSecurePOSTStringValue('task_tools',T_("(generic) Compilation Scripts"));
   $task_other1 = getSecurePOSTStringValue('task_other1',T_("(generic) Update team WIKI"));

   $action = getSecurePOSTStringValue('action','');
   if ("addTeam" == $action) {
      $formatedDate  = date("Y-m-d", time());
      $now = date2timestamp($formatedDate);

      // 1) --- create new Team
      $teamid = Team::create($team_name, $team_desc, $teamleader_id, $now);

      if ($teamid > 0 && $isCreateSTProj) {

         $team = TeamCache::getInstance()->getTeam($teamid);

         // 2) --- add ExternalTasksProject
         $team->addExternalTasksProject();

         // 3) --- add <team> SideTaskProject
         $stproj_id = $team->createSideTaskProject($stproj_name);
         if ($stproj_id < 0) {
            $logger->error("SideTaskProject creation FAILED");
            echo "<span style='color:red'>ERROR: SideTaskProject creation FAILED</span>";
            exit;
         } else {
            $stproj = ProjectCache::getInstance()->getProject($stproj_id);

            // 4) --- add SideTaskProject Categories
            $stproj->addCategoryProjManagement(T_("Project Management"));
            $stproj->addCategoryMngtProvision(T_("Provision"));

            if ($isCatInactivity) {
               $stproj->addCategoryInactivity(T_("Inactivity"));
            }
            if ($isCatIncident) {
               $stproj->addCategoryIncident(T_("Incident"));
            }
            if ($isCatTools) {
               $stproj->addCategoryTools(T_("Tools"));
            }
            if ($isCatOther) {
               $stproj->addCategoryWorkshop(T_("Team Workshop"));
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
               $stproj->addIssueWorkshop($task_other1);
            }
         }
      }

      // 6) --- open EditTeam Page
      header('Location: edit_team.php?teamid='.$teamid);
   }

   $smartyHelper->assign('team_name', $team_name);
   $smartyHelper->assign('team_desc', $team_desc);
   $smartyHelper->assign('users', getSmartyArray(User::getUsers(),$teamleader_id));

   $smartyHelper->assign('isCreateSTProj', $isCreateSTProj);
   $smartyHelper->assign('stproj_name', $stproj_name);
   $smartyHelper->assign('isCatIncident', $isCatIncident);
   $smartyHelper->assign('isCatTools', $isCatTools);
   $smartyHelper->assign('isCatOther', $isCatOther);
   $smartyHelper->assign('isCatInactivity', $isCatInactivity);
   $smartyHelper->assign('isTaskProjManagement', $isTaskProjManagement);
   $smartyHelper->assign('isTaskMeeting', $isTaskMeeting);
   $smartyHelper->assign('isTaskIncident', $isTaskIncident);
   $smartyHelper->assign('isTaskTools', $isTaskTools);
   $smartyHelper->assign('isTaskOther', $isTaskOther);

   $smartyHelper->assign('task_projManagement', $task_projManagement);
   $smartyHelper->assign('task_meeting', $task_meeting);
   $smartyHelper->assign('task_incident', $task_incident);
   $smartyHelper->assign('task_tools', $task_tools);
   $smartyHelper->assign('task_other1', $task_other1);

   $smartyHelper->assign('is_modified', $is_modified);
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
