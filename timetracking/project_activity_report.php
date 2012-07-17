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

include_once('classes/issue_cache.class.php');
include_once('classes/project_cache.class.php');
include_once('classes/team_cache.class.php');
include_once('classes/time_tracking.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

/**
 * Get project activity report
 * @param mixed[][][] $projectTracks
 * @param int $teamid The team id
 * @param boolean $isDetailed
 * @return mixed[]
 */
function getProjectActivityReport($projectTracks, $teamid, $isDetailed) {
   $team = TeamCache::getInstance()->getTeam($teamid);
   $projectActivityReport = NULL;
   foreach ($projectTracks as $projectId => $bugList) {
      $project = ProjectCache::getInstance()->getProject($projectId);

      $jobList = $project->getJobList($team->getProjectType($projectId));
      $jobTypeList = "";
      if ($isDetailed) {
         $jobColWidth = (0 != count($jobList)) ? (100 - 50 - 10 - 6) / count($jobList) : 10;
         foreach($jobList as $jobId => $jobName) {
            $jobTypeList[$jobId] = array(
                'width' => $jobColWidth,
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
               $subJobList[$jobId] = array(
                   'width' => $jobColWidth,
                   'id' => $jobs[$jobId]
               );
            }
            $totalTime += $jobs[$jobId];
         }

         $row_id += 1;

         $bugDetailedList[$bugid] = array(
             'class' => $tr_class,
             'mantisURL' => Tools::mantisIssueURL($bugid, NULL, true),
             'issueURL' => Tools::issueInfoURL($bugid),
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

      $projectActivityReport[$projectId] = array(
          'id' => $project->id,
          'name' => $project->name,
          'jobList' => $jobTypeList,
          'bugList' => $bugDetailedList
      );
   }

   return $projectActivityReport;
}

// ================ MAIN =================
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Weekly activities');

if(isset($_SESSION['userid'])) {
   // team
   $user = UserCache::getInstance()->getUser($_SESSION['userid']);
   $teamList = $user->getTeamList();

   if (count($teamList) > 0) {
      // use the teamid set in the form, if not defined (first page call) use session teamid
      $teamid = 0;
      if (isset($_POST['teamid'])) {
         $teamid = Tools::getSecurePOSTIntValue('teamid');
         $_SESSION['teamid'] = $teamid;
      } elseif (isset($_SESSION['teamid'])) {
         $teamid = $_SESSION['teamid'];
      }

      // dates
      $weekDates = Tools::week_dates(date('W'),date('Y'));
      $startdate = Tools::getSecurePOSTStringValue("startdate",Tools::formatDate("%Y-%m-%d",$weekDates[1]));
      $smartyHelper->assign('startDate', $startdate);

      $enddate = Tools::getSecurePOSTStringValue("enddate",Tools::formatDate("%Y-%m-%d",$weekDates[5]));
      $smartyHelper->assign('endDate', $enddate);

      $smartyHelper->assign('teams', Tools::getSmartyArray($teamList,$teamid));

      $isDetailed = Tools::getSecurePOSTStringValue('cb_detailed','');
      $smartyHelper->assign('isDetailed', $isDetailed);

      if (isset($_POST['teamid']) && array_key_exists($teamid, $teamList)) {
         $startTimestamp = Tools::date2timestamp($startdate);
         $endTimestamp = Tools::date2timestamp($enddate);
         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

         $smartyHelper->assign('projectActivityReport', getProjectActivityReport($timeTracking->getProjectTracks(true), $teamid, $isDetailed));
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
