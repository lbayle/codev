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
            ($this->session_user->isTeamObserver($this->teamid))) {

            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $teamMembers = $team->getActiveMembers();

            $userid = Tools::getSecurePOSTIntValue('userid',$this->session_userid);

            if ($this->session_user->isTeamManager($this->teamid)) {
               // session_user is Manager, let him choose the teamMember he wants to manage
               $this->smartyHelper->assign('users', $teamMembers);
               $this->smartyHelper->assign('selectedUser', $userid);
               $this->smartyHelper->assign("isManager", true);
            }

            // display AddTrack Page
            $job_support = Config::getInstance()->getValue(Config::id_jobSupport);

            $year   = Tools::getSecurePOSTIntValue('year',date('Y'));
            $userid = Tools::getSecurePOSTIntValue('userid',$this->session_userid);

            $managed_user = UserCache::getInstance()->getUser($userid);

            if($userid != $this->session_userid) {

               // Need to be Manager to handle other users
               if (($this->session_user->isTeamManager($this->teamid)) &&
                  (array_key_exists($userid,$teamMembers))) {

                  $this->smartyHelper->assign('userid', $userid);
               } else {
                  Tools::sendForbiddenAccess();
               }
            }

            // developper & manager can add timeTracks
            $mTeamList = $managed_user->getDevTeamList();
            $managedTeamList = $managed_user->getManagedTeamList();
            $teamList = $mTeamList + $managedTeamList;

            // updateBacklog data
            $backlog = Tools::getSecurePOSTNumberValue('backlog',0);

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

               // open the updateBacklog DialogBox on page reload.
               // do NOT decrease backlog if job is job_support !
               // do NOT decrease backlog if sideTask or externalTask
               $issue = IssueCache::getInstance()->getIssue($defaultBugid);
               $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
               if (($job != $job_support) &&
                  (!$project->isSideTasksProject(array_keys($teamList)) &&
                  (!$project->isExternalTasksProject()))) {

                  $deadline = $issue->getDeadLine();
                  if (!is_null($deadline) || (0 != $deadline)) {
                     $formatedDate = Tools::formatDate(T_("%Y-%m-%d"), $deadline);
                  }

                  $totalEE = ($issue->getEffortEstim() + $issue->getEffortAdd());

                  // Note: if Backlog is NULL, the values to propose in the DialogBox
                  //       are not the ones used for ProjectManagement
                  $backlog = $issue->getBacklog();
                  if ( !is_null($backlog) && is_numeric($backlog)) {
                     // normal case
                     $drift = $issue->getDrift();
                  } else {
                     // reestimated cannot be used...
                     $backlog = $totalEE - $issue->getElapsed();
                     if ($backlog < 0) { $backlog = 0;}
                     $drift = ($issue->getElapsed() + $backlog) - $totalEE;
                  }

                  $issueInfo = array(
                     'backlog' => $backlog,
                     'bugid' => $issue->getId(),
                     'summary' => $issue->getSummary(),
                     'dialogBoxTitle' => $issue->getFormattedIds(),
                     'effortEstim' => $totalEE,
                     'mgrEffortEstim' => $issue->getMgrEffortEstim(),
                     'elapsed' => $issue->getElapsed(),
                     'drift' => $drift,
                     'driftMgr' => $issue->getDriftMgr(),
                     'reestimated' => $issue->getReestimated(),
                     'driftColor' => $issue->getDriftColor($drift),
                     'currentStatus' => $issue->getCurrentStatus(),
                     'availableStatusList' => $issue->getAvailableStatusList(true),
                     'bugResolvedStatusThreshold' =>  $issue->getBugResolvedStatusThreshold()
                  );
                  if (isset($formatedDate)) {
                     $issueInfo['deadline'] = $formatedDate;
                  }

                  $jsonIssueInfo = json_encode($issueInfo);
                  $this->smartyHelper->assign('updateBacklogJsonData', $jsonIssueInfo);
               }

               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("Track $trackid added  : userid=$userid bugid=$defaultBugid job=$job duration=$duration timestamp=$timestamp");
               }

               // Don't show job and duration after add track
               $job = 0;
               $duration = 0;
            }
            elseif ("deleteTrack" == $action) {
               $trackid = Tools::getSecurePOSTIntValue('trackid');
               // increase backlog (only if 'backlog' already has a value)
               $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($trackid);
               $defaultBugid = $timeTrack->getIssueId();
               $duration = $timeTrack->getDuration();
               $job = $timeTrack->getJobId();

               // delete track
               if(!$timeTrack->remove()) {
                  $this->smartyHelper->assign('error', T_("Failed to delete the tasks"));
               }

               try {
                  $issue = IssueCache::getInstance()->getIssue($defaultBugid);
                  $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
                  // do NOT increase backlog if job is job_support !
                  // do NOT increase backlog if sideTask or ExternalTask (they have no backlog)
                  if (($job != $job_support) &&
                      (!$project->isSideTasksProject(array_keys($teamList))) &&
                      (!$project->isExternalTasksProject())) {

                     if (!is_null($issue->getBacklog())) {
                        $backlog = $issue->getBacklog() + $duration;
                        $issue->setBacklog($backlog);
                     }
                  }

                  // pre-set form fields
                  $defaultProjectid  = $issue->getProjectId();
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

            // TODO: remove unused filter: isHideDevProjects
            $isHideDevProjects = ('0' == $managed_user->getTimetrackingFilter('hideDevProjects')) ? false : true;
            $this->smartyHelper->assign('isHideDevProjects', $isHideDevProjects);

            $availableIssues = $this->getIssues($defaultProjectid, $isOnlyAssignedTo, $managed_user->getId(), $projList, $isHideResolved, $defaultBugid);
            $this->smartyHelper->assign('issues', $availableIssues);

            $this->smartyHelper->assign('jobs', SmartyTools::getSmartyArray($this->getJobs($defaultProjectid, $this->teamid), $job));
            $this->smartyHelper->assign('duration', SmartyTools::getSmartyArray($this->getDuration(),$duration));

            $this->smartyHelper->assign('weeks', SmartyTools::getWeeks($weekid, $year));
            $this->smartyHelper->assign('years', SmartyTools::getYears($year,1));

            $weekDates = Tools::week_dates($weekid,$year);
            $startTimestamp = $weekDates[1];
            $endTimestamp = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
            $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);

            $incompleteDays = array_keys($timeTracking->checkCompleteDays($userid, TRUE));
            $missingDays = $timeTracking->checkMissingDays($userid);
            $errorDays = array_merge($incompleteDays,$missingDays);
            $smartyWeekDates = TimeTrackingTools::getSmartyWeekDates($weekDates,$errorDays);

            // UTF8 problems in smarty, date encoding needs to be done in PHP
            $this->smartyHelper->assign('weekDates', array(
               $smartyWeekDates[1], $smartyWeekDates[2], $smartyWeekDates[3], $smartyWeekDates[4], $smartyWeekDates[5]
            ));
            $this->smartyHelper->assign('weekEndDates', array(
               $smartyWeekDates[6], $smartyWeekDates[7]
            ));

            $weekTasks = TimeTrackingTools::getWeekTask($weekDates, $this->teamid, $userid, $timeTracking, $errorDays);
            $this->smartyHelper->assign('weekTasks', $weekTasks["weekTasks"]);
            $this->smartyHelper->assign('dayTotalElapsed', $weekTasks["totalElapsed"]);

            $timeTrackingTuples = $this->getTimetrackingTuples($userid, $timeTracking);
            $this->smartyHelper->assign('weekTimetrackingTuples', $timeTrackingTuples['current']);
            $this->smartyHelper->assign('timetrackingTuples', $timeTrackingTuples['future']);

            // ConsistencyCheck
            $consistencyErrors = $this->getConsistencyErrors($userid, $this->teamid);
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
               'issueSummary' => $issue->getSummary(),
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

   /**
    * Get issues
    * @param int $projectid
    * @param boolean $isOnlyAssignedTo
    * @param int $userid
    * @param string[] $projList
    * @param boolean $isHideResolved
    * @param int $defaultBugid
    * @return mixed[]
    */
   private function getIssues($projectid, $isOnlyAssignedTo, $userid, array $projList, $isHideResolved, $defaultBugid) {

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $hideStatusAndAbove = (1 == $team->getGeneralPreference('forbidAddTimetracksOnClosed')) ? Constants::$status_closed : 0;

      if (0 != $projectid) {
         // Project list
         $project1 = ProjectCache::getInstance()->getProject($projectid);

         try {
            $isSideTasksProject = $project1->isSideTasksProject(array($this->teamid));
            $isNoStatsProject   = $project1->isNoStatsProject(array($this->teamid));

            // do not filter on userId if SideTask or ExternalTask
            if (($isSideTasksProject) || ($isNoStatsProject)) {
               $handler_id = 0; // all users
               $hideStatusAndAbove = 0; // hide none
               $isHideResolved = false; // do not hide resolved
            } else {
               // normal project
               $handler_id = $isOnlyAssignedTo ? $userid : 0;
            }

         } catch (Exception $e) {
            self::$logger->error("getIssues(): isOnlyAssignedTo & isHideResolved filters not applied : ".$e->getMessage());
            $handler_id = 0; // all users
            $isHideResolved = false; // do not hide resolved
         }
         $issueList = $project1->getIssues($handler_id, $isHideResolved, $hideStatusAndAbove);
      } else {
         // no project specified: show all tasks
         $issueList = array();

         foreach ($projList as $pid => $pname) {
            $proj = ProjectCache::getInstance()->getProject($pid);
            try {
               if (($proj->isSideTasksProject(array($this->teamid))) ||
                  ($proj->isNoStatsProject(array($this->teamid)))) {
                  // do not hide any task for SideTasks & ExternalTasks projects
                  $buglist = $proj->getIssues(0, false, 0);
                  $issueList = array_merge($issueList, $buglist);
               } else {
                  $handler_id = $isOnlyAssignedTo ? $userid : 0;
                  $buglist = $proj->getIssues($handler_id, $isHideResolved, $hideStatusAndAbove);
                  $issueList = array_merge($issueList, $buglist);
               }
            } catch (Exception $e) {
               self::$logger->error("getIssues(): task filters not applied for project $pid : ".$e->getMessage());
               // do not hide any task if unknown project type
               $buglist = $proj->getIssues(0, false, 0);
               $issueList = array_merge($issueList, $buglist);

            }
         }
         rsort($issueList);
      }

      $issues = array();
      foreach ($issueList as $issue) {
         //$issue = IssueCache::getInstance()->getIssue($bugid);
         $issues[$issue->getId()] = array(
            'id' => $issue->getId(),
            'tcId' => $issue->getTcId(),
            'summary' => $issue->getSummary(),
            'selected' => $issue->getId() == $defaultBugid);
      }

      // If the default bug is filtered, we add it anyway
      if(!array_key_exists($defaultBugid,$issues) && $defaultBugid != 0) {
         try {
            $issue = IssueCache::getInstance()->getIssue($defaultBugid);
            // Add the bug only if the selected project is the bug project
            if($projectid == 0 || $issue->getProjectId() == $projectid) {
               $issues[$issue->getId()] = array(
                  'id' => $issue->getId(),
                  'tcId' => $issue->getTcId(),
                  'summary' => $issue->getSummary(),
                  'selected' => $issue->getId() == $defaultBugid);
               krsort($issues);
            }
         } catch (Exception $e) {
               self::$logger->error("getIssues(): task not found in MantisDB : ".$e->getMessage());
         }
      }

      // $issues is sorted, but we want the 5 most recent used issues to be in front
      if (0 != $userid) {
         $user = UserCache::getInstance()->getUser($userid);
         $recentBugidList = $user->getRecentlyUsedIssues(5, array_keys($issues));
         #var_dump($recentBugidList);
         $smartyRecentList = array();
         foreach ($recentBugidList as $bugid) {
            if (array_key_exists("$bugid", $issues)) {
               $smartyRecentList["$bugid"] = $issues["$bugid"];
               unset($issues["$bugid"]);
            }
         }
         // insert in front
         $issues = $smartyRecentList + $issues;
      }

      return $issues;
   }

   /**
    * get Job list
    *
    * Note: the jobs depend on project type, which depends on the team
    *
    * @param int $projectid
    * @param string $teamid  user's team
    * @return string[]
    */
   private function getJobs($projectid, $teamid) {

      if ((0 == $projectid) || (0 == $teamid)) {

         //this happens when project = "All", it's a normal case.
         // team == 0 should not happen
         //self::$logger->warn("getJobs($projectid, $teamid): could not find jobList. Action = $action");
         return array();
      }

      $team = TeamCache::getInstance()->getTeam($teamid);
      $project = ProjectCache::getInstance()->getProject($projectid);

      $ptype = $team->getProjectType($projectid);
      $jobList = $project->getJobList($ptype);

      return $jobList;
   }

   /**
    * @return string[]
    */
   private function getDuration() {
      $duration["0"] = "";
      $duration["1"] = "1";
      $duration["0.9"] = "0.9";
      $duration["0.8"] = "0.8";
      $duration["0.75"] = "0.75";
      $duration["0.7"] = "0.7";
      $duration["0.6"] = "0.6";
      $duration["0.5"] = "0.5";
      $duration["0.4"] = "0.4";
      $duration["0.3"] = "0.3";
      $duration["0.25"] = "0.25";
      $duration["0.2"] = "0.2";
      $duration["0.1"] = "0.1";
      $duration["0.05"] = "0.05";
      return $duration;
   }

}

// ========== MAIN ===========
TimeTrackingController::staticInit();
$controller = new TimeTrackingController('../', 'Time Tracking','TimeTracking');
$controller->execute();

?>
