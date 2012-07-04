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

require_once('../path.inc.php');

require_once('super_header.inc.php');

include_once('i18n.inc.php');

include_once('user_cache.class.php');
include_once('issue_cache.class.php');
include_once('project_cache.class.php');
include_once('jobs.class.php');
include_once('holidays.class.php');
include_once('team.class.php');
include_once('time_tracking.class.php');

$logger = Logger::getLogger("time_tracking_tools");

/**
 * display accordion with missing imputations
 * @param int $userid
 * @param int $team_id
 * @param boolean $isStrictlyTimestamp
 * @return array
 */
function getCheckWarnings($userid, $team_id = NULL, $isStrictlyTimestamp = FALSE) {
   // 2010-05-31 is the first date of use of this tool
   $user1 = UserCache::getInstance()->getUser($userid);

   $startTimestamp = $user1->getArrivalDate($team_id);
   $endTimestamp = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
   $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $team_id);

   $incompleteDays = $timeTracking->checkCompleteDays($userid, $isStrictlyTimestamp);
   $missingDays = $timeTracking->checkMissingDays($userid);

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
 * @param unknown_type $startTimestamp
 * @param unknown_type $endTimestamp
 * @return array
 */
function getTimetrackingTuples($userid, $startTimestamp=NULL, $endTimestamp=NULL) {
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

   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      // get information on this bug
      $issue = IssueCache::getInstance()->getIssue($row->bugid);

      // get general information

      $jobName = $jobs->getJobName($row->jobid);

      $formatedDate= date("Y-m-d", $row->date);
      $cosmeticDate    = date("Y-m-d", $row->date).' - '.T_(date("l", $row->date));
      $formatedId = "$row->bugid / $issue->tcId";
      $formatedJobName = str_replace("'", "\'", $jobName);
      $formatedSummary = str_replace("'", "\'", $issue->summary);
      $formatedSummary = str_replace('"', "\'", $formatedSummary);
      $totalEstim = $issue->effortEstim + $issue->effortAdd;

      // --- choose row color
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
                                    'mantisURL' => mantisIssueURL($row->bugid, NULL, true),
                                    'issueURL' => issueInfoURL($row->bugid),
                                    'issueId' => $issue->tcId,
                                    'projectName' => $issue->getProjectName(),
                                    'issueSummary' => $issue->summary,
                                    'jobName' => $jobName,
                                    'categoryName' => $issue->getCategoryName(),
                                    'currentStatusName' => $issue->getCurrentStatusName());
   }
   return $timetrackingTuples;
}

