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

class CreateTeamController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      $this->smartyHelper->assign('activeGlobalMenuItem', 'Admin');

      if(isset($_SESSION['userid'])) {
         $is_modified = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";

         // Form user selections
         $team_name = Tools::getSecurePOSTStringValue('team_name',"");

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

         $team_desc = Tools::getSecurePOSTStringValue('team_desc',"");
         $teamleader_id = Tools::getSecurePOSTStringValue('teamleader_id',"");

         $task_projManagement = Tools::getSecurePOSTStringValue('task_projManagement',T_("(generic) Project Management"));
         $task_meeting = Tools::getSecurePOSTStringValue('task_meeting',T_("(generic) Meeting"));
         $task_incident = Tools::getSecurePOSTStringValue('task_incident',T_("(generic) Network is down"));
         $task_tools = Tools::getSecurePOSTStringValue('task_tools',T_("(generic) Compilation Scripts"));
         $task_other1 = Tools::getSecurePOSTStringValue('task_other1',T_("(generic) Update team WIKI"));

         $action = Tools::getSecurePOSTStringValue('action','');
         if ("addTeam" == $action) {
            $formatedDate  = date("Y-m-d", time());
            $now = Tools::date2timestamp($formatedDate);

            // 1) --- create new Team
            $teamid = Team::create($team_name, $team_desc, $teamleader_id, $now);

            if ($teamid > 0 && $isCreateSTProj) {

               $team = TeamCache::getInstance()->getTeam($teamid);

               // 2) --- add ExternalTasksProject
               $team->addExternalTasksProject();

               // 3) --- add <team> SideTaskProject
               $stproj_id = $team->createSideTaskProject($stproj_name);
               if ($stproj_id < 0) {
                  self::$logger->error("SideTaskProject creation FAILED");
                  echo "<span style='color:red'>ERROR: SideTaskProject creation FAILED</span>";
                  exit;
               } else {
                  $stproj = ProjectCache::getInstance()->getProject($stproj_id);

                  // --- add teamLeader as Mantis manager of the SideTaskProject
                  $leader = UserCache::getInstance()->getUser($teamleader_id);
                  $access_level = 70; // TODO mantis manager
                  $leader->setProjectAccessLevel($stproj_id, $access_level);

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

         $this->smartyHelper->assign('team_name', $team_name);
         $this->smartyHelper->assign('team_desc', $team_desc);
         $this->smartyHelper->assign('users', SmartyTools::getSmartyArray(User::getUsers(),$teamleader_id));

         $this->smartyHelper->assign('isCreateSTProj', $isCreateSTProj);
         $this->smartyHelper->assign('stproj_name', $stproj_name);
         $this->smartyHelper->assign('isCatIncident', $isCatIncident);
         $this->smartyHelper->assign('isCatTools', $isCatTools);
         $this->smartyHelper->assign('isCatOther', $isCatOther);
         $this->smartyHelper->assign('isCatInactivity', $isCatInactivity);
         $this->smartyHelper->assign('isTaskProjManagement', $isTaskProjManagement);
         $this->smartyHelper->assign('isTaskMeeting', $isTaskMeeting);
         $this->smartyHelper->assign('isTaskIncident', $isTaskIncident);
         $this->smartyHelper->assign('isTaskTools', $isTaskTools);
         $this->smartyHelper->assign('isTaskOther', $isTaskOther);

         $this->smartyHelper->assign('task_projManagement', $task_projManagement);
         $this->smartyHelper->assign('task_meeting', $task_meeting);
         $this->smartyHelper->assign('task_incident', $task_incident);
         $this->smartyHelper->assign('task_tools', $task_tools);
         $this->smartyHelper->assign('task_other1', $task_other1);

         $this->smartyHelper->assign('is_modified', $is_modified);
      }
   }

}

// ========== MAIN ===========
CreateTeamController::staticInit();
$controller = new CreateTeamController('CoDev Administration : Team Creation','Admin');
$controller->execute();

?>
