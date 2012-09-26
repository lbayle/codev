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

class ProjectInfoController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if(Tools::isConnectedUser()) {
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamList = $user->getTeamList();
         if (0 != count($teamList)) {
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


            // if display project allowed
            if (in_array($projectid, array_keys($projList))) {
               $isManager = true; // TODO
               $this->smartyHelper->assign("isManager", $isManager);

               $project = ProjectCache::getInstance()->getProject($projectid);

               // get selected filters
               $selectedFilters="";
               if(isset($_GET['selectedFilters'])) {
                  $selectedFilters = Tools::getSecureGETStringValue('selectedFilters');
               } else {
                  $selectedFilters = $user->getProjectFilters($projectid);
               }

               // cleanup filters (remove empty lines)
               $filterList = explode(',', $selectedFilters);
               $filterList = array_filter($filterList, create_function('$a','return $a!="";'));
               $selectedFilters = implode(',', $filterList);

               // save user preferances
               $user->setProjectFilters($selectedFilters, $projectid);

               // --- FILTER TABS -------------

               // TODO: get allFilters from config.ini
               $allFilters = "ProjectVersionFilter,ProjectCategoryFilter,IssueExtIdFilter,IssuePublicPrivateFilter,IssueTagFilter";
               
               $tmpList = explode(',', $allFilters);
               $tmpList = array_filter($tmpList, create_function('$a','return $a!="";'));

               $allFilterList = array();
               foreach ($tmpList as $class_name) {
                  if (NULL == $class_name) { continue; } // skip trailing commas ','
                  $filter = new $class_name("fake_id");
                  $allFilterList[$class_name] = $filter->getDisplayName();
               }

               // init dialogbox lists: $availFilterList & $selectedFilterList
               $availFilterList = $allFilterList;

               $selectedFilterList = array();
               $filterDisplayNames = array();
               foreach ($filterList as $id) {
                  $selectedFilterList[$id] = $availFilterList[$id];
                  $filterDisplayNames[]    = $allFilterList[$id];
                  unset($availFilterList[$id]);
               }

               // do the work ...
               $projectIssueSel = $project->getIssueSelection();
               $filterMgr = new FilterManager($projectIssueSel, $filterList);
               $resultList = $filterMgr->execute();
               $explodeResults = $filterMgr->explodeResults($resultList);


               // set smarty objects
               $this->smartyHelper->assign('availFilterList', $availFilterList);
               $this->smartyHelper->assign('selectedFilterList', $selectedFilterList);
               $this->smartyHelper->assign('selectedFilters', $selectedFilters);
               $this->smartyHelper->assign('nbFilters', count($filterList));
               $this->getOverview($explodeResults, $filterDisplayNames);
               $this->getDetailed($explodeResults, $filterDisplayNames);
               if ($isManager) {
                  $this->getDetailedMgr($explodeResults, $filterDisplayNames);
               }
               $this->getIssues($explodeResults, $filterDisplayNames);


               // --- DRIFT TABS -------------------
               
               $currentIssuesInDrift = NULL;
               $resolvedIssuesInDrift = NULL;
               foreach ($projectIssueSel->getIssuesInDrift($isManager) as $issue) {
                  $smartyIssue = $this->getSmartyDirftedIssue($issue, $isManager);
                  if(NULL != $smartyIssue) {
                     if ($issue->isResolved()) {
                        $resolvedIssuesInDrift[] = $smartyIssue;
                     } else {
                        $currentIssuesInDrift[] = $smartyIssue;
                     }
                  }
               }

               $this->smartyHelper->assign("currentIssuesInDrift", $currentIssuesInDrift);
               $this->smartyHelper->assign("resolvedIssuesInDrift", $resolvedIssuesInDrift);
            } else if ($projectid) {
               $this->smartyHelper->assign("error", T_("Sorry, you are not allowed to view the details of this project"));
            }
         } else {
            $this->smartyHelper->assign("error", T_("Sorry, you need to be member of a Team to access this page."));
         }
      }
   }

   /**
    * $explodeResults contains a list of filterNames + an IssueSelection on the last column.
    * This function will replace the IssueSelection with a smarty comprehensible array
    * containing the info to be displayed.
    *
    * @param mixed[] $explodeResults
    * @param string[] $filterDisplayNames
    */
   private function getOverview(array $explodeResults, array $filterDisplayNames) {

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         // ---
         $valuesMgr = $isel->getDriftMgr();
         $values = $isel->getDrift();

         // TODO show date only if ProjectVersion
         /*
         $date = "";
         if ('ProjectVersion' == get_class($isel)) {
            $vdate =  $isel->getVersionDate();
            if (is_numeric($vdate)) {
               $date = date(T_("Y-m-d"),$vdate);
            }
         }
         */

         $smartyElem = array(
            #'name' => $isel->name,
            #'date' => $date,
            'progressMgr' => round(100 * $isel->getProgressMgr()),
            'progress' => round(100 * $isel->getProgress()),
            'driftMgrColor' => IssueSelection::getDriftColor($valuesMgr['percent']),
            'driftMgr' => round(100 * $valuesMgr['percent']),
            'driftColor' => IssueSelection::getDriftColor($values['percent']),
            'drift' => round(100 * $values['percent'])
         );

         // ---
         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      #$titles[] = T_("Date");
      $titles[] = T_("Progress Mgr");
      $titles[] = T_("Progress");
      $titles[] = T_("Drift Mgr");
      $titles[] = T_("Drift");

      // set Smarty
      $totalLine = array_shift($smartyObj); // first line is rootElem (TOTAL)

      $this->smartyHelper->assign('overviewTitles', $titles);
      $this->smartyHelper->assign('overviewLines', $smartyObj);
      $this->smartyHelper->assign('overviewTotal', $totalLine);

   }

   /**
    * $explodeResults contains a list of filterNames + an IssueSelection on the last column.
    * This function will replace the IssueSelection with a smarty comprehensible array
    * containing the info to be displayed.
    *
    * @param type $explodeResults
    * @param type $filterDisplayNames
    */
   private function getDetailedMgr($explodeResults, $filterDisplayNames) {

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         $valuesMgr = $isel->getDriftMgr();
         $smartyElem = array(
            #'name' => $isel->name,
            #'progress' => round(100 * $pv->getProgress()),
            'effortEstim' => $isel->mgrEffortEstim,
            'reestimated' => $isel->getReestimatedMgr(),
            'elapsed' => $isel->elapsed,
            'backlog' => $isel->durationMgr,
            'driftColor' => IssueSelection::getDriftColor($valuesMgr['percent']),
            'drift' => round($valuesMgr['nbDays'],2)
         );

         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      $titles[] = T_("MgrEffortEstim");
      $titles[] = T_("Reestimated Mgr");
      $titles[] = T_("Elapsed");
      $titles[] = T_("Backlog Mgr");
      $titles[] = T_("Drift Mgr");

      // set Smarty
      $totalLine = array_shift($smartyObj); // first line is rootElem (TOTAL)

      $this->smartyHelper->assign('detailedMgrTitles', $titles);
      $this->smartyHelper->assign('detailedMgrLines', $smartyObj);
      $this->smartyHelper->assign('detailedMgrTotal', $totalLine);
   }

   /**
    * $explodeResults contains a list of filterNames + an IssueSelection on the last column.
    * This function will replace the IssueSelection with a smarty comprehensible array
    * containing the info to be displayed.
    *
    * @param type $explodeResults
    * @param type $filterDisplayNames
    */
   private function getDetailed($explodeResults, $filterDisplayNames) {

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         $values = $isel->getDrift();
         $smartyElem = array(
            #'name' => $isel->name,
            #'progress' => round(100 * $pv->getProgress()),
            'title' => $isel->effortEstim." + ".$isel->effortAdd,
            'effortEstim' => ($isel->effortEstim + $isel->effortAdd),
            'reestimated' => $isel->getReestimated(),
            'elapsed' => $isel->elapsed,
            'backlog' => $isel->duration,
            'driftColor' => IssueSelection::getDriftColor($values['percent']),
            'drift' => round($values['nbDays'],2)
         );

         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      $titles[] = T_("EffortEstim");
      $titles[] = T_("Reestimated");
      $titles[] = T_("Elapsed");
      $titles[] = T_("Backlog");
      $titles[] = T_("Drift");

      // set Smarty
      $totalLine = array_shift($smartyObj); // first line is rootElem (TOTAL)

      $this->smartyHelper->assign('detailedTitles', $titles);
      $this->smartyHelper->assign('detailedLines', $smartyObj);
      $this->smartyHelper->assign('detailedTotal', $totalLine);
   }


   /**
    * $explodeResults contains a list of filterNames + an IssueSelection on the last column.
    * This function will replace the IssueSelection with a smarty comprehensible array
    * containing the info to be displayed.
    *
    * @param type $explodeResults
    * @param type $filterDisplayNames
    */
   private function getIssues($explodeResults, $filterDisplayNames) {

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         // format Issues list
         $formatedResolvedList = "";
         $formatedOpenList = "";
         $formatedNewList = "";
         foreach ($isel->getIssueList() as $bugid => $issue) {

            if (Constants::$status_new == $issue->getCurrentStatus()) {
               if ("" != $formatedNewList) {
                  $formatedNewList .= ', ';
               }
               $formatedNewList .= Tools::issueInfoURL($bugid, '['.$issue->getProjectName().'] '.$issue->getSummary());

            } elseif ($issue->getCurrentStatus() >= $issue->getBugResolvedStatusThreshold()) {
               if ("" != $formatedResolvedList) {
                  $formatedResolvedList .= ', ';
               }
               $title = "(".$issue->getDrift().') ['.$issue->getProjectName().'] '.$issue->getSummary();
               $formatedResolvedList .= Tools::issueInfoURL($bugid, $title);
            } else {
               if ("" != $formatedOpenList) {
                  $formatedOpenList .= ', ';
               }
               $title = "(".$issue->getDrift().", ".$issue->getCurrentStatusName().') ['.$issue->getProjectName().'] '.$issue->getSummary();
               $formatedOpenList .= Tools::issueInfoURL($bugid, $title);
            }
         }

         $smartyElem = array(
            #'name' => $isel->name,
            'newList' => $formatedNewList,
            'openList' => $formatedOpenList,
            'resolvedList' => $formatedResolvedList
         );

         // ---
         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      $titles[] = T_("New Tasks");
      $titles[] = T_("Current Tasks");
      $titles[] = T_("Resolved Tasks");

      // set Smarty
      $totalLine = array_shift($smartyObj); // first line is rootElem (TOTAL)

      $this->smartyHelper->assign('issuesTitles', $titles);
      $this->smartyHelper->assign('issuesLines', $smartyObj);
      $this->smartyHelper->assign('issuesTotal', $totalLine);
   }

   /**
    * @param Issue $issue
    * @param boolean $isManager
    * @return mixed[]
    */
   private function getSmartyDirftedIssue(Issue $issue, $isManager) {
      $driftPrelEE = ($isManager) ? $issue->getDriftMgr() : 0;
      $driftEE = $issue->getDrift();

      $driftMgrColor = NULL;
      $driftMgr = NULL;
      if ($isManager) {
         if ($driftPrelEE < -1) {
            $driftMgrColor = "#61ed66";
         } else if ($driftPrelEE > 1) {
            $driftMgrColor = "#fcbdbd";
         }
         $driftMgr = round($driftPrelEE, 2);
      }
      
      $driftColor = NULL;
      if ($driftEE < -1) {
         $driftColor = "#61ed66";
      } else if ($driftEE > 1) {
         $driftColor = "#fcbdbd";
      }

      return array(
         'issueURL' => Tools::issueInfoURL($issue->getId()),
         'mantisURL' => Tools::mantisIssueURL($issue->getId(), NULL, true),
         'projectName' => $issue->getProjectName(),
         'targetVersion' => $issue->getTargetVersion(),
         'driftMgrColor' => $driftMgrColor,
         'driftMgr' => $driftMgr,
         'driftColor' => $driftColor,
         'drift' => round($driftEE, 2),
         'backlog' => $issue->getBacklog(),
         'progress' => round(100 * $issue->getProgress()),
         'currentStatusName' => $issue->getCurrentStatusName(),
         'summary' => $issue->getSummary()
      );
   }

}

// ========== MAIN ===========
ProjectInfoController::staticInit();
$controller = new ProjectInfoController('Project Info','ProjectInfo');
$controller->execute();

?>
