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

require('include/display.inc.php');

include_once('classes/sqlwrapper.class.php');
include_once('classes/user_cache.class.php');
include_once('classes/project_cache.class.php');

$logger = Logger::getLogger("prepare_project");

/**
 * get all existing projects, except ExternalTasksProject & SideTasksProjects
 */
function getProjectList() {
   global $logger;

   $query  = "SELECT id, name ".
             "FROM `mantis_project_table` ";
             #"WHERE mantis_project_table.id = $this->id ";

    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
       return NULL;
    }
    
   $extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);
   $projectList = array();
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      if ($extproj_id != $row->id) {
         try {
            $p = ProjectCache::getInstance()->getProject($row->id);
            if (!$p->isSideTasksProject()) {
               $projectList[$row->id] = $row->name;
            } else {
               // exclude SideTasksProjects
               $logger->debug("project $row->id: sideTaskProjects are excluded");
            }
         } catch (Exception $e) {
            // could not determinate, so the project should be included in the list
            $logger->debug("project $row->id: Unknown type, project included anyway.");
            // nothing to do.
         }
      } else {
         // exclude ExternalTasksProject
         $logger->debug("project $row->id: ExternalTasksProject is excluded");
      }
   }
   return $projectList;
}

// ========== MAIN ===========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'CoDev Administration : Prepare Projects');

if(isset($_SESSION['userid'])) {
   // Admins only
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   global $admin_teamid;
   if ($session_user->isTeamMember($admin_teamid)) {
      if (isset($_POST['projects']) && !empty($_POST['projects'])) {
         $selectedProjects = $_POST['projects'];
         $result = array();
         foreach ($selectedProjects as $projectid) {
            $project = ProjectCache::getInstance()->getProject($projectid);
            $result[$projectid] = $project->name;
            $project->prepareProjectToCodev();
         }
         $smartyHelper->assign('result', $result);
      }

      $smartyHelper->assign('projects', getProjectList());
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
