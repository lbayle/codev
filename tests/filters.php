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


   private function testFilterManager(IssueSelection $issueSel) {
      
      $filterList = array("ProjectVersionFilter", "ProjectCategoryFilter", "ExtIdFilter");
      #$filterList = array("ProjectVersionFilter", "ProjectCategoryFilter");
      
      $filterMgr = new FilterManager($issueSel, $filterList);

      $resultList = $filterMgr->execute();
/*
      echo "nbResults = ".count($resultList)."<br>";

      foreach ($resultList as $tag => $issueSel) {
         echo "[$tag]"." nbIssues = ".$issueSel->getNbIssues()." - ".$issueSel->getFormattedIssueList()."<br>";
      }
*/
      return $this->explodeResults(count($filterList) + 1 , $resultList);

   }

   private function explodeResults($nbLevels, $resultList) {

      // array (filter1,filter2,filter3, issueSel)
      $resultArray = array();
      foreach ($resultList as $tag => $issueSel) {
         #echo "[$tag]"." nbIssues = ".$issueSel->getNbIssues()." - ".$issueSel->getFormattedIssueList()."<br>";

         $tagList = explode(',',$tag);
         $nbTags = count($tagList);

         #echo "nbLevels = $nbLevels nbTags = $nbTags - $tag<br>";


         $line = array();
         foreach ($tagList as $subtag) { $line[] = $subtag; }
         for ($i=0; $i < ($nbLevels - $nbTags); $i++) { $line[] = ""; }

         #echo "line ".implode(',', $line).",issueSel<br>";


         //$line[] = $issueSel;
         $line[] = $issueSel->getFormattedIssueList();

         $resultArray[] = $line;

      }
      return $resultArray;
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

            // ---- test

            $projectIssueSel = $project->getIssueSelection();

            $issueSelList = $this->testFilterManager($projectIssueSel);

            $this->smartyHelper->assign('filterResults', $issueSelList);
            
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

