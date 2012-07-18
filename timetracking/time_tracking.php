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

require_once('timetracking/time_tracking_tools.php');

include_once('classes/config.class.php');
include_once('classes/issue_cache.class.php');
include_once('classes/project_cache.class.php');
include_once('classes/time_track.class.php');
include_once('classes/timetrack_cache.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

$logger = Logger::getLogger("time_tracking");

// ================ MAIN =================
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Time Tracking');

if($_SESSION['userid']) {

   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   // if first call to this page
   if (!isset($_POST['nextForm'])) {
      $lTeamList = $session_user->getLeadedTeamList();

      if (0 != count($lTeamList)) {
         // User is TeamLeader, let him choose the user he wants to manage
         $smartyHelper->assign('users', getUsers());
         $smartyHelper->assign('selectedUser', $session_user->id);
      } else {
         // if session_user (not a teamLeader) is defined in a team, display AddTrack page

         // developper & manager can add timeTracks
         $mTeamList = $session_user->getDevTeamList();
         $managedTeamList = $session_user->getManagedTeamList();
         $teamList = $mTeamList + $managedTeamList;

         if (0 != count($teamList)) {
            $_POST['userid']   = $session_user->id;
            $_POST['nextForm'] = "addTrackForm";
         }
      }
   }

   // display AddTrack Page
   if ($_POST['nextForm'] == "addTrackForm") {
      $job_support = Config::getInstance()->getValue(Config::id_jobSupport);

      $year   = Tools::getSecurePOSTIntValue('year',date('Y'));
      $userid = Tools::getSecurePOSTIntValue('userid',$session_user->id);

      $managed_user = UserCache::getInstance()->getUser($userid);

      if($userid != $session_user->id) {
         // Need to be a Team Leader to handle other users
         $lTeamList = $session_user->getLeadedTeamList();
         if (count($lTeamList) > 0 && array_key_exists($userid,getUsers())) {
            $smartyHelper->assign('userid', $userid);

         } else {
            Tools::sendForbiddenAccess();
         }
      }

      // developper & manager can add timeTracks
      $mTeamList = $managed_user->getDevTeamList();
      $managedTeamList = $managed_user->getManagedTeamList();
      $teamList = $mTeamList + $managedTeamList;

      // updateRemaining data
      $remaining = Tools::getSecurePOSTNumberValue('remaining',0);

      $action = Tools::getSecurePOSTStringValue('action','');
      $weekid = Tools::getSecurePOSTIntValue('weekid',date('W'));

      $defaultDate = Tools::getSecurePOSTStringValue('date',date("Y-m-d", time()));;
      $defaultBugid = Tools::getSecurePOSTIntValue('bugid',0);
      $defaultProjectid = Tools::getSecurePOSTIntValue('projectid',0);
      $job = Tools::getSecurePOSTIntValue('job',0);
      $duration = Tools::getSecurePOSTNumberValue('duree',0);

      if ("addTrack" == $action) {
         $timestamp = Tools::date2timestamp($defaultDate);
         $defaultBugid = Tools::getSecurePOSTIntValue('bugid');
         $job = Tools::getSecurePOSTStringValue('job');
         $duration = Tools::getSecurePOSTNumberValue('duree');
         $defaultProjectid = Tools::getSecurePOSTIntValue('projectid');

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

            // open the updateRemaining DialogBox on page reload
            $project = ProjectCache::getInstance()->getProject($issue->projectId);
            if (($job != $job_support) &&
                (!$project->isSideTasksProject(array_keys($teamList)) &&
                (!$project->isExternalTasksProject()))) {
               $issueInfo = array( 'remaining' => $issue->remaining,
                                   'bugid' => $issue->bugId,
                                   'description' => $issue->summary,
                                   'dialogBoxTitle' => $issue->bugId." / ".$issue->tcId);

               $smartyHelper->assign('updateRemainingRequested', $issueInfo);
            }
         }

         $logger->debug("Track $trackid added  : userid=$userid bugid=$defaultBugid job=$job duration=$duration timestamp=$timestamp");

         // Don't show job and duration after add track
         $job = 0;
         $duration = 0;
      }
      elseif ("deleteTrack" == $action) {
         $trackid = Tools::getSecurePOSTIntValue('trackid');

         // increase remaining (only if 'remaining' already has a value)
         $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($trackid);
         $defaultBugid = $timeTrack->bugId;
         $duration = $timeTrack->duration;
         $job = $timeTrack->jobId;
         $trackUserid = $timeTrack->userId;
         $trackDate = $timeTrack->date;

         // delete track
         if(!$timeTrack->remove()) {
            $smartyHelper->assign('error', "Failed to delete the tasks");
         }

         try {
            $issue = IssueCache::getInstance()->getIssue($defaultBugid);
            // do NOT decrease remaining if job is job_support !
            if ($job != $job_support) {
               if (NULL != $issue->remaining) {
                  $remaining = $issue->remaining + $duration;
                  $issue->setRemaining($remaining);
               }
            }

            // pre-set form fields
            $defaultProjectid  = $issue->projectId;
         } catch (Exception $e) {
            $defaultProjectid  = 0;
         }
      }
      elseif ("setProjectid" == $action) {
         // pre-set form fields
         $defaultProjectid = Tools::getSecurePOSTIntValue('projectid');
         // Don't show job and duration after change project
         $job = 0;
         $duration = 0;
      }
      elseif ("setBugId" == $action) {
         // pre-set form fields
         // find ProjectId to update categories
         $defaultBugid = Tools::getSecurePOSTIntValue('bugid');
         $issue = IssueCache::getInstance()->getIssue($defaultBugid);
         $defaultProjectid  = $issue->projectId;
      }
      elseif ("setFiltersAction" == $action) {
         $isFilter_onlyAssignedTo = isset($_POST["cb_onlyAssignedTo"]) ? '1' : '0';
         $isFilter_hideResolved   = isset($_POST["cb_hideResolved"])   ? '1' : '0';

         $managed_user->setTimetrackingFilter('onlyAssignedTo', $isFilter_onlyAssignedTo);
         $managed_user->setTimetrackingFilter('hideResolved', $isFilter_hideResolved);

         if($defaultBugid != 0) {
            $issue = IssueCache::getInstance()->getIssue($defaultBugid);
            $defaultProjectid = $issue->projectId;
         }
      }

      // Display user name
      $smartyHelper->assign('otherrealname', $managed_user->getRealname());

      // display Track Form
      $smartyHelper->assign('date', $defaultDate);

      // All projects from teams where I'm a Developper
      $devProjList = $managed_user->getProjectList($managed_user->getDevTeamList());

      // SideTasksProjects from Teams where I'm a Manager
      $managedProjList = $managed_user->getProjectList($managed_user->getManagedTeamList());
      $projList = $devProjList + $managedProjList;

      $smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList,$defaultProjectid));

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

      $smartyHelper->assign('issues', getIssues($defaultProjectid, $isOnlyAssignedTo, $managed_user->id, $projList, $isHideResolved, $defaultBugid));

      $smartyHelper->assign('jobs', getJobs($defaultProjectid, $teamList, $job));
      $smartyHelper->assign('duration', getDuration($duration));

      $smartyHelper->assign('weeks', SmartyTools::getWeeks($weekid, $year));
      $smartyHelper->assign('years', SmartyTools::getYears($year,1));

      $weekDates      = Tools::week_dates($weekid,$year);
      $startTimestamp = $weekDates[1];
      $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
      $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp);

      // UTF8 problems in smarty, date encoding needs to be done in PHP
      $smartyHelper->assign('weekDates', array(
         date('Y-m-d',$weekDates[1]) => Tools::formatDate("%A %d %B", $weekDates[1]),
         date('Y-m-d',$weekDates[2]) => Tools::formatDate("%A %d %B", $weekDates[2]),
         date('Y-m-d',$weekDates[3]) => Tools::formatDate("%A %d %B", $weekDates[3]),
         date('Y-m-d',$weekDates[4]) => Tools::formatDate("%A %d %B", $weekDates[4]),
         date('Y-m-d',$weekDates[5]) => Tools::formatDate("%A %d %B", $weekDates[5]))
      );
      $smartyHelper->assign('weekEndDates', array(Tools::formatDate("%A %d %B", $weekDates[6]),Tools::formatDate("%A %d %B", $weekDates[7])));

      $smartyHelper->assign('weekTasks', getWeekTask($weekDates, $userid, $timeTracking));

      $smartyHelper->assign('warnings', getCheckWarnings($userid));

      $smartyHelper->assign('weekTimetrackingTuples', getTimetrackingTuples($userid, $startTimestamp, $endTimestamp));
      $smartyHelper->assign('timetrackingTuples', getTimetrackingTuples($userid, $endTimestamp));
   }

}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
