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
   $_POST['page_name'] = T_("Forecasting");
   include 'header.inc.php';
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>


<script language="JavaScript">

  function submitTeam(){

     foundError = 0;
     msgString = "Some fields are missing:" + "\n\n";

     if (0 == document.forms["teamSelectForm"].f_teamid.value)  { msgString += "Team\n"; ++foundError; }

     if (0 != foundError) {
       alert(msgString);
     }
     document.forms["teamSelectForm"].action.value = "displayPage";
     document.forms["teamSelectForm"].submit();

   }

   // ------ JQUERY --------
   $(function() {

		$( "#dialog_CurrentDriftStats" ).dialog({	autoOpen: false, hide: "fade"	});
		$( "#dialog_CurrentDriftStats_link" ).click(function() { $( "#dialog_CurrentDriftStats" ).dialog( "open" ); return false;	});

	});

</script>

<?php
// TODO mettre dans un fichier separe pour inclure aussi dans stats

// ------ JQUERY --------
echo "<div id='dialog_CurrentDriftStats' title='".T_("Effort Deviation")."' style='display: none'>";
echo "<p><strong>".T_("Effort Deviation").":</strong><br>";
echo T_("Overflow day quantity")."<br/>".
        "- ".T_("Computed on task NOT Resolved/Closed on ").date("Y-m-d").".<br/>";
echo "<span style='color:blue'>".T_("Formula").": elapsed - EffortEstim</span></p>";
echo "<p><strong>".T_("Tasks in drift").":</strong><br>";
echo T_("Tasks for which the elapsed time is greater than the estimated effort")."<br>";
echo "<span style='color:blue'>".T_("Formula").": ".T_("drift")." > 1</span></p>";
echo "<p><strong>".T_("Tasks in time").":</strong><br>";
echo T_("Tasks resolved in time")."<br>";
echo "<span style='color:blue'>".T_("Formula").": -1 <= ".T_("drift")." <= 1</span></p>";
echo "<p><strong>".T_("Tasks ahead").":</strong><br>";
echo T_("Tasks resolved in less time than the estimated effort")."<br>";
echo "<span style='color:blue'>".T_("Formula").": ".T_("drift")." < -1</span></p>";
echo "</div>";

// ---


?>

<div id="content">

<?php

include_once "period_stats_report.class.php";
include_once "issue.class.php";
include_once "team.class.php";
include_once "time_tracking.class.php";

$logger = Logger::getLogger("forecasting");

// -----------------------------------------
function setTeamForm($originPage, $defaultSelection, $teamList) {

   // create form
   echo "<div align=center>\n";
   if (isset($_GET['w'])) {
      echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage?w=".$_GET['w']."&h=".$_GET['h']."'>\n";
   } else {
      echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage'>\n";
   }
   echo T_("Team")." :\n";
   echo "<select name='f_teamid'>\n";
   echo "<option value='0'></option>\n";

   foreach ($teamList as $tid => $tname) {

      if ($tid == $defaultSelection) {
         echo "<option selected value='".$tid."'>".$tname."</option>\n";
      } else {
         echo "<option value='".$tid."'>".$tname."</option>\n";
      }
   }
   echo "</select>\n";

   echo "<input type=button value='".T_("Update")."' onClick='javascript: submitTeam()'>\n";

   echo "<input type=hidden name=action value=noAction>\n";

   echo "</form>\n";
   echo "</div>\n";
}


// -----------------------------------------------
/**
 *
 * display Drifts for Issues that are CURRENTLY OPENED
 *
 * @param $timeTracking
 */
