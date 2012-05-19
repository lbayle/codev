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

include('../smarty_tools.php');

include_once "issue.class.php";
include_once "project.class.php";
include_once "user.class.php";
include_once "time_tracking.class.php";

/**
 * Get project activity report
 * @param array $projectTracks
 * @param boolean $isDetailed
 * @return array
 */
function getProjectActivityReport($projectTracks, $isDetailed) {
   foreach ($projectTracks as $projectId => $bugList) {
      $project = ProjectCache::getInstance()->getProject($projectId);

      $jobList = $project->getJobList();
      $jobTypeList = "";
      if ($isDetailed) {
         $jobColWidth = (0 != count($jobList)) ? (100 - 50 - 10 - 6) / count($jobList) : 10;
         foreach($jobList as $jobId => $jobName) {
            $jobTypeList[] = array('width' => $jobColWidth,
                                   'name' => $jobName
            );
         }
      }

      // write table content (by bugid)
      $row_id = 0;
      $bugDetailedList = "";
      foreach ($bugList as $bugid => $jobs) {
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $totalTime = 0;
         $tr_class = ($row_id & 1) ? "row_even" : "row_odd";

         $subJobList = "";
         foreach($jobList as $jobId => $jobName) {
            if ($isDetailed) {
               $subJobList[] = array('width' => $jobColWidth,
                                     'id' => $jobs[$jobId]
               );
            }
            $totalTime += $jobs[$jobId];
         }

         $row_id += 1;

         $bugDetailedList[] = array('class' => $tr_class,
                                  'issueURL' => issueInfoURL($bugid),
                                  'id' => $issue->tcId,
                                  'summary' => $issue->summary,
                                  'jobList' => $subJobList,
                                  'targetVersion' => $issue->getTargetVersion(),
                                  'currentStatusName' => $issue->getCurrentStatusName(),
                                  'progress' => round(100 * $issue->getProgress()),
                                  'remaining' => $issue->remaining,
                                  'totalTime' => $totalTime,
         );
      }

      $projectActivityReport[] = array('name' => $project->name,
                                       'jobList' => $jobTypeList,
                                       'bugList' => $bugDetailedList
      );
   }

   return $projectActivityReport;
}

// ================ MAIN =================
require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Weekly activities'));

if(isset($_SESSION['userid'])) {
   // team
   $user = UserCache::getInstance()->getUser($_SESSION['userid']);
   $mTeamList = $user->getDevTeamList();
   $lTeamList = $user->getLeadedTeamList();
   $oTeamList = $user->getObservedTeamList();
   $managedTeamList = $user->getManagedTeamList();
   $teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;

   if (0 != count($teamList)) {
      $defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
      $teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
      $_SESSION['teamid'] = $teamid;

      // dates
      $weekDates = week_dates(date('W'),date('Y'));
      $startdate = isset($_POST["startdate"]) ? $_POST["startdate"] : formatDate("%Y-%m-%d",$weekDates[1]);
      $smartyHelper->assign('startDate', $startdate);

      $enddate = isset($_POST["enddate"]) ? $_POST["enddate"] : formatDate("%Y-%m-%d",$weekDates[5]);
      $smartyHelper->assign('endDate', $enddate);

      $smartyHelper->assign('teams', getTeams($teamList,$teamid));

      $isDetailed = isset($_POST['cb_detailed']);
      $smartyHelper->assign('isDetailed', $isDetailed);

      if (isset($_POST['teamid']) && NULL != $teamList[$teamid]) {
         $startTimestamp = date2timestamp($startdate);
         $endTimestamp = date2timestamp($enddate);
         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

         $smartyHelper->assign('projectActivityReport', getProjectActivityReport($timeTracking->getProjectTracks(true), $isDetailed));
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
