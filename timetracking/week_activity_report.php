<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include_once '../path.inc.php'; ?>

<?php
   include_once 'i18n.inc.php';
   if (!isset($_SESSION['userid'])) {
      echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
      exit;
   }
?>

<?php
   $_POST[page_name] = T_("Weekly activities");
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>


<script language="JavaScript">
  function submitForm() {
  // TODO: check teamid presence
    document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
    document.forms["form1"].weekid.value = document.getElementById('weekidSelector').value;
    document.forms["form1"].year.value   = document.getElementById('yearSelector').value;
    document.forms["form1"].action.value = "updateWeekDisplay";
    document.forms["form1"].submit();
  }
</script>

<div id="content" class="center">

<?php

include_once "constants.php";
include_once "tools.php";
include_once "issue.class.php";
include_once "project.class.php";
include_once "user.class.php";
include_once "time_tracking.class.php";

// ------------------------------------------------
function displayTeamAndWeekSelectionForm($leadedTeamList, $teamid, $weekid, $curYear=NULL) {

  if (NULL == $curYear) { $curYear = date('Y'); }

  echo "<div>\n";
  echo "<form id='form1' name='form1' method='post' action='week_activity_report.php'>\n";

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


// ------------------------------------------------
function displayWeekActivityReport($teamid, $weekid, $weekDates, $timeTracking) {

  echo "<div>\n";

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

	    echo "<div align='left'>\n";
	    echo "<br/>";
       echo "<br/>";
	    displayWeekDetails($weekid, $weekDates, $row->user_id, $timeTracking, $row->realname, $user->getWorkload());
	    echo "</div>";
  	}

  }

  echo "</div>\n";

}

// ------------------------------------------------
function displayWeekDetails($weekid, $weekDates, $userid, $timeTracking, $realname, $workload) {
  // PERIOD week
  //$thisWeekId=date("W");

  $weekTracks = $timeTracking->getWeekDetails($userid, true);
  echo "<span class='caption_font'>$realname</span> &nbsp;&nbsp;&nbsp; <span title='".T_("sum(Remaining) of current tasks")."'>".T_("workload")." = $workload</span><br/>\n";
  echo "<table width='95%'>\n";
  //echo "<caption>".$realname."</caption>\n";
  echo "<tr>\n";
  echo "<th width='50%'>".T_("Task")."</th>\n";
  echo "<th width='7%'>".T_("Project")."</th>\n";
  echo "<th width='10%'>".T_("Job")."</th>\n";
  echo "<th width='10'>".T_("Monday")."<br>".date("d M", $weekDates[1])."</th>\n";
  echo "<th width='10'>".T_("Tuesday")."<br/>".date("d M", $weekDates[2])."</th>\n";
  echo "<th width='10'>".T_("Wednesday")."<br/>".date("d M", $weekDates[3])."</th>\n";
  echo "<th width='10'>".T_("Thursday")."<br/>".date("d M", $weekDates[4])."</th>\n";
  echo "<th width='10'>".T_("Friday")."<br/>".date("d M", $weekDates[5])."</th>\n";
  echo "</tr>\n";
  foreach ($weekTracks as $bugid => $jobList) {
    $issue = IssueCache::getInstance()->getIssue($bugid);
    foreach ($jobList as $jobid => $dayList) {

      $query3  = "SELECT name FROM `codev_job_table` WHERE id=$jobid";
      $result3 = mysql_query($query3) or die("Query failed: $query3");
      $jobName = mysql_result($result3, 0);

      echo "<tr>\n";
      echo "<td>".issueInfoURL($bugid)." / ".$issue->tcId." : ".$issue->summary."</td>\n";
      echo "<td>".$issue->getProjectName()."</td>\n";
      echo "<td>".$jobName."</td>\n";
      for ($i = 1; $i <= 5; $i++) {
        echo "<td>".$dayList[$i]."</td>\n";
      }
      echo "</tr>\n";
    }
  }
  echo "</table>\n";
}


