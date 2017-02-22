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

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

	$logger = Logger::getLogger("TimeTrackingAjax");

   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   $session_user = $_SESSION['userid'];

   // TODO check $session_user & teamid ?

   $action = Tools::getSecurePOSTStringValue('action');

   if(isset($action)) {
      $smartyHelper = new SmartyHelper();
      $team = TeamCache::getInstance()->getTeam($teamid);
      
      // ================================================================
      if ("getIssuesAndDurations" == $action) {

         // TODO check session_user is allowed to manage user ( & get issue list...)

         $defaultProjectid = Tools::getSecurePOSTIntValue('projectid');
         $managedUserid = Tools::getSecurePOSTIntValue('managedUserid');

         $projList = $team->getProjects(true, false);

         $managedUser = UserCache::getInstance()->getUser($managedUserid);
         $isOnlyAssignedTo = ('0' == $managedUser->getTimetrackingFilter('onlyAssignedTo')) ? false : true;
         $isHideResolved = ('0' == $managedUser->getTimetrackingFilter('hideResolved')) ? false : true;

         $availableIssues = TimeTrackingTools::getIssues($teamid, $defaultProjectid, $isOnlyAssignedTo, $managedUserid, $projList, $isHideResolved, 0);
         $jobs = TimeTrackingTools::getJobs($defaultProjectid, $teamid);
         $durations = TimeTrackingTools::getDurationList($teamid);

         // return data
         $data = array(
             'availableIssues' => $availableIssues,
             'availableJobs' => $jobs,
             'availableDurations' => $durations,
         );
         $jsonData = json_encode($data);
         // return data
         echo $jsonData;

      // ================================================================
      } elseif($action == 'getUpdateBacklogData') {

			// get info to display the updateBacklog dialogbox
         // (when clicking on the backlog value in WeekTaskDetails)
         // OR clicking the addTrack button in addTrack form (form1)
         $bugid       = Tools::getSecurePOSTIntValue('bugid');
         $job         = Tools::getSecurePOSTIntValue('trackJobid', 0);

         $issue = IssueCache::getInstance()->getIssue($bugid);
         $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
         if (($job == Jobs::JOB_SUPPORT) ||
            ($project->isSideTasksProject(array($teamid)) ||
            ($project->isExternalTasksProject()))) {
            // no backlog update for this task
            $data = array('diagnostic' => 'BacklogUpdateNotNeeded');
            $updateBacklogJsonData = json_encode($data);
         } else {
            $managedUserid  = Tools::getSecurePOSTIntValue('userid', 0);
            $trackDuration  = Tools::getSecurePOSTNumberValue('trackDuration', 0);
            $trackDate      = Tools::getSecurePOSTStringValue('trackDate', 0);

            $updateBacklogJsonData = TimeTrackingTools::getUpdateBacklogJsonData($bugid, $job, $teamid, $managedUserid, $trackDate, $trackDuration);
         }

         // return data
			echo $updateBacklogJsonData;

      // ================================================================
		} else if($action == 'updateBacklog') {
         // updateBacklogDoalogbox with 'updateBacklog' action

         $bugid = Tools::getSecurePOSTIntValue('bugid');
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $formattedBacklog = Tools::getSecurePOSTNumberValue('backlog');
         $issue->setBacklog($formattedBacklog);

         // setStatus
         $newStatus = Tools::getSecurePOSTNumberValue('statusid');
         $issue->setStatus($newStatus);

         // return data
         // the complete WeekTaskDetails Div must be updated
         $weekid = Tools::getSecurePOSTIntValue('weekid');
         $year = Tools::getSecurePOSTIntValue('year');
         $userid = Tools::getSecurePOSTIntValue('userid',$session_user);

         setWeekTaskDetails($smartyHelper, $weekid, $year, $userid, $teamid);
         $smartyHelper->display('ajax/weekTaskDetails');

      // ================================================================
      } else if ($action == 'getIssueNoteText') {
         $bugid = Tools::getSecurePOSTIntValue('bugid');
         $issueNote = IssueNote::getTimesheetNote($bugid);
         if (!is_null($issueNote)) {
            $issueNoteId = $issueNote->getId();
            $issueNoteText = trim(IssueNote::removeAllReadByTags($issueNote->getText()));
         } else {
            $issueNoteId = 0;
            $issueNoteText = '';
         }
         // return data
         $data = array(
             'bugid' => $bugid,
             'issuenoteid' => $issueNoteId,
             'issuenote_text' => $issueNoteText,
         );
         $jsonData = json_encode($data);
         // return data
         echo $jsonData;

      // ================================================================
      } else if ($action == 'saveIssueNote') {
         $bugid       = Tools::getSecurePOSTIntValue('bugid');
         $reporter_id = $session_user;
         $issueNoteText = filter_input(INPUT_POST, 'issuenote_text');
         $isTimesheetNote = Tools::getSecurePOSTIntValue('isTimesheetNote');

         if ($isTimesheetNote) {
            IssueNote::setTimesheetNote($bugid, $issueNoteText, $reporter_id);
         } else {
            // create a normal issue note
            IssueNote::create($bugid, $reporter_id, $issueNoteText);
         }

         // return data
         // the complete WeekTaskDetails Div must be updated
         $weekid = Tools::getSecurePOSTIntValue('weekid');
         $year = Tools::getSecurePOSTIntValue('year');
         $userid = Tools::getSecurePOSTIntValue('userid',$session_user);

         setWeekTaskDetails($smartyHelper, $weekid, $year, $userid, $teamid);
         $smartyHelper->display('ajax/weekTaskDetails');

      // ================================================================
      } else if ($action == 'getEditTimetrackData') {
         $timetrackId = Tools::getSecurePOSTStringValue('timetrackId');

         try {
            $tt = TimeTrackCache::getInstance()->getTimeTrack($timetrackId);
            $issue = IssueCache::getInstance()->getIssue($tt->getIssueId());

            $durationsList = TimeTrackingTools::getDurationList($teamid);

            $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
            $projType = $team->getProjectType($issue->getProjectId());
            $availableJobs = $project->getJobList($projType);
            $isRecreditBacklog = (0 == $team->getGeneralPreference('recreditBacklogOnTimetrackDeletion')) ? false : true;


            // return data
            $data = array(
               'statusMsg' => 'SUCCESS',
               'durationsList' => $durationsList,
               'backlog' => $issue->getBacklog(),
               'availableJobs' => $availableJobs,
               'duration' => $tt->getDuration(),
               'jobid' => $tt->getJobId(),
               'note' => $tt->getNote(),
               'issueSummary' => $issue->getSummary(),
               'date' => date("Y-m-d", $tt->getDate()),
               'isRecreditBacklog' => $isRecreditBacklog
            );
         } catch (Exception $e) {
            $data = array(
               'statusMsg' => 'Could not get timetrack data',
            );
         }
         $jsonData = json_encode($data);
         // return data
         echo $jsonData;

      // ================================================================
      } else if ($action == 'getDeleteTimetrackData'){

         $timetrackId = Tools::getSecurePOSTStringValue('timetrackId');

         try {
            $tt = TimeTrackCache::getInstance()->getTimeTrack($timetrackId);
            $issue = IssueCache::getInstance()->getIssue($tt->getIssueId());
            $isRecreditBacklog = (0 == $team->getGeneralPreference('recreditBacklogOnTimetrackDeletion')) ? false : true;

            if ($isRecreditBacklog) {
               if (!is_null($issue->getBacklog())) {
                  $diff = (0 !== $duration) ? ($tt->getDuration() - $duration): $tt->getDuration();
                  $futureBacklog = $issue->getBacklog() + $diff;
               }
               else {
                  $statusMsg = "Backlog update failed.";
                  $isRecreditBacklog = false;
               }
            }

            $jobs = new Jobs();
            // return data
            $data = array(
               'statusMsg' => 'SUCCESS',
               'isRecreditBacklog' => $isRecreditBacklog,
               'futureBacklog' => $futureBacklog,
               'formatedId' => $issue->getFormattedIds(),
               'duration' => $tt->getDuration(),
               'jobName' => $jobs->getJobName($tt->getJobId()),
               'issueSummary' => $issue->getSummary(),
               'date' => date("Y-m-d", $tt->getDate()),
            );
         } catch (Exception $e) {
            $data = array(
               'statusMsg' => 'Could not get timetrack data',
            );
         }
         $jsonData = json_encode($data);
         // return data
         echo $jsonData;

      // ================================================================
      } else if ($action == 'updateTimetrack') {

         $timetrackId = Tools::getSecurePOSTIntValue('timetrackId');
         $weekid = Tools::getSecurePOSTIntValue('weekid');
         $year = Tools::getSecurePOSTIntValue('year');
         $userid = Tools::getSecurePOSTIntValue('userid',$session_user);

         $team = TeamCache::getInstance()->getTeam($teamid);

         $dateStr = Tools::getSecurePOSTStringValue('date');
         $date = strtotime($dateStr);

         $duration = Tools::getSecurePOSTNumberValue('duration');
         $jobid = Tools::getSecurePOSTIntValue('jobid');
         $timetrack = TimeTrackCache::getInstance()->getTimeTrack($timetrackId);
         $note = NULL;

         if (1 == $team->getGeneralPreference('useTrackNote')) {
            $note = Tools::getSecurePOSTStringValue('note');
         }

         $isRecreditBacklog = (0 == $team->getGeneralPreference('recreditBacklogOnTimetrackDeletion')) ? false : true;

         if ($isRecreditBacklog) {
            $diff = $timetrack->getDuration() - $duration;
            $timetrack->updateBacklog($diff);
         }

         $updateDone = $timetrack->update($date, $duration, $jobid, $note );
         $statusMsg = ($updateDone) ? "SUCCESS" : "timetrack update failed.";

         // the complete WeekTaskDetails Div must be updated
         setWeekTaskDetails($smartyHelper, $weekid, $year, $userid, $teamid);
         $html = $smartyHelper->fetch('ajax/weekTaskDetails');

         $jobs = new Jobs();
         $data = array(
            'statusMsg' => $statusMsg,
            'timetrackId' => $timetrackId,
            'cosmeticDate' => Tools::formatDate("%Y-%m-%d - %A", $timetrack->getDate()),
            'jobName' => $jobs->getJobName($jobid),
            'timesheetHtml' => $html,
         );
         $jsonData = json_encode($data);

         // return data
         echo $jsonData;
         
      // ================================================================
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

/**
 * set smarty variables needed to display the WeekTaskDetails table
 *
 * @param type $smartyHelper
 * @param type $weekid
 * @param type $year
 * @param type $managed_userid
 * @param type $teamid
 */
function setWeekTaskDetails($smartyHelper, $weekid, $year, $managed_userid, $teamid) {

   $weekDates = Tools::week_dates($weekid,$year);
   $startTimestamp = $weekDates[1];
   $endTimestamp = mktime(23, 59, 59, date('m', $weekDates[7]), date('d', $weekDates[7]), date('Y', $weekDates[7]));
   $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);


   $incompleteDays = array_keys($timeTracking->checkCompleteDays($managed_userid, TRUE));
   $missingDays = $timeTracking->checkMissingDays($managed_userid);
   $errorDays = array_merge($incompleteDays,$missingDays);
   $smartyWeekDates = TimeTrackingTools::getSmartyWeekDates($weekDates,$errorDays);

   // UTF8 problems in smarty, date encoding needs to be done in PHP
   $smartyHelper->assign('weekDates', array(
      $smartyWeekDates[1], $smartyWeekDates[2], $smartyWeekDates[3], $smartyWeekDates[4], $smartyWeekDates[5]
   ));
   $smartyHelper->assign('weekEndDates', array(
      $smartyWeekDates[6], $smartyWeekDates[7]
   ));

   $weekTasks = TimeTrackingTools::getWeekTask($weekDates, $teamid, $managed_userid, $timeTracking, $errorDays);
   $smartyHelper->assign('weekTasks', $weekTasks["weekTasks"]);
   $smartyHelper->assign('dayTotalElapsed', $weekTasks["totalElapsed"]);

   // weekTaskDetails.html includes edit_issueNote.html & update_issueBacklog.html
   // these files need userid,weekid,year to be set.
   $smartyHelper->assign('userid', $managed_userid);
   $smartyHelper->assign('weekid', $weekid);
   $smartyHelper->assign('year', $year);


}

?>
