<?php if (!isset($_SESSION)) { session_start(); header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); } ?>
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
   $_POST['page_name'] = T_("Weekly activities");
   include 'header.inc.php';
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_week_activity.inc.php'; ?>


<script language="JavaScript">
  function submitForm() {
  // TODO: check teamid presence
    document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
    document.forms["form1"].weekid.value = document.getElementById('weekidSelector').value;
    document.forms["form1"].year.value   = document.getElementById('yearSelector').value;
    document.forms["form1"].action.value = "updateWeekDisplay";
    document.forms["form1"].submit();
  }

  function submitPeriodActivityForm() {

     document.forms["form2"].teamid.value = document.getElementById('teamidSelector').value;
     document.forms["form2"].is_modified.value= "true";
     document.forms["form2"].action.value = "displayProjectActivity";

     document.forms["form2"].submit();
  }


</script>

<div id="content" class="center">

<?php

include_once "issue.class.php";
include_once "project.class.php";
include_once "user.class.php";
include_once "time_tracking.class.php";
require_once('tc_calendar.php');

$logger = Logger::getLogger("project_activity_report");

// ------------------------------------------------
function displayTeamAndWeekSelectionForm($leadedTeamList, $teamid, $weekid, $curYear=NULL) {

  if (NULL == $curYear) { $curYear = date('Y'); }

  echo "<div>\n";
  echo "<form id='form1' name='form1' method='post' action='project_activity_report.php'>\n";

  // -----------
  echo T_("Team").": <select id='teamidSelector' name='teamidSelector' onchange='javascript: submitForm()'>\n";
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
  echo T_("Week").": <select id='weekidSelector' name='weekidSelector' onchange='javascript: submitForm()'>\n";
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
  echo "<select id='yearSelector' name='yearSelector' onchange='javascript: submitForm()'>\n";
  for ($y = ($curYear -1); $y <= ($curYear +1); $y++) {

    if ($y == $curYear) {
      echo "<option selected value='".$y."'>".$y."</option>\n";
    } else {
      echo "<option value='".$y."'>".$y."</option>\n";
    }
  }
  echo "</select>\n";

  echo "<input type=hidden name=teamid  value=1>\n";
  echo "<input type=hidden name=weekid  value=".date('W').">\n";
  echo "<input type=hidden name=year    value=$curYear>\n";

  echo "<input type=hidden name=action       value=noAction>\n";
  echo "<input type=hidden name=currentForm  value=weekActivityReport>\n";
  echo "<input type=hidden name=nextForm     value=weekActivityReport>\n";

  echo "</form>\n";
  echo "</div>\n";
}


// -----------------------------------------------
function displayTeamAndPeriodSelectionForm($teamList, $teamid, $defaultDate1, $defaultDate2, $isDetailed = false, $is_modified = "false") {

  list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate1);

  $myCalendar1 = new tc_calendar("date1", true, false);
  $myCalendar1->setIcon("../calendar/images/iconCalendar.gif");
  $myCalendar1->setDate($defaultDay, $defaultMonth, $defaultYear);
  $myCalendar1->setPath("../calendar/");
  $myCalendar1->setYearInterval(2010, 2015);
  $myCalendar1->dateAllow('2010-01-01', '2015-12-31');
  $myCalendar1->setDateFormat('Y-m-d');
  $myCalendar1->startMonday(true);

  list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate2);

  $myCalendar2 = new tc_calendar("date2", true, false);
  $myCalendar2->setIcon("../calendar/images/iconCalendar.gif");
  $myCalendar2->setDate($defaultDay, $defaultMonth, $defaultYear);
  $myCalendar2->setPath("../calendar/");
  $myCalendar2->setYearInterval(2010, 2015);
  $myCalendar2->dateAllow('2010-01-01', '2015-12-31');
  $myCalendar2->setDateFormat('Y-m-d');
  $myCalendar2->startMonday(true);

  echo "<div class=center>";
  // Create form
  echo "<form id='form2' name='form1' method='post' action='project_activity_report.php'>\n";

  echo T_("Team").": <select id='teamidSelector' name='teamidSelector'>\n";
   echo "<option value='0'></option>\n";
   foreach ($teamList as $tid => $tname) {
    if ($tid == $teamid) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";

  echo "&nbsp;".T_("Start Date").": "; $myCalendar1->writeScript();

  echo "&nbsp; <span title='".T_("(included)")."'>".T_("End Date").": </span>"; $myCalendar2->writeScript();

  $isChecked = $isDetailed ? "CHECKED" : "";
  echo "&nbsp;<input type=CHECKBOX  $isChecked name='cb_detailed' id='cb_detailed'>".T_("Detailed")."</input>\n";

  echo "&nbsp;<input type=button value='".T_("Compute")."' onClick='javascript: submitPeriodActivityForm()'>\n";

  echo "<input type=hidden name=teamid  value=$teamid>\n";

  echo "<input type=hidden name=action       value=noAction>\n";
  echo "<input type=hidden name=is_modified value=$is_modified>\n";

  echo "</form>\n";
  echo "</div>";
}