// ------------------------------------------------
function displayProjectActivityReport($timeTracking) {

   // $projectTracks[projectid][bugid][jobid] = duration
   $projectTracks = $timeTracking->getProjectTracks(true);

  echo "<div align='center'>\n";

  foreach ($projectTracks as $projectId => $bugList) {

     // write table header
     $project = new Project($projectId);
     echo "<br/>\n";
     echo "<table width='95%'>\n";
     echo "<caption>".$project->name."</caption>\n";
     echo "<tr>\n";
     echo "  <th width='50%'>".T_("Task")."</th>\n";
     echo "  <th width='2%'>".T_("RAE")."</th>\n";

     $jobList = $project->getJobList();
     foreach($jobList as $jobId => $jobName) {
         echo "  <th>$jobName</th>\n";
     }
     echo "  <th width='2%' title='".T_("Total time spent on this issue")."'>".T_("Total")."</th>\n";
     echo "</tr>\n";

     // write table content (by bugid)
     foreach ($bugList as $bugid => $jobs) {
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $totalTime = 0;
         echo "<tr>\n";
         echo "<td>".issueInfoURL($bugid)." / ".$issue->tcId." : ".$issue->summary."</td>\n";
         echo "<td>".$issue->remaining."</td>\n";

         foreach($jobList as $jobId => $jobName) {
            echo "<td width='10%'>".$jobs[$jobId]."</td>\n";
            $totalTime += $jobs[$jobId];
         }
         echo "<td>".$totalTime."</td>\n";
         echo "</tr>\n";
     }
     echo "</table>\n";
  }
  echo "</div>\n";
}



function displayCheckWarnings($timeTracking) {
  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $timeTracking->team_id ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";

  // FIXME AND user is not Observer
  
  $result = mysql_query($query) or die("Query failed: $query");

  echo "<p style='color:red'>\n";

  while($row = mysql_fetch_object($result))
  {
    $incompleteDays = $timeTracking->checkCompleteDays($row->user_id, TRUE);
    foreach ($incompleteDays as $date => $value) {
      $formatedDate = date("Y-m-d", $date);
      echo "<br/>$row->username: $formatedDate ".T_("incomplete (missing ").(1-$value).T_(" days").").\n";
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
$year = isset($_POST[year]) ? $_POST[year] : date('Y');

$userid = $_SESSION['userid'];

// use the teamid set in the form, if not defined (first page call) use session teamid
if (isset($_POST[teamid])) {
   $teamid = $_POST[teamid];
   $_SESSION['teamid'] = $teamid;
} else {
   $teamid = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
}

// ---------
$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass)
  or die(T_("Could not connect to database"));
mysql_select_db($db_mantis_database) or die("Could not select database");


// ------

$user = UserCache::getInstance()->getUser($userid);
$mTeamList = $user->getTeamList();    // are team members allowed to see other member's timeTracking ?
$lTeamList = $user->getLeadedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $mTeamList + $lTeamList + $managedTeamList;

if (0 == count($teamList)) {
	echo T_("Sorry, you do NOT have access to this page.");

} else {

	$action = $_POST[action];
	$weekid = isset($_POST[weekid]) ? $_POST[weekid] : date('W');

	displayTeamAndWeekSelectionForm($teamList, $teamid, $weekid, $year);

	if (NULL != $teamList[$teamid]) {

	   $weekDates      = week_dates($weekid,$year);
		$startTimestamp = $weekDates[1];
	   $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[5]), date("d", $weekDates[5]), date("Y", $weekDates[5]));
	   $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

      echo "<div align='left'>\n";
	   echo "<ul>\n";
      echo "   <li><a href='#tagTeamMember'>".T_("By Team Member")."</a></li>\n";
      echo "   <li><a href='#tagProject'>".T_("By Project")."</a></li>\n";
	   echo "</ul><br/>\n";
      echo "</div>\n";


      echo "<br/>\n";
	   echo "<hr width='95%'/>\n";
	   echo "<a name='tagTeamMember'><h2>".T_("By Team Member")."</h2></a>\n";
		displayWeekActivityReport($teamid, $weekid, $weekDates, $timeTracking);

      echo "<br/><br/>\n";
      displayCheckWarnings($timeTracking);

      echo "<br/><br/>\n";
		//echo "<hr align='left' width='50%'/>\n";
      echo "<hr width='95%'/>\n";
      echo "<a name='tagProject'><h2>".T_("By Project")."</h2></a>\n";
      displayProjectActivityReport($timeTracking);

	}
}


?>

</div>

<?php include 'footer.inc.php'; ?>
