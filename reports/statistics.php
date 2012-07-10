<?php
include_once('../include/session.inc.php');

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

require('super_header.inc.php');

require('../smarty_tools.php');

include_once "period_stats_report.class.php";
include_once "issue.class.php";
include_once "team.class.php";
include_once "time_tracking.class.php";

/**
 * @param $start_day
 * @param $start_month
 * @param $start_year
 * @param $teamid
 * @return array
 */
function createTimeTrackingList($start_day, $start_month, $start_year, $teamid) {
   $timeTrackingTable = array();

   $day = $start_day;
   $now = time();
   for ($y = $start_year; $y <= date('Y'); $y++) {
      for ($month=$start_month ; $month <= 12 ; $month++) {
         $startTimestamp = mktime(0, 0, 0, $month, $day, $y);
         $nbDaysInMonth = date("t", mktime(0, 0, 0, $month, 1, $y));
         $endTimestamp   = mktime(23, 59, 59, $month, $nbDaysInMonth, $y);

         if ($startTimestamp > $now) {
            break;
         }

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);
         $timeTrackingTable[$startTimestamp] = $timeTracking;

         $day = 1;
      }
      $start_month = 1;
   }
   return $timeTrackingTable;
}

/**
 * @param array $timeTrackingTable
 * @return string
 */
function getSubmittedResolvedGraph(array $timeTrackingTable) {
   $submittedList = array();
   $resolvedList = array();
   $bottomLabel = array();
   foreach ($timeTrackingTable as $d1 => $tt1) {
      $submittedList[$d1] = count($tt1->getSubmitted()); // returns bug_id !
      $bottomLabel[] = formatDate("%b %y", $d1);
      $resolvedList[$d1] = count($tt1->getResolvedIssues()); // returns Issue instances !
   }

   $strVal1 = implode(':', array_values($submittedList));
   $strVal2 = implode(':', array_values($resolvedList));
   $strBottomLabel = implode(':', $bottomLabel);

   return SmartUrlEncode('title='.T_('Submitted / Resolved Issues').'&bottomLabel='.$strBottomLabel.'&leg1='.T_('Submitted').'&x1='.$strVal1.'&leg2='.T_('Resolved').'&x2='.$strVal2);
}

/**
 * @param array $timeTrackingTable
 * @return array
 */
function getSubmittedResolvedLegend(array $timeTrackingTable) {
   $submittedResolvedLegend = NULL;
   foreach ($timeTrackingTable as $d => $tt) {
      $submittedResolvedLegend[] = array(
         "date" => formatDate("%B %Y", $d),
         "nbSubmitted" => count($tt->getSubmitted()),
         "nbResolvedIssues" => count($tt->getResolvedIssues())
      );
   }

   return $submittedResolvedLegend;
}

/**
 * @param array $timeTrackingTable
 * @return string
 */
function getTimeDriftGraph(array $timeTrackingTable) {
   foreach ($timeTrackingTable as $startTimestamp1 => $timeTracking1) {
      // REM: the 'normal' drifts DO include support
      $timeDriftStats1 = $timeTracking1->getTimeDriftStats();
      $nbTasks1 = $timeDriftStats1["nbDriftsNeg"] + $timeDriftStats1["nbDriftsPos"];
      $val[] = (0 != $nbTasks1) ? $timeDriftStats1["nbDriftsNeg"] * 100 / $nbTasks1 : 100;

      $bottomLabel[] = formatDate("%b %y", $startTimestamp1);
   }

   $strVal1 = implode(':', $val);
   $strBottomLabel = implode(':', $bottomLabel);

   return SmartUrlEncode('title='.T_('Adherence to deadlines').'&bottomLabel='.$strBottomLabel.'&leg1='.T_('% Tasks').'&x1='.$strVal1);
}

/**
 * Display 'Adherence to deadlines'
 * in percent of tasks delivered before the deadLine.
 * @param array $timeTrackingTable
 * @return array
 */
function getTimeDriftLegend(array $timeTrackingTable) {
   $timeDriftLegend = NULL;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      $timeDriftStats = $timeTracking->getTimeDriftStats();
      $nbTasks = $timeDriftStats["nbDriftsNeg"] + $timeDriftStats["nbDriftsPos"];
      $timeDriftLegend[] = array(
         "date" => formatDate("%B %Y", $startTimestamp),
         "nbDriftsNeg" => round((0 != $nbTasks) ? $timeDriftStats["nbDriftsNeg"] * 100 / $nbTasks : 100, 1)
      );
   }
   return $timeDriftLegend;
}

/**
 * @param array $timeTrackingTable
 * @param bool $displayNoSupport
 * @return string
 */
