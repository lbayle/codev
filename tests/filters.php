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



   private function getDetailedMgr($explodeResults, $filterList) {
      
      $iselIdx = count($explodeResults[0]) -1;
      
      $smartyObj = array();
      
      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         $valuesMgr = $isel->getDriftMgr();
         $detailedMgr = array(
            'name' => $isel->name,
            //'progress' => round(100 * $pv->getProgress()),
            'effortEstim' => $isel->mgrEffortEstim,
            'reestimated' => $isel->getReestimated(),
            'elapsed' => $isel->elapsed,
            'backlog' => $isel->duration,
            'driftColor' => IssueSelection::getDriftColor($valuesMgr['percent']),
            'drift' => round($valuesMgr['nbDays'],2)
         );

         $line[$iselIdx] = $detailedMgr;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterList;
      $titles[] = T_("MgrEffortEstim");
      $titles[] = T_("Reestimated");
      $titles[] = T_("Elapsed");
      $titles[] = T_("Backlog");
      $titles[] = T_("Drift Mgr");
      $smartyObj[] = $titles;

      return $smartyObj;
   }


   protected function display() {
      if(Tools::isConnectedUser()) {
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

            // get selected filters
            if(isset($_GET['selectedFilters']) && (NULL != $_GET['selectedFilters'])) {
               $selectedFilters = Tools::getSecureGETStringValue('selectedFilters');

               #echo "last = ".$selectedFilters[strlen($selectedFilters)-1];
               if (',' == $selectedFilters[strlen($selectedFilters)-1]) {
                  $selectedFilters = substr($selectedFilters,0,-1); // last char is a ','
               }

               $filterList = explode(',', $selectedFilters);

            } else {
               $selectedFilters="";
              $filterList = array();
            }


            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList,$projectid));

            $project = ProjectCache::getInstance()->getProject($projectid);

            // ---- 
            $availFilterList = array("ProjectVersionFilter" => "Project Version",
                                     "ProjectCategoryFilter" => "Project Category",
                                     "IssueExtIdFilter" => "Issue External ID",
                                     "IssuePublicPrivateFilter" => "Issue Public / Private",
                                     "IssueTagFilter" => "Issue Tags"
                );
            $selectedFilterList = array();
            foreach ($filterList as $id) {
               $selectedFilterList[$id] = $availFilterList[$id];
               unset($availFilterList[$id]);
            }


            // do the work ...
            $projectIssueSel = $project->getIssueSelection();
            $filterMgr = new FilterManager($projectIssueSel, $filterList);
            $resultList = $filterMgr->execute();
            $issueSelList = $filterMgr->explodeResults($resultList);

            

            $smatyObj = $this->getDetailedMgr($issueSelList, $filterList);

            $totalLine = array_shift($smatyObj); // first line is rootElem (TOTAL)
            $titleLine = array_pop($smatyObj); // last line is the table titles


            $this->smartyHelper->assign('availFilterList', $availFilterList);
            $this->smartyHelper->assign('selectedFilterList', $selectedFilterList);
            $this->smartyHelper->assign('selectedFilters', $selectedFilters);
            $this->smartyHelper->assign('nbFilters', count($filterList));
            $this->smartyHelper->assign('filterResultsTitles', $titleLine);
            $this->smartyHelper->assign('filterResults', $smatyObj);
            $this->smartyHelper->assign('filterResultsTotal', $totalLine);
            
         }
      }

   }

}

// ========== MAIN ===========
FilterController::staticInit();
$controller = new FilterController('../', 'TEST Filters','Admin');
$controller->execute();

