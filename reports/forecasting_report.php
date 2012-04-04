<?php 
if (!isset($_SESSION)) { 
	$tokens = explode('/', $_SERVER['PHP_SELF'], 3);
	$sname = str_replace('.', '_', $tokens[1]);
	session_name($sname); 
	session_start(); 
	header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); 
} 

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

include_once "user_cache.class.php";
include_once "issue_cache.class.php";
include_once "issue.class.php";
include_once "team.class.php";
include_once "time_tracking.class.php";

$logger = Logger::getLogger("forecasting");

/**
 * Get teams
 * @param $teamList
 * @return array
 */
function getTeams($teamList) {
    foreach ($teamList as $tid => $tname) {
        $teams[] = array(
            'id' => $tid,
            'name' => $tname,
            'selected' => $tid == $_SESSION['teamid']
        );
    }
    return $teams;
}

/**
 * display Drifts for Issues that are CURRENTLY OPENED
 * @param $timeTracking
 */
function getCurrentDriftStats ($startTimestamp, $endTimestamp, $teamid, $isManager = false) {
    global $logger;
    global $status_new;

    $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

    // ---- get Issues that are not Resolved/Closed
    $prodProjectList = $timeTracking->prodProjectList;

    if ((NULL == $prodProjectList) || (0 == count($prodProjectList))) {
        $logger->error("No valid projects defined for team ".$timeTracking->team_id);
        return;
    }

    $formatedProdProjectList = implode( ', ', $prodProjectList);

    $query = "SELECT DISTINCT id ".
             "FROM `mantis_bug_table` ".
             "WHERE status < get_project_resolved_status_threshold(project_id) ".
             "AND status > $status_new ".
             "AND project_id IN ($formatedProdProjectList) ".
             "ORDER BY id DESC";
    $result = mysql_query($query);
    if (!$result) {
        $logger->error("Query FAILED: $query");
        $logger->error(mysql_error());
        return;
    }

    $issueList = array();
    while($row = mysql_fetch_object($result)) {
        $issueList[] = IssueCache::getInstance()->getIssue($row->id);
    }
    if (0 != count($issueList)) {
        $driftStats_new = $timeTracking->getIssuesDriftStats($issueList);
    } else {
        $driftStats_new = array();
    }

    return $driftStats_new;
}

/**
 *
 */
