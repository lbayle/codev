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

class ForecastingReportController extends Controller {

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
      if (Tools::isConnectedUser()) {

         $threshold = 0.5; // for Deviation filters

        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            $withSupport = TRUE;

            $weekDates = Tools::week_dates(date('W'), date('Y'));

            // The first day of the current week
            $startDate = date("Y-m-d", $weekDates[1]);
            $startTimestamp = Tools::date2timestamp($startDate);
            #echo "DEBUG startTimestamp ".date("Y-m-d H:i:s", $startTimestamp)."<br/>";
            // The last day of the current week
            $endDate = date("Y-m-d", $weekDates[5]);
            $endTimestamp = Tools::date2timestamp($endDate);
            $endTimestamp += 24 * 60 * 60 - 1; // + 1 day -1 sec.
            #echo "DEBUG endTimestamp   ".date("Y-m-d H:i:s", $endTimestamp)."<br/>";

            #$managedTeamList = $this->session_user->getManagedTeamList();
            #$isManager = array_key_exists($this->teamid, $managedTeamList);
            $isManager = $this->session_user->isTeamManager($this->teamid);
            $isObserver = $this->session_user->isTeamObserver($this->teamid);
            $this->smartyHelper->assign('manager', ($isManager || $isObserver));
            $this->smartyHelper->assign('threshold', $threshold);

            $this->smartyHelper->assign('currentDeviationStats', $this->getCurrentDeviationStats($this->teamid, $threshold));

            $this->smartyHelper->assign('issuesInDrift', $this->getIssuesInDrift($this->teamid, $withSupport));

         }
      }
   }

   /**
    * display Drifts for Issues that have NOT been marked as 'Resolved' until now
    * @param int $teamid
    * @param int $threshold
    * @return mixed[]
    */
   private function getCurrentDeviationStats($teamid, $threshold = 1) {
      $issueList = TeamCache::getInstance()->getTeam($teamid)->getCurrentIssueList(TRUE, FALSE, FALSE);

      if ((NULL == $issueList) || (0 == count($issueList))) {
         self::$logger->info("getCurrentDeviationStats: No opened issues for team $teamid");
         return NULL;
      }

      $issueSelection = new IssueSelection("current issues");
      $issueSelection->addIssueList($issueList);

      $deviationGroups = $issueSelection->getDeviationGroups($threshold);
      $deviationGroupsMgr = $issueSelection->getDeviationGroupsMgr($threshold);

      $currentDeviationStats = array();

      $currentDeviationStats['totalDeviationMgr'] = $issueSelection->getDriftMgr();
      $currentDeviationStats['totalDeviation'] = $issueSelection->getDrift();

      $posDriftMgr = $deviationGroupsMgr['positive']->getDriftMgr();
      $posDrift = $deviationGroups['positive']->getDrift();
      $currentDeviationStats['nbIssuesPosMgr'] = $deviationGroupsMgr['positive']->getNbIssues();
      $currentDeviationStats['nbIssuesPos'] = $deviationGroups['positive']->getNbIssues();
      $currentDeviationStats['nbDaysPosMgr'] = $posDriftMgr['nbDays'];
      $currentDeviationStats['nbDaysPos'] = $posDrift['nbDays'];
      $currentDeviationStats['issuesPosMgr'] = $deviationGroupsMgr['positive']->getFormattedIssueList();
      $currentDeviationStats['issuesPos'] = $deviationGroups['positive']->getFormattedIssueList();

      $equalDriftMgr = $deviationGroupsMgr['equal']->getDriftMgr();
      $equalDrift = $deviationGroups['equal']->getDrift();
      $currentDeviationStats['nbIssuesEqualMgr'] = $deviationGroupsMgr['equal']->getNbIssues();
      $currentDeviationStats['nbIssuesEqual'] = $deviationGroups['equal']->getNbIssues();
      $currentDeviationStats['nbDaysEqualMgr'] = $equalDriftMgr['nbDays'];
      $currentDeviationStats['nbDaysEqual'] = $equalDrift['nbDays'];
      $currentDeviationStats['issuesEqualMgr'] = $deviationGroupsMgr['equal']->getFormattedIssueList();
      $currentDeviationStats['issuesEqual'] = $deviationGroups['equal']->getFormattedIssueList();

      $negDriftMgr = $deviationGroupsMgr['negative']->getDriftMgr();
      $negDrift = $deviationGroups['negative']->getDrift();
      $currentDeviationStats['nbIssuesNegMgr'] = $deviationGroupsMgr['negative']->getNbIssues();
      $currentDeviationStats['nbIssuesNeg'] = $deviationGroups['negative']->getNbIssues();
      $currentDeviationStats['nbDaysNegMgr'] = $negDriftMgr['nbDays'];
      $currentDeviationStats['nbDaysNeg'] = $negDrift['nbDays'];
      $currentDeviationStats['issuesNegMgr'] = $deviationGroupsMgr['negative']->getFormattedIssueList();
      $currentDeviationStats['issuesNeg'] = $deviationGroups['negative']->getFormattedIssueList();

      return $currentDeviationStats;
   }

   /**
    * @param int $teamid
    * @param bool $withSupport
    * @return mixed[]
    */
   private function getIssuesInDrift($teamid, $withSupport = TRUE) {
      $team = TeamCache::getInstance()->getTeam($teamid);
      $mList = $team->getMembers();
      $projList = $team->getProjects(true, false); // exclude disabled projects

      $issueArray = NULL;

      foreach ($mList as $id => $name) {
         $user = UserCache::getInstance()->getUser($id);

         // do not take observer's tasks
         if ((!$user->isTeamDeveloper($teamid)) &&
             (!$user->isTeamCustomer($teamid)) &&
             (!$user->isTeamManager($teamid))) {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("getIssuesInDrift user $id ($name) excluded.");
            }
            continue;
         }

         // TODO WARN: unassigned issues will be ommitted !!
         $issueList = $user->getAssignedIssues($projList);
         foreach ($issueList as $issue) {
            $driftMgrEE = $issue->getDriftMgr($withSupport);
            $driftEE = $issue->getDrift($withSupport);
            if (($driftMgrEE > 0) || ($driftEE > 0)) {

               $tooltipAttr = $issue->getTooltipItems($teamid, $this->session_userid);

               $issueArray[] = array(
                  'bugId' => Tools::issueInfoURL($issue->getId(), $tooltipAttr),
                  'handlerName' => $user->getName(),
                  'projectName' => $issue->getProjectName(),
                  'progress' => round(100 * $issue->getProgress()),
                  'effortEstimMgr' => $issue->getMgrEffortEstim(),
                  'effortEstim' => ($issue->getEffortEstim() + $issue->getEffortAdd()),
                  'elapsed' => $issue->getElapsed(),
                  'reestimated' => $issue->getReestimated(),
                  'backlog' => $issue->getBacklog(),
                  'driftPrelEE' => $driftMgrEE,
                  'driftEE' => $driftEE,
                  'statusName' => $issue->getCurrentStatusName(),
                  'summary' => $issue->getSummary()
               );
            }
         }
      }
      return $issueArray;
   }

}

// ========== MAIN ===========
ForecastingReportController::staticInit();
$controller = new ForecastingReportController('../', 'Forecasting','ProdReports');
$controller->execute();

?>
