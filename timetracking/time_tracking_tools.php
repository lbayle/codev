<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
include_once "user.class.php";


function displayCheckWarnings($userid, $team_id = NULL, $isStrictlyTimestamp = FALSE) {
   // 2010-05-31 is the first date of use of this tool
   $user1 = new User($userid);
	
   $startTimestamp = $user1->getArrivalDate($team_id);
   $endTimestamp   = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
   $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp, $team_id);

   $incompleteDays = $timeTracking->checkCompleteDays($userid, $isStrictlyTimestamp);

   echo "<p>\n";
   foreach ($incompleteDays as $date => $value) {
      $formatedDate = date("Y-m-d", $date);
      $color = ($date >= ($endTimestamp + (24 * 60 * 60))) ? "blue": "red"; // tomorow is blue
      if ($value < 1) {
        echo "<br/><span style='color:$color' width='70'>$formatedDate incomplet (manque ".(1-$value)." jour)</span>\n";
      } else {
        echo "<br/><span style='color:$color' width='70'>$formatedDate incoh&eacute;rent (".($value)." jour)</span>\n";
      }
   }
   
   $missingDays = $timeTracking->checkMissingDays($userid);
   foreach ($missingDays as $date) {
      $formatedDate = date("Y-m-d", $date);
      echo "<br/><span style='color:red' width='70'>$formatedDate non d&eacute;finie.</span>\n";
   }
}

function displayTimetrackingTuples($userid, $startTimestamp=NULL, $endTimestamp=NULL) {
   // Display previous entries
   echo "<div align='center'>\n";
   echo "<table>\n";
   echo "<caption>Imputations</caption>\n";   
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>Date</th>\n";
   echo "<th>Mantis</th>\n";
   echo "<th>Fiche TC</th>\n";
   echo "<th>Duree</th>\n";
   echo "<th>Projet</th>\n";
   echo "<th>Description</th>\n";
   echo "<th>Poste</th>\n";
   echo "<th>Categorie</th>\n";
   echo "<th>Status</th>\n";
   echo "<th title='BI + BS'>Effort Estim&eacute;</th>\n";
   echo "<th title='Remaining'>RAE</th>\n";
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
      $issue = new Issue ($row->bugid);
         
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
      
      echo "<tr>\n";
      //echo "<td width=40>\n";
      echo "<td>\n";
      echo "<a title='delete this row' href=\"javascript: deleteTrack('".$row->id."', '".$trackDescription."', '".$row->bugid."')\" ><img border='0' src='b_drop.png'></a>\n";
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
   echo "<br/>Semaine \n";
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
   
   $weekTracks = $timeTracking->getWeekDetails($userid);
   echo "<table>\n";
   echo "<tr>\n";
   echo "<th>Tache</th>\n";
   echo "<th>RAE</th>\n";
   echo "<th>Poste</th>\n";
   echo "<th width='80'>Lundi<br/>".date("d M", $weekDates[1])."</th>\n";
   echo "<th width='80'>Mardi<br/>".date("d M", $weekDates[2])."</th>\n";
   echo "<th width='80'>Mercredi<br/>".date("d M", $weekDates[3])."</th>\n";
   echo "<th width='80'>Jeudi<br/>".date("d M", $weekDates[4])."</th>\n";
   echo "<th width='80'>Vendredi<br/>".date("d M", $weekDates[5])."</th>\n";
   echo "</tr>\n";
   foreach ($weekTracks as $bugid => $jobList) {
      $issue = new Issue($bugid);
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
         echo "</tr>\n";
      }
   }   
   echo " </table>\n";
   echo "</div>\n";
}

?>