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

class PrepareProjectController extends Controller {

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
      if(isset($_SESSION['userid'])) {
         // Admins only
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            if (isset($_POST['projects']) && !empty($_POST['projects'])) {
               $selectedProjects = $_POST['projects'];
               $result = array();
               foreach ($selectedProjects as $projectid) {
                  $project = ProjectCache::getInstance()->getProject($projectid);
                  $result[$projectid] = $project->getName();
                  Project::prepareProjectToCodev($projectid);
               }
               $this->smartyHelper->assign('result', $result);
            }

            $this->smartyHelper->assign('projects', $this->getProjectList());
         }
      }
   }

   /**
    * get all existing projects, except ExternalTasksProject & SideTasksProjects
    * @return string[int] : name[id]
    */
   private function getProjectList() {
      $projects = Project::getProjects();
      if($projects != NULL) {
         $extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);
         $smartyProjects = array();
         foreach($projects as $id => $name) {
            if ($extproj_id != $id) {
               try {
                  $p = ProjectCache::getInstance()->getProject($id);
                  if (!$p->isSideTasksProject()) {
                     $smartyProjects[$id] = $name;
                  } else {
                     // exclude SideTasksProjects
                     if(self::$logger->isDebugEnabled()) {
                        self::$logger->debug("project $id: sideTaskProjects are excluded");
                     }
                  }
               } catch (Exception $e) {
                  // could not determinate, so the project should be included in the list
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("project $id: Unknown type, project included anyway.");
                  }
                  // nothing to do.
               }
            } else {
               // exclude ExternalTasksProject
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("project $id: ExternalTasksProject is excluded");
               }
            }
         }
         return $smartyProjects;
      } else {
         return NULL;
      }
   }

}

// ========== MAIN ===========
PrepareProjectController::staticInit();
$controller = new PrepareProjectController('CoDev Administration : Prepare Projects','Admin');
$controller->execute();

?>