function getResolvedDriftGraph(array $timeTrackingTable, $displayNoSupport = false) {
   foreach ($timeTrackingTable as $startTimestamp1 => $timeTracking1) {
      // REM: the 'normal' drifts DO include support
      $driftStats_new1 = $timeTracking1->getResolvedDriftStats(true);
      $val11[] = $driftStats_new1["totalDriftETA"] ? $driftStats_new1["totalDriftETA"] : 0;
      $val21[] = $driftStats_new1["totalDrift"] ? $driftStats_new1["totalDrift"] : 0;
      if ($displayNoSupport) {
         $driftStats_noSupport1 = $timeTracking1->getResolvedDriftStats(false);
         $val31[] = $driftStats_noSupport1["totalDrift"] ? $driftStats_noSupport1["totalDrift"] : 0;
      }
      $bottomLabel[] = formatDate("%b %y", $startTimestamp1);
   }

   $strVal1 = implode(':', $val11);
   $strVal2 = implode(':', $val21);
   $strBottomLabel = implode(':', $bottomLabel);

   if ($displayNoSupport) {
      $strVal3 = '&leg3='.T_('No Support').'&x3='.implode(':', $val31);
   }
   return SmartUrlEncode('title='.T_('Effort Deviation').'&bottomLabel='.$strBottomLabel.'&leg1='.T_('MgrEffortEstim').'&x1='.$strVal1.'&leg2='.T_('EffortEstim').'&x2='.$strVal2.$strVal3);
}

/**
 * @param array $timeTrackingTable
 * @param bool $displayNoSupport
 * @return array
 */
function getResolvedDriftLegend(array $timeTrackingTable, $displayNoSupport = false) {
   $resolvedDriftLegend = NULL;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      $driftStats_new = $timeTracking->getResolvedDriftStats(true);
      if ($displayNoSupport) {
         $driftStats_noSupport = $timeTracking->getResolvedDriftStats(false);
         $resolvedDriftLegend[] = array(
            "date" => formatDate("%B %Y", $startTimestamp),
            "totalDriftETA" => round($driftStats_new["totalDriftETA"] ? $driftStats_new["totalDriftETA"] : 0,2),
            "totalDrift" => round($driftStats_new["totalDrift"] ? $driftStats_new["totalDrift"] : 0,2),
            "totalDriftWithoutSupport" => round($driftStats_noSupport["totalDrift"] ? $driftStats_noSupport["totalDrift"] : 0,2)
         );
      }
      else {
         $resolvedDriftLegend[] = array(
            "date" => formatDate("%B %Y", $startTimestamp),
            "totalDriftETA" => round($driftStats_new["totalDriftETA"] ? $driftStats_new["totalDriftETA"] : 0,2),
            "totalDrift" => round($driftStats_new["totalDrift"] ? $driftStats_new["totalDrift"] : 0,2)
         );
      }
   }

   return $resolvedDriftLegend;
}

/**
 * @param array $timeTrackingTable
 * @return string
 */
function getEfficiencyGraph(array $timeTrackingTable) {
   foreach ($timeTrackingTable as $startTimestamp1 => $timeTracking1) {
      $val1[] = $timeTracking1->getEfficiencyRate();
      $val2[] = $timeTracking1->getSystemDisponibilityRate();
      $bottomLabel[] = formatDate("%b %y", $startTimestamp1);
   }

   $strVal1 = implode(':', $val1);
   $strVal2 = implode(':', $val2);
   $strBottomLabel = implode(':', $bottomLabel);

   return SmartUrlEncode('title='.T_('Efficiency').'&bottomLabel='.$strBottomLabel.'&leg1='.T_('% Efficiency').'&x1='.$strVal1.'&leg2='.T_('% Sys Disp').'&x2='.$strVal2);
}

/**
 * @param array $timeTrackingTable
 * @return array
 */
function getEfficiencyLegend(array $timeTrackingTable) {
   $efficiencyLegend = NULL;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      $efficiencyLegend[] = array(
         "date" => formatDate("%B %Y", $startTimestamp),
         "efficiencyRate" => round($timeTracking->getEfficiencyRate(), 2),
         "systemDisponibilityRate" => round($timeTracking->getSystemDisponibilityRate(), 3)
      );
   }
   return $efficiencyLegend;
}

/**
 * @param array $timeTrackingTable
 * @return string
 */
function getReopenedRateGraph(array $timeTrackingTable) {
   foreach ($timeTrackingTable as $startTimestamp1 => $timeTracking1) {
      $val1[] = $timeTracking1->getReopenedRate() * 100; // x100 to get a percentage;
      $val2[] = $timeTracking1->getReopenedRateResolved() * 100; // x100 to get a percentage;
      //$val2[] = count($timeTracking1->getReopened());
      $bottomLabel[] = formatDate("%b %y", $startTimestamp1);
   }

   $strVal1 = implode(':', $val1);
   $strVal2 = implode(':', $val2);
   $strBottomLabel = implode(':', $bottomLabel);

   return SmartUrlEncode('title='.T_('Reopened Rate').'&bottomLabel='.$strBottomLabel.'&leg1='.T_('% to Submitted').'&x1='.$strVal1.'&leg2='.T_('% to Resolved').'&x2='.$strVal2);
}