function displayCurrentDriftStats ($timeTracking, $isManager = false) {

   global $logger;
   global $status_new;

   echo "<h2>".T_("Effort Deviation")."&nbsp;&nbsp; <a id='dialog_CurrentDriftStats_link' href='#'><img title='help' src='../images/help_icon.gif'/></a></h2>\n";

   // ---- get Issues that are not Resolved/Closed
   $prodProjectList = $timeTracking->prodProjectList;

   if ((NULL == $prodProjectList) || (0 == count($prodProjectList))) {
      echo "<span style='color:red'>".T_("ERROR: No valid projects defined for this team")."</span><br>\n";
      $logger->error("No valid projects defined for team ".$timeTracking->team_id);
      return;
   }

   $formatedProdProjectList = implode( ', ', $prodProjectList);
   $issueList = array();

   $query = "SELECT DISTINCT id ".
               "FROM `mantis_bug_table` ".
               "WHERE status < get_project_resolved_status_threshold(project_id) ".
               "AND status > $status_new ".
               "AND project_id IN ($formatedProdProjectList) ".
               "ORDER BY id DESC";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }

   while($row = mysql_fetch_object($result)) {
      $issue = IssueCache::getInstance()->getIssue($row->id);
      $issueList[] = $issue;
   }

   if (0 != count($issueList)) {
      $driftStats_new = $timeTracking->getIssuesDriftStats($issueList);
   } else {
      $driftStats_new = array();
   }


   #echo "<span class='help_font'>\n";
   #echo T_("")."<br/>\n";
   #echo "</span>\n";
   echo "<br/>\n";

   echo "<table>\n";
   #echo "<caption>".T_("EffortDeviation - Today opened Tasks")."&nbsp;&nbsp; <a id='dialog_CurrentDriftStats_link' href='#'><img title='help' src='../images/help_icon.gif'/></a></caption>\n";
   echo "<tr>\n";
   echo "<th></th>\n";
   if (true == $isManager) {
      echo "<th width='100' title='".T_("Manager Estimation")."'>".T_("Manager")."</th>\n";
   }
   echo "<th width='100'>".T_("Value")."</th>\n";
   echo "<th>".T_("Tasks")."</th>\n";
   echo "</tr>\n";

   echo "<tr>\n";
   echo "<td title='".T_("If < 0 then ahead on planning.")."'>".T_("EffortDeviation")."</td>\n";


   if (true == $isManager) {
      $value = number_format($driftStats_new["totalDriftETA"], 2);
      $color = "";
      if ($value < 0) { $color = "style='background-color: #61ed66;'"; }
      if ($value > 0) { $color = "style='background-color: #fcbdbd;'"; }
      echo "<td title='elapsed - MgrEffortEstim' $color >".$value."</td>\n";
   }

   $value = number_format($driftStats_new["totalDrift"], 2);
   $color = "";
   if ($value < 0) { $color = "style='background-color: #61ed66;'"; }
   if ($value > 0) { $color = "style='background-color: #fcbdbd;'"; }

   echo "<td title='elapsed - EffortEstim' $color>".$value."</td>\n";
   echo "<td></td>\n";
   echo "</tr>\n";

   echo "<tr>\n";
   echo "<td>".T_("Tasks in drift")."</td>\n";
   echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsPosETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftPosETA"]).")</span></td>\n";
   if (true == $isManager) {
      echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsPos"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftPos"]).")</span></td>\n";
   }
   echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats_new["formatedBugidPosList"]."</td>\n";
   echo "</tr>\n";

   echo "<tr>\n";
   echo "<td>".T_("Tasks in time")."</td>\n";
   if (true == $isManager) {
      echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsEqualETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftEqualETA"] + $driftStatsClosed["driftEqualETA"]).")</span></td>\n";
   }
   echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsEqual"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftEqual"] + $driftStatsClosed["driftEqual"]).")</span></td>\n";
   echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats_new["formatedBugidEqualList"]."</td>\n";
   echo "</tr>\n";

   echo "<tr>\n";
   echo "<td>".T_("Tasks ahead")."</td>\n";
   if (true == $isManager) {
      echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsNegETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftNegETA"]).")</span></td>\n";
   }
   echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsNeg"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftNeg"]).")</span></td>\n";
   echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats_new["formatedBugidNegList"]."</td>\n";
   echo "</tr>\n";
   echo "</table>\n";
}



/**
 *
 */
