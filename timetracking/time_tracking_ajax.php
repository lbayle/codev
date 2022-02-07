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
   $session_userid = $_SESSION['userid'];

   // TODO check $session_userid & teamid ?

   $action = Tools::getSecurePOSTStringValue('action');

   if(isset($action)) {
      $smartyHelper = new SmartyHelper();
      $team = TeamCache::getInstance()->getTeam($teamid);

      // ================================================================
      if('searchIssues' == $action) {

         $searchStr = Tools::getSecurePOSTStringValue('search', '');
         $projectId = Tools::getSecurePOSTIntValue('projectId');
         $managedUserid = Tools::getSecurePOSTIntValue('managedUserid');

         $managedUser = UserCache::getInstance()->getUser($managedUserid);
         $isOnlyAssignedTo = ('0' == $managedUser->getTimetrackingFilter('onlyAssignedTo')) ? false : true;
         $isHideResolved = ('0' == $managedUser->getTimetrackingFilter('hideResolved')) ? false : true;
         //$isHideForbidenStatus = ('0' == $managedUser->getTimetrackingFilter('hideForbidenStatus')) ? false : true;
         $isHideForbidenStatus=true;
         $hideNoActivitySince = $managedUser->getTimetrackingFilter('hideNoActivitySince');
//         $availableIssues = TimeTrackingTools::getIssues($teamid, $defaultProjectid, $isOnlyAssignedTo, $managedUserid, $projList, $isHideResolved, $isHideForbidenStatus, 0, $hideNoActivitySince);
         
         
         $data = array();
         try {
            if (!empty($searchStr)) {
               $projectidList = array($projectId);
               if (0 == $projectId) {
                  $projList = array ();
                     $projectidList = array_keys($team->getProjects(true, false, true));
               }
               $issueList = Issue::search($searchStr, $projectidList);
               // https://select2.org/data-sources/formats
               foreach ($issueList as $issue) {
                  $data[] = array('id'=>$issue->getId(), 'text'=>$issue->getFormattedIds().' : '.$issue->getSummary());
               }
            }
         } catch (Exception $e) {
            self::$logger->error("EXCEPTION searchIssues: " . $e->getMessage());
            self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());         
         }

         $jsonData=json_encode($data);
         //$logger->error("jsonData=$jsonData");
         echo $jsonData;

      // ================================================================
      } elseif ("getJobsAndDurations" == $action) {

         $projectid = Tools::getSecurePOSTIntValue('projectid', 0);
         $bugid = Tools::getSecurePOSTIntValue('bugid', 0);
         try {
            if (0 != $bugid) {
               $issue = IssueCache::getInstance()->getIssue($bugid);
               $projectid = $issue->getProjectId();
            }
            if (0 != $projectid) {
               $ptype = $team->getProjectType($projectid);
               $project = ProjectCache::getInstance()->getProject($projectid);
               $jobs = $project->getJobList($ptype, $teamid);
            } else {
               $jobs = array();
            }

            if (0 != $bugid) {
               // JOB_SUPPORT on a 'new' task is nonsense (if work has not been started,
               // then the handler cannot be 'helped' by someone) but MOST IMPORTANT
               // we must avoid to have timetracks on a 'new' task. This happens because
               // for JOB_SUPPORT the UpdateBacklogDialogbox is not opened and status check is skipped
               if ( Constants::$status_new ==  $issue->getStatus()) {
                  unset($jobs[Jobs::JOB_SUPPORT]);
               }
            }
            $durations = TimeTrackingTools::getDurationList($teamid);

            // return data
            $data = array(
               'statusMsg' => 'SUCCESS',
               'availableJobs' => $jobs,
               'availableDurations' => $durations,
            );
         }  catch (Exception $e) {
            $logger->error("EXCEPTION deleteTrack: ".$e->getMessage());
            $data = array(
               'statusMsg' => 'Could not get jobList',
            );
         }
         $jsonData = json_encode($data);
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
         $issueStatus = $issue->getStatus();

         $ttForbidenStatusList = $team->getTimetrackingForbidenStatusList($issue->getProjectId());
         if (array_key_exists($issueStatus, $ttForbidenStatusList)) {
            $data = array(
               'diagnostic' => 'TimetrackingForbidden',
               'reasonWhy' => T_("Timetracking is forbidden when issue's status is :").' "'.$ttForbidenStatusList[$issueStatus].'"'
            );
            $updateBacklogJsonData = json_encode($data);
         } else if (($job == Jobs::JOB_SUPPORT) ||
            ($project->isSideTasksProject(array($teamid)) ||
            ($project->isExternalTasksProject()))) {

            // no backlog update for this task
            $isTrackNoteDisplayed = (0 == $team->getGeneralPreference('useTrackNote')) ? false : true;
            if ($isTrackNoteDisplayed) {
               $data = array('diagnostic' => 'timetrackNoteOnly');
            } else {
               $data = array('diagnostic' => 'BacklogUpdateNotNeeded');
            }
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
         $userid = Tools::getSecurePOSTIntValue('trackUserid',$session_userid);

         setWeekTaskDetails($smartyHelper, $weekid, $year, $userid, $teamid);
         $htmlContent = $smartyHelper->fetch('ajax/weekTaskDetails');

         // return data
         $data = array(
             'statusMsg' => 'SUCCESS',
             'weekTaskDetailsHtml' => $htmlContent,
         );
         $jsonData = json_encode($data);
         echo $jsonData;

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
         $reporter_id = $session_userid;
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
         $userid = Tools::getSecurePOSTIntValue('userid',$session_userid);

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

            if (null !== $projType) {
               $availableJobs = $project->getJobList($projType, $teamid);
            } else {
               // if this timetrack is on a project not defined in this team,
               // it is not possible to get the jobList. All we can do is
               // to leave the existing job as only selection
               $jobs = new Jobs();
               $jobid = $tt->getJobId();
               $availableJobs = array(
                  $jobid => $jobs->getJobName($jobid)
               );
            }

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
         $userid = Tools::getSecurePOSTIntValue('userid',$session_userid);

         $dateStr = Tools::getSecurePOSTStringValue('date');
         $date = strtotime($dateStr);

         $duration = Tools::getSecurePOSTNumberValue('duration');
         $jobid = Tools::getSecurePOSTIntValue('jobid');
         $timetrack = TimeTrackCache::getInstance()->getTimeTrack($timetrackId);
         $note = NULL;

         $isTrackNoteUsed = $team->getGeneralPreference('useTrackNote');

         if (1 == $isTrackNoteUsed) {
            // filter_input replaces nl2br(htmlspecialchars($string))
            // rem: no need to sql_real_escape_string($note);
            $note = filter_input(INPUT_POST, 'note');
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
            'ttNote' => nl2br(htmlspecialchars($timetrack->getNote())),
            'timesheetHtml' => $html,
         );
         $jsonData = json_encode($data);

         // return data
         echo $jsonData;

      // ================================================================
      } else if ('deleteTrack' == $action) {
         try {
            $trackid = Tools::getSecurePOSTIntValue('trackid');
            $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($trackid);
            $defaultBugid = $timeTrack->getIssueId();
            $duration = $timeTrack->getDuration();
            $job = $timeTrack->getJobId();
            $defaultDate = date("Y-m-d", $timeTrack->getDate());

            if (!TimeTrack::exists($trackid)) {
               $e = new Exception("track $trackid does not exist !");
               throw $e;
            }

            // check if backlog must be recredited
            $ttProject = ProjectCache::getInstance()->getProject($timeTrack->getProjectId());
            if (!$ttProject->isSideTasksProject(array($teamid)) &&
                !$ttProject->isExternalTasksProject()) {
               $isRecreditBacklog = (0 == $team->getGeneralPreference('recreditBacklogOnTimetrackDeletion')) ? false : true;
            } else {
               // no backlog update for external & side tasks
               $isRecreditBacklog = false;
            }

            // delete track
            if(!$timeTrack->remove($session_userid, $isRecreditBacklog)) {
               $e = new Exception("Delete track $trackid  : FAILED");
               throw $e;
            }
            $statusMsg = "SUCCESS";

         } catch (Exception $e) {
            $errMsg = "Delete trackid= $trackid, bugid = $defaultBugid, job = $job, timestamp = $defaultDate, duration = $duration, managedUser = $managed_userid, sessionUser = ".$session_userid;
            $logger->error($errMsg);
            $logger->error("EXCEPTION deleteTrack: ".$e->getMessage());
            $statusMsg = T_("ERROR: Failed to delete the timetrack !");
         }
         $data = array(
            'statusMsg' => $statusMsg,
            'bugid' => $defaultBugid,
            'trackDate' => $defaultDate,
         );
         $jsonData = json_encode($data);

         // return data
         echo $jsonData;

      } else if ('addTimetrack' == $action) {
         try {
            // updateBacklogDialogbox with 'addTimetrack' action
            // add track AND update backlog & status & handlerId

            $trackDate   = Tools::getSecurePOSTStringValue('trackDate');
            $defaultBugid  = Tools::getSecurePOSTIntValue('bugid');
            $job           = Tools::getSecurePOSTIntValue('trackJobid');
            $duration      = Tools::getSecurePOSTNumberValue('timeToAdd'); // 'duree'
            $trackUserid = Tools::getSecurePOSTIntValue('trackUserid');
            $taskHandlerId = Tools::getSecurePOSTIntValue('handlerid', 0); // optional

            $issue = IssueCache::getInstance()->getIssue($defaultBugid);
            $errMessage = '';

            if (1 == $team->getGeneralPreference('useTrackNote')) {
               $trackNote   = filter_input(INPUT_POST, 'issue_note');
               if (1 == $team->getGeneralPreference('isTrackNoteMandatory') &&
                   0 == strlen($trackNote)) {
                  // forbid adding timetrack, return error
                  $errMessage .= T_('Timetrack not added: Timetrack note is mandatory')."\n";
               }
            }
            if(0 == $job) {
               $errMessage .= T_("Timetrack not added: Job is not specified")."\n";
            }
            if(0 == $defaultBugid) {
               $errMessage .= T_("Timetrack not added: bugid is not specified")."\n";
            }
            if(0 == $duration) {
               $errMessage .= T_("Timetrack not added: duration is not specified");
            }
            if (!empty($errMessage)) {
               $e = new Exception($errMessage);
               throw $e;
            }
            // add timetrack
            $timestamp = Tools::date2timestamp($trackDate);
            $trackid = TimeTrack::create($trackUserid, $defaultBugid, $job, $timestamp, $duration, $session_userid, $teamid);

            if (1 == $team->getGeneralPreference('useTrackNote') && strlen($trackNote)!=0) {
               TimeTrack::setNote($defaultBugid, $trackid, $trackNote, $trackUserid);
            }
            // There is no backup/status update if sideTask or externalTask
            $projType = $team->getProjectType($issue->getProjectId());

            // sideTasks & externalTasks have no backlog nor status update
            if ((Project::type_regularProject == $projType) &&
                (Jobs::JOB_SUPPORT != $job)) {
               $formattedBacklog = Tools::getSecurePOSTNumberValue('backlog');
               $newStatus        = Tools::getSecurePOSTIntValue('statusid');
               $issue->setBacklog($formattedBacklog);
               $issue->setStatus($newStatus);
            }

            // The taskHandler is not always specified,
            // it is present on a regular updateBacklogDialogbox
            // it is not specified if : timetrackNoteOnly, BacklogUpdateNotNeeded
            if ((0 !== $taskHandlerId) && ($taskHandlerId != $issue->getHandlerId())) {
               // TODO security check (userid exists/valid ?)
               $issue->setHandler($taskHandlerId);
            }

            $statusMsg = "SUCCESS";

         } catch (Exception $e) {
            $logger->error("addTimetrack: issue=$defaultBugid, jobid=$job, duration=$duration date=$trackDate handlerId=$taskHandlerId sessionUser=$session_userid");
            $logger->error("EXCEPTION addTimetrack: ".$e->getMessage());
            $statusMsg = T_("ERROR: Failed to add timetrack !");
            $defaultBugid = 0;
         }
         // return data
         $data = array(
            'statusMsg' => nl2br(htmlspecialchars($statusMsg)),
            // return info needed to pre-fill the form on page reload
            'bugid' => $defaultBugid,
            'date' => $trackDate,
            'weekid' => date('W', $timestamp),
         );
         $jsonData = json_encode($data);
         echo $jsonData;
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

