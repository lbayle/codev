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

      if(Tools::isConnectedUser()) {

         if (!$this->session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
         } else {
            if (isset($_POST['team_name'])) {
               // Form user selections
               $team_name = Tools::getSecurePOSTStringValue('team_name');

               $team_desc = Tools::getSecurePOSTStringValue('team_desc', '');
               $teamleader_id = Tools::getSecurePOSTStringValue('teamleader_id');

               $formatedDate  = date("Y-m-d", time());
               $now = Tools::date2timestamp($formatedDate);

               // 1) --- create new Team
               $teamid = Team::create($team_name, $team_desc, $teamleader_id, $now);

               if ($teamid > 0) {
                  $team = TeamCache::getInstance()->getTeam($teamid);

                  // --- add teamLeader as 'manager'
                  $team->addMember($teamleader_id, $now, Team::accessLevel_manager);

                  // 2) --- add ExternalTasksProject
                  $team->addExternalTasksProject();

                  $stproj_name = Tools::getSecurePOSTStringValue("stproj_name");

                  if (isset($_POST['cb_createSideTaskProj'])) {
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

                        if (isset($_POST['cb_catInactivity'])) {
                           $stproj->addCategoryInactivity(T_("Inactivity"));
                        }
                        if (isset($_POST['cb_catIncident'])) {
                           $stproj->addCategoryIncident(T_("Incident"));
                        }
                        if (isset($_POST['cb_catTools'])) {
                           $stproj->addCategoryTools(T_("Tools"));
                        }
                        if (isset($_POST['cb_catOther'])) {
                           $stproj->addCategoryWorkshop(T_("Team Workshop"));
                        }

                        // 5) --- add SideTaskProject default SideTasks
                        if (isset($_POST['cb_taskProjManagement'])) {
                           $stproj->addIssueProjManagement(Tools::getSecurePOSTStringValue('task_projManagement'));
                        }
                        if (isset($_POST['cb_taskMeeting'])) {
                           $stproj->addIssueProjManagement(Tools::getSecurePOSTStringValue('task_meeting'));
                        }
                        if (isset($_POST['cb_taskIncident'])) {
                           $stproj->addIssueIncident(Tools::getSecurePOSTStringValue('task_incident'));
                        }
                        if (isset($_POST['cb_taskTools'])) {
                           $stproj->addIssueTools(Tools::getSecurePOSTStringValue('task_tools'));
                        }
                        if (isset($_POST['cb_taskOther'])) {
                           $stproj->addIssueWorkshop(Tools::getSecurePOSTStringValue('task_other1'));
                        }
                        if (isset($_POST['cb_taskWaste'])) {
                           $stproj->addIssueInactivity(Tools::getSecurePOSTStringValue('task_Waste1'));
                        }
                     }
                  }
               }

               // 6) --- open EditTeam Page
               header('Location: edit_team.php?teamid='.$teamid);
            } else {
                              $smartyUserList = array();
               $userList = User::getUsers();
               foreach ($userList as $id => $name) {
                  $u = UserCache::getInstance()->getUser($id);
                  $uname = $u->getRealname();
                  if (empty($uname)) { $uname = $name;}
                  $smartyUserList[$id] = array(
                     'id' => $id,
                     'name' => $uname,
                     'selected' => ($id == $this->session_userid),
                  );
               }

               $this->smartyHelper->assign('users', $smartyUserList);
            }
         }
      }
   }

}

// ========== MAIN ===========
CreateTeamController::staticInit();
$controller = new CreateTeamController('../', 'Administration : Team Creation','Admin');
$controller->execute();

?>
