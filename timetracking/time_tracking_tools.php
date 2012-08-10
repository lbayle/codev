<?php
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

require_once('tools.php');

require_once('lib/log4php/Logger.php');

class TimeTrackingTools {

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

   /**
    * display accordion with missing imputations
    * @param int $userid
    * @param int $team_id
    * @param boolean $isStrictlyTimestamp
    * @return mixed[]
    */
   public static function getCheckWarnings($userid, $team_id = NULL, $isStrictlyTimestamp = FALSE) {
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
   public static function getTimetrackingTuples($userid, $startTimestamp=NULL, $endTimestamp=NULL) {
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
            $formatedId = $row->bugid.' / '.$issue->tcId;
            $formatedJobName = str_replace("'", "\'", $jobName);
            $formatedSummary = str_replace("'", "\'", $issue->summary);
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
               'formatedId' => $formatedId,
               'duration' => $row->duration,
               'formatedJobName' => $formatedJobName,
               'summary' => $formatedSummary,
               'cosmeticDate' => $cosmeticDate,
               'mantisURL' => Tools::mantisIssueURL($row->bugid, NULL, true),
               'issueURL' => Tools::issueInfoURL($row->bugid),
               'issueId' => $issue->tcId,
               'projectName' => $issue->getProjectName(),
               'issueSummary' => $issue->summary,
               'jobName' => $jobName,
               'categoryName' => $issue->getCategoryName(),
               'currentStatusName' => $issue->getCurrentStatusName());
         } catch (Exception $e) {
            $summary = T_('Error: Task not found in Mantis DB !');
            $timetrackingTuples[] = array('id' => $row->id,
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
               'currentStatusName' => '!');

         }
      }
      return $timetrackingTuples;
   }

   /**
    * @param int[] $weekDates
    * @param int $userid
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   public static function getWeekTask(array $weekDates, $userid, TimeTracking $timeTracking) {
      $jobs = new Jobs();

      $weekTasks = NULL;
      $holidays = Holidays::getInstance();
      $weekTracks = $timeTracking->getWeekDetails($userid);
      foreach ($weekTracks as $bugid => $jobList) {

         try {
            $issue = IssueCache::getInstance()->getIssue($bugid);

            $backlog = $issue->backlog;
            $extRef = $issue->tcId;
            $summary = $issue->summary;
            $issueURL = Tools::issueInfoURL($bugid);
            $mantisURL = Tools::mantisIssueURL($bugid, NULL, true);

         } catch (Exception $e) {
            $backlog = '!';
            $extRef = '';
            $summary = '<span class="error_font">'.T_('Error: Task not found in Mantis DB !').'</span>';
            $issueURL = $bugid;
            $mantisURL = '';
         }

         foreach ($jobList as $jobid => $dayList) {
            // if no backlog set, display a '?' to allow Backlog edition
            if(is_numeric($backlog)) {
               $formattedBacklog = $backlog;
            } else {
               #if (($team->isSideTasksProject($issue->projectId)) ||
               #    ($team->isNoStatsProject($issue->projectId))) {
               // do not allow to edit sideTasks Backlog
               $formattedBacklog = '';
               #} else {
               #   $formattedBacklog = '?';
               #}
               //
            }

            $dayTasks = "";
            for ($i = 1; $i <= 7; $i++) {
               if($i <= 5) {
                  $h = $holidays->isHoliday($weekDates[$i]);
                  if ($h) {
                     $bgColor = $h->color;
                     #$bgColor = "style='background-color: #".Holidays::$defaultColor.";'";
                     $title = "title='".$h->description."'";
                  } else {
                     $bgColor = NULL;
                     $title = "";
                  }
               } else {
                  $bgColor = Holidays::$defaultColor;
                  $title = "";
               }
               $dayTasks[] = array('bgColor' => $bgColor,
                  'title' => $title,
                  'day' => $dayList[$i]
               );
            }

            $weekTasks[$bugid."_".$jobid] = array(
               'bugid' => $bugid,
               'issueURL' => $issueURL,
               'mantisURL' => $mantisURL,
               'issueId' => $extRef,
               'summary' => $summary,
               'backlog' => $backlog,
               'description' => addslashes(htmlspecialchars($summary)),
               'formattedBacklog' => $formattedBacklog,
               'jobid' => $jobid,
               'jobName' => $jobs->getJobName($jobid),
               'dayTasks' => $dayTasks
            );
         }
      }

      return $weekTasks;
   }

   /**
    * Get users of teams I lead
    * @return string[] : array of users
    */
   public static function getUsers() {
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
   public static function getIssues($projectid, $isOnlyAssignedTo, $userid, array $projList, $isHideResolved, $defaultBugid) {
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
         $issues[$issue->bugId] = array('id' => $issue->bugId,
            'tcId' => $issue->tcId,
            'summary' => $issue->summary,
            'selected' => $issue->bugId == $defaultBugid);
      }

      // If the default bug is filtered, we add it anyway
      if(!array_key_exists($defaultBugid,$issues) && $defaultBugid != 0) {
         $issue = IssueCache::getInstance()->getIssue($defaultBugid);
         // Add the bug only if the selected project is the bug project
         if($projectid == 0 || $issue->projectId == $projectid) {
            $issues[$issue->bugId] = array(
               'id' => $issue->bugId,
               'tcId' => $issue->tcId,
               'summary' => $issue->summary,
               'selected' => $issue->bugId == $defaultBugid);
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
   public static function getJobs($projectid, array $teamList) {
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
   public static function getDuration() {
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

// Initialize complex static variables
Tools::staticInit();

?>
