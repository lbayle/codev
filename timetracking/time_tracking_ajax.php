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

if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {

	$logger = Logger::getLogger("TimeTrackingAjax");

   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   $session_user = $_SESSION['userid'];

   $action = $_POST['action'];
   $bugid       = Tools::getSecurePOSTIntValue('bugid');

   if(isset($action)) {
      $smartyHelper = new SmartyHelper();
      if($action == 'getUpdateBacklogData') {

			$logger->error("getUpdateBacklogData");

			// get info to display the updateBacklog dialogbox
         // (when clicking on the backlog value in WeekTaskDetails)
         // OR clicking the addTrack button in addTrack form (form1)
         $job         = Tools::getSecurePOSTIntValue('trackJobid', 0);
         $job_support = Config::getInstance()->getValue(Config::id_jobSupport);

         $issue = IssueCache::getInstance()->getIssue($bugid);
         $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
         if (($job == $job_support) ||
            ($project->isSideTasksProject(array_keys(array($teamid))) ||
            ($project->isExternalTasksProject()))) {
            // no backlog update for this task
            $data = array('diagnostic' => 'BacklogUpdateNotNeeded');
            $updateBacklogJsonData = json_encode($data);
         } else {
            $managedUserid  = Tools::getSecurePOSTIntValue('trackUserid', 0);
            $trackDuration  = Tools::getSecurePOSTNumberValue('trackDuration', 0);
            $trackDate      = Tools::getSecurePOSTStringValue('trackDate', 0);

            $updateBacklogJsonData = TimeTrackingTools::getUpdateBacklogJsonData($bugid, $job, $managedUserid, $trackDate, $trackDuration);
         }

         // return data
			echo $updateBacklogJsonData;

		} else if($action == 'updateBacklog') {
         // updateBacklogDoalogbox with 'updateBacklog' action

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

      } else if ($action == 'getIssueNoteText') {
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

      } else if ($action == 'saveIssueNote') {
         $reporter_id = $session_user;
         $issueNoteText = Tools::getSecurePOSTStringValue('issuenote_text');

         IssueNote::setTimesheetNote($bugid, $issueNoteText, $reporter_id);

         // return data
         // the complete WeekTaskDetails Div must be updated
         $weekid = Tools::getSecurePOSTIntValue('weekid');
         $year = Tools::getSecurePOSTIntValue('year');
         $userid = Tools::getSecurePOSTIntValue('userid',$session_user);

         setWeekTaskDetails($smartyHelper, $weekid, $year, $userid, $teamid);
         $smartyHelper->display('ajax/weekTaskDetails');
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

   $session_user = $_SESSION['userid'];

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

}

?>
