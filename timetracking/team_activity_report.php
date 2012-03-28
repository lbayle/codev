<?php 
if (!isset($_SESSION)) { 
	$tokens = explode('/', $_SERVER['PHP_SELF'], 3);
	$sname = str_replace('.', '_', $tokens[1]);
	session_name($sname); 
	session_start(); 
	header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); 
} 
?>
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
    document.forms["form1"].is_modified.value= "true";
    document.forms["form1"].submit();
  }

  function previousWeek() {
     document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;

     weekid = document.getElementById('weekidSelector').value;
     year   = document.getElementById('yearSelector').value;

     if (1 != weekid) {
       document.forms["form1"].weekid.value = --weekid;
       document.forms["form1"].year.value = year;
     }

     document.forms["form1"].action.value="updateWeekDisplay";
     document.forms["form1"].submit();
   }

  function nextWeek() {
     document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;

     weekid = document.getElementById('weekidSelector').value;
     year   = document.getElementById('yearSelector').value;

     if (weekid <= 52) {
       document.forms["form1"].weekid.value = ++weekid;
       document.forms["form1"].year.value = year;
     } else {
        document.forms["form1"].weekid.value = 1;
        document.forms["form1"].year.value = ++year;
     }

     document.forms["form1"].action.value="updateWeekDisplay";
     document.forms["form1"].submit();
  }


   // ------ JQUERY ------
	$(function() {
		$( "#accordion" ).accordion({
			collapsible: true
		});
	});


</script>

<div id="content" class="center">

<?php

include_once "issue.class.php";
include_once "project.class.php";
include_once "user.class.php";
include_once "time_tracking.class.php";

$logger = Logger::getLogger("team_activity");

// ------------------------------------------------
function displayTeamAndWeekSelectionForm($leadedTeamList, $teamid, $weekid, $curYear=NULL, $isDetailed = false, $is_modified = "false") {

  if (NULL == $curYear) { $curYear = date('Y'); }

  echo "<div>\n";
  echo "<form id='form1' name='form1' method='post' action='team_activity_report.php'>\n";

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
  echo T_("Week")." \n";
  echo "<input type=button title='".T_("Previous week")."' value='<<' onClick='javascript: previousWeek()'>\n";
  echo "<select id='weekidSelector' name='weekidSelector' onchange='javascript: submitForm()'>\n";
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
  echo "<input type=button title='".T_("Next week")."' value='>>' onClick='javascript: nextWeek()'>\n";

  $isChecked = $isDetailed ? "CHECKED" : "";
  echo "&nbsp;<input type=CHECKBOX  $isChecked name='cb_detailed' id='cb_detailed' onChange='javascript: submitForm()'>".T_("Detailed")."</input>\n";


  echo "<input type=hidden name=teamid  value=1>\n";
  echo "<input type=hidden name=weekid  value=".date('W').">\n";
  echo "<input type=hidden name=year    value=$curYear>\n";

  echo "<input type=hidden name=action       value=noAction>\n";
  echo "<input type=hidden name=is_modified value=$is_modified>\n";
  echo "<input type=hidden name=currentForm  value=weekActivityReport>\n";
  echo "<input type=hidden name=nextForm     value=weekActivityReport>\n";
  echo "</form>\n";
  echo "</div>\n";
}


// ------------------------------------------------
function displayWeekActivityReport($teamid, $weekid, $weekDates, $timeTracking, $isDetailed = false) {

  global $logger;

  echo "<div>\n";

  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.realname ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $teamid ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.realname";

  $result = mysql_query($query);
  if (!$result) {
     $logger->error("Query FAILED: $query");
     $logger->error(mysql_error());
     echo "<span style='color:red'>ERROR: Query FAILED</span>";
     exit;
  }

  while($row = mysql_fetch_object($result))
  {
  	// if user was working on the project during the timestamp
  	$user = UserCache::getInstance()->getUser($row->user_id);
  	if (($user->isTeamDeveloper($teamid, $timeTracking->startTimestamp, $timeTracking->endTimestamp)) ||
       ($user->isTeamManager($teamid, $timeTracking->startTimestamp, $timeTracking->endTimestamp))) {

	    echo "<div align='left'>\n";
	    echo "<br/>";
      echo "<br/>";
      if ($isDetailed) {
	       displayWeekDetails($weekid, $weekDates, $row->user_id, $timeTracking, $row->realname, $user->getWorkload());
	    } else {
	       displayWeek($weekid, $weekDates, $row->user_id, $timeTracking, $row->realname, $user->getWorkload());
	    }
	    echo "</div>";
  	}

  }

  echo "</div>\n";

}