function getIssuesInDrift($teamid, $isManager=false, $withSupport=true) {
    $issuesInDrift = "";

    $mList = Team::getMemberList($teamid);
    foreach ($mList as $id => $name) {
        $user = UserCache::getInstance()->getUser($id);

        // take only developper's tasks
        if (!$user->isTeamDeveloper($teamid)) {
            continue;
        }

        $issueList = $user->getAssignedIssues();

        foreach ($issueList as $issue) {
            // TODO: check if issue in team project list ?
            $driftPrelEE = $issue->getDriftMgrEE($withSupport);
            $driftEE = $issue->getDrift($withSupport);
            if (($driftPrelEE > 0) || ($driftEE > 0)) {
                $issueArray[] = array(
                    'bugId' => issueInfoURL($issue->bugId),
                    'projectName' => $issue->getProjectName(),
                    'targetVersion' => $issue->getTargetVersion(),
                    'driftPrelEE' => $driftPrelEE,
                    'driftEE' => $driftEE,
                    'remaining' => $issue->remaining,
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
 * @param $width
 * @param $height
 * @param $legend
 * @param $bottomLabel
 * @return string
 */
function getGraphUrl($width, $height, $legend, $bottomLabel) {
    $title = 'title='.T_("Available Workload");
    $dimension = 'width='.$width.'&height='.$height;
    $bottomLabel = 'bottomLabel='.implode(':', $bottomLabel);
    $legend = 'leg1='.T_("man-days").'&x1='.implode(':', $legend);
    return SmartUrlEncode('graphs/two_lines.php?displayPointLabels&'.$title.'&'.$dimension.'&'.$bottomLabel.'&'.$legend);
}

/**
 * @param $timeTrackingTable
 * @param $val1
 * @return array
 */
function getDates($timeTrackingTable, $val1) {
    $i = 0;
    foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
        $availableWorkloadGraph[date("F Y", $startTimestamp)] = number_format($val1[$i], 1);
        $i++;
    }
    return $availableWorkloadGraph;
}

/**
 *
 * @param unknown_type $start_day
 * @param unknown_type $start_month
 * @param unknown_type $start_year
 * @param unknown_type $teamid
 */
function createTimeTrackingList($start_day, $start_month, $start_year, $teamid) {
   $now = time();
   $timeTrackingTable = array();

   $day = $start_day;

   for ($y = $start_year; $y <= date('Y'); $y++) {

      for ($month=$start_month; $month<13; $month++) {

         $startTimestamp = mktime(0, 0, 0, $month, $day, $y);
         $nbDaysInMonth = date("t", mktime(0, 0, 0, $month, 1, $y));
         $endTimestamp   = mktime(23, 59, 59, $month, $nbDaysInMonth, $y);

         #echo "DEBUG createTimeTrackingList: startTimestamp=".date("Y-m-d H:i:s", $startTimestamp)." endTimestamp=".date("Y-m-d H:i:s", $endTimestamp)." nbDays = $nbDaysInMonth<br/>";

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);
         $timeTrackingTable[$startTimestamp] = $timeTracking;

         $day   = 1;
      }
      $start_month = 1;
   }
   return $timeTrackingTable;
}

// =========== MAIN ==========

require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Forecasting'));

if (isset($_SESSION['userid'])) {
    /*
    $defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
    $teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
    $_SESSION['teamid'] = $teamid;
    */
    // use the teamid set in the form, if not defined (first page call) use session teamid
    if (isset($_POST['teamid'])) {
        $teamid = $_POST['teamid'];
    } else {
        $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
    }
    $_SESSION['teamid'] = $teamid;

    $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

    $mTeamList = $session_user->getTeamList();
    $lTeamList = $session_user->getLeadedTeamList();
    $oTeamList = $session_user->getObservedTeamList();
    $managedTeamList = $session_user->getManagedTeamList();
    $teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;

    if (count($teamList) > 0) {
        $smartyHelper->assign('teams', getTeams($teamList));

        if ("displayPage" == $_POST['action'] && 0 != $teamid) {
            $withSupport = true;

            $weekDates = week_dates(date('W'),date('Y'));

            // The first day of the current week
            $startDate = date("Y-m-d", $weekDates[1]);
            $startTimestamp = date2timestamp($startDate);
            #echo "DEBUG startTimestamp ".date("Y-m-d H:i:s", $startTimestamp)."<br/>";

            // The last day of the current week
            $endDate  = date("Y-m-d", $weekDates[5]);
            $endTimestamp = date2timestamp($endDate);
            $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.
            #echo "DEBUG endTimestamp   ".date("Y-m-d H:i:s", $endTimestamp)."<br/>";

            $isManager = array_key_exists($teamid, $managedTeamList);
            $smartyHelper->assign('manager', $isManager);

            $smartyHelper->assign('currentDriftStats', getCurrentDriftStats($startTimestamp, $endTimestamp, $teamid, $isManager));

            $smartyHelper->assign('issuesInDrift', getIssuesInDrift($teamid, $isManager, $withSupport));

            $start_day = 1;
            if (1 == date("m")) {
                $start_month = 12;
                $start_year = date("Y") -1;
            } else {
                $start_month = date("m") -1;
                $start_year = date("Y");
            }
            $timeTrackingTable = createTimeTrackingList($start_day, $start_month, $start_year, $teamid);
            foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
                $val1[] = $timeTracking->getAvailableWorkload();
                $bottomLabel[] = date("M y", $startTimestamp);
                #$logger->debug("workload=$workload date=".date('M y', $startTimestamp));
            }
            $smartyHelper->assign('graphUrl', getGraphUrl(800, 300, $val1, $bottomLabel));
            $smartyHelper->assign('dates', getDates($timeTrackingTable, $val1));
        }
    }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