function showIssuesInDrift($teamid, $isManager=false, $withSupport=true) {

	$mList = Team::getMemberList($teamid);
    echo "<table>\n";
    echo "<caption>".T_("Tasks in drift")."</caption>\n";
    echo "<tr>\n";
    echo "<th>".T_("ID")."</th>\n";
    echo "<th>".T_("Project")."</th>\n";

   if (true == $isManager) {
      echo "<th title='".T_("Drift relatively to the managers Estimation")."'>".T_("Drift Mgr")."</th>\n";
   }
	echo "<th title='".T_("Drift relatively to (EE + AddEE)")."'>".T_("Drift")."</th>\n";
	echo "<th>".T_("RAF")."</th>\n";
	echo "<th>".T_("Progress")."</th>\n";
	echo "<th>".T_("Status")."</th>\n";
	echo "<th>".T_("Summary")."</th>\n";
    echo "</tr>\n";

	foreach ($mList as $id => $name) {
		$user = UserCache::getInstance()->getUser($id);

		// take only developper's tasks
		if (!$user->isTeamDeveloper($teamid)) {
			continue;
		}

		$issueList = $user->getAssignedIssues();

		foreach ($issueList as $issue) {

		    // TODO: check if issue in team project list ?

			$driftPrelEE = $issue->getDriftMgrEE($withSupport);
			$driftEE = $issue->getDrift($withSupport);
		    if (($driftPrelEE > 1) || ($driftEE > 1)) {
		           echo "<tr>\n";
		   		   echo "<td>".issueInfoURL($issue->bugId)."</td>\n";
		   		   echo "<td>".$issue->getProjectName()."</td>\n";
                  if (true == $isManager) {
                     $color = "";
                     if ($driftPrelEE < -1) { $color = "style='background-color: #61ed66;'"; }
                     if ($driftPrelEE > 1) { $color = "style='background-color: #fcbdbd;'"; }
		   		      echo "<td $color >".$driftPrelEE."</td>\n";
                  }
                  $color = "";
                  if ($driftEE <= -1) { $color = "style='background-color: #61ed66;'"; }
                  if ($driftEE >= 1) { $color = "style='background-color: #fcbdbd;'"; }
		   		   echo "<td $color >".$driftEE."</td>\n";
		   		   echo "<td>".$issue->getRemaining()."</td>\n";
                  echo "<td>".round(100 * $issue->getProgress())."%</td>\n";
		   		   echo "<td>".$issue->getCurrentStatusName()."</td>\n";
		   		   echo "<td>".$issue->summary."</td>\n";
		           echo "</tr>\n";
		    }
		}
	}
    echo "</table>\n";


}



/**
*
* TODO factorize: this function also exists in statistics.php
*
* Display 'Available Workload'
* nb of days.: (holidays & externalTasks not included, developers only)
*
* @param unknown_type $timeTrackingTable
* @param unknown_type $width
* @param unknown_type $height
*/
function displayAvailableWorkloadGraph ($timeTrackingTable, $width, $height) {

   $start_day = 1;
   $now = time();

   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {

      $workload = $timeTracking->getAvailableWorkload();
      $val1[] = $workload;
      $bottomLabel[] = date("M y", $startTimestamp);

      #$logger->debug("workload=$workload date=".date('M y', $startTimestamp));
   }
   $graph_title="title=".("Available Workload");
   $graph_width="width=$width";
   $graph_height="height=$height";

   $strVal1 = "leg1=man-days&x1=".implode(':', $val1);
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);

   echo "<div>\n";
   echo "<h2>".T_("Available Workload")."</h2>\n";

   echo "<span class='help_font'>\n";
   echo T_("man-day").": ".T_("Nombre de jours-homme disponibles sur la periode (hors vacances et taches externes)")."<br/>\n";
   echo "</span>\n";
   echo "<br/>\n";

   echo "<div class=\"float\">\n";
   $graphURL = getServerRootURL()."/graphs/two_lines.php?displayPointLabels&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1";
   $graphURL = SmartUrlEncode($graphURL);
   echo "    <img src='$graphURL'/>";
   echo "</div>\n";
   echo "<div class=\"float\">\n";
   echo "<table>\n";
   echo "<caption title='".("Available Workload")."'</caption>";
   echo "<tr>\n";
   echo "<th>Date</th>\n";
   echo "<th title='".T_("nb production days")."'>".T_("man-day")."</th>\n";
   echo "</tr>\n";
   $i = 0;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      echo "<tr>\n";
      echo "<td class=\"right\">".date("F Y", $startTimestamp)."</td>\n";
      echo "<td class=\"right\">".number_format($val1[$i], 1)."</td>\n";
      echo "</tr>\n";
      $i++;
   }
   echo "</table>\n";
   echo "</div>\n";
   echo "</div>\n";

}

