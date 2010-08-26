<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
include_once "../auth/user.class.php";

function addTrackForm($weekid, $userid, $defaultDate, $defaultBugid, $originPage) {
   list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate);

   $myCalendar = new tc_calendar("date1", true, false);
   $myCalendar->setIcon("calendar/images/iconCalendar.gif");
   $myCalendar->setDate($defaultDay, $defaultMonth, $defaultYear);
   $myCalendar->setPath("calendar/");
   $myCalendar->setYearInterval(2010, 2015);
   $myCalendar->dateAllow('2010-01-01', '2015-12-31');
   $myCalendar->setDateFormat('Y-m-d');
   $myCalendar->startMonday(true);

   $query = "SELECT realname FROM `mantis_user_table` WHERE id = $userid";
   $result = mysql_query($query) or die("Query failed: $query");
   $userName    = mysql_result($result, 0);

   // Display form
   echo "<h2>Saisie des Temps : $userName</h2>\n";

   echo "<div style='text-align: center;'>";

   echo "<form name='form1' method='post' Action='$originPage'>\n";

   echo "Date: "; $myCalendar->writeScript();

   // This filters the bugid list to shorten the 'bugid' Select.
   $user1 = new User($_SESSION['userid']);
   $taskList = $user1->getPossibleWorkingTasksList();

   echo "&nbsp;Task: <select name='bugid' style='width: 600px;'>\n";
   foreach ($taskList as $bid)
   {
      $issue = new Issue ($bid);
      if ($bid == $defaultBugid) {
         echo "<option selected value='".$bid."'>".$bid." / $issue->tcId : $issue->summary</option>\n";
      } else {
         echo "<option value='".$bid."'>".$bid." / $issue->tcId : $issue->summary</option>\n";
      }
   }
   echo "</select>\n";
   
   echo "Poste: <select name='job'>\n";
   echo "<option value='0'></option>\n";
   $query     = "SELECT id, name FROM `codev_job_table`";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      echo "<option value='".$row->id."'>".$row->name."</option>\n";
   }
   echo "</select>\n";

   echo "Duration: <select name='duree'>\n";
   echo "<option value='0'></option>\n";
   echo "<option value='1'>1</option>\n";
   echo "<option value='0.9'>0.9</option>\n";
   echo "<option value='0.8'>0.8</option>\n";
   echo "<option value='0.75'>0.75</option>\n";
   echo "<option value='0.7'>0.7</option>\n";
   echo "<option value='0.6'>0.6</option>\n";
   echo "<option value='0.5'>0.5 (4h)</option>\n";
   echo "<option value='0.4'>0.4 (3h)</option>\n";
   echo "<option value='0.3'>0.3 (2h 30)</option>\n";
   echo "<option value='0.25'>0.25 (2h)</option>\n";
   echo "<option value='0.2'>0.2 (1h 30)</option>\n";
   echo "<option value='0.1'>0.1 (1h)</option>\n";
   echo "</select>\n";

   echo "<input type=button name='btAddTrack' value='Ajouter' onClick='javascript: addTrack()'>\n";

   echo "<input type=hidden name=userid  value=$userid>\n";
   echo "<input type=hidden name=weekid  value=$weekid>\n";
   echo "<input type=hidden name=trackid value=unknown1>\n";

   echo "<input type=hidden name=action       value=noAction>\n";
   echo "<input type=hidden name=currentForm  value=addTrackForm>\n";
   echo "<input type=hidden name=nextForm     value=addTrackForm>\n";
   echo "</form>\n";
   
   echo "</div>";
}

function displayCheckWarnings($userid, $isStrictlyTimestamp = FALSE) {
   // 2010-05-31 is the first date of use of this tool
   $user1 = new User($userid);
	
   $startTimestamp = $user1->getArrivalDate();
   $endTimestamp   = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
   $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp);

   $incompleteDays = $timeTracking->checkCompleteDays($userid, $isStrictlyTimestamp);

   echo "<p>\n";
   foreach ($incompleteDays as $date => $value) {
      $formatedDate = date("Y-m-d", $date);
      $color = ($date >= ($endTimestamp + (24 * 60 * 60))) ? "blue": "red"; // tomorow is blue
      echo "<br/><span style='color:$color' width='70'>$formatedDate incomplet (manque ".(1-$value)." jour)</span>\n";
   }
   
   $missingDays = $timeTracking->checkMissingDays($userid);
   foreach ($missingDays as $date) {
      $formatedDate = date("Y-m-d", $date);
      echo "<br/><span style='color:red' width='70'>$formatedDate non d&eacute;finie.</span>\n";
   }
}