// ------------------------------------------------
function displayProjectActivityReport($timeTracking, $isDetailed = true) {
   global $logger;

   // $projectTracks[projectid][bugid][jobid] = duration
   $projectTracks = $timeTracking->getProjectTracks(true);

  echo "<div align='center'>\n";

  foreach ($projectTracks as $projectId => $bugList) {

     // write table header
     $project = ProjectCache::getInstance()->getProject($projectId);
     echo "<br/>\n";
     echo "<table width='98%'>\n";
     echo "<caption>".$project->name."</caption>\n";
     echo "<tr>\n";
     echo "  <th width='50%'>".T_("Task")."</th>\n";

     $jobList = $project->getJobList();
     if ($isDetailed) {
	    $jobColWidth = (0 != count($jobList)) ? (100 - 50 - 10 - 6) / count($jobList) : 10;
         foreach($jobList as $jobId => $jobName) {
             echo "  <th width='".$jobColWidth."%'>$jobName</th>\n";
         }
    }
     echo "<th width='10%' title='".T_("Target version")."'>".T_("Target")."</th>\n";
     echo "  <th width='2%'>".T_("Status")."</th>\n";
     echo "  <th width='2%'>".T_("Progress")."</th>\n";
     echo "  <th width='2%'>".T_("RAF")."</th>\n";
     echo "  <th width='2%' title='".T_("Total time spent on this issue")."'>".T_("Total")."</th>\n";
     echo "</tr>\n";

     // write table content (by bugid)
     foreach ($bugList as $bugid => $jobs) {
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $totalTime = 0;
         echo "<tr>\n";
         echo "<td>".issueInfoURL($bugid)." / ".$issue->tcId." : ".$issue->summary."</td>\n";

         foreach($jobList as $jobId => $jobName) {
            if ($isDetailed) {
               echo "<td width='".$jobColWidth."%'>".$jobs[$jobId]."</td>\n";
            }
            $totalTime += $jobs[$jobId];
         }

         echo "<td>".$issue->getTargetVersion()."</td>\n";
         echo "<td>".$issue->getCurrentStatusName()."</td>\n";
         echo "<td>".round(100 * $issue->getProgress())."%</td>\n";
         echo "<td>".$issue->remaining."</td>\n";
         echo "<td>".$totalTime."</td>\n";
         echo "</tr>\n";
     }
     echo "</table>\n";
  }
  echo "</div>\n";
}



function displayCheckWarnings($timeTracking) {
  
  global $logger;  
    
  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $timeTracking->team_id ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";

  // FIXME AND user is not Observer

  $result = mysql_query($query);
  if (!$result) {
     $logger->error("Query FAILED: $query");
     $logger->error(mysql_error());
     echo "<span style='color:red'>ERROR: Query FAILED</span>";
     exit;
  }

  echo "<p style='color:red'>\n";

  while($row = mysql_fetch_object($result))
  {
    $incompleteDays = $timeTracking->checkCompleteDays($row->user_id, TRUE);
    foreach ($incompleteDays as $date => $value) {
      $formatedDate = date("Y-m-d", $date);
      if ($value < 1) {
        echo "<br/>$row->username: $formatedDate ".T_("incomplete (missing ").(1-$value).T_(" days").").\n";
      } else {
        echo "<br/>$row->username: $formatedDate ".T_("inconsistent")." (".($value)." ".T_("days").").\n";
      }
    }

    $missingDays = $timeTracking->checkMissingDays($row->user_id);
    foreach ($missingDays as $date) {
      $formatedDate = date("Y-m-d", $date);
      echo "<br/>$row->username: $formatedDate ".T_("not defined.")."\n";
    }
  }
  echo "</p>\n";
}


// ================ MAIN =================
$year = isset($_POST['year']) ? $_POST['year'] : date('Y');

$userid = $_SESSION['userid'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$weekid = isset($_POST['weekid']) ? $_POST['weekid'] : date('W');

$defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
$teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
$_SESSION['teamid'] = $teamid;

// 'is_modified' is used because it's not possible to make a difference
// between an unchecked checkBox and an unset checkbox variable
$is_modified = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";
if ("false" == $is_modified) {
   $isDetailed = false;
} else {
   $isDetailed   = isset($_POST['cb_detailed']) ? true : false;
}


// team
$user = UserCache::getInstance()->getUser($userid);
$mTeamList = $user->getTeamList();
$lTeamList = $user->getLeadedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $mTeamList + $lTeamList + $managedTeamList;

// dates
$month = date('m');
$year = date('Y');

if (isset($_REQUEST["date1"])) {
   $date1          = $_REQUEST["date1"];
   $startTimestamp = date2timestamp($date1);
} else {
   #$startTimestamp = mktime(0, 0, 0, $month, 1, $year);
   $weekDates      = week_dates($weekid,$year);
   $startTimestamp = $weekDates[1];

   $date1          = date("Y-m-d", $startTimestamp);
}
if (isset($_REQUEST["date2"])) {
   $date2          = $_REQUEST["date2"];
   $endTimestamp   = date2timestamp($date2);
   $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.
} else {
   #$nbDaysInMonth  = date("t", mktime(0, 0, 0, $month, 1, $year));
   #$endTimestamp   = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);

   $weekDates      = week_dates($weekid,$year);
   $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[5]), date("d", $weekDates[5]), date("Y", $weekDates[5]));

   $date2          = date("Y-m-d", $endTimestamp);

}

// ------

if (0 == count($teamList)) {
	echo T_("Sorry, you do NOT have access to this page.");

} else {

   echo "<div class='center'>";
   echo "<h2>".T_("Projects Activity")."</h2><br/>";
   echo "</div>";

   displayTeamAndPeriodSelectionForm($teamList, $teamid, $date1, $date2, $isDetailed, $is_modified);

   if ("displayProjectActivity" == $action) {

	if (NULL != $teamList[$teamid]) {

	   $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp, $teamid);


      echo "<br/><br/>\n";
      displayProjectActivityReport($timeTracking, $isDetailed);
	}
   } // if action

   echo "<br/><br/>\n";
   echo "<br/><br/>\n";

}


?>

</div>

<?php include 'footer.inc.php'; ?>
