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

require('super_header.inc.php');

require('../smarty_tools.php');

require_once('time_tracking_tools.php');

include_once('user_cache.class.php');
include_once('config.class.php');
include_once('time_track.class.php');
include_once('issue_cache.class.php');

$logger = Logger::getLogger("time_tracking");

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
         $smartyHelper->assign('selectedUser', $_SESSION['userid']);
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

      $year = getSecurePOSTIntValue('year',date('Y'));

      $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

      $userid = getSecurePOSTIntValue('userid',$_SESSION['userid']);
      if($userid != $_SESSION['userid']) {
         // Need to be a Team Leader to handle other users
         $teamList = $session_user->getLeadedTeamList();
         if (count($teamList) > 0 && array_key_exists($userid,getUsers())) {
            $smartyHelper->assign('userid', $userid);
            $session_user = UserCache::getInstance()->getUser($userid);
         }
         else {
            sendForbiddenAccess();
         }
      }

      // updateRemaining data
      $remaining = getSecurePOSTNumberValue('remaining',0);

      $action = getSecurePOSTStringValue('action','');
      $weekid = getSecurePOSTIntValue('weekid',date('W'));

      $defaultDate = date("Y-m-d", time());
      $defaultBugid = 0;
      $defaultProjectid=0;

      if ("addTrack" == $action) {
         $defaultDate = getSecurePOSTStringValue('date','');
         $timestamp = date2timestamp($defaultDate);
         $defaultBugid = getSecurePOSTIntValue('bugid');
         $job = getSecurePOSTStringValue('job');
         $duration = getSecurePOSTNumberValue('duree');
         $defaultProjectid  = getSecurePOSTIntValue('projectid');

         // save to DB
         $trackid = TimeTrack::create($userid, $defaultBugid, $job, $timestamp, $duration);

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

         $logger->debug("Track $trackid added  : userid=$userid bugid=$defaultBugid job=$job duration=$duration timestamp=$timestamp");
      }
      elseif ("deleteTrack" == $action) {
         $trackid = getSecurePOSTIntValue('trackid');

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
      }
      elseif ("setProjectid" == $action) {
         // pre-set form fields
         $defaultProjectid = getSecurePOSTIntValue('projectid');
         $defaultDate = getSecurePOSTStringValue('date','');
      }
      elseif ("setBugId" == $action) {
         // --- pre-set form fields
         // find ProjectId to update categories
         $defaultBugid = getSecurePOSTIntValue('bugid');
         $issue = IssueCache::getInstance()->getIssue($defaultBugid);
         $defaultProjectid  = $issue->projectId;
         $defaultDate = getSecurePOSTStringValue('date','');
      }
      elseif ("setFiltersAction" == $action) {
         $isFilter_onlyAssignedTo = isset($_POST["cb_onlyAssignedTo"]) ? '1' : '0';
         $isFilter_hideResolved   = isset($_POST["cb_hideResolved"])   ? '1' : '0';

         $session_user->setTimetrackingFilter('onlyAssignedTo', $isFilter_onlyAssignedTo);
         $session_user->setTimetrackingFilter('hideResolved', $isFilter_hideResolved);

         $defaultProjectid  = getSecurePOSTIntValue('projectid');
      }
      elseif ("updateWeekDisplay" == $action) {
         $defaultBugid = getSecurePOSTIntValue('bugid');
         $defaultProjectid = getSecurePOSTIntValue('projectid');
      }

      // Display user name
      $smartyHelper->assign('otherrealname', $session_user->getRealname());

      // display Track Form
      $smartyHelper->assign('date', $defaultDate);

      // All projects from teams where I'm a Developper
      $devProjList = $session_user->getProjectList($session_user->getDevTeamList());

      // SideTasksProjects from Teams where I'm a Manager
      $managedProjList = $session_user->getProjectList($session_user->getManagedTeamList());
      $projList = $devProjList + $managedProjList;

      $smartyHelper->assign('projects', getProjects($projList,$defaultProjectid));

      $smartyHelper->assign('defaultProjectid', $defaultProjectid);
      $smartyHelper->assign('defaultBugid', $defaultBugid);
      $smartyHelper->assign('weekid', $weekid);
      $smartyHelper->assign('year', $year);

      $isOnlyAssignedTo = ('0' == $session_user->getTimetrackingFilter('onlyAssignedTo')) ? false : true;
      $smartyHelper->assign('isOnlyAssignedTo', $isOnlyAssignedTo);

      $isHideResolved = ('0' == $session_user->getTimetrackingFilter('hideResolved')) ? false : true;
      $smartyHelper->assign('isHideResolved', $isHideResolved);

      $isHideDevProjects = ('0' == $session_user->getTimetrackingFilter('hideDevProjects')) ? false : true;
      $smartyHelper->assign('isHideDevProjects', $isHideDevProjects);

      $smartyHelper->assign('issues', getIssues($defaultProjectid, $isOnlyAssignedTo, $session_user, $projList, $isHideResolved, $defaultBugid));

      $smartyHelper->assign('jobs', getJobs($defaultProjectid));

      $smartyHelper->assign('weeks', getWeeks($weekid, $year));
      $smartyHelper->assign('years', getYears($year,1));

      $weekDates      = week_dates($weekid,$year);
      $startTimestamp = $weekDates[1];
      $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
      $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp);

      // UTF8 problems in smarty, date encoding needs to be done in PHP
      $smartyHelper->assign('weekDates', array(formatDate("%A %d %B", $weekDates[1]),formatDate("%A %d %B", $weekDates[2]),
         formatDate("%A %d %B", $weekDates[3]),formatDate("%A %d %B", $weekDates[4]),
         formatDate("%A %d %B", $weekDates[5])));
      $smartyHelper->assign('weekEndDates', array(formatDate("%A %d %B", $weekDates[6]),formatDate("%A %d %B", $weekDates[7])));

      $smartyHelper->assign('weekTasks', getWeekTask($weekDates, $userid, $timeTracking));

      $smartyHelper->assign('warnings', getCheckWarnings($userid));
      $smartyHelper->assign('timetrackingTuples', getTimetrackingTuples($userid, $startTimestamp));
   }

}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