function displayTimetrackingTuples($userid) {
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
   echo "<th>Description</th>\n";
   echo "<th>Poste</th>\n";
   echo "<th>Projet</th>\n";
   echo "<th>Categorie</th>\n";
   echo "<th>Status</th>\n";
   echo "<th>Effort Estim&eacute;</th>\n";
   echo "<th>RAE</th>\n";
   echo "</tr>\n";

   $query     = "SELECT id, bugid, jobid, date, duration FROM `codev_timetracking_table` WHERE userid=$userid ORDER BY date DESC";
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
      $formatedSummary = str_replace('"', "\'", $issue->summary);
      
      echo "<tr>\n";
      echo "<td>\n";
      $trackDescription = "$formatedDate | $row->bugid ($issue->tcId) | $formatedJobName | $row->duration | $formatedSummary";
      echo "<a title='delete this row' href=\"javascript: deleteTrack('".$row->id."', '".$trackDescription."')\" ><img border='0' src='b_drop.png'></a>\n";
      echo "<a title='Edit Mantis Issue' href='http://".$_SERVER['HTTP_HOST']."/mantis/view.php?id=$row->bugid' target='_blank'><img border='0' src='http://".$_SERVER['HTTP_HOST']."/mantis/images/favicon.ico'></a>";
      
      echo "</td>\n";
      echo "<td>".$cosmeticDate."</td>\n";
      echo "<td>".$row->bugid."</td>\n";
      echo "<td>".$issue->tcId."</td>\n";
      echo "<td>".$row->duration."</td>\n";
      echo "<td>".$issue->summary."</td>\n";
      echo "<td>".$jobName."</td>\n";
      echo "<td>".$issue->getProjectName()."</td>\n";
      echo "<td>".$issue->getCategoryName()."</td>\n";
      echo "<td>".$issue->getCurrentStatusName()."</td>\n";
      echo "<td>".$issue->EffortEstim."</td>\n";
      echo "<td>".$issue->remaining."</td>\n";

      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "<div>\n";
}

function displayWeekDetails($weekid, $weekDates, $userid, $timeTracking) {
   echo "<div align='center'>\n";
   echo "<br/>Semaine \n";
   echo "<select name='weekidSelector' onchange='javascript: submitWeekid(this)'>\n";
   for ($i = 1; $i <= 53; $i++)
   {
      $wDates      = week_dates($i,date('Y'));
      
      if ($i == $weekid) {
        echo "<option selected value='".$i."'>W".$i." | ".date("d M", $wDates[1])." - ".date("d M", $wDates[5])."</option>\n";
      } else {
        echo "<option value='".$i."'>W".$i." | ".date("d M", $wDates[1])." - ".date("d M", $wDates[5])."</option>\n";
      }
   }
   echo "</select>\n";
   
   $weekTracks = $timeTracking->getWeekDetails($userid);
   echo "<table>\n";
   echo "<tr>\n";
   echo "<th>Tache</th>\n";
   echo "<th>Poste</th>\n";
   echo "<th width='80'>Lundi<br/>".date("d F", $weekDates[1])."</th>\n";
   echo "<th width='80'>Mardi<br/>".date("d F", $weekDates[2])."</th>\n";
   echo "<th width='80'>Mercredi<br/>".date("d F", $weekDates[3])."</th>\n";
   echo "<th width='80'>Jeudi<br/>".date("d F", $weekDates[4])."</th>\n";
   echo "<th width='80'>Vendredi<br/>".date("d F", $weekDates[5])."</th>\n";
   echo "</tr>\n";
   foreach ($weekTracks as $bugid => $jobList) {
      $issue = new Issue($bugid);
      foreach ($jobList as $jobid => $dayList) {
         
         $query3  = "SELECT name FROM `codev_job_table` WHERE id=$jobid";
         $result3 = mysql_query($query3) or die("Query failed: $query3");
         $jobName = mysql_result($result3, 0);
         
         echo "<tr>\n";
         echo "<td>$bugid / ".$issue->tcId." : ".$issue->summary."</td>\n";
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