/**
 *
 * @param unknown_type $start_day
 * @param unknown_type $start_month
 * @param unknown_type $start_year
 * @param unknown_type $teamid
 */
function createTimeTrackingList($start_day, $start_month, $start_year, $teamid) {

   $now = time();
   $timeTrackingTable = array();

   $day = $start_day;

   for ($y = $start_year; $y <= date('Y'); $y++) {

      for ($month=$start_month; $month<13; $month++) {

         $startTimestamp = mktime(0, 0, 0, $month, $day, $y);
         $nbDaysInMonth = date("t", mktime(0, 0, 0, $month, 1, $y));
         $endTimestamp   = mktime(23, 59, 59, $month, $nbDaysInMonth, $y);

         #echo "DEBUG createTimeTrackingList: startTimestamp=".date("Y-m-d H:i:s", $startTimestamp)." endTimestamp=".date("Y-m-d H:i:s", $endTimestamp)." nbDays = $nbDaysInMonth<br/>";

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);
         $timeTrackingTable[$startTimestamp] = $timeTracking;

         $day   = 1;
      }
      $start_month = 1;
   }
   return $timeTrackingTable;
}




// =========== MAIN ==========


$year = date('Y');
/*
$defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
$teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
$_SESSION['teamid'] = $teamid;
*/
// use the teamid set in the form, if not defined (first page call) use session teamid
if (isset($_POST['f_teamid'])) {
   $teamid = $_POST['f_teamid'];
   $_SESSION['teamid'] = $teamid;
} else {
   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
}


$withSupport = true;

$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

$action = isset($_POST['action']) ? $_POST['action'] : '';

$mTeamList = $session_user->getTeamList();
$lTeamList = $session_user->getLeadedTeamList();
$oTeamList = $session_user->getObservedTeamList();
$managedTeamList = $session_user->getManagedTeamList();
$teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;


if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
   echo T_("Sorry, you need to be member of a Team to access this page.");
   echo "</div>";

} else {

   setTeamForm("forecasting_report.php", $teamid, $teamList);

   if ("displayPage" == $action) {


      $weekDates = week_dates(date('W'),$year);
      $date1  = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : date("Y-m-d", $weekDates[1]);
      $date2  = isset($_REQUEST["date2"]) ? $_REQUEST["date2"] : date("Y-m-d", $weekDates[5]);
      $startTimestamp = date2timestamp($date1);
      $endTimestamp = date2timestamp($date2);
      $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

      #echo "DEBUG startTimestamp ".date("Y-m-d H:i:s", $startTimestamp)."<br/>";
      #echo "DEBUG endTimestamp   ".date("Y-m-d H:i:s", $endTimestamp)."<br/>";

      $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

      #setInfoForm($teamid, $teamList, $date1, $date2, $defaultProjectid);
      echo "<br/><br/>\n";


      if (0 != $teamid) {

         $isManager = array_key_exists($teamid, $managedTeamList);

         echo "<br/><br/><hr>\n";
         displayCurrentDriftStats($timeTracking, $isManager);

         echo "<br/><br/>\n";
         showIssuesInDrift($teamid, $isManager, $withSupport);

         // ----
         echo "<br/><br/>\n";
         echo "<br/><br/>\n";
         echo "<br/><br/><hr>\n";
         $start_day = 1;
         $start_month = date("m");
         $start_year = date("Y");
         $timeTrackingTable = createTimeTrackingList($start_day, $start_month, $start_year, $teamid);
         displayAvailableWorkloadGraph($timeTrackingTable, 800, 300);

         flush();
         echo "<div class=\"spacer\"> </div>\n";

         echo "<br/><br/>\n";

      } // if teamid
   } // if action
} // else teamList
?>

</div>

<?php include 'footer.inc.php'; ?>

