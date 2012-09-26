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
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         // if first call to this page
         if (!isset($_POST['nextForm'])) {
            $lTeamList = $session_user->getLeadedTeamList();

            if (0 != count($lTeamList)) {
               // User is TeamLeader, let him choose the user he wants to manage
               $this->smartyHelper->assign('users', $this->getUsers());
               $this->smartyHelper->assign('selectedUser', $session_user->getId());
            } else {
               // if session_user (not a teamLeader) is defined in a team, display AddTrack page

               // developper & manager can add timeTracks
               $mTeamList = $session_user->getDevTeamList();
               $managedTeamList = $session_user->getManagedTeamList();
               $teamList = $mTeamList + $managedTeamList;

               if (0 != count($teamList)) {
                  $_POST['userid']   = $session_user->getId();
                  $_POST['nextForm'] = "addTrackForm";
               }
            }
         }

         // display AddTrack Page
         $nextForm = Tools::getSecurePOSTStringValue('nextForm','');
         if ($nextForm == "addTrackForm") {
            $job_support = Config::getInstance()->getValue(Config::id_jobSupport);

            $year   = Tools::getSecurePOSTIntValue('year',date('Y'));
            $userid = Tools::getSecurePOSTIntValue('userid',$session_user->getId());

            $managed_user = UserCache::getInstance()->getUser($userid);

            if($userid != $session_user->getId()) {
               // Need to be a Team Leader to handle other users
               $lTeamList = $session_user->getLeadedTeamList();
               if (count($lTeamList) > 0 && array_key_exists($userid,$this->getUsers())) {
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

               // do NOT decrease backlog if job is job_support !
               if ($job != $job_support) {
                  // decrease backlog (only if 'backlog' already has a value)
                  $issue = IssueCache::getInstance()->getIssue($defaultBugid);
                  if (!is_null($issue->getBacklog())) {
                     $backlog = $issue->getBacklog() - $duration;
                     if ($backlog < 0) { $backlog = 0; }
                     $issue->setBacklog($backlog);
                  }

                  // open the updateBacklog DialogBox on page reload
                  $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
                  if (($job != $job_support) &&
                     (!$project->isSideTasksProject(array_keys($teamList)) &&
                        (!$project->isExternalTasksProject()))) {

                     $formatedDate = Tools::formatDate(T_("%Y-%m-%d"), $issue->getDeadLine());

                     $issueInfo = array(
                        'backlog' => $issue->getBacklog(),
                        'bugid' => $issue->getId(),
                        'description' => $issue->getSummary(),
                        'dialogBoxTitle' => $issue->getFormattedIds(),
                        'effortEstim' => ($issue->getEffortEstim() + $issue->getEffortAdd()),
                        'mgrEffortEstim' => $issue->getMgrEffortEstim(),
                        'elapsed' => $issue->getElapsed(),
                        'drift' => $issue->getDrift(),
                        'driftMgr' => $issue->getDriftMgr(),
                        'reestimated' => $issue->getReestimated(),
                        'reestimatedMgr' => $issue->getReestimatedMgr(),
                        'driftColor' => $issue->getDriftColor(),
                        'deadline' => $formatedDate

                     );

                     $this->smartyHelper->assign('updateBacklogRequested', $issueInfo);
                  }
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
               $trackUserid = $timeTrack->getUserId();
               $trackDate = $timeTrack->getDate();

               // delete track
               if(!$timeTrack->remove()) {
                  $this->smartyHelper->assign('error', T_("Failed to delete the tasks"));
               }

               try {
                  $issue = IssueCache::getInstance()->getIssue($defaultBugid);
                  // do NOT decrease backlog if job is job_support !
                  if ($job != $job_support) {
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
            $this->smartyHelper->assign('otherrealname', $managed_user->getRealname());

            // display Track Form
            $this->smartyHelper->assign('date', $defaultDate);

            // All projects from teams where I'm a Developper
            $devProjList = $managed_user->getProjectList($managed_user->getDevTeamList());

            // SideTasksProjects from Teams where I'm a Manager
            $managedProjList = $managed_user->getProjectList($managed_user->getManagedTeamList());
            $projList = $devProjList + $managedProjList;

            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList,$defaultProjectid));

            $this->smartyHelper->assign('defaultProjectid', $defaultProjectid);
            $this->smartyHelper->assign('defaultBugid', $defaultBugid);
            $this->smartyHelper->assign('weekid', $weekid);
            $this->smartyHelper->assign('year', $year);

            $isOnlyAssignedTo = ('0' == $managed_user->getTimetrackingFilter('onlyAssignedTo')) ? false : true;
            $this->smartyHelper->assign('isOnlyAssignedTo', $isOnlyAssignedTo);

            $isHideResolved = ('0' == $managed_user->getTimetrackingFilter('hideResolved')) ? false : true;
            $this->smartyHelper->assign('isHideResolved', $isHideResolved);

            $isHideDevProjects = ('0' == $managed_user->getTimetrackingFilter('hideDevProjects')) ? false : true;
            $this->smartyHelper->assign('isHideDevProjects', $isHideDevProjects);

            $this->smartyHelper->assign('issues', $this->getIssues($defaultProjectid, $isOnlyAssignedTo, $managed_user->getId(), $projList, $isHideResolved, $defaultBugid));

            $this->smartyHelper->assign('jobs', SmartyTools::getSmartyArray($this->getJobs($defaultProjectid, $teamList), $job));
            $this->smartyHelper->assign('duration', SmartyTools::getSmartyArray($this->getDuration(),$duration));

            $this->smartyHelper->assign('weeks', SmartyTools::getWeeks($weekid, $year));
            $this->smartyHelper->assign('years', SmartyTools::getYears($year,1));

            $weekDates = Tools::week_dates($weekid,$year);
            $startTimestamp = $weekDates[1];
            $endTimestamp = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
            $timeTracking = new TimeTracking($startTimestamp, $endTimestamp);

            // UTF8 problems in smarty, date encoding needs to be done in PHP
            $this->smartyHelper->assign('weekDates', array(
                  date('Y-m-d',$weekDates[1]) => Tools::formatDate("%A\n%d %b", $weekDates[1]),
                  date('Y-m-d',$weekDates[2]) => Tools::formatDate("%A\n%d %b", $weekDates[2]),
                  date('Y-m-d',$weekDates[3]) => Tools::formatDate("%A\n%d %b", $weekDates[3]),
                  date('Y-m-d',$weekDates[4]) => Tools::formatDate("%A\n%d %b", $weekDates[4]),
                  date('Y-m-d',$weekDates[5]) => Tools::formatDate("%A\n%d %b", $weekDates[5]))
            );
            $this->smartyHelper->assign('weekEndDates', array(
               date('Y-m-d',$weekDates[6]) => Tools::formatDate("%A\n%d %b", $weekDates[6]),
               date('Y-m-d',$weekDates[7]) => Tools::formatDate("%A\n%d %b", $weekDates[7])));

            $this->smartyHelper->assign('weekTasks', TimeTrackingTools::getWeekTask($weekDates, $userid, $timeTracking));

            $this->smartyHelper->assign('warnings', $this->getCheckWarnings($userid));

            $this->smartyHelper->assign('weekTimetrackingTuples', $this->getTimetrackingTuples($userid, $startTimestamp, $endTimestamp));
            $this->smartyHelper->assign('timetrackingTuples', $this->getTimetrackingTuples($userid, $endTimestamp));
         }
      }
   }

   /**
    * display accordion with missing imputations
    * @param int $userid
    * @param int $team_id
    * @param boolean $isStrictlyTimestamp
    * @return mixed[]
    */
   private function getCheckWarnings($userid, $team_id = NULL, $isStrictlyTimestamp = FALSE) {
      // 2010-05-31 is the first date of use of this tool
      $user1 = UserCache::getInstance()->getUser($userid);

      $startTimestamp = $user1->getArrivalDate($team_id);
      $endTimestamp = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
      $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $team_id);

      $incompleteDays = $timeTracking->checkCompleteDays($userid, $isStrictlyTimestamp);
      $missingDays = $timeTracking->checkMissingDays($userid);

      $warnings = NULL;
      foreach ($incompleteDays as $date => $value) {
         if ($date > time()) {
            // skip dates in the future
            continue;
         }

         if ($value < 1) {
            $value = T_("incomplete (missing ").(1-$value).T_(" days").")";
         } else {
            $value = T_("inconsistent")." (".($value)." ".T_("days").")";
         }

         $warnings[] = array('date' => date("Y-m-d", $date),
            'value' => $value);
      }

      foreach ($missingDays as $date) {
         if ($date > time()) {
            // skip dates in the future
            continue;
         }

         $warnings[] = array('date' => date("Y-m-d", $date),
            'value' => T_("not defined."));
      }

      return $warnings;
   }

   /**
    * display Timetracking Tuples
    * @param int $userid
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return mixed[]
    */
   private function getTimetrackingTuples($userid, $startTimestamp=NULL, $endTimestamp=NULL) {
      $curJulian = 0;

      // Display previous entries
      $query = "SELECT id, bugid, jobid, date, duration ".
         "FROM `codev_timetracking_table` ".
         "WHERE userid=$userid";

      if (NULL != $startTimestamp) { $query .= " AND date >= $startTimestamp"; }
      if (NULL != $endTimestamp)   { $query .= " AND date <= $endTimestamp"; }
      $query .= " ORDER BY date";
      $result = SqlWrapper::getInstance()->sql_query($query) or die("Query failed: $query");

      $jobs = new Jobs();

      $timetrackingTuples = NULL;
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

            // --- choose row color
            $tr_class = NULL;
            if (0 == $curJulian) {
               // set first day displayed
               $tr_class = "row_odd";
               $curJulian = $row->date;
            }
            if ($curJulian != $row->date) {
               // day changed, swap row color
               $tr_class = ($tr_class == "row_odd") ? "row_even" : "row_odd";
               $curJulian = $row->date;
            }

            $timetrackingTuples[] = array('id' => $row->id,
               'class' => $tr_class,
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
            $timetrackingTuples[] = array(
               'id' => $row->id,
               'class' => $tr_class,
               'date' => $formatedDate,
               'formatedId' => $row->bugid,
               'duration' => $row->duration,
               'formatedJobName' => $formatedJobName,
               'summary' => $summary,
               'cosmeticDate' => $cosmeticDate,
               'mantisURL' => '',
               'issueURL' => $row->bugid,
               'issueId' => '!',
               'projectName' => '!',
               'issueSummary' => '<span class="error_font">'.$summary.'</span>',
               'jobName' => $jobName,
               'categoryName' => '!',
               'currentStatusName' => '!'
            );
         }
      }
      return $timetrackingTuples;
   }

   /**
    * Get users of teams I lead
    * @return string[] : array of users
    */
   private function getUsers() {
      $accessLevel_dev = Team::accessLevel_dev;
      $accessLevel_manager = Team::accessLevel_manager;

      $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
      $teamList = $session_user->getLeadedTeamList();

      // separate list elements with ', '
      $formatedTeamString = implode( ', ', array_keys($teamList));

      // check departure date:
      // manager can manage removed users up to 7 days after their departure date.
      $today = Tools::date2timestamp(date("Y-m-d", time()));
      $timestamp=  strtotime("-7 day",$today);

      // show only users from the teams that I lead.
      $query = "SELECT DISTINCT mantis_user_table.id, mantis_user_table.username ".
         "FROM `mantis_user_table`, `codev_team_user_table` ".
         "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
         "AND (codev_team_user_table.departure_date = 0 OR codev_team_user_table.departure_date >= $timestamp) ".
         "AND codev_team_user_table.team_id IN ($formatedTeamString) ".
         "AND codev_team_user_table.access_level IN ($accessLevel_dev, $accessLevel_manager) ".
         "ORDER BY mantis_user_table.username";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         exit;
      }

      $users = NULL;
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $users[$row->id] = $row->username;
      }

      return $users;
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
      if (0 != $projectid) {
         // Project list
         $project1 = ProjectCache::getInstance()->getProject($projectid);

         // do not filter on userId if SideTask or ExternalTask
         try {
            if (($isOnlyAssignedTo) &&
               (!$project1->isSideTasksProject()) &&
               (!$project1->isNoStatsProject())) {
               $handler_id = $userid;
            } else {
               $handler_id = 0; // all users
               $isHideResolved = false; // do not hide resolved
            }
         } catch (Exception $e) {
            self::$logger->error("getIssues(): isOnlyAssignedTo & isHideResolved filters not applied : ".$e->getMessage());
            $handler_id = 0; // all users
            $isHideResolved = false; // do not hide resolved
         }

         $issueList = $project1->getIssues($handler_id, $isHideResolved);
      } else {
         // no project specified: show all tasks
         $issueList = array();

         foreach ($projList as $pid => $pname) {
            $proj = ProjectCache::getInstance()->getProject($pid);
            try {
               if (($proj->isSideTasksProject()) ||
                  ($proj->isNoStatsProject())) {
                  // do not hide any task for SideTasks & ExternalTasks projects
                  $buglist = $proj->getIssues(0, false);
                  $issueList = array_merge($issueList, $buglist);
               } else {
                  $handler_id = $isOnlyAssignedTo ? $userid : 0;
                  $buglist = $proj->getIssues($handler_id, $isHideResolved);
                  $issueList = array_merge($issueList, $buglist);
               }
            } catch (Exception $e) {
               self::$logger->error("getIssues(): task filters not applied for project $pid : ".$e->getMessage());
               // do not hide any task if unknown project type
               $buglist = $proj->getIssues(0, false);
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
      }

      return $issues;
   }


   /**
    * get Job list
    *
    * Note: the jobs depend on project type, which depends on the team
    * so we need to now in which team the user is defined in.
    *
    * @param int $projectid
    * @param string[] $teamList  user's teams
    * @return string[]
    */
   private function getJobs($projectid, array $teamList) {
      if (0 != $projectid) {
         // Project list
         $project1 = ProjectCache::getInstance()->getProject($projectid);

         $jobList = array();
         foreach ($teamList as $teamid => $name) {
            $team = TeamCache::getInstance()->getTeam($teamid);
            $ptype = $team->getProjectType($projectid);
            if (NULL != $ptype) {
               $pjobs = $project1->getJobList($ptype);
               $jobList += $pjobs; // array_merge does not work here...
            }
         }
         return $jobList;
      } else {
         $query = "SELECT id, name FROM `codev_job_table` ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            return NULL;
         }

         if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
            $jobList = array();
            while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
               $jobList[$row->id] = $row->name;
            }
            return $jobList;
         } else {
            return array();
         }
      }
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
$controller = new TimeTrackingController('Time Tracking','TimeTracking');
$controller->execute();

?>
