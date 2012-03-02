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
   $_POST['page_name'] = T_("Gantt Chart");
   include 'header.inc.php';
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

<script language="JavaScript">
  function submitForm() {

    document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
    document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
    document.forms["form1"].action.value = "ganttReport";
    document.forms["form1"].submit();
 }


  function teamChanged() {
     document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
     document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
     document.forms["form1"].action.value = "teamChanged";
     document.forms["form1"].submit();
  }

  function projectChanged() {
     document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
     document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
     document.forms["form1"].action.value = "projectChanged";
     document.forms["form1"].submit();
  }
  
</script>

<div id="content">

<?php
require_once ('jpgraph.php');
require_once ('jpgraph_gantt.php');

include_once "issue.class.php";
include_once "user.class.php";
include_once "team.class.php";
include_once "time_tracking.class.php";
include_once "gantt_manager.class.php";
require_once('tc_calendar.php');

// -----------------------------------------------
function setInfoForm($teamid, $teamList, $projectid, $projectList, $defaultDate1, $defaultDate2, $originPage) {
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
  if (isset($_GET['debug'])) {
      echo "<form id='form1' name='form1' method='post' action='$originPage?debug'>\n";
  } else {
  	   echo "<form id='form1' name='form1' method='post' action='$originPage'>\n";
  }

  echo T_("Team").": <select id='teamidSelector' name='teamidSelector' onchange='teamChanged()'>\n";
    echo "<option value='0'></option>\n";

  foreach($teamList as $tid => $tname) {
    if ($tid == $teamid) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";
  echo "&nbsp;";

  echo T_("Project").": <select id='projectidSelector' name='projectidSelector'>\n";
  foreach($projectList as $pid => $pname) {
    if ($pid == $projectid) {
      echo "<option selected value='".$pid."'>".$pname."</option>\n";
    } else {
      echo "<option value='".$pid."'>".$pname."</option>\n";
    }
  }
  echo "</select>\n";
  
  echo "&nbsp;".T_("Start Date").": "; $myCalendar1->writeScript();

  echo "&nbsp; <span title='".T_("(included)")."'>".T_("End Date").": </span>"; $myCalendar2->writeScript();

  echo "&nbsp;<input type=button value='".T_("Compute")."' onClick='javascript: submitForm()'>\n";

  echo "<input type=hidden name=teamid  value=$teamid>\n";
  echo "<input type=hidden name=projectid  value=$projectid>\n";
  echo "<input type=hidden name=action  value=noAction>\n";

  echo "</form>\n";
  echo "</div>";
}




# ============= MAIN =================

$originPage = "gantt_report.php";

$userid = $_SESSION['userid'];

$defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
$defaultProject = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : 0;
$teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
$projectid = isset($_POST['projectid']) ? $_POST['projectid'] : $defaultProject;
$_SESSION['teamid'] = $teamid;
$_SESSION['projectid'] = $projectid;

$session_user = UserCache::getInstance()->getUser($userid);
$mTeamList = $session_user->getDevTeamList();
$lTeamList = $session_user->getLeadedTeamList();
$oTeamList = $session_user->getObservedTeamList();
$managedTeamList = $session_user->getManagedTeamList();
$teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;

/*
foreach ($teamList as $tid => $tname) {
   $projectList = array_merge ($projectList , Team::getProjectList($tid, false) );
}
*/
$projectList = array();
$projectList[0] = "All projects";
$projectList += Team::getProjectList($teamid, false);

if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
   echo T_("Sorry, you do NOT have access to this page.");
   echo "</div>";

} else {

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	$weekDates      = week_dates(date('W'),date('Y'));

   $defaultDate1 = mktime(0, 0, 0, date("m", $weekDates[1]), date("d", $weekDates[1]), date("Y", $weekDates[1]));
   $defaultDate2 = mktime(0, 0, 0, date("m"), date("d"), date("Y")+1);

   if ($action == "teamChanged") {
   	$date1 = date("Y-m-d", $defaultDate1);
   	$date2 = date("Y-m-d", $defaultDate2);
   } else {
      $date1  = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : date("Y-m-d", $defaultDate1);
      $date2  = isset($_REQUEST["date2"]) ? $_REQUEST["date2"] : date("Y-m-d", $defaultDate2);
   }

   $startT = date2timestamp($date1);
   $endT   = date2timestamp($date2);
	#$endT += 24 * 60 * 60 -1; // + 1 day -1 sec.

	// -----

	setInfoForm($teamid, $teamList, $projectid, $projectList, $date1, $date2, $originPage);
	echo "<br/><br/>\n";
	echo "<br/><br/>\n";

   if ("ganttReport" == $action) {

	   if (0 != $teamid) {

         // draw graph
         if (0 != $projectid) {
            $graphURL = getServerRootURL()."/graphs/gantt_graph.php?teamid=$teamid&projects=$projectid&startT=$startT&endT=$endT";
         } else {
            // if no projects specified, then display all projects
            $graphURL = getServerRootURL()."/graphs/gantt_graph.php?teamid=$teamid&startT=$startT&endT=$endT";
         }
         $graphURL = SmartUrlEncode($graphURL);
         echo "<img src='$graphURL'/>";
	   } else {
		   echo "DEBUG teamid 0 <br/>";
	   }
   }
}


   echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";

?>

</div>

<?php include 'footer.inc.php'; ?>

