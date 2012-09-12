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

class FilterController extends Controller {

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


   private function manualFilterTest($projectVersionList) {
            $finalIssueSelList = array();
            foreach ($projectVersionList as $versionName => $issueSel) {
               echo "version $versionName<br>";

               $class = 'ProjectCategoryFilter';
               $categoryFilter = new $class($versionName.'_Categories');
               $catList = $categoryFilter->execute($issueSel, NULL);

               foreach ($catList as $catName => $catIssueSel) {
                  echo "version $versionName cat ".Project::getCategoryName($catIssueSel->name)." nbIssues=".$catIssueSel->getNbIssues()."<br>";

                  $class = 'ExtIdFilter';
                  $extIdFilter = new $class($catName.'_ExtId');
                  $extIdList = $extIdFilter->execute($catIssueSel, NULL);

                  echo "withExtId nbIssues = ".$extIdList['withExtRef']->getNbIssues()." : ".$extIdList['withExtRef']->getFormattedIssueList()."<br>";
                  echo "withoutExtId nbIssues = ".$extIdList['withoutExtRef']->getNbIssues()." : ".$extIdList['withoutExtRef']->getFormattedIssueList()."<br>";

                  $finalIssueSelList[] = $extIdList['withExtRef'];
                  $finalIssueSelList[] = $extIdList['withoutExtRef'];

               }
            }

   }


   private function testFilterManager(IssueSelection $issueSel) {
      
      $filterList = array("ProjectVersionFilter", "ProjectCategoryFilter", "ExtIdFilter");
      
      $filterMgr = new FilterManager($issueSel, $filterList);

      $filterMgr->execute();

   }

   protected function display() {

      if(isset($_SESSION['userid'])) {
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamList = $user->getTeamList();
         if (0 != count($teamList)) {

            // ---- select project

            // define the list of tasks the user can display
            // All projects from teams where I'm a Developper or Manager AND Observers
            $dTeamList = $user->getDevTeamList();
            $devProjList = (0 == count($dTeamList)) ? array() : $user->getProjectList($dTeamList);
            $managedTeamList = $user->getManagedTeamList();
            $managedProjList = (0 == count($managedTeamList)) ? array() : $user->getProjectList($managedTeamList);
            $oTeamList = $user->getObservedTeamList();
            $observedProjList = (0 == count($oTeamList)) ? array() : $user->getProjectList($oTeamList);
            $projList = $devProjList + $managedProjList + $observedProjList;

            if(isset($_GET['projectid'])) {
               $projectid = Tools::getSecureGETIntValue('projectid');
               $_SESSION['projectid'] = $projectid;
            }
            else if(isset($_SESSION['projectid'])) {
               $projectid = $_SESSION['projectid'];
            }
            else {
               $projectsid = array_keys($projList);
               $projectid = $projectsid[0];
            }

            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList,$projectid));

            $project = ProjectCache::getInstance()->getProject($projectid);

            // ----

            $projectVersionList = $project->getVersionList();



            $this->manualFilterTest($projectVersionList);

            echo "<br><br>=====================<br><br>";

            $projectIssueSel = $project->getIssueSelection();
            $this->testFilterManager($projectIssueSel);
            
         }
      }

   }

}

// ========== MAIN ===========
FilterController::staticInit();
$controller = new FilterController('TEST Filters','Admin');
$controller->execute();


/*

CHOOSE FILTERS WITH :

http://jqueryui.com/demos/sortable/#empty-lists


 */

