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

        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            $tmpTeamList = array($this->teamid => $this->teamList[$this->teamid]);
            $projList = $this->session_user->getProjectList($tmpTeamList, true, false);

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

            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList, $projectid));


            // if display project allowed
            if (in_array($projectid, array_keys($projList))) {

               $this->smartyHelper->assign('projectid', $projectid);

               // Managers can see detailed view
               $isManager = $this->session_user->isTeamManager($this->teamid);
               $isObserver = $this->session_user->isTeamObserver($this->teamid);
               $this->smartyHelper->assign("isManager", ($isManager || $isObserver));

               $project = ProjectCache::getInstance()->getProject($projectid);
               $projectIssueSel = $project->getIssueSelection();

               // --- FILTER TABS -------------

               // get selected filters
               if(isset($_GET['selectedFilters'])) {
                  $selectedFilters = Tools::getSecureGETStringValue('selectedFilters');
               } else {
                  $selectedFilters = $this->session_user->getProjectFilters($projectid);
               }

               // cleanup filters (remove empty lines)
               $filterList = explode(',', $selectedFilters);
               $filterList = array_filter($filterList, create_function('$a','return $a!="";'));
               $selectedFilters = implode(',', $filterList);

               // save user preferances
               $this->session_user->setProjectFilters($selectedFilters, $projectid);

               // TODO: get allFilters from config.ini
               $data = ProjectInfoTools::getDetailedCharges($projectid, ($isManager || $isObserver), $selectedFilters);
               foreach ($data as $smartyKey => $smartyVariable) {
                  $this->smartyHelper->assign($smartyKey, $smartyVariable);
               }

               // --- DRIFT TABS -------------------

               $currentIssuesInDrift = NULL;
               $resolvedIssuesInDrift = NULL;
               foreach ($projectIssueSel->getIssuesInDrift(($isManager || $isObserver)) as $issue) {
                  $smartyIssue = $this->getSmartyDirftedIssue($issue, ($isManager || $isObserver));
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
               
               // indicator_plugins (old style plugins - deprecated)
               $this->smartyHelper->assign('detailedChargesIndicatorFile', DetailedChargesIndicator::getSmartyFilename());
               
                  // Dashboard
                  ProjectInfoTools::dashboardSettings($this->smartyHelper, $project, $this->session_userid, $this->teamid);
               
            }
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
   private function getOverview(array $explodeResults, array $filterDisplayNames, $isManager) {

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         // ---
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
            'progress' => round(100 * $isel->getProgress()),
            #'elapsed' => $isel->elapsed,
            'backlog' => $isel->duration,
            'driftColor' => IssueSelection::getDriftColor($values['percent']),
            'drift' => round(100 * $values['percent'])
         );
         if ($isManager) {
            $valuesMgr = $isel->getDriftMgr();
            $smartyElem['reestimated'] = $isel->getReestimated();
            $smartyElem['driftMgrColor'] = IssueSelection::getDriftColor($valuesMgr['percent']);
            $smartyElem['driftMgr'] = round(100 * $valuesMgr['percent']);

         }

         // ---
         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      #$titles[] = T_("Date");
      $titles[] = T_("Progress");
      if ($isManager) { $titles[] = T_("Reestimated"); }
      #$titles[] = T_("Elapsed");
      $titles[] = T_("Backlog");
      if ($isManager) { $titles[] = T_("Drift Mgr"); }
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
         $values = $isel->getDrift();
         $smartyElem = array(
            #'name' => $isel->name,
            'progress' => round(100 * $isel->getProgress()),
            'effortEstimMgr' => $isel->mgrEffortEstim,
            'effortEstim' => ($isel->effortEstim + $isel->effortAdd),
            'reestimated' => $isel->getReestimated(),
            'elapsed' => $isel->elapsed,
            'backlog' => $isel->duration,
            'driftColorMgr' => IssueSelection::getDriftColor($valuesMgr['percent']),
            'driftMgr' => round($valuesMgr['nbDays'],2),
            'driftColor' => IssueSelection::getDriftColor($values['percent']),
            'drift' => round($values['nbDays'],2)
         );

         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      $titles[] = T_("Progress");
      $titles[] = T_("MgrEffortEstim");
      $titles[] = T_("EffortEstim");
      $titles[] = T_("Reestimated");
      $titles[] = T_("Elapsed");
      $titles[] = T_("Backlog");
      $titles[] = T_("Drift Mgr");
      $titles[] = T_("Drift");

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
               $titleAttr = array(
                     T_('Project') => $issue->getProjectName(),
                     T_('Summary') => $issue->getSummary(),
               );
               $formatedNewList .= Tools::issueInfoURL($bugid, $titleAttr);

            } elseif ($issue->getCurrentStatus() >= $issue->getBugResolvedStatusThreshold()) {
               if ("" != $formatedResolvedList) {
                  $formatedResolvedList .= ', ';
               }
               #$title = "(".$issue->getDrift().') ['.$issue->getProjectName().'] '.$issue->getSummary();
               $titleAttr = array(
                   T_('Project') => $issue->getProjectName(),
                   T_('Summary') => $issue->getSummary(),
                   T_('Drift') => $issue->getDrift(),
                   'DriftColor' => $issue->getDriftColor()
               );
               $formatedResolvedList .= Tools::issueInfoURL($bugid, $titleAttr);
            } else {
               if ("" != $formatedOpenList) {
                  $formatedOpenList .= ', ';
               }
               #$title = "(".$issue->getDrift().", ".$issue->getCurrentStatusName().') ['.$issue->getProjectName().'] '.$issue->getSummary();
               $titleAttr = array(
                   T_('Project') => $issue->getProjectName(),
                   T_('Summary') => $issue->getSummary(),
                   T_('Status') => $issue->getCurrentStatusName(),
                   T_('Drift') => $issue->getDrift(),
                   'DriftColor' => $issue->getDriftColor()
               );
               $formatedOpenList .= Tools::issueInfoURL($bugid, $titleAttr);
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
      $driftMgr = ($isManager) ? $issue->getDriftMgr() : 0;
      $drift = $issue->getDrift();
      $driftMgrColor = NULL;
      if ($isManager) {
         if ($driftMgr < -1) {
            $driftMgrColor = "#61ed66";
         } else if ($driftMgr > 1) {
            $driftMgrColor = "#fcbdbd";
         }
         $driftMgr = round($driftMgr, 2);
      }

      $driftColor = NULL;
      if ($drift < -1) {
         $driftColor = "#61ed66";
      } else if ($drift > 1) {
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
         'drift' => round($drift, 2),
         'backlog' => $issue->getBacklog(),
         'progress' => round(100 * $issue->getProgress()),
         'currentStatusName' => $issue->getCurrentStatusName(),
         'summary' => $issue->getSummary()
      );
   }

}

// ========== MAIN ===========
ProjectInfoController::staticInit();
$controller = new ProjectInfoController('../', 'Project Info','ProjectInfo');
$controller->execute();

?>
