<?php if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
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
*/ ?>

<?php include_once '../path.inc.php'; ?>

<?php
include_once "user.class.php";


function displayCheckWarnings($userid, $team_id = NULL, $isStrictlyTimestamp = FALSE) {
   // 2010-05-31 is the first date of use of this tool
   $user1 = UserCache::getInstance()->getUser($userid);

   $startTimestamp = $user1->getArrivalDate($team_id);
   $endTimestamp   = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
   $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp, $team_id);

   $incompleteDays = $timeTracking->checkCompleteDays($userid, $isStrictlyTimestamp);

   echo "<p>\n";
   foreach ($incompleteDays as $date => $value) {
      $formatedDate = date("Y-m-d", $date);
      $color = ($date >= ($endTimestamp + (24 * 60 * 60))) ? "blue": "red"; // tomorow is blue
      if ($value < 1) {
        echo "<br/><span style='color:$color' width='70'>$formatedDate ".T_("incomplete (missing ").(1-$value)." ".T_("days").")</span>\n";
      } else {
        echo "<br/><span style='color:$color' width='70'>$formatedDate ".T_("inconsistent")." (".($value)." ".T_("days").")</span>\n";
      }
   }

   $missingDays = $timeTracking->checkMissingDays($userid);
   foreach ($missingDays as $date) {
      $formatedDate = date("Y-m-d", $date);
      echo "<br/><span style='color:red' width='70'>$formatedDate ".T_("not defined.")."</span>\n";
   }
}

function displayTimetrackingTuples($userid, $startTimestamp=NULL, $endTimestamp=NULL) {

	$curJulian = 0;

   // Display previous entries
   echo "<div align='center'>\n";
   echo "<table>\n";
   echo "<caption>".T_("Imputations")."</caption>\n";
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>".T_("Date")."</th>\n";
   echo "<th title='Mantis ID'>".T_("ID")."</th>\n";
   echo "<th>".T_("Ext.ID")."</th>\n";
   echo "<th>".T_("Duration")."</th>\n";
   echo "<th>".T_("Project")."</th>\n";
   echo "<th>".T_("Description")."</th>\n";
   echo "<th>".T_("Job")."</th>\n";
   echo "<th>".T_("Category")."</th>\n";
   echo "<th>".T_("Status")."</th>\n";
   echo "<th title='BI + BS'>".T_("Load Estimation")."</th>\n";
   echo "<th title='".T_("Remaining")."'>".T_("RAF")."</th>\n";
   echo "</tr>\n";

   $query     = "SELECT id, bugid, jobid, date, duration ".
                "FROM `codev_timetracking_table` ".
                "WHERE userid=$userid ";

   if (NULL != $startTimestamp) { $query .= "AND date >= $startTimestamp "; }
   if (NULL != $endTimestamp)   { $query .= "AND date <= $endTimestamp "; }
   $query .= "ORDER BY date DESC";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
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
      $cosmeticDate    = date("Y-m-d (l)", $row->date);
      $formatedJobName = str_replace("'", "\'", $jobName);
      $formatedSummary = str_replace("'", "\'", $issue->summary);
      $formatedSummary = str_replace('"', "\'", $formatedSummary);
      $trackDescription = "$formatedDate | $row->bugid ($issue->tcId) | $formatedJobName | $row->duration | $formatedSummary";

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

      // --- display row
      echo "<tr class ='$tr_class'>\n";
      echo "<td>\n";
      echo "<a title='".T_("delete this row")."' href=\"javascript: deleteTrack('".$row->id."', '".$trackDescription."', '".$row->bugid."')\" ><img border='0' src='../images/b_drop.png'></a>\n";
      echo "</td>\n";
      echo "<td width=170>".$cosmeticDate."</td>\n";
      echo "<td>".mantisIssueURL($row->bugid)."</td>\n";
      echo "<td>".$issue->tcId."</td>\n";
      echo "<td>".$row->duration."</td>\n";
      echo "<td>".$issue->getProjectName()."</td>\n";
      echo "<td>".$issue->summary."</td>\n";
      echo "<td>".$jobName."</td>\n";
      echo "<td>".$issue->getCategoryName()."</td>\n";
      echo "<td>".$issue->getCurrentStatusName()."</td>\n";
      echo "<td title='$issue->effortEstim + $issue->effortAdd'>".$totalEstim."</td>\n";
      echo "<td>".$issue->remaining."</td>\n";

      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "<div>\n";
}

