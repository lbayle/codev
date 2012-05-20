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

include_once "issue.class.php";
include_once "project.class.php";
include_once "user.class.php";
include_once "time_tracking.class.php";
include_once "time_tracking_tools.php";

$logger = Logger::getLogger("time_tracking");

/**
 * Get users of teams I lead
 * @return array of users
 */
function getUsers() {
  global $logger;

   $accessLevel_dev = Team::accessLevel_dev;
   $accessLevel_manager = Team::accessLevel_manager;

   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   $teamList = $session_user->getLeadedTeamList();

   // separate list elements with ', '
   $formatedTeamString = implode( ', ', array_keys($teamList));

   // show only users from the teams that I lead.
   $query = "SELECT DISTINCT mantis_user_table.id, mantis_user_table.username, mantis_user_table.realname ".
            "FROM `mantis_user_table`, `codev_team_user_table` ".
            "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
            "AND codev_team_user_table.team_id IN ($formatedTeamString) ".
            "AND codev_team_user_table.access_level IN ($accessLevel_dev, $accessLevel_manager) ".
            "ORDER BY mantis_user_table.username";

   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      exit;
   }

   while($row = mysql_fetch_object($result)) {
      $users[] = array('id' => $row->id,
         'name' => $row->username,
         'selected' => $row->id == $_SESSION['userid']
      );
   }

   return $users;
}

/**
 * Get issues
 * @param int $projectid
 * @param boolean $isOnlyAssignedTo
 * @param unknown_type $user1
 * @param array $projList
 * @param boolean $isHideResolved
 * @param int $defaultBugid
 * @return array
 */
function getIssues($projectid, $isOnlyAssignedTo, $user1, $projList, $isHideResolved, $defaultBugid) {
   if (0 != $projectid) {
      // Project list
      $project1 = ProjectCache::getInstance()->getProject($projectid);

      // do not filter on userId if SideTask or ExternalTask
      if (($isOnlyAssignedTo) && (!$project1->isSideTasksProject()) && (!$project1->isNoStatsProject())) {
         $handler_id = $user1->id;
      } else {
         $handler_id = 0; // all users
         $isHideResolved = false; // do not hide resolved
      }

      $issueList = $project1->getIssueList($handler_id, $isHideResolved);
   } else {
      // no project specified: show all tasks
      $issueList = array();

      foreach ($projList as $pid => $pname) {
         $proj = ProjectCache::getInstance()->getProject($pid);
         if (($proj->isSideTasksProject()) || ($proj->isNoStatsProject())) {
            // do not hide any task for SideTasks & ExternalTasks projects
            $buglist = $proj->getIssueList(0, false);
            $issueList = array_merge($issueList, $buglist);
         } else {
            $handler_id = $isOnlyAssignedTo ? $user1->id : 0;
            $buglist = $proj->getIssueList($handler_id, $isHideResolved);
            $issueList = array_merge($issueList, $buglist);
         }
      }
      rsort($issueList);
   }

   foreach ($issueList as $bugid) {
      $issue = IssueCache::getInstance()->getIssue($bugid);
      $issues[] = array('id' => $bugid,
            'tcId' => $issue->tcId,
            'summary' => $issue->summary,
            'selected' => $bugid == $defaultBugid);
   }

   return $issues;
}

/**
 * get Job list
 * @param int $projectid
 * @return array
 */
function getJobs($projectid) {
   global $logger;

   if (0 != $projectid) {
      // Project list
      $project1 = ProjectCache::getInstance()->getProject($projectid);

      $jobList = $project1->getJobList();
   } else {
      $query = "SELECT id, name FROM `codev_job_table` ";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         return;
      }

      if (0 != mysql_num_rows($result)) {
         while ($row = mysql_fetch_object($result)) {
            $jobList[$row->id] = $row->name;
         }
      }
   }

   return $jobList;
}

// ================ MAIN =================

require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Time Tracking'));

if($_SESSION['userid']) {

// if first call to this page
if (!isset($_POST['nextForm'])) {
      $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
      $teamList = $session_user->getLeadedTeamList();

   if (0 != count($teamList)) {
      // User is TeamLeader, let him choose the user he wants to manage
      $smartyHelper->assign('users', getUsers());
   } else {
      // developper & manager can add timeTracks
      $mTeamList = $session_user->getDevTeamList();
      $managedTeamList = $session_user->getManagedTeamList();
      $teamList = $mTeamList + $managedTeamList;

      if (0 != count($teamList)) {
         $_POST['nextForm'] = "addTrackForm";
      }
   }
}

if ($_POST['nextForm'] == "addTrackForm") {
   $job_support = Config::getInstance()->getValue(Config::id_jobSupport);

   $year = isset($_POST['year']) ? $_POST['year'] : date('Y');

   $userid = isset($_POST['userid']) ? $_POST['userid'] : $_SESSION['userid'];
   $managed_user = UserCache::getInstance()->getUser($userid);

   // updateRemaining data
   $bugid  = isset($_POST['bugid']) ? $_POST['bugid'] : '';
   $remaining  = isset($_POST['remaining']) ? $_POST['remaining'] : '';

   $action = isset($_POST["action"]) ? $_POST["action"] : '';
   $weekid = isset($_POST['weekid']) ? $_POST['weekid'] : date('W');

   $defaultDate = date("Y-m-d", time());
   $defaultBugid = 0;
   $defaultProjectid=0;

   if ("addTrack" == $action) {
      $defaultDate = isset($_POST["date"]) ? $_POST["date"] : "";
      $timestamp = date2timestamp($defaultDate);
      $defaultBugid = $_POST['bugid'];
      $job = $_POST['job'];
      $duration = $_POST['duree'];
      $defaultProjectid  = $_POST['projectid'];

      // save to DB
      $trackid = TimeTrack::create($managed_user->id, $defaultBugid, $job, $timestamp, $duration);

      // do NOT decrease remaining if job is job_support !
      if ($job != $job_support) {
         // decrease remaining (only if 'remaining' already has a value)
         $issue = IssueCache::getInstance()->getIssue($defaultBugid);
         if (NULL != $issue->remaining) {
            $remaining = $issue->remaining - $duration;
            if ($remaining < 0) { $remaining = 0; }
            $issue->setRemaining($remaining);
         }
      }

      $logger->debug("Track $trackid added  : userid=$managed_user->id bugid=$defaultBugid job=$job duration=$duration timestamp=$timestamp");
   } elseif ("deleteTrack" == $action) {
      $trackid  = $_POST['trackid'];

      // increase remaining (only if 'remaining' already has a value)
      $query = 'SELECT * FROM `codev_timetracking_table` WHERE id = '.$trackid.';';
      $result = mysql_query($query);
      if ($result) {
         while($row = mysql_fetch_object($result)) {
            // REM: only one line in result, while should be optimized
            $defaultBugid = $row->bugid;
            $duration = $row->duration;
            $job = $row->jobid;
            $trackUserid = $row->userid;
            $trackDate = $row->date;
         }

         $issue = IssueCache::getInstance()->getIssue($defaultBugid);
         // do NOT decrease remaining if job is job_support !
         if ($job != $job_support) {
            if (NULL != $issue->remaining) {
               $remaining = $issue->remaining + $duration;
               $issue->setRemaining($remaining);
            }
         }

         // delete track
         # TODO use TimeTrack::delete($trackid)
         $query = "DELETE FROM `codev_timetracking_table` WHERE id = $trackid;";
         $result = mysql_query($query);
         if (!$result) {
            $logger->error("Query FAILED: $query");
            $logger->error(mysql_error());
            $smartyHelper->assign('error', "Failed to delete the tasks");
         } else {
            $logger->debug("Track $trackid deleted: userid=$trackUserid bugid=$defaultBugid job=$job duration=$duration timestamp=$trackDate");
         }
      }
      else {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         $smartyHelper->assign('error', "Failed to update the remaining");
      }

      // pre-set form fields
      $defaultProjectid  = $issue->projectId;
   } elseif ("setProjectid" == $action) {
      // pre-set form fields
      $defaultProjectid = $_POST['projectid'];
      $defaultDate = isset($_POST["date"]) ? $_POST["date"] : "";
   } elseif ("setBugId" == $action) {
      // --- pre-set form fields
      // find ProjectId to update categories
      $defaultBugid = $_POST['bugid'];
      $issue = IssueCache::getInstance()->getIssue($defaultBugid);
      $defaultProjectid  = $issue->projectId;
      $defaultDate = isset($_POST["date"]) ? $_POST["date"] : "";
   } elseif ("setFiltersAction" == $action) {
      $isFilter_onlyAssignedTo = isset($_POST["cb_onlyAssignedTo"]) ? '1' : '0';
      $isFilter_hideResolved   = isset($_POST["cb_hideResolved"])   ? '1' : '0';

      $managed_user->setTimetrackingFilter('onlyAssignedTo', $isFilter_onlyAssignedTo);
      $managed_user->setTimetrackingFilter('hideResolved', $isFilter_hideResolved);

      $defaultProjectid  = $_POST['projectid'];
   } elseif ("updateWeekDisplay" == $action) {
      $defaultBugid = $bugid;
      $defaultProjectid = $_POST['projectid'];
   }

   // Display user name
   $smartyHelper->assign('otherrealname', $managed_user->getRealname());

   // display Track Form
   $smartyHelper->assign('date', $defaultDate);
   $smartyHelper->assign('userid', $managed_user->id);

   // All projects from teams where I'm a Developper
   $devProjList = $managed_user->getProjectList($managed_user->getDevTeamList());

   // SideTasksProjects from Teams where I'm a Manager
   $managedProjList = $managed_user->getProjectList($managed_user->getManagedTeamList());
   $projList = $devProjList + $managedProjList;

   $smartyHelper->assign('projects', getProjects($projList,$defaultProjectid));

   $smartyHelper->assign('defaultProjectid', $defaultProjectid);
   $smartyHelper->assign('defaultBugid', $defaultBugid);
   $smartyHelper->assign('weekid', $weekid);
   $smartyHelper->assign('year', $year);

   $isOnlyAssignedTo = ('0' == $managed_user->getTimetrackingFilter('onlyAssignedTo')) ? false : true;
   $smartyHelper->assign('isOnlyAssignedTo', $isOnlyAssignedTo);

   $isHideResolved = ('0' == $managed_user->getTimetrackingFilter('hideResolved')) ? false : true;
   $smartyHelper->assign('isHideResolved', $isHideResolved);

   $isHideDevProjects = ('0' == $managed_user->getTimetrackingFilter('hideDevProjects')) ? false : true;
   $smartyHelper->assign('isHideDevProjects', $isHideDevProjects);

   $smartyHelper->assign('issues', getIssues($defaultProjectid, $isOnlyAssignedTo, $managed_user, $projList, $isHideResolved, $defaultBugid));

   $smartyHelper->assign('jobs', getJobs($defaultProjectid));

   $smartyHelper->assign('weeks', getWeeks($weekid, $year));
   $smartyHelper->assign('years', getYears($year,1));

   $weekDates      = week_dates($weekid,$year);
   $startTimestamp = $weekDates[1];
   $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
   $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp);
   $smartyHelper->assign('weekDates', $weekDates);
   $smartyHelper->assign('weekTasks', getWeekTask($weekDates, $userid, $timeTracking));

   $smartyHelper->assign('warnings', getCheckWarnings($userid));
   $smartyHelper->assign('timetrackingTuples', getTimetrackingTuples($userid, $startTimestamp));
}

}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
