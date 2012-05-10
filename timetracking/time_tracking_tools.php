<?php
include_once('../include/session.inc.php');

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

include_once 'i18n.inc.php';

include_once "issue.class.php";
include_once "user.class.php";
include_once "time_tracking.class.php";
include_once "holidays.class.php";
include_once "team.class.php";

$logger = Logger::getLogger("time_tracking_tools");

// MAIN
if(isset($_GET['action'])) {
   if (!isset($_SESSION['userid'])) {
      header('HTTP/1.1 403 Forbidden');
      exit;
   }
   if($_GET['action'] == 'updateRemainingAction') {
      if (NULL != $_GET['remaining']) {
         $issue = IssueCache::getInstance()->getIssue($_GET['bugid']);
         $formattedRemaining = mysql_real_escape_string($_GET['remaining']);
         $issue->setRemaining($formattedRemaining);
      }

      echo getWeekTaskDetails($_GET['userid'],$_GET['weekid'],$_GET['year']);
   } else if($_GET['action'] == 'updateWeekDisplay') {
      echo getWeekTaskDetails($_GET['userid'],$_GET['weekid'],$_GET['year']);
   }

}

/**
 * display accordion with missing imputations
 * @param unknown_type $userid
 * @param unknown_type $team_id
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
 * @param unknown_type $userid
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
   $result = mysql_query($query) or die("Query failed: $query");

   while($row = mysql_fetch_object($result)) {
      // get information on this bug
      $query2  = "SELECT summary, status, date_submitted, project_id, category_id FROM `mantis_bug_table` WHERE id=$row->bugid";
      $result2 = mysql_query($query2) or die("Query failed: $query2");
      $row2 = mysql_fetch_object($result2);
      $issue = IssueCache::getInstance()->getIssue($row->bugid);

      // get general information
      $query3  = "SELECT name FROM `codev_job_table` WHERE id=$row->jobid";
      $result3 = mysql_query($query3) or die("Query failed: $query3");
      $jobName = mysql_result($result3, 0);
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

      $timetrackingTuples[] = array('id' => $row->id, 'class' => $tr_class,
                                    'date' => $formatedDate, 'formatedId' => $formatedId,
                                    'duration' => $row->duration, 'formatedJobName' => $formatedJobName,
                                    'summary' => $formatedSummary, 'cosmeticDate' => $cosmeticDate,
                                    'issueURL' => issueInfoURL($row->bugid), 'issueId' => $issue->tcId,
                                    'projectName' => $issue->getProjectName(),
                                    'issueSummary' => $issue->summary, 'jobName' => $jobName,
                                    'categoryName' => $issue->getCategoryName(),
                                    'currentStatusName' => $issue->getCurrentStatusName());
   }
   return $timetrackingTuples;
}

function getWeeks($weekid, $year) {
   for ($i = 1; $i <= 53; $i++) {
      $wDates = week_dates($i,$year);
      $monday = strftime(T_('W').'%U | %d %b', strtotime("Monday",$wDates[1]));
      $friday = strftime("%d %b", strtotime("Friday",$wDates[1]));
      $weeks[] = array('id' => $i,
                       'value' => utf8_encode(ucwords($monday)." - ".ucwords($friday)),
                       'selected' => $i == $weekid);
   }

   return $weeks;
}

function getYears($year) {
   for ($y = ($year -1); $y <= ($year +1); $y++) {
      $years[] = array('id' => $y,
                       'selected' => $y == $year);
   }

   return $years;
}

/**
 * TODO Use a Smarty template
 * @param int $userid
 * @param int $weekid
 * @param int $year
 * @return String html
 */
function getWeekTaskDetails($userid, $weekid, $year) {
   $weekDates = week_dates($weekid,$year);
   $startTimestamp = $weekDates[1];
   $endTimestamp = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
   $timeTracking = new TimeTracking($startTimestamp, $endTimestamp);

   $weekTasks = getWeekTask($weekDates,$userid,$timeTracking);
   $html = "";
   if($weekTasks != NULL) {
      foreach($weekTasks as $weekTask) {
         $html .= '<tr>';
         $html .= '<td>'.$weekTask['issueURL'].' / '.$weekTask['issueId'].' : '.$weekTask['summary'].'</td>';
         $html .= '<td><a title="'.T_('update remaining').'" href="javascript: updateRemaining(\''.$weekTask['remaining']."','".
                  $weekTask['description']."','".$weekTask['bugid']."','".$weekTask['dialogBoxTitle'].'\')">'.$weekTask['formattedRemaining'].'</a>';
         $html .= '</td>';
         $html .= '<td>'.$weekTask['jobName'].'</td>';
         foreach($weekTask['dayTasks'] as $dayTasks) {
            $html .= '<td '.$dayTasks['bgColor'].' '.$dayTasks['title'].'>'.$dayTasks['day'].'</td>';
         }
         $html .= '</tr>';
      }
      }
   return $html;
}

function getWeekTask($weekDates, $userid, $timeTracking) {
   $linkList = array();
   $holidays = Holidays::getInstance();
   $weekTracks = $timeTracking->getWeekDetails($userid);
   foreach ($weekTracks as $bugid => $jobList) {
      $issue = IssueCache::getInstance()->getIssue($bugid);

      foreach ($jobList as $jobid => $dayList) {
         $linkid = $bugid."_".$jobid;
         $linkList[$linkid] = $issue;

         $query3  = "SELECT name FROM `codev_job_table` WHERE id=$jobid";
         $result3 = mysql_query($query3) or die("Query failed: $query3");
         $jobName = mysql_result($result3, 0);

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

         $weekTasks[] = array('bugid' => $bugid, 'issueURL' => issueInfoURL($bugid),
                              'issueId' => $issue->tcId, 'summary' => $issue->summary,
                              'remaining' => $issue->remaining,
                              'description' => addslashes(htmlspecialchars($issue->summary)),
                              'dialogBoxTitle' => T_("Task")." ".$issue->bugId." / ".$issue->tcId." - ".T_("Update Remaining"),
                              'formattedRemaining' => $formattedRemaining,
                              'jobName' => $jobName, 'dayTasks' => $dayTasks
         );

      }
   }

   return $weekTasks;
}

?>
