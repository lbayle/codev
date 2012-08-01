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

include_once('classes/project.class.php');
include_once('classes/project_cache.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');
require_once('lib/log4php/Logger.php');

$logger = Logger::getLogger("workflow");

/**
 * get all existing projects,
 * @param bool $isCodevtt if true, include ExternalTasksProject & SideTasksProjects
 * @return string[] : name[id]
 */
function getProjectList($isCodevtt = false) {
   global $logger;

   $projects = Project::getProjects();
   if($projects != NULL) {
      $extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);
      $smartyProjects = array();
      foreach($projects as $id => $name) {
         if (!$isCodevtt) {
            // exclude ExternalTasksProject
            if ($extproj_id == $id) {
               $logger->debug("project $id: ExternalTasksProject is excluded");
               continue;
            }

            // exclude SideTasksProjects
            try {
               $p = ProjectCache::getInstance()->getProject($id);
               if ($p->isSideTasksProject()) {
                  $logger->debug("project $id: sideTaskProjects are excluded");
                  continue;
               }
            } catch (Exception $e) {
               // could not determinate, so the project should be included in the list
               $logger->debug("project $id: Unknown type, project included anyway.");
               // nothing to do.
            }
         }
         $smartyProjects[$id] = $name;
      }
      return $smartyProjects;
   } else {
      return NULL;
   }
}

/**
 * 
 * @param Project $project
 * @param string $tabsName
 * @return mixed[]
 */
function getProjectInfo(Project $project, $tabsName) {
   $projectInfo = array(
       'name' => $project->name,
       'tabsName' => $tabsName
   );
   
   $wfTrans = $project->getWorkflowTransitions();
   if (NULL != $wfTrans) {
      $statusTitles = $wfTrans[0];
      $statusNames = Config::getInstance()->getValue(Config::id_statusNames);

      $statusTitlesSmarty = array();
      foreach ($statusTitles as $sid => $sname) {
         $statusTitlesSmarty[$sid] = $statusNames[$sid];
      }
      $projectInfo['statusTitles'] = $statusTitlesSmarty;
      
      unset($wfTrans[0]);
      $content = array();
      foreach ($wfTrans as $sid => $sList) {
         $statusTitlesSmarty = array();
         foreach ( $statusTitles as $sid1 => $sname) {
            $statusTitlesSmarty[$sid1] = (null == $sList[$sid1]) ? "" : "X";
         }
         $content[$sid] = array(
            'name' => $statusNames[$sid],
            'statusTitles' => $statusTitlesSmarty
         );
      }
      $projectInfo['content'] = $content;
   }
   
   $configItems = $project->getProjectConfig();
   if (0 != count($configItems)) {
      unset($configItems["status_enum_workflow"]);
      $projectInfo['config'] = $configItems;
   }
   
   return $projectInfo;
}

// ================ MAIN =================
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Clone Project Settings');
$smartyHelper->assign('activeGlobalMenuItem', 'Admin');

if(isset($_SESSION['userid'])) {
   // Admins only
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   global $admin_teamid;
   if ($session_user->isTeamMember($admin_teamid)) {
      $projectList = getProjectList(false);
      if(isset($_POST['projectid']) && array_key_exists($_POST['projectid'], $projectList)) {
         $projectid = Tools::getSecurePOSTIntValue('projectid');
         $_SESSION['projectid'] = $projectid;
      } elseif(isset($_SESSION['projectid']) && array_key_exists($_SESSION['projectid'], $projectList)) {
         $projectid = $_SESSION['projectid'];
      } else {
         $projectIds = array_keys($projectList);
         if(count($projectIds) > 0) {
            $projectid = $projectIds[0];
         } else {
            $projectid = 0;
         }
      }

      $smartyHelper->assign('projects', SmartyTools::getSmartyArray($projectList, $projectid));
      
      // display current workflow
      if (0 != $projectid) {
         $clone_projectid = Tools::getSecurePOSTIntValue('clone_projectid', 0);
         if($clone_projectid == $projectid || !array_key_exists($clone_projectid, $projectList)) {
            $clone_projectid = 0;
         }

         $action = Tools::getSecurePOSTStringValue('action', '');
      
         if ("cloneToProject" == $action) {
            #echo "Clone $projectid ---> $clone_projectid<br>";
	    $errMsg = Project::cloneAllProjectConfig($projectid, $clone_projectid);
            $smartyHelper->assign('msg', 'Clone to project : '.$errMsg);
         } elseif ("cloneFromProject" == $action) {
            #echo "Clone $clone_projectid ---> $projectid<br>";
	    $errMsg = Project::cloneAllProjectConfig($clone_projectid, $projectid);
            $smartyHelper->assign('msg', 'Clone from project : '.$errMsg);
         }
         
         unset($projectList[$projectid]);
         $smartyHelper->assign('cloneProjects', SmartyTools::getSmartyArray($projectList, $clone_projectid));
         
         $proj = ProjectCache::getInstance()->getProject($projectid);         
         
         $smartyHelper->assign('currentProjectId', $projectid);
         $smartyHelper->assign('defaultProjectId', $clone_projectid);
         $smartyHelper->assign('currentProjectName', $proj->name);
         $smartyHelper->assign('disabled', (0 == $clone_projectid));
         
         $projectsInfo = array();
         $projectsInfo[] = getProjectInfo($proj, "tabsProject");

         if (0 != $clone_projectid) {
            $cproj = ProjectCache::getInstance()->getProject($clone_projectid);
            $smartyHelper->assign('defaultProjectName', $cproj->name);
            $projectsInfo[] = getProjectInfo($cproj, "tabsCloneProject");
         }
         $smartyHelper->assign('projectsInfo', $projectsInfo);
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