// ------------------------------------------------
function displayWeekDetails($weekid, $weekDates, $userid, $timeTracking, $realname, $workload) {

  global $logger;

  // PERIOD week
  //$thisWeekId=date("W");

  $weekTracks = $timeTracking->getWeekDetails($userid, true);
  echo "<span class='caption_font'>$realname</span> &nbsp;&nbsp;&nbsp; <span title='".T_("sum(Remaining) of current tasks")."'>".T_("workload")." = $workload</span><br/>\n";
  echo "<table width='100%'>\n";
  //echo "<caption>".$realname."</caption>\n";
  echo "<tr>\n";
  echo "<th width='50%'>".T_("Task")."</th>\n";
  echo "<th width='1%'>".T_("RAF")."</th>\n";
  echo "<th width='1%'>".T_("Progress")."</th>\n";
  echo "<th width='7%'>".T_("Project")."</th>\n";
  echo "<th width='5' title='".T_("Target version")."'>".T_("Target")."</th>\n";
  echo "<th width='10%'>".T_("Job")."</th>\n";
  echo "<th width='10'>".T_("Monday")."<br>".date("d M", $weekDates[1])."</th>\n";
  echo "<th width='10'>".T_("Tuesday")."<br/>".date("d M", $weekDates[2])."</th>\n";
  echo "<th width='10'>".T_("Wednesday")."<br/>".date("d M", $weekDates[3])."</th>\n";
  echo "<th width='10'>".T_("Thursday")."<br/>".date("d M", $weekDates[4])."</th>\n";
  echo "<th width='10'>".T_("Friday")."<br/>".date("d M", $weekDates[5])."</th>\n";
  echo "<th width='10' style='background-color: #D8D8D8;' >".T_("Saturday")."<br/>".date("d M", $weekDates[6])."</th>\n";
  echo "<th width='10' style='background-color: #D8D8D8;' >".T_("Sunday")."<br/>".date("d M", $weekDates[7])."</th>\n";
  echo "</tr>\n";
  foreach ($weekTracks as $bugid => $jobList) {
    $issue = IssueCache::getInstance()->getIssue($bugid);
    foreach ($jobList as $jobid => $dayList) {

      $query  = "SELECT name FROM `codev_job_table` WHERE id=$jobid";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $jobName = mysql_result($result, 0);

      echo "<tr>\n";
      echo "<td>".issueInfoURL($bugid)." / ".$issue->tcId." : ".$issue->summary."</td>\n";
      echo "<td>".$issue->getDuration()."</td>\n";
      echo "<td>".round(100 * $issue->getProgress())."%</td>\n";
      echo "<td>".$issue->getProjectName()."</td>\n";
      echo "<td>".$issue->getTargetVersion()."</td>\n";
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
  echo "</table>\n";
}


// ------------------------------------------------
function displayWeek($weekid, $weekDates, $userid, $timeTracking, $realname, $workload) {
  // PERIOD week
  //$thisWeekId=date("W");

  $weekTracks = $timeTracking->getWeekDetails($userid, true);
  echo "<span class='caption_font'>$realname</span> &nbsp;&nbsp;&nbsp; <span title='".T_("sum(Remaining) of current tasks")."'>".T_("workload")." = $workload</span><br/>\n";
  echo "<table width='100%'>\n";
  //echo "<caption>".$realname."</caption>\n";
  echo "<tr>\n";
  echo "<th width='50%'>".T_("Task")."</th>\n";
  echo "<th width='1%'>".T_("RAF")."</th>\n";
  echo "<th width='1%'>".T_("Progress")."</th>\n";
  echo "<th width='7%'>".T_("Project")."</th>\n";
  echo "<th width='5' title='".T_("Target version")."'>".T_("Target")."</th>\n";
  echo "<th width='10'>".T_("Monday")."<br>".date("d M", $weekDates[1])."</th>\n";
  echo "<th width='10'>".T_("Tuesday")."<br/>".date("d M", $weekDates[2])."</th>\n";
  echo "<th width='10'>".T_("Wednesday")."<br/>".date("d M", $weekDates[3])."</th>\n";
  echo "<th width='10'>".T_("Thursday")."<br/>".date("d M", $weekDates[4])."</th>\n";
  echo "<th width='10'>".T_("Friday")."<br/>".date("d M", $weekDates[5])."</th>\n";
  echo "<th width='10' style='background-color: #D8D8D8;' >".T_("Saturday")."<br/>".date("d M", $weekDates[6])."</th>\n";
  echo "<th width='10' style='background-color: #D8D8D8;' >".T_("Sunday")."<br/>".date("d M", $weekDates[7])."</th>\n";
  echo "</tr>\n";
  foreach ($weekTracks as $bugid => $jobList) {
    $issue = IssueCache::getInstance()->getIssue($bugid);

    echo "<tr>\n";
    echo "<td>".issueInfoURL($bugid)." / ".$issue->tcId." : ".$issue->summary."</td>\n";
    echo "<td>".$issue->getDuration()."</td>\n";
    echo "<td>".round(100 * $issue->getProgress())."%</td>\n";
    echo "<td>".$issue->getProjectName()."</td>\n";
    echo "<td>".$issue->getTargetVersion()."</td>\n";


    // for each day, concat jobs duration
    for ($i = 1; $i <= 7; $i++) {
       $duration = 0;
       foreach ($jobList as $jobid => $dayList) {
          $duration += $dayList[$i];
       }
       if (0 == $duration) { $duration = ""; }
       if ($i < 6) {
          echo "<td>".$duration."</td>\n";
       } else {
          echo "<td style='background-color: #D8D8D8;' >".$duration."</td>\n";
       }
    }
    echo "</tr>\n";
  }
  echo "</table>\n";
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

  echo "<div align='center'>";
  echo "<div id='accordion' style='width:350px;' >\n";
  echo "<h3><a href='#'>".T_("Dates manquantes")."</a></h3>\n";

  echo "<p style='color:red'>\n";

  while($row = mysql_fetch_object($result))
  {
    $incompleteDays = $timeTracking->checkCompleteDays($row->user_id, TRUE);
    foreach ($incompleteDays as $date => $value) {

      if ($date > time()) { continue; } // skip dates in the future

      $formatedDate = date("Y-m-d", $date);
      if ($value < 1) {
        echo "<br/>$row->username: $formatedDate ".T_("incomplete (missing ").(1-$value).T_(" days").").\n";
      } else {
        echo "<br/>$row->username: $formatedDate ".T_("inconsistent")." (".($value)." ".T_("days").").\n";
      }
    }

    $missingDays = $timeTracking->checkMissingDays($row->user_id);
    foreach ($missingDays as $date) {
    	if ($date > time()) { continue; } // skip dates in the future

      $formatedDate = date("Y-m-d", $date);
      echo "<br/>$row->username: $formatedDate ".T_("not defined.")."\n";
    }
  }
  echo "</p>\n";
  echo "</div>\n";
  echo "</div>\n";
}


// ================ MAIN =================
$year = isset($_POST['year']) ? $_POST['year'] : date('Y');

$userid = $_SESSION['userid'];

// use the teamid set in the form, if not defined (first page call) use session teamid
if (isset($_POST['teamid'])) {
   $teamid = $_POST['teamid'];
   $_SESSION['teamid'] = $teamid;
} else {
   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
}

// 'is_modified' is used because it's not possible to make a difference
// between an unchecked checkBox and an unset checkbox variable
$is_modified = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";
if ("false" == $is_modified) {
   $isDetailed = false;
} else {
   $isDetailed   = isset($_POST['cb_detailed']) ? true : false;
}

// ------

$user = UserCache::getInstance()->getUser($userid);
$mTeamList = $user->getDevTeamList();    // are team members allowed to see other member's timeTracking ?
$lTeamList = $user->getLeadedTeamList();
$oTeamList = $user->getObservedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;

if (0 == count($teamList)) {
	echo T_("Sorry, you do NOT have access to this page.");

} else {

	$action = isset($_POST['action']) ? $_POST['action'] : '';
	$weekid = isset($_POST['weekid']) ? $_POST['weekid'] : date('W');

   echo "<div class='center'>";
   echo "<h2>".T_("Weekly Activity")."</h2><br/>";
   echo "</div>";

   displayTeamAndWeekSelectionForm($teamList, $teamid, $weekid, $year, $isDetailed, $is_modified);

	if (isset($teamList["$teamid"]) && (NULL != $teamList["$teamid"])) {

	   $weekDates      = week_dates($weekid,$year);
		$startTimestamp = $weekDates[1];
	   $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
	   $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

      echo "<br/><br/>\n";
	   displayWeekActivityReport($teamid, $weekid, $weekDates, $timeTracking, $isDetailed);

      echo "<br/><br/>\n";
      displayCheckWarnings($timeTracking);

	}
}


?>

</div>

<?php include 'footer.inc.php'; ?>