/**
 * @param array $timeTrackingTable
 * @return array
 */
function getReopenedRateLegend(array $timeTrackingTable) {
   $reopenedRateLegend = NULL;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      $reopenedRateLegend[] = array(
         "date" => formatDate("%B %Y", $startTimestamp),
         "reopenedRate" => round($timeTracking->getReopenedRate() * 100, 1),
         "reopenedRateResolved" => round($timeTracking->getReopenedRateResolved() * 100, 1),
         "reopened" => count($timeTracking->getReopened())
      );
   }
   return $reopenedRateLegend;
}

/**
 * Display 'Developers Workload'
 * nb of days.: (holidays & externalTasks not included, developers only)
 * @param array $timeTrackingTable
 * @return string
 */
function getDevelopersWorkloadGraph($timeTrackingTable) {
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      $val1[] = $timeTracking->getAvailableWorkload();
      $bottomLabel[] = formatDate("%b %y", $startTimestamp);
   }

   $strVal1 = implode(':', $val1);
   $strBottomLabel = implode(':', $bottomLabel);

   return SmartUrlEncode('title='.T_('Developers Workload').'&bottomLabel='.$strBottomLabel.'&leg1='.T_('man-days').'&x1='.$strVal1);
}

/**
 * @param array $timeTrackingTable
 * @return array
 */
function getDevelopersWorkloadLegend(array $timeTrackingTable) {
   $developersWorkloadLegend = NULL;
   foreach ($timeTrackingTable as $startTimestamp1 => $timeTracking1) {
      $developersWorkloadLegend[] = array(
         "date" => formatDate("%B %Y", $startTimestamp1),
         "value" => round($timeTracking1->getAvailableWorkload(), 1)
      );
   }
   return $developersWorkloadLegend;
}

// ================ MAIN ================
require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Statistics');

if(isset($_SESSION['userid'])) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   $teamList = $session_user->getTeamList();
   if (count($teamList) > 0) {
      if(isset($_POST['teamid']) && array_key_exists($_POST['teamid'],$teamList)) {
         $teamid = $_POST['teamid'];
         $_SESSION['teamid'] = $_POST['teamid'];
      }
      else if(isset($_SESSION['teamid']) && array_key_exists($_SESSION['teamid'],$teamList)) {
         $teamid = $_SESSION['teamid'];
      }
      else {
         $teamsid = array_keys($teamList);
         $teamid = $teamsid[0];
      }

      if($teamid != 0) {
         // if 'support' is set in the URL, display graphs for 'with/without Support'
         $displayNoSupport  = isset($_GET['support']) ? true : false;
         $smartyHelper->assign('displayNoSupport', $displayNoSupport);

         $team = new Team($teamid);
         $min_year = date("Y", $team->date);
         $year = isset($_POST['year']) && $_POST['year'] > $min_year ? $_POST['year'] : $min_year;

         $smartyHelper->assign('teams', getTeams($teamList,$teamid));
         $smartyHelper->assign('years', getYearsToNow($min_year, $year));

         if (isset($_POST['teamid'])) {
            $month = ($year == $min_year) ? date("m", $team->date) : 1;
            $day = ($year == $min_year) ? date("d", $team->date) : 1;

            $timeTrackingTable = createTimeTrackingList($day, $month, $year, $teamid);

            $smartyHelper->assign('submittedResolvedGraph', getSubmittedResolvedGraph($timeTrackingTable));
            $smartyHelper->assign('submittedResolvedLegend', getSubmittedResolvedLegend($timeTrackingTable));

            $smartyHelper->assign('timeDriftGraph', getTimeDriftGraph($timeTrackingTable));
            $smartyHelper->assign('timeDriftLegend', getTimeDriftLegend($timeTrackingTable));

            $smartyHelper->assign('resolvedDriftGraph', getResolvedDriftGraph($timeTrackingTable, $displayNoSupport));
            $smartyHelper->assign('resolvedDriftLegend', getResolvedDriftLegend($timeTrackingTable, $displayNoSupport));

            $smartyHelper->assign('efficiencyGraph', getEfficiencyGraph($timeTrackingTable));
            $smartyHelper->assign('efficiencyLegend', getEfficiencyLegend($timeTrackingTable));

            $smartyHelper->assign('reopenedRateGraph', getReopenedRateGraph($timeTrackingTable));
            $smartyHelper->assign('reopenedRateLegend', getReopenedRateLegend($timeTrackingTable));

            $smartyHelper->assign('developersWorkloadGraph', getDevelopersWorkloadGraph($timeTrackingTable));
            $smartyHelper->assign('developersWorkloadLegend', getDevelopersWorkloadLegend($timeTrackingTable));
         }
      }
   }

   // log stats
   IssueCache::getInstance()->logStats();
   ProjectCache::getInstance()->logStats();
   UserCache::getInstance()->logStats();
   TimeTrackCache::getInstance()->logStats();
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