function displayWeekDetails($weekid, $weekDates, $userid, $timeTracking, $curYear=NULL) {

	if (NULL == $curYear) { $curYear = date('Y'); }

	echo "<div align='center'>\n";
   echo "<br/>\n";
   echo T_("Week")." \n";
   echo "<input type=button title='".T_("Previous week")."' value='<<' onClick='javascript: previousWeek()'>\n";
	echo "<select id='weekidSelector' name='weekidSelector' onchange='javascript: submitWeekid()'>\n";
   for ($i = 1; $i <= 53; $i++)
   {
      $wDates      = week_dates($i,$curYear);

      if ($i == $weekid) {
        echo "<option selected value='".$i."'>W".$i." | ".date("d M", $wDates[1])." - ".date("d M", $wDates[5])."</option>\n";
      } else {
        echo "<option value='".$i."'>W".$i." | ".date("d M", $wDates[1])." - ".date("d M", $wDates[5])."</option>\n";
      }
   }
   echo "</select>\n";
  echo "<select id='yearSelector' name='yearSelector' onchange='javascript: submitWeekid()'>\n";
  for ($y = ($curYear -1); $y <= ($curYear +1); $y++) {

    if ($y == $curYear) {
      echo "<option selected value='".$y."'>".$y."</option>\n";
    } else {
      echo "<option value='".$y."'>".$y."</option>\n";
    }
  }
  echo "</select>\n";
  echo "<input type=button title='".T_("Next week")."' value='>>' onClick='javascript: nextWeek()'>\n";

   $weekTracks = $timeTracking->getWeekDetails($userid);
   echo "<table>\n";
   echo "<tr>\n";
   echo "<th>".T_("Task")."</th>\n";
   echo "<th>".T_("RAF")."</th>\n";
   echo "<th>".T_("Job")."</th>\n";
   echo "<th width='80'>".T_("Monday")."<br/>".date("d M", $weekDates[1])."</th>\n";
   echo "<th width='80'>".T_("Tuesday")."<br/>".date("d M", $weekDates[2])."</th>\n";
   echo "<th width='80'>".T_("Wednesday")."<br/>".date("d M", $weekDates[3])."</th>\n";
   echo "<th width='80'>".T_("Thursday")."<br/>".date("d M", $weekDates[4])."</th>\n";
   echo "<th width='80'>".T_("Friday")."<br/>".date("d M", $weekDates[5])."</th>\n";
   echo "<th width='80' style='background-color: #D8D8D8;' >".T_("Saturday")."<br/>".date("d M", $weekDates[6])."</th>\n";
   echo "<th width='80' style='background-color: #D8D8D8;' >".T_("Sunday")."<br/>".date("d M", $weekDates[7])."</th>\n";
   echo "</tr>\n";
   foreach ($weekTracks as $bugid => $jobList) {
      $issue = IssueCache::getInstance()->getIssue($bugid);
      foreach ($jobList as $jobid => $dayList) {

         $query3  = "SELECT name FROM `codev_job_table` WHERE id=$jobid";
         $result3 = mysql_query($query3) or die("Query failed: $query3");
         $jobName = mysql_result($result3, 0);

         echo "<tr>\n";
         echo "<td>".mantisIssueURL($bugid)." / ".$issue->tcId." : ".$issue->summary."</td>\n";
         echo "<td>".$issue->remaining."</td>\n";
         echo "<td>".$jobName."</td>\n";
         for ($i = 1; $i <= 5; $i++) {
            echo "<td>".$dayList[$i]."</td>\n";
         }
         for ($i = 6; $i <= 7; $i++) {
            echo "<td style='background-color: #D8D8D8;' >".$dayList[$i]."</td>\n";
         }
         echo "</tr>\n";
      }
   }
   echo " </table>\n";
   echo "</div>\n";
}

?>