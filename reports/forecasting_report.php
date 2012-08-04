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

include_once('classes/issue_selection.class.php');
include_once('classes/team_cache.class.php');
include_once('classes/time_tracking.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

$logger = Logger::getLogger("forecasting");

/**
 * display Drifts for Issues that have NOT been marked as 'Resolved' until now
 * @param int $teamid
 * @param int $threshold
 * @param bool $withSupport
 * @return mixed[]
 */
function getCurrentDeviationStats($teamid, $threshold = 1, $withSupport = true) {
   global $logger;

   $issueList = TeamCache::getInstance()->getTeam($teamid)->getCurrentIssueList(true, false);

   if ((NULL == $issueList) || (0 == count($issueList))) {
      $logger->info("getCurrentDeviationStats: No opened issues for team $teamid");
      return NULL;
   }

   $issueSelection = new IssueSelection("current issues");
   $issueSelection->addIssueList($issueList);

   $deviationGroups = $issueSelection->getDeviationGroups($threshold, $withSupport);
   $deviationGroupsMgr = $issueSelection->getDeviationGroupsMgr($threshold, $withSupport);

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
function getIssuesInDrift($teamid, $withSupport = true) {
   global $logger;

   $team = TeamCache::getInstance()->getTeam($teamid);
   $mList = $team->getMembers();
   $projList = $team->getProjects();

   $issueArray = NULL;

   foreach ($mList as $id => $name) {
      $user = UserCache::getInstance()->getUser($id);

      // do not take observer's tasks
      if ((!$user->isTeamDeveloper($teamid)) &&
              (!$user->isTeamManager($teamid))) {
         $logger->debug("getIssuesInDrift user $id ($name) excluded.");
         continue;
      }

      $issueList = $user->getAssignedIssues($projList);
      foreach ($issueList as $issue) {
         $driftPrelEE = $issue->getDriftMgr($withSupport);
         $driftEE = $issue->getDrift($withSupport);
         if (($driftPrelEE > 0) || ($driftEE > 0)) {
            $issueArray[] = array(
                'bugId' => Tools::issueInfoURL($issue->bugId),
                'handlerName' => $user->getName(),
                'projectName' => $issue->getProjectName(),
                'targetVersion' => $issue->getTargetVersion(),
                'driftPrelEE' => $driftPrelEE,
                'driftEE' => $driftEE,
                'backlog' => $issue->backlog,
                'progress' => round(100 * $issue->getProgress()),
                'statusName' => $issue->getCurrentStatusName(),
                'summary' => $issue->summary
            );
         }
      }
   }
   return $issueArray;
}

/**
 * TODO factorize: this function also exists in statistics.php
 * Display 'Available Workload'
 * nb of days.: (holidays & externalTasks not included, developers only)
 * @param int $width
 * @param int $height
 * @param number[] $legend
 * @param string[] $bottomLabel
 * @return string
 */
function getGraphUrl($width, $height, array $legend, array $bottomLabel) {
   $title = 'title=' . T_("Available Workload");
   $dimension = 'width=' . $width . '&height=' . $height;
   $bottomLabel = 'bottomLabel=' . implode(':', $bottomLabel);
   $legend = 'leg1=' . T_("man-days") . '&x1=' . implode(':', $legend);
   return Tools::SmartUrlEncode('graphs/two_lines.php?displayPointLabels&' . $title . '&' . $dimension . '&' . $bottomLabel . '&' . $legend);
}

/**
 * @param TimeTracking[] $timeTrackingTable
 * @param int[] $val1
 * @return int[]
 */
function getDates(array $timeTrackingTable, array $val1) {
   $i = 0;
   $availableWorkloadGraph = NULL;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      $availableWorkloadGraph[Tools::formatDate("%B %Y", $startTimestamp)] = round($val1[$i], 1);
      $i++;
   }
   return $availableWorkloadGraph;
}

/**
 * @param int $start_day
 * @param int $start_month
 * @param int $start_year
 * @param int $teamid
 * @return TimeTracking[]
 */
function createTimeTrackingList($start_day, $start_month, $start_year, $teamid) {
   $timeTrackingTable = array();

   $day = $start_day;

   for ($y = $start_year; $y <= date('Y'); $y++) {

      for ($month = $start_month; $month < 13; $month++) {

         $startTimestamp = mktime(0, 0, 0, $month, $day, $y);
         $nbDaysInMonth = date("t", mktime(0, 0, 0, $month, 1, $y));
         $endTimestamp = mktime(23, 59, 59, $month, $nbDaysInMonth, $y);

         #echo "DEBUG createTimeTrackingList: startTimestamp=".date("Y-m-d H:i:s", $startTimestamp)." endTimestamp=".date("Y-m-d H:i:s", $endTimestamp)." nbDays = $nbDaysInMonth<br/>";

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);
         $timeTrackingTable[$startTimestamp] = $timeTracking;

         $day = 1;
      }
      $start_month = 1;
   }
   return $timeTrackingTable;
}

// =========== MAIN ==========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Forecasting');
$smartyHelper->assign('activeGlobalMenuItem', 'Forecasting');

if (isset($_SESSION['userid'])) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   
   $threshold = 0.5; // for Deviation filters

   // use the teamid set in the form, if not defined (first page call) use session teamid
   if (isset($_GET['teamid'])) {
      $teamid = $_GET['teamid'];
      $_SESSION['teamid'] = $teamid;
   } else {
      $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   }

   $teamList = $session_user->getTeamList();

   if (count($teamList) > 0) {
      $teams = SmartyTools::getSmartyArray($teamList, $teamid);
      $smartyHelper->assign('teams', $teams);
      
      if (isset($_GET['teamid']) && array_key_exists($teamid, $teams)) {
         $withSupport = true;

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

         $managedTeamList = $session_user->getManagedTeamList();
         $isManager = array_key_exists($teamid, $managedTeamList);
         $smartyHelper->assign('manager', $isManager);
         $smartyHelper->assign('threshold', $threshold);

         $smartyHelper->assign('currentDeviationStats', getCurrentDeviationStats($teamid, $threshold, $withSupport = true));

         $smartyHelper->assign('issuesInDrift', getIssuesInDrift($teamid, $withSupport));

         $start_day = 1;
         if (1 == date("m")) {
            $start_month = 12;
            $start_year = date("Y") - 1;
         } else {
            $start_month = date("m") - 1;
            $start_year = date("Y");
         }
         $timeTrackingTable = createTimeTrackingList($start_day, $start_month, $start_year, $teamid);
         foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
            $val1[] = $timeTracking->getAvailableWorkload();
            $bottomLabel[] = Tools::formatDate("%b %y", $startTimestamp);
            #$logger->debug("workload=$workload date=".formatDate("%b %y", $startTimestamp));
         }
         $smartyHelper->assign('graphUrl', getGraphUrl(800, 300, $val1, $bottomLabel));
         $smartyHelper->assign('dates', getDates($timeTrackingTable, $val1));
      }
   }
}

$smartyHelper->displayTemplate($mantisURL);

?>
