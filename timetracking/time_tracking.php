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

class TimeTrackingController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if(Tools::isConnectedUser()) {

        // only teamMembers can access this page
        if ((0 == $this->teamid) ||
            ($this->session_user->isTeamCustomer($this->teamid)) ||
            ($this->session_user->isTeamObserver($this->teamid)) ||
            (!$this->session_user->isTeamMember($this->teamid))) {

            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $teamMembers = $team->getActiveMembers(NULL, NULL, TRUE);

            $managed_userid = Tools::getSecurePOSTIntValue('userid',$this->session_userid);

            if ($this->session_user->isTeamManager($this->teamid)) {
               // session_user is Manager, let him choose the teamMember he wants to manage
               $this->smartyHelper->assign('users', $teamMembers);
               $this->smartyHelper->assign('selectedUser', $managed_userid);
               $this->smartyHelper->assign("isManager", true);
            }

            // display AddTrack Page

            $year   = Tools::getSecurePOSTIntValue('year',date('Y'));
            $managed_user = UserCache::getInstance()->getUser($managed_userid);

            // Need to be Manager to handle other users
            if($managed_userid != $this->session_userid) {
               if ((!$this->session_user->isTeamManager($this->teamid)) ||
                  (!array_key_exists($managed_userid,$teamMembers))) {
                  self::$logger->error(' SECURITY ALERT changeManagedUser: session_user '.$this->session_userid." is not allowed to manage user $managed_userid");
                  Tools::sendForbiddenAccess();
               }
            }

            // developper & manager can add timeTracks
            $mTeamList = $managed_user->getDevTeamList();
            $managedTeamList = $managed_user->getManagedTeamList();
            $teamList = $mTeamList + $managedTeamList;

            $action = Tools::getSecurePOSTStringValue('action','');
            $weekid = Tools::getSecurePOSTIntValue('weekid',date('W'));

            $defaultDate = Tools::getSecurePOSTStringValue('date',date("Y-m-d", time()));;
            $defaultBugid = Tools::getSecurePOSTIntValue('bugid',0);
            $defaultProjectid = Tools::getSecurePOSTIntValue('projectid',0);
            $job = Tools::getSecurePOSTIntValue('job',0);
            $duration = Tools::getSecurePOSTNumberValue('duree',0);

            if ("addTrack" == $action) {
               self::$logger->debug("addTrack: called from form1");

               // TODO merge addTrack & addTimetrack actions !

               // called by form1 when no backlog has to be set.
               // updateBacklogDialogBox must not raise up,
               // track must be added, backlog & status must NOT be updated

               $timestamp = Tools::date2timestamp($defaultDate);
               $defaultBugid = Tools::getSecurePOSTIntValue('bugid');
               $job = Tools::getSecurePOSTStringValue('job');
               $duration = Tools::getSecurePOSTNumberValue('duree');

               // dialogBox is not called, then track must be saved to DB
               $trackid = TimeTrack::create($managed_userid, $defaultBugid, $job, $timestamp, $duration, $this->session_userid);
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("Track $trackid added  : userid=$managed_userid bugid=$defaultBugid job=$job duration=$duration timestamp=$timestamp");
               }

               // Don't show job and duration after add track
               $job = 0;
               $duration = 0;
               $defaultProjectid = Tools::getSecurePOSTIntValue('projectid');
            }
            elseif ("addTimetrack" == $action) {
               // updateBacklogDialogbox with 'addTimetrack' action
               // add track AND update backlog & status & handlerId

               // TODO merge addTrack & addTimetrack actions !

               self::$logger->debug("addTimetrack: called from the updateBacklogDialogBox");

               // add timetrack (all values mandatory)
               $defaultDate  = Tools::getSecurePOSTStringValue('trackDate');
               $defaultBugid = Tools::getSecurePOSTIntValue('bugid');
               $job          = Tools::getSecurePOSTIntValue('trackJobid');
               $duration     = Tools::getSecurePOSTNumberValue('timeToAdd');
               $handlerId    = Tools::getSecurePOSTNumberValue('handlerid');

               // check jobid (bug happens sometime...
               if(0 == $job) {
                  $this->smartyHelper->assign('error', T_("Timetrack not added: Job has not specified."));
                  self::$logger->error("Add track : FAILED. issue=$defaultBugid, jobid=$job, duration=$duration date=$defaultDate");
               }

               // check bug_id (this happens when user uses the 'back' button of the browser ?)
               if (0 == $defaultBugid) {
                  self::$logger->error("Add track : FAILED. issue=0, jobid=$job, duration=$duration date=$defaultDate");
                  
               } else {
                  $timestamp = (0 !== $defaultDate) ? Tools::date2timestamp($defaultDate) : 0;

                  $trackid = TimeTrack::create($managed_userid, $defaultBugid, $job, $timestamp, $duration, $this->session_userid);
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("Track $trackid added  : userid=$managed_userid bugid=$defaultBugid job=$job duration=$duration timestamp=$timestamp");
                  }

                  $issue = IssueCache::getInstance()->getIssue($defaultBugid);

                  // setBacklog
                  $formattedBacklog = Tools::getSecurePOSTNumberValue('backlog');
                  $issue->setBacklog($formattedBacklog);

                  // setStatus
                  $newStatus = Tools::getSecurePOSTIntValue('statusid');
                  $issue->setStatus($newStatus);

                  // set handlerId
                  if ($handlerId != $issue->getHandlerId()) {
                     // TODO security check (userid exists/valid ?)
                     $issue->setHandler($handlerId);
                  }
                  
                  $defaultProjectid = $issue->getProjectId();
               }                  
               // Don't show job and duration after add track
               $job = 0;
               $duration = 0;
            }
            elseif ("deleteTrack" == $action) {
               $trackid = Tools::getSecurePOSTIntValue('trackid');
               $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($trackid);
               $defaultBugid = $timeTrack->getIssueId();
               $duration = $timeTrack->getDuration();
               $job = $timeTrack->getJobId();
               $defaultDate = date("Y-m-d", $timeTrack->getDate());

               // delete track
               if(!$timeTrack->remove()) {
                  $this->smartyHelper->assign('error', T_("Failed to delete the timetrack !"));
                  self::$logger->error("Delete track $trackid  : FAILED.");
               }

               if (0 == $defaultBugid) {
                  self::$logger->error("Delete track : bug_id=0");
                  $defaultProjectid  = 0;
               } else {
                  try {
                     // pre-set form fields
                     $issue = IssueCache::getInstance()->getIssue($defaultBugid);
                     $defaultProjectid  = $issue->getProjectId();

                     // if project not defined for current team, do not pre-set form fields.
                     if (!in_array($defaultProjectid, array_keys($team->getProjects()))) {
                        $defaultProjectid  = 0;
                        $defaultBugid = 0;
                     }
                  } catch (Exception $e) {
                     $defaultProjectid  = 0;
                     $defaultBugid = 0;
                  }
               }
            }
            elseif ("setBugId" == $action) {
               // pre-set form fields
               // find ProjectId to update categories
               $defaultBugid = Tools::getSecurePOSTIntValue('bugid');
               $issue = IssueCache::getInstance()->getIssue($defaultBugid);
               $defaultProjectid  = $issue->getProjectId();
            }
            elseif ("setFiltersAction" == $action) {
               $isFilter_onlyAssignedTo = isset($_POST["cb_onlyAssignedTo"]) ? '1' : '0';
               $isFilter_hideResolved = isset($_POST["cb_hideResolved"])   ? '1' : '0';

               $managed_user->setTimetrackingFilter('onlyAssignedTo', $isFilter_onlyAssignedTo);
               $managed_user->setTimetrackingFilter('hideResolved', $isFilter_hideResolved);

               if($defaultBugid != 0) {
                  $issue = IssueCache::getInstance()->getIssue($defaultBugid);
                  $defaultProjectid = $issue->getProjectId();
               }
            }

            // Display user name
            $this->smartyHelper->assign('managedUser_realname', $managed_user->getRealname());
            $this->smartyHelper->assign('userid', $managed_userid);

            // display Track Form
            $this->smartyHelper->assign('date', $defaultDate);

            // All projects except disabled
            $projList = $team->getProjects(true, false);
            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList,$defaultProjectid));

            $this->smartyHelper->assign('defaultProjectid', $defaultProjectid);
            $this->smartyHelper->assign('defaultBugid', $defaultBugid);
            $this->smartyHelper->assign('weekid', $weekid);
            $this->smartyHelper->assign('year', $year);

            $isOnlyAssignedTo = ('0' == $managed_user->getTimetrackingFilter('onlyAssignedTo')) ? false : true;
            $this->smartyHelper->assign('isOnlyAssignedTo', $isOnlyAssignedTo);

            $isHideResolved = ('0' == $managed_user->getTimetrackingFilter('hideResolved')) ? false : true;
            $this->smartyHelper->assign('isHideResolved', $isHideResolved);

            $availableIssues = TimeTrackingTools::getIssues($this->teamid, $defaultProjectid, $isOnlyAssignedTo, $managed_user->getId(), $projList, $isHideResolved, $defaultBugid);
            $this->smartyHelper->assign('issues', $availableIssues);

            $this->smartyHelper->assign('jobs', SmartyTools::getSmartyArray(TimeTrackingTools::getJobs($defaultProjectid, $this->teamid), $job));
            $this->smartyHelper->assign('duration', SmartyTools::getSmartyArray(TimeTrackingTools::getDurationList($this->teamid),$duration));

            $this->smartyHelper->assign('weeks', SmartyTools::getWeeks($weekid, $year));
            $this->smartyHelper->assign('years', SmartyTools::getYears($year,1));

            $weekDates = Tools::week_dates($weekid,$year);
            $startTimestamp = $weekDates[1];
            $endTimestamp = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
            $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);

            $incompleteDays = array_keys($timeTracking->checkCompleteDays($managed_userid, TRUE));
            $missingDays = $timeTracking->checkMissingDays($managed_userid);
            $errorDays = array_merge($incompleteDays,$missingDays);
            $smartyWeekDates = TimeTrackingTools::getSmartyWeekDates($weekDates,$errorDays);

            // UTF8 problems in smarty, date encoding needs to be done in PHP
            $this->smartyHelper->assign('weekDates', array(
               $smartyWeekDates[1], $smartyWeekDates[2], $smartyWeekDates[3], $smartyWeekDates[4], $smartyWeekDates[5]
            ));
            $this->smartyHelper->assign('weekEndDates', array(
               $smartyWeekDates[6], $smartyWeekDates[7]
            ));

            $weekTasks = TimeTrackingTools::getWeekTask($weekDates, $this->teamid, $managed_userid, $timeTracking, $errorDays);
            $this->smartyHelper->assign('weekTasks', $weekTasks["weekTasks"]);
            $this->smartyHelper->assign('dayTotalElapsed', $weekTasks["totalElapsed"]);

            $timeTrackingTuples = $this->getTimetrackingTuples($managed_userid, $timeTracking);
            $this->smartyHelper->assign('weekTimetrackingTuples', $timeTrackingTuples['current']);
            $this->smartyHelper->assign('timetrackingTuples', $timeTrackingTuples['future']);

            // ConsistencyCheck
            $consistencyErrors = $this->getConsistencyErrors($managed_userid, $this->teamid);
            if(count($consistencyErrors) > 0) {
               $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
               $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
               $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("days are incomplete or undefined"));
            }

            $this->smartyHelper->assign('isForbidAddTimetracksOnClosed', (1 == $team->getGeneralPreference('forbidAddTimetracksOnClosed')) ? true : false);
         }
      }
   }

   /**
    * display missing imputations
    *
    * @param int $userid
    * @param int $team_id
    * @return mixed[] consistencyErrors
    */
   private function getConsistencyErrors($userid, $team_id = NULL) {

      $user = UserCache::getInstance()->getUser($userid);

      $startTimestamp = $user->getArrivalDate($team_id);
      $endTimestamp = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
      $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $team_id);

      $cerrList = ConsistencyCheck2::checkIncompleteDays($timeTracking, $userid);

      $consistencyErrors = array();
      if (count($cerrList) > 0) {
         foreach ($cerrList as $cerr) {
            
            // skip alerts on today
            if ($endTimestamp == $cerr->timestamp) { continue; }
            
            if ($userid == $cerr->userId) {
               $consistencyErrors[] = array(
                  'date' => date("Y-m-d", $cerr->timestamp),
                  'severity' => $cerr->getLiteralSeverity(),
                  'severityColor' => $cerr->getSeverityColor(),
                  'desc' => $cerr->desc);
               }
         }
      }
      return $consistencyErrors;
   }

   /**
    * display Timetracking Tuples
    * @param int $userid
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   private function getTimetrackingTuples($userid, TimeTracking $timeTracking) {
      // Display previous entries
      $query = "SELECT id, bugid, jobid, date, duration".
               " FROM `codev_timetracking_table`".
               " WHERE userid = $userid".
               " AND date >= ".$timeTracking->getStartTimestamp().
               " ORDER BY date";
      $result = SqlWrapper::getInstance()->sql_query($query) or die("Query failed: $query");

      $jobs = new Jobs();

      $timetrackingTuples = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         // get information on this bug
         try {
            $issue = IssueCache::getInstance()->getIssue($row->bugid);

            // get general information

            $jobName = $jobs->getJobName($row->jobid);

            $formatedDate = Tools::formatDate("%Y-%m-%d", $row->date);
            $cosmeticDate = Tools::formatDate("%Y-%m-%d - %A", $row->date);
            $formatedJobName = str_replace("'", "\'", $jobName);
            $formatedSummary = str_replace("'", "\'", $issue->getSummary());
            $formatedSummary = str_replace('"', "\'", $formatedSummary);
            $formatedSummary = htmlspecialchars(preg_replace('![\t\r\n]+!',' ',$formatedSummary));

            //$totalEstim = $issue->effortEstim + $issue->effortAdd;

            $timetrackingTuples[$row->id] = array(
               'timestamp' => $row->date,
               'date' => $formatedDate,
               'formatedId' => $issue->getFormattedIds(),
               'duration' => $row->duration,
               'formatedJobName' => $formatedJobName,
               'summary' => $formatedSummary,
               'cosmeticDate' => $cosmeticDate,
               'mantisURL' => Tools::mantisIssueURL($row->bugid, NULL, true),
               'issueURL' => Tools::issueInfoURL($row->bugid),
               'issueId' => $issue->getTcId(),
               'projectName' => $issue->getProjectName(),
               'issueSummary' => htmlspecialchars(preg_replace('![\t\r\n]+!',' ',$issue->getSummary())),
               'jobName' => $jobName,
               'categoryName' => $issue->getCategoryName(),
               'currentStatusName' => $issue->getCurrentStatusName());
         } catch (Exception $e) {
            $summary = T_('Error: Task not found in Mantis DB !');
            $timetrackingTuples[$row->id] = array(
               'formatedId' => $row->bugid,
               'duration' => $row->duration,
               'summary' => $summary,
               'mantisURL' => '',
               'issueURL' => $row->bugid,
               'issueId' => '!',
               'projectName' => '!',
               'issueSummary' => '<span class="error_font">'.$summary.'</span>',
               'categoryName' => '!',
               'currentStatusName' => '!'
            );
         }
      }

      $currentTimeTrackingTuples = array();
      $futureTimeTrackingTuples = array();
      foreach ($timetrackingTuples as $trackId => $timeTrackingTuple) {
         if($timeTrackingTuple['timestamp'] <= $timeTracking->getEndTimestamp()) {
            $currentTimeTrackingTuples[$trackId] = $timeTrackingTuple;
         } else {
            $futureTimeTrackingTuples[$trackId] = $timeTrackingTuple;
         }
         unset($timeTrackingTuple['timestamp']);
      }

      return array(
         "current" => $currentTimeTrackingTuples,
         "future" => $futureTimeTrackingTuples
      );
   }



}

// ========== MAIN ===========
TimeTrackingController::staticInit();
$controller = new TimeTrackingController('../', 'Time Tracking','TimeTracking');
$controller->execute();

?>
