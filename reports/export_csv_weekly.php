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
include_once 'i18n.inc.php';
if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}
?>

<?php
   $_POST[page_name] = T_("CSV Report");
   include 'header.inc.php';
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_export_csv.inc.php'; ?>


<script language="JavaScript">

  function submitWeekActivityForm() {
     // TODO: check teamid presence
       document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
       document.forms["form1"].weekid.value = document.getElementById('weekidSelector').value;
       document.forms["form1"].year.value   = document.getElementById('yearSelector').value;

       document.forms["form1"].action.value = "exportManagementReport";
       document.forms["form1"].submit();
  }


</script>

<div id="content">

<?php

include_once "period_stats.class.php";
include_once "project.class.php";
include_once 'export_csv_tools.php';

include_once "time_tracking.class.php";
require_once('tc_calendar.php');


// -----------------------------------------------
function displayTeamAndWeekSelectionForm($leadedTeamList, $teamid, $weekid, $curYear) {

  echo "<div align='center'>\n";
  echo "<form id='form1' name='form1' method='post' action='export_csv_weekly.php'>\n";

  // -----------
  //echo "Team: <select id='teamidSelector' name='teamidSelector' onchange='javascript:submitWeekActivityForm()'>\n";
  echo T_("Team").": <select id='teamidSelector' name='teamidSelector'>\n";
  echo "<option value='0'></option>\n";
  foreach ($leadedTeamList as $tid => $tname) {
    if ($tid == $teamid) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";


  // -----------
  //echo "Week: <select id='weekidSelector' name='weekidSelector' onchange='javascript:submitWeekActivityForm()'>\n";
  echo T_("Week").": <select id='weekidSelector' name='weekidSelector'>\n";
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


  // -----------
  echo "<select id='yearSelector' name='yearSelector'>\n";
  for ($y = ($curYear -2); $y <= ($curYear +2); $y++) {

    if ($y == $curYear) {
      echo "<option selected value='".$y."'>".$y."</option>\n";
    } else {
      echo "<option value='".$y."'>".$y."</option>\n";
    }
  }
  echo "</select>\n";


  echo "&nbsp;<input type=button value='Envoyer' onClick='javascript:submitWeekActivityForm()'>\n";


  echo "<input type=hidden name=teamid  value=0>\n";
  echo "<input type=hidden name=weekid  value=".date('W').">\n";
  echo "<input type=hidden name=year    value=".date('Y').">\n";

  echo "<input type=hidden name=action       value=noAction>\n";
  echo "<input type=hidden name=currentForm  value=weekActivityReport>\n";
  echo "<input type=hidden name=nextForm     value=weekActivityReport>\n";
  echo "</form>\n";
  echo "</div>\n";
}




// ---------------------------------------------
function exportWeekActivityReportToCSV($teamid, $weekDates, $timeTracking, $myFile) {

  $sepChar=';';

  // create filename & open file
  $fh = fopen($myFile, 'w');

  $stringData = T_("Task").$sepChar.
                T_("Job").$sepChar.
                T_("Description").$sepChar.
                T_("Assigned to").$sepChar.
                T_("Monday")." ".date("d/m", $weekDates[1]).$sepChar.
                T_("Tuesday")." ".date("d/m", $weekDates[2]).$sepChar.
                T_("Wednesday")." ".date("d/m", $weekDates[3]).$sepChar.
                T_("Thursday")." ".date("d/m", $weekDates[4]).$sepChar.
                T_("Friday")." ".date("d/m", $weekDates[5])."\n";
  fwrite($fh, $stringData);


  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.realname ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $teamid ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.realname";

  $result = mysql_query($query) or die("Query failed: $query");

  while($row = mysql_fetch_object($result))
  {
      // if user was working on the project during the timestamp
      $user = UserCache::getInstance()->getUser($row->user_id);
      if (($user->isTeamDeveloper($teamid, $timeTracking->startTimestamp, $timeTracking->endTimestamp)) ||
          ($user->isTeamManager($teamid, $timeTracking->startTimestamp, $timeTracking->endTimestamp))) {

         exportWeekDetailsToCSV($row->user_id, $timeTracking, $user->getShortname(), $fh);
      }

  }
  fclose($fh);
  return $myFile;
}

// ---------------------------------------------
function exportWeekDetailsToCSV($userid, $timeTracking, $realname, $fh) {

  $sepChar=';';

  $weekTracks = $timeTracking->getWeekDetails($userid);
  foreach ($weekTracks as $bugid => $jobList) {
    $issue = IssueCache::getInstance()->getIssue($bugid);

    // remove sepChar from summary text
    $formatedSummary = str_replace("$sepChar", " ", $issue->summary);

    foreach ($jobList as $jobid => $dayList) {

      $query3  = "SELECT name FROM `codev_job_table` WHERE id=$jobid";
      $result3 = mysql_query($query3) or die("Query failed: $query3");
      $jobName = mysql_result($result3, 0);
      $stringData = $bugid.$sepChar.
                    $jobName.$sepChar.
                    $formatedSummary.$sepChar.
                    $realname.$sepChar;
      for ($i = 1; $i <= 4; $i++) {
        $stringData .= $dayList[$i].$sepChar;
      }
      $stringData .= $dayList[5]."\n";
      fwrite($fh, $stringData);
    }
  }
}



// =========== MAIN ==========
global $codevReportsDir;


$userid = $_SESSION['userid'];
$action = $_POST[action];

$defaultTeam = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;

$year = isset($_POST[year]) ? $_POST[year] : date('Y');


$user = UserCache::getInstance()->getUser($userid);
$lTeamList = $user->getLeadedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $lTeamList + $managedTeamList;
$weekid = isset($_POST[weekid]) ? $_POST[weekid] : date('W');

$query = "SELECT name FROM `codev_team_table` WHERE id = $teamid";
$result = mysql_query($query) or die("Query failed: $query");
$teamName  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : $teamid;


if (0 == count($teamList)) {
   echo "<div id='content' class='center'>";
	echo T_("Sorry, you do NOT have access to this page.");
   echo "</div>";

} else {
   echo "<div class='center'>";
	echo "<h2>".T_("Weekly report")."</h2><br/>";
   echo "</div>";

	// ----
	displayTeamAndWeekSelectionForm($teamList, $teamid, $weekid, $year);

	echo "<br/><br/>\n";

	if ("exportManagementReport" == $action) {


      if (0 != $teamid) {

      	echo "<br/>\n";
      	echo "<hr/>";
		   echo "<br/>\n";
	      echo T_("Team").": ".$teamList[$teamid]."<br/>\n";
		   echo "<br/>\n";

	      $weekDates      = week_dates($weekid,$year);
	      $startTimestamp = $weekDates[1];
	      $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[5]), date("d", $weekDates[5]), date("Y", $weekDates[5]));
	      $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

	      // -----------------------------
         echo "<span style='font-weight: bold;' title='".T_("Issues form Team projects, including issues assigned to other teams").
              "'>- ".T_("Export Managed Issues")."...</span><br/>\n";
	      flush(); // envoyer tout l'affichage courant au navigateur

         $myFile = $codevReportsDir.DIRECTORY_SEPARATOR."AOI-PIL-Mantis_".date("Ymd").".csv";
		   $filename = exportManagedIssuesToCSV($teamid, $startTimestamp, $endTimestamp, $myFile);
	      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$myFile<br/>\n";
	      flush();

	      // -----------------------------
	      echo "<br/>\n";
	      echo "<b>- ".T_("Export Week ").$weekid.T_(" Member Activity")."...</b><br/>\n";
	      flush(); // envoyer tout l'affichage courant au navigateur


         $myFile = $codevReportsDir.DIRECTORY_SEPARATOR."AOI-PIL-CRA_".$teamName."_".date("Y", $timeTracking->startTimestamp)."_W".sprintf('%02d',$weekid).".csv";
	      exportWeekActivityReportToCSV($teamid, $weekDates, $timeTracking, $myFile);
	      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$myFile<br/>\n";
	      flush();

         // -----------------------------
         echo "<br/>\n";
         echo "<b>- ".T_("Export Week ").$weekid.T_(" Projects Activity")."...</b><br/>\n";
         flush();

         $myFile = $codevReportsDir.DIRECTORY_SEPARATOR."AOI-PIL-Projects_".$teamName."_".date("Y", $timeTracking->startTimestamp)."_W".sprintf('%02d',$weekid).".csv";
         exportProjectActivityToCSV($timeTracking, $myFile);
         echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$myFile<br/>\n";
         flush();

         // -----------------------------
         echo "<br/>\n";
         echo "<b>- ".T_("Export Holidays ").$year."...</b><br/>\n";
         flush(); // envoyer tout l'affichage courant au navigateur

         // reduce scope to enhance speed
         $startMonth = 1;
         for ($i = $startMonth; $i <= 12; $i++) {
            $filename = exportHolidaystoCSV($i, $year, $teamid, $teamName, $codevReportsDir);
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$filename<br/>\n";
            //echo "<a href='$filename'>$filename</a><br/>\n";
            flush();
         }

         // -----------------------------
		   echo "<br/>\n";
		   echo "<br/>\n";
		   echo T_("Done").".<br/>\n";
		   echo "<br/>\n";
		   echo T_("Results in : ").$codevReportsDir."<br/>\n";


		}
	}
}

?>

</div>

<?php include 'footer.inc.php'; ?>