function getWeekTask($weekDates, $userid, $timeTracking) {
   $jobs = new Jobs();

   $linkList = array();
   $holidays = Holidays::getInstance();
   $weekTracks = $timeTracking->getWeekDetails($userid);
   foreach ($weekTracks as $bugid => $jobList) {
      $issue = IssueCache::getInstance()->getIssue($bugid);

      foreach ($jobList as $jobid => $dayList) {
         $linkid = $bugid."_".$jobid;
         $linkList[$linkid] = $issue;

         $jobName = $jobs->getJobName($jobid);

         // if no remaining set, display a '?' to allow Remaining edition
         if (NULL == $issue->remaining) {

            #if (($team->isSideTasksProject($issue->projectId)) ||
            #    ($team->isNoStatsProject($issue->projectId))) {
            // do not allow to edit sideTasks Remaining
            $formattedRemaining = '';
            #} else {
            #   $formattedRemaining = '?';
            #}
         } else {
            $formattedRemaining = $issue->remaining;
         }

         $dayTasks = "";
         for ($i = 1; $i <= 7; $i++) {
            if($i <= 5) {
               $h = $holidays->isHoliday($weekDates[$i]);
               if ($h) {
                  $bgColor = "style='background-color: #".$h->color.";'";
                  #$bgColor = "style='background-color: #".Holidays::$defaultColor.";'";
                  $title = "title='".$h->description."'";
               } else {
                  $bgColor = "";
                  $title = "";
               }
            } else {
               $bgColor = "style='background-color: #".Holidays::$defaultColor.";'";
               $title = "";
            }
            $dayTasks[] = array('bgColor' => $bgColor,
                                'title' => $title,
                                'day' => $dayList[$i]
            );
         }

         $weekTasks[] = array('bugid' => $bugid,
                              'issueURL' => issueInfoURL($bugid),
                              'mantisURL' => mantisIssueURL($bugid, NULL, true),
                              'issueId' => $issue->tcId,
                              'summary' => $issue->summary,
                              'remaining' => $issue->remaining,
                              'description' => addslashes(htmlspecialchars($issue->summary)),
                              'dialogBoxTitle' => T_("Task")." ".$issue->bugId." / ".$issue->tcId." - ".T_("Update Remaining"),
                              'formattedRemaining' => $formattedRemaining,
                              'jobName' => $jobName,
                              'dayTasks' => $dayTasks
         );

      }
   }

   return $weekTasks;
}

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

   // check departure date:
   // manager can manage removed users up to 7 days after their departure date.
   $today = date2timestamp(date("Y-m-d", time()));
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

   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $users[$row->id] = $row->username;
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
   global $logger;

   if (0 != $projectid) {
      // Project list
      $project1 = ProjectCache::getInstance()->getProject($projectid);

      // do not filter on userId if SideTask or ExternalTask
      try {
         if (($isOnlyAssignedTo) &&
            (!$project1->isSideTasksProject()) &&
            (!$project1->isNoStatsProject())) {
            $handler_id = $user1->id;
         } else {
            $handler_id = 0; // all users
            $isHideResolved = false; // do not hide resolved
         }
      } catch (Exception $e) {
         $logger->error("getIssues(): isOnlyAssignedTo & isHideResolved filters not applied : ".$e->getMessage());
         $handler_id = 0; // all users
         $isHideResolved = false; // do not hide resolved
      }

      $issueList = $project1->getIssueList($handler_id, $isHideResolved);
   } else {
      // no project specified: show all tasks
      $issueList = array();

      foreach ($projList as $pid => $pname) {
         $proj = ProjectCache::getInstance()->getProject($pid);
         try {
            if (($proj->isSideTasksProject()) ||
               ($proj->isNoStatsProject())) {
               // do not hide any task for SideTasks & ExternalTasks projects
               $buglist = $proj->getIssueList(0, false);
               $issueList = array_merge($issueList, $buglist);
            } else {
               $handler_id = $isOnlyAssignedTo ? $user1->id : 0;
               $buglist = $proj->getIssueList($handler_id, $isHideResolved);
               $issueList = array_merge($issueList, $buglist);
            }
         } catch (Exception $e) {
            $logger->error("getIssues(): task filters not applied for project $pid : ".$e->getMessage());
            // do not hide any task if unknown project type
            $buglist = $proj->getIssueList(0, false);
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
 *
 * Note: the jobs depend on project type, which depends on the team
 * so we need to now in which team the user is defined in.
 *
 * @param array $teamList  user's teams
 * @param int $projectid
 * @return array
 */
function getJobs($projectid, $teamList) {
   global $logger;

   $jobList = array();

   if (0 != $projectid) {
      // Project list
      $project1 = ProjectCache::getInstance()->getProject($projectid);

      foreach ($teamList as $teamid => $name) {
         $team = TeamCache::getInstance()->getTeam($teamid);
         $ptype = $team->getProjectType($projectid);
         if (NULL != $ptype) {
            $pjobs = $project1->getJobList($ptype);
            $jobList += $pjobs; // array_merge does not work here...
         }
      }


   } else {
      $query = "SELECT id, name FROM `codev_job_table` ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return;
      }

      if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $jobList[$row->id] = $row->name;
         }
      }
   }

   return $jobList;
}

?>
