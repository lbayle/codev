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
   $_POST['page_name'] = T_("Period Statistics");
   include 'header.inc.php';
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_statistics.inc.php'; ?>


<script language="JavaScript">

  function submitForm() {
    document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
    document.forms["form1"].action.value = "timeTrackingReport";
    document.forms["form1"].submit();
  }

  function setProjectid() {
     document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
     document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
     document.forms["form1"].action.value="setProjectid";
     document.forms["form1"].submit();
  }

  // ------ JQUERY --------
  $(function() {
		// function displayRates
		$( "#dialog_AvailWorkload" ).dialog({	autoOpen: false, hide: "fade"	});
		$( "#dialog_AvailWorkload_link" ).click(function() { $( "#dialog_AvailWorkload" ).dialog( "open" ); return false;	});

		$( "#dialog_ProdDays" ).dialog({	autoOpen: false, hide: "fade"	});
		$( "#dialog_ProdDays_link" ).click(function() { $( "#dialog_ProdDays" ).dialog( "open" ); return false;	});

		$( "#dialog_EfficiencyRate" ).dialog({	autoOpen: false, hide: "fade"	});
		$( "#dialog_EfficiencyRate_link" ).click(function() { $( "#dialog_EfficiencyRate" ).dialog( "open" ); return false;	});

		$( "#dialog_SystemAvailability" ).dialog({	autoOpen: false, hide: "fade"	});
		$( "#dialog_SystemAvailability_link" ).click(function() { $( "#dialog_SystemAvailability" ).dialog( "open" ); return false;	});

		$( "#dialog_ResolvedDriftStats" ).dialog({	autoOpen: false, hide: "fade"	});
		$( "#dialog_ResolvedDriftStats_link" ).click(function() { $( "#dialog_ResolvedDriftStats" ).dialog( "open" ); return false;	});

	});

</script>


<?php
// TODO mettre dans un fichier separe pour inclure aussi dans stats
  // ------ JQUERY --------
echo "<div id='dialog_AvailWorkload' title='".T_("Available Workload")."' style='display: none'>";
echo "<p>".T_("Workload Forecasting (holidays & externalTasks not included, developpers only)")."</p>";
echo "</div>";

// ---
echo "<div id='dialog_EfficiencyRate' title='".T_("Efficiency Rate")."' style='display: none'>";
echo "<p>".T_("Development workload (developpers only)")."</p>";
echo "<p><strong>".T_("Formula").":</strong><br>";
echo "ProjProdDays / TotalProdDays * 100</p>";
echo "</div>";

// ---
echo "<div id='dialog_SystemAvailability' title='".T_("System Availability")."' style='display: none'>";
echo "<p>".T_("Platform Availability")."</p>";
echo "<p><strong>".T_("Formula").":</strong><br>";
echo "100 - ((breakdownDays / prodDays)*100)</p>";
echo "</div>";

// ---
echo "<div id='dialog_ProdDays' title='".T_("Production days")."' style='display: none'>";
echo "<p><strong>".T_("Projects")."</strong><br>";
echo T_("Days spent on projects");
echo "</p>";
echo "<p><strong>".T_("SideTasks: Project Management")."</strong><br>";
echo T_("Days spent on project management tasks")."<br>";
echo "</p>";
echo "<p><strong>".T_("SideTasks: Others")."</strong><br>";
echo T_("Days spent on sideTasks, except project management and inactivity tasks")."<br>";
echo "</p>";
echo "<p><strong>".T_("Production Days : total")."</strong><br>";
echo T_("number of days billed")."<br>";
echo "<span style='color:blue'>".T_("Formula").": projects + sideTasks</span>";
echo "</p>";
echo "</div>";

// ---
echo "<div id='dialog_ResolvedDriftStats' title='".T_("Effort Deviation")."' style='display: none'>";
echo "<p><strong>".T_("Effort Deviation").":</strong><br>";
echo T_("Overflow day quantity")."<br/>".
            "- ".T_("Computed on task Resolved/Closed in the given period")."<br/>".
            "- ".T_("Reopened tasks are not taken into account")."<br/>\n".
            "- ".T_("If < 0 then ahead on planning.")."<br>";
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

include_once "period_stats.class.php";
include_once "team.class.php";
include_once "project.class.php";
include_once "jobs.class.php";

include_once "time_tracking.class.php";
require_once('tc_calendar.php');

$logger = Logger::getLogger("productivity_report");

// -----------------------------------------------
function setInfoForm($teamid, $teamList, $defaultDate1, $defaultDate2, $defaultProjectid) {
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
      echo "<form id='form1' name='form1' method='post' action='productivity_report.php?debug'>\n";
  } else {
  	   echo "<form id='form1' name='form1' method='post' action='productivity_report.php'>\n";
  }

  echo T_("Team").": <select id='teamidSelector' name='teamidSelector'>\n";

  foreach($teamList as $tid => $tname) {
    if ($tid == $teamid) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";

  echo "&nbsp;".T_("Start Date").": "; $myCalendar1->writeScript();

  echo "&nbsp; <span title='".T_("(included)")."'>".T_("End Date").": </span>"; $myCalendar2->writeScript();

  echo "&nbsp;<input type=button value='".T_("Compute")."' onClick='javascript: submitForm()'>\n";

  echo "<input type=hidden name=teamid  value=$teamid>\n";
  echo "<input type=hidden name=projectid value=$defaultProjectid>\n";

  echo "<input type=hidden name=currentAction value=setInfoForm>\n";
  echo "<input type=hidden name=nextAction    value=timeTrackingReport>\n";

  echo "</form>\n";
  echo "</div>";
}




// ---------------------------------------------------------------
function setProjectSelectionForm($teamid, $defaultProjectid) {

   global $logger;

   // Display form
   echo "<div style='text-align: left;'>";
  if (isset($_GET['debug'])) {
      echo "<form id='projectSelectionForm' name='projectSelectionForm' method='post' action='productivity_report.php?debug'>\n";
  } else {
      echo "<form id='projectSelectionForm' name='projectSelectionForm' method='post' action='productivity_report.php'>\n";
  }

  $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);

   // --- Project List
   $query  = "SELECT mantis_project_table.id, mantis_project_table.name ".
                 "FROM `codev_team_project_table`, `mantis_project_table` ".
                 "WHERE codev_team_project_table.team_id = $teamid ".
                 "AND codev_team_project_table.project_id = mantis_project_table.id ".
                 "ORDER BY mantis_project_table.name";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }
         if (0 != mysql_num_rows($result)) {
            while($row = mysql_fetch_object($result))
            {
               $projList[$row->id] = $row->name;
            }
       }
   echo "<span class='caption_font'>".T_("Project Detail")." </span>\n";
   echo "<select id='projectidSelector' name='projectidSelector' onchange='javascript: setProjectid()' title='".T_("Project")."'>\n";
   echo "<option value='0'>".T_("All sideTasks Projects")."</option>\n";
   foreach ($projList as $pid => $pname)
   {
      if ($pid == $defaultProjectid) {
         echo "<option selected value='".$pid."'>$pname</option>\n";
      } else {
         echo "<option value='".$pid."'>$pname</option>\n";
      }
   }
   echo "</select>\n";

   echo "<input type=hidden name=teamid     value=$teamid>\n";
   echo "<input type=hidden name=projectid value=$defaultProjectid>\n";
   echo "<input type=hidden name=action    value=noAction>\n";
   echo "</form>\n";

   echo "</div>\n";
}




// -----------------------------------------------
function displayProductionDays ($timeTracking) {

  $prodDays                = $timeTracking->getProdDays();
  $sideProdDays            = $timeTracking->getProdDaysSideTasks(false);
  //$sideProdDaysDevel       = $timeTracking->getProdDaysSideTasks(true);
  //$sideProdDaysManagers    = $sideProdDays - $sideProdDaysDevel;

  $managementDays          = $timeTracking->getManagementDays();
  $sideNoManagement        = $sideProdDays - $managementDays;


  echo "<div class=\"float\">\n";

  echo "<table width='300'>\n";
  echo "<caption>".T_("Production Days")." &nbsp;&nbsp;<a id='dialog_ProdDays_link' href='#'><img title='help' src='../images/help_icon.gif'/></a></caption>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>";
  $color = "92C5FC";
  echo "<img src='".getServerRootURL()."/graphs/rectangle.png.php?height=12&width=12&border&color=$color'/>";
  echo " ".T_("Projects")."</td>\n";
  echo "<td>$prodDays</td>\n";
  echo "</tr>\n";
/*
  echo "<tr>\n";
  echo "<td>".T_("SideTasks (Developers only)")."</td>\n";
  echo "<td>$sideProdDaysDevel</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("SideTasks (Managers only)")."</td>\n";
  echo "<td>$sideProdDaysManagers</td>\n";
  echo "</tr>\n";
*/
  echo "<tr>\n";
  echo "<td>";
  $color = "FFC16B";
  echo "<img src='".getServerRootURL()."/graphs/rectangle.png.php?height=12&width=12&border&color=$color'/>";
  echo " ".T_("Project Management")."</td>\n";
  echo "<td>$managementDays</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>";
  $color = "FFF494";
  echo "<img src='".getServerRootURL()."/graphs/rectangle.png.php?height=12&width=12&border&color=$color'/>";
  echo " ".T_("Other SideTasks")."</td>\n";
  echo "<td>$sideNoManagement</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Total")."</td>\n";
  echo "<td>".($prodDays + $sideProdDays)."</td>\n";
  echo "</tr>\n";

  echo "</table>\n";
  echo "</div>\n";

  $formatedValues = "$prodDays:$managementDays:$sideNoManagement";

  if (0 != $prodDays + $managementDays + $sideNoManagement) {
     echo "<div class=\"float\">\n";
     $title = T_("Production Days");
     $formatedColors = "#92C5FC:#FFC16B:#FFF494";
     $formatedLegends = T_("Projects").":".T_("Project Management").":".T_("Other SideTasks");
     #$graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:200&title=$title&colors=#0000FF:#FFA500:#FF4500&values=$prodDays:$sideProdDaysDevel:$sideProdDaysManagers";
     $graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:150&colors=$formatedColors&legends=$formatedLegends&values=$formatedValues";
     $graphURL = SmartUrlEncode($graphURL);
     echo "<img src='$graphURL'/>";
     echo "</div>\n";
  }
}

// -----------------------------------------------
function displayRates ($timeTracking) {

   //  $productivityRateETA     = $timeTracking->getProductivityRate("ETA");
   //  $productivityRateBI      = $timeTracking->getProductivityRate("EffortEstim");
   $efficiencyRate          = $timeTracking->getEfficiencyRate();
   $systemDisponibilityRate = $timeTracking->getSystemDisponibilityRate();
   $productionDaysForecast  = $timeTracking->getAvailableWorkload();
   //  $prodRateNoSupportETA    = $timeTracking->getProductivityRateNoSupport("ETA");
   //  $prodRateNoSupportBI     = $timeTracking->getProductivityRateNoSupport("EffortEstim");



   echo "<div class=\"float\">\n";

   echo "<table width='300'>\n";
   echo "<caption>".T_("Productivity indicators")."</caption>\n";
   echo "<tr>\n";
   echo "<th>".T_("Indicator")."</th>\n";
   echo "<th>".T_("Value")."</th>\n";
   echo "<th></th>\n";
   echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Available Workload")."</td>\n";
  echo "<td>".$productionDaysForecast."</td>\n";
  echo "<td><a id='dialog_AvailWorkload_link' href='#'><img title='help' src='../images/help_icon.gif'/></a></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Efficiency Rate")."</td>\n";
  echo "<td>".number_format($efficiencyRate, 2)."%</td>\n";
  echo "<td><a id='dialog_EfficiencyRate_link' href='#'><img title='help' src='../images/help_icon.gif'/></a></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("System Availability")."</td>\n";
  echo "<td>".number_format($systemDisponibilityRate, 3)."%</td>\n";
  echo "<td><a id='dialog_SystemAvailability_link' href='#'><img title='help' src='../images/help_icon.gif'/></a></td>\n";
  echo "</tr>\n";

   /* productivityRate is not an 'efficient' indicator because it depends on the EffortEstim
   * which is not a very credible value. It is also hard to understand and the value does not
   * fluctuate much. so let's get rid of it !

   echo "<tr>\n";
   echo "<td title='".T_("BEFORE analysis")."'>".T_("Prod. Rate PrelEffortEstim")."</td>\n";
   echo "<td>".number_format($productivityRateETA, 2)."</td>\n";
   echo "<td>".T_("- If estimation is correct the aimed number should be 1.")."<br/>".
   T_("- A number below 1 means a lesser efficiency.")."<br/>".
   T_("- Time spent on a task is balanced by a toughness indicator")."<br/>".
   T_("- Computed on task Resolved/Closed in the given period")."<br/>".
   T_("- Reopened tasks are not taken into account")."</td>\n";
   echo "<td>sum(PrelEffortEstim) / sum(elapsed)</td>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "<td title='".T_("AFTER analysis")."'>".T_("Productivity Rate")."</td>\n";
   echo "<td>".number_format($productivityRateBI, 2)."</td>\n";
   echo "<td>".T_("- If estimation is correct the aimed number should be 1.")."<br/>".
   T_("- A number below 1 means a lesser efficiency.")."<br/>".
   T_("- Computed on task Resolved/Closed in the given period")."<br/>".
   T_("- Reopened tasks are not taken into account")."</td>\n";
   echo "<td>sum(EffortEstim + BS) / sum(elapsed)</td>\n";
   echo "</tr>\n";
   */
   echo "</table>\n";
   echo "</div>\n";


  //echo "<br/>SideTasks<br/>";
   //echo "Nb Production Days  : $sideProdDays<br/>";
   //echo "ProductivityRate    : ".$sideProductivityRate."<br/>\n";

}
// -----------------------------------------------
// display Drifts for Issues that have been marked as 'Resolved' durung the timestamp
function displayResolvedDriftStats ($timeTracking, $withSupport = true) {

  $driftStats = $timeTracking->getResolvedDriftStats($withSupport);

  echo "<table>\n";
  echo "<caption>".T_("EffortDeviation - Tasks resolved in the period")."&nbsp;&nbsp; <a id='dialog_ResolvedDriftStats_link' href='#'><img title='help' src='../images/help_icon.gif'/></a></caption>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  echo "<th width='100' title='".T_("Manager Estimation")."'>".T_("Manager")."</th>\n";
  echo "<th width='100'>".T_("Value")."</th>\n";
  echo "<th>".T_("Tasks")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td title='".T_("If < 0 then ahead on planning.")."'>".T_("EffortDeviation")."</td>\n";
   $value = number_format($driftStats["totalDriftETA"], 2);
   $color = "";
   if ($value < 0) { $color = "style='background-color: #61ed66;'"; }
   if ($value > 0) { $color = "style='background-color: #fcbdbd;'"; }
   echo "<td title='elapsed - MgrEffortEstim' $color >".$value."</td>\n";

   $value = number_format($driftStats["totalDrift"], 2);
   $color = "";
   if ($value < 0) { $color = "style='background-color: #61ed66;'"; }
   if ($value > 0) { $color = "style='background-color: #fcbdbd;'"; }

   echo "<td title='elapsed - EffortEstim' $color>".$value."</td>\n";
     echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks in drift")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsPosETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftPosETA"]).")</span></td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsPos"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftPos"]).")</span></td>\n";
  echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats["formatedBugidPosList"]."</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks in time")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsEqualETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftEqualETA"] + $driftStatsClosed["driftEqualETA"]).")</span></td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsEqual"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftEqual"] + $driftStatsClosed["driftEqual"]).")</span></td>\n";
  if (isset($_GET['debug'])) {
   echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats["formatedBugidEqualList"]."</td>\n";
  } else {
   echo "<td title='".$driftStats["bugidEqualList"]."'>".T_("Tasks resolved in time")."</td>\n";
  }
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks ahead")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsNegETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftNegETA"]).")</span></td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsNeg"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftNeg"]).")</span></td>\n";
  echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats["formatedBugidNegList"]."</td>\n";
  echo "</tr>\n";
  echo "</table>\n";
}


// -----------------------------------------------
// display TimeDrifts for Issues that have been marked as 'Resolved' durung the timestamp
function displayTimeDriftStats ($timeTracking) {

  $timeDriftStats = $timeTracking->getTimeDriftStats();  // all issues delivered within the period

  $nbTasks = $timeDriftStats["nbDriftsNeg"] + $timeDriftStats["nbDriftsPos"];
  $percent = (0 != $nbTasks) ? $timeDriftStats["nbDriftsNeg"] * 100 / $nbTasks : 100;

  echo "<table>\n";
  echo "<caption title='".T_("Tasks having no deadLine are not reported here")."'>".T_("Adherence to deadlines")."&nbsp;&nbsp;&nbsp;(".number_format($percent, 1)."%)</caption>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  echo "<th width='100'>".T_("Total")."</th>\n";
  echo "<th>".T_("Tasks")."</th>\n";
  echo "<th>".T_("Formula")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks NOT delivered on time")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($timeDriftStats["nbDriftsPos"])."<span title='".T_("nb days")."' class='floatr'>(".round($timeDriftStats["driftPos"]).")</span></td>\n";
  echo "<td title='".T_("Tasks NOT delivered on time")."'>".$timeDriftStats["formatedBugidPosList"]."</td>\n";
  echo "<td>DeliveryDate > DeadLine</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks delivered on time")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($timeDriftStats["nbDriftsNeg"])."<span title='".T_("nb days")."' class='floatr'>(".round($timeDriftStats["driftNeg"]).")</span></td>\n";
  echo "<td title='".T_("Task list for EffortEstim")."'>".$timeDriftStats["formatedBugidNegList"]."</td>\n";
  echo "<td>DeliveryDate <= DeadLine</td>\n";
  echo "</tr>\n";
  echo "</table>\n";
}


// --------------------------------
function displayWorkingDaysPerJob($timeTracking, $teamid) {

   $jobs = new Jobs();

  // find out which jobs must be displayed
  $projList = Team::getProjectList($teamid);
  $jobList  = array();

  foreach ($projList as $id => $pname) {
     $p = ProjectCache::getInstance()->getProject($id);
     $jl = $p->getJobList();
     $jobList += $jl;
  }

  echo "<div class=\"float\">\n";
  echo "<table width='300'>\n";
  echo "<caption>".T_("Load per Job")."</caption>\n";
  echo "<tr>\n";
  echo "<th>".T_("Job")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "</tr>\n";

  foreach ($jobList as $id => $jname) {
    if (Jobs::JOB_NA != $id) {

       $nbDays = $timeTracking->getWorkingDaysPerJob($id);
       $color = $jobs->getJobColor($id);

       echo "<tr>\n";
       echo "<td>";
       echo "<img src='".getServerRootURL()."/graphs/rectangle.png.php?height=12&width=12&border&color=$color'/>";
       echo " $jname</td>\n";
       echo "<td>".$nbDays."</td>\n";
       echo "</tr>\n";

       if (0 != $nbDays) {
          if (NULL != $formatedValues) { $formatedValues .= ":"; $formatedLegends .= ":"; $formatedColors .= ":"; }
          $formatedValues .= "$nbDays";
          $formatedLegends .= "$jname";
          $formatedColors .= "#".$color;
       }
    }
  }
  echo "</table>\n";
  echo "</div>\n";

  if (NULL != $formatedValues) {
     echo "<div class=\"float\">\n";
     #$graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:150&title=".T_("Load per Job")."&legends=$formatedLegends&values=$formatedValues&colors=$formatedColors";
     $graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:150&legends=$formatedLegends&values=$formatedValues&colors=$formatedColors";
     #$graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:180&values=$formatedValues&colors=$formatedColors";
     $graphURL = SmartUrlEncode($graphURL);
     echo "<img src='$graphURL'/>";
     echo "</div>\n";
  }
}

// -----------------------------------------------
function displayWorkingDaysPerProject($timeTracking) {

  global $logger;

  echo "<div class=\"float\">\n";

  echo "<table width='300'>\n";
  echo "<caption>".T_("Load per Project")."</caption>\n";
  echo "<tr>\n";
  echo "<th>".T_("Project")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  $query     = "SELECT mantis_project_table.id, mantis_project_table.name ".
               "FROM `mantis_project_table`, `codev_team_project_table` ".
               "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
               "AND codev_team_project_table.team_id = $timeTracking->team_id ".
               " ORDER BY name";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }
  while($row = mysql_fetch_object($result))
  {
     $nbDays = $timeTracking->getWorkingDaysPerProject($row->id);
    echo "<tr>\n";
    echo "<td>";
    echo "$row->name\n";
    echo "</td>\n";
    echo "<td>".$nbDays."</td>\n";
    echo "</tr>\n";

    if (0 != $nbDays) {
       if (NULL != $formatedValues) {
          $formatedValues .= ":"; $formatedLegends .= ":";
       }
       $formatedValues .= $nbDays;
       $formatedLegends .= $row->name;
    }

  }
  echo "</table>\n";
  echo "</div>\n";

  if (NULL != $formatedValues) {
     echo "<div class=\"float\">\n";
     #$graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:200&title=".T_("Load per Project")."&legends=$formatedLegends&values=$formatedValues";
     $graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:150&legends=$formatedLegends&values=$formatedValues";
     $graphURL = SmartUrlEncode($graphURL);
     echo "<img src='$graphURL'/>";
     echo "</div>\n";
  }
}

// -----------------------------------------------
function displaySideTasksProjectDetails($timeTracking) {

  global $logger;

  $sideTaskProjectType = Project::type_sideTaskProject;

  $durationPerCategory = array();
  $formatedBugsPerCategory = array();
  $stProjList = "";

  // find all sideTasksProjects (type = 1)
  $query     = "SELECT project_id ".
               "FROM `codev_team_project_table` ".
               "WHERE team_id = $timeTracking->team_id ".
               "AND type = $sideTaskProjectType";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }
  while($row = mysql_fetch_object($result))
  {
     $durPerCat = $timeTracking->getProjectDetails($row->project_id);
     foreach ($durPerCat as $catName => $bugList)
     {
     	   foreach ($bugList as $bugid => $duration) {
     	   	$durationPerCategory[$catName] += $duration;

     	   	if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
     	   	$issue = IssueCache::getInstance()->getIssue($bugid);
            $formatedBugsPerCategory[$catName] .= issueInfoURL($bugid, $issue->summary);
     	   }
     }

     $proj = ProjectCache::getInstance()->getProject($row->project_id);
     $stProjList[] = $proj->name;

  }
  $formatedProjList = implode( ', ', $stProjList);

  $formatedBugList = "";

  echo "<div class=\"float\">\n";
  echo "<table width='300'>\n";
#  echo "<caption title='".T_("Projects").": $formatedProjList'>".T_("Project Management Detail")."</caption>\n";
  echo "<tr>\n";
  echo "<th>".T_("Category")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "<th>".T_("Tasks")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  foreach ($durationPerCategory as $catName => $duration)
  {
    echo "<tr bgcolor='white'>\n";
    echo "<td>$catName</td>\n";
    echo "<td>$duration</td>\n";
    echo "<td>".$formatedBugsPerCategory[$catName]."</td>\n";
    echo "</tr>\n";

      if (0 != $duration) {
       if (NULL != $formatedValues) {
          $formatedValues .= ":"; $formatedLegends .= ":";
       }
       $formatedValues .= $duration;
       $formatedLegends .= $catName;
    }
  }
  echo "</table>\n";
  echo "</div>\n";

  if (NULL != $formatedValues) {
     echo "<div class=\"float\">\n";
     #$graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:200&title=".T_("Load per Project")."&legends=$formatedLegends&values=$formatedValues";
     $graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:150&legends=$formatedLegends&values=$formatedValues";
     $graphURL = SmartUrlEncode($graphURL);
     echo "<img src='$graphURL'/>";
     echo "</div>\n";
  }
}

// -----------------------------------------------
function displayProjectDetails($timeTracking, $projectId) {

  $durationPerCategory = array();
  $formatedBugsPerCategory = array();

         $durPerCat = $timeTracking->getProjectDetails($projectId);
  foreach ($durPerCat as $catName => $bugList)
  {
      foreach ($bugList as $bugid => $duration) {
         $durationPerCategory[$catName] += $duration;

         if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $formatedBugsPerCategory[$catName] .= issueInfoURL($bugid, $issue->summary);
      }
  }

  $proj = ProjectCache::getInstance()->getProject($projectId);

  echo "<div class=\"float\">\n";
  echo "<table width='300'>\n";
  //echo "<caption>".T_("Project Detail")." ".$proj->name."</caption>\n";
  echo "<tr>\n";
  echo "<th>".T_("Category")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "<th>".T_("Tasks")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  foreach ($durationPerCategory as $catName => $duration)
  {
    echo "<tr bgcolor='white'>\n";
    echo "<td>$catName</td>\n";
    echo "<td>$duration</td>\n";
    echo "<td>".$formatedBugsPerCategory[$catName]."</td>\n";
    echo "</tr>\n";

    if (0 != $duration) {
       if (NULL != $formatedValues) {
          $formatedValues .= ":"; $formatedLegends .= ":";
       }
       $formatedValues .= $duration;
       $formatedLegends .= $catName;
    }

  }
  echo "</table>\n";
  echo "</div>\n";

  if (NULL != $formatedValues) {
     echo "<div class=\"float\">\n";
     $title = $proj->name." ".T_("Categories");
     #$graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:150&title=$title&legends=$formatedLegends&values=$formatedValues";
     $graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:150&legends=$formatedLegends&values=$formatedValues";
     $graphURL = SmartUrlEncode($graphURL);
     echo "<img src='$graphURL'/>";
     echo "</div>\n";
  }
}

// -----------------------------------------------
function displayCheckWarnings($timeTracking) {

  global $logger;

  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $timeTracking->team_id ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";

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
        echo "<br/>$row->username: $formatedDate ".T_("incomplete (missing ")." ".(1-$value)." jour).\n";
      } else {
        echo "<br/>$row->username: $formatedDate ".T_("inconsistent")." (".($value)." jour).\n";
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

// -----------------------------------------------
// display the tasks having been reopened in the period
function displayReopenedStats ($timeTracking) {

  $submittedList   = $timeTracking->getSubmitted();
  $reopenedList    = $timeTracking->getReopened();
  $reopenedRate    = $timeTracking->getReopenedRate() * 100;  // x100 to get a percentage

  foreach ($reopenedList as $bug_id) {
     $issue = IssueCache::getInstance()->getIssue($bug_id);

      if ($formatedTaskList != "") { $formatedTaskList .= ', '; }
      $formatedTaskList .= issueInfoURL($issue->bugId, $issue->summary);
   }


  echo "<table>\n";
  echo "<caption>".T_("Reopened tasks")."</caption>\n";
  echo "<tr>\n";
  echo "<th width='100'>".T_("nb submitted")."</th>\n";
  echo "<th width='100'>".T_("nb reopened")."</th>\n";
  echo "<th width='100' title='".T_("nb reopened / nb submitted")."'>".T_("Rate")."</th>\n";
  echo "<th width='400' title='".T_("tasks having been reopened in the period")."'>".T_("Tasks")."</th>\n";
  echo "<th>".T_("Formula")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";

  echo "<td>".count($submittedList)."</td>\n";
  echo "<td>".count($reopenedList)."</td>\n";
  echo "<td>".round($reopenedRate, 1)." %</td>\n";
  echo "<td>$formatedTaskList</td>\n";
  echo "<td>nb reopened / nb submitted</td>\n";
  echo "</tr>\n";
  echo "</table>\n";

}


// =========== MAIN ==========
$year = date('Y');

$defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
$teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
$_SESSION['teamid'] = $teamid;

$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

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


	$weekDates      = week_dates(date('W'),$year);

	$action           = isset($_POST['action']) ? $_POST['action'] : '';
	$defaultProjectid = isset($_POST['projectid']) ? $_POST['projectid'] : 0;

	$date1  = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : date("Y-m-d", $weekDates[1]);
	$date2  = isset($_REQUEST["date2"]) ? $_REQUEST["date2"] : date("Y-m-d", $weekDates[5]);

	$startTimestamp = date2timestamp($date1);
	$endTimestamp = date2timestamp($date2);

	$endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

	//echo "DEBUG startTimestamp $startTimestamp  ".date("Y-m-d H:i:s", $startTimestamp)."<br/>";
	//echo "DEBUG endTimestamp $endTimestamp  ".date("Y-m-d H:i:s", $endTimestamp)."<br/>";

	$timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

	setInfoForm($teamid, $teamList, $date1, $date2, $defaultProjectid);
	echo "<br/><br/>\n";


	if (0 != $teamid) {

		echo "<br/>\n";
		echo "du ".date("Y-m-d  (H:i)", $startTimestamp)."&nbsp;<br/>";
		echo "au ".date("Y-m-d  (H:i)", $endTimestamp)."<br/><br/>\n";

		displayProductionDays($timeTracking);

		echo "<div class=\"spacer\"> </div>\n";
		echo "<br>";
		echo "<br>";
		echo "<hr width='100%'/>\n";
		echo "<br>";
		echo "<br>";

		displayWorkingDaysPerJob($timeTracking, $teamid);
		echo "<div class=\"spacer\"> </div>\n";
		echo "<br>";
		echo "<br>";
		echo "<hr/>\n"; # "<hr width='80%'/>\n";
		echo "<br>";
		echo "<br>";


		setProjectSelectionForm($teamid, $defaultProjectid);
		$defaultProjectid  = $_POST['projectid'];
		if (0 != $defaultProjectid) {
		   displayProjectDetails($timeTracking, $defaultProjectid);
		} else {
		   // all sideTasks
		   displaySideTasksProjectDetails($timeTracking);
		}

		echo "<div class=\"spacer\"> </div>\n";
		echo "<br>";
		echo "<br>";
		echo "<hr width='100%'/>\n";
		echo "<br>";
		echo "<br>";

		displayWorkingDaysPerProject($timeTracking);

		echo "<div class=\"spacer\"> </div>\n";
		echo "<br>";
		echo "<br>";
		echo "<hr width='100%'/>\n";
		echo "<br>";
		echo "<br>";

		displayRates($timeTracking);

		echo "<div class=\"spacer\"> </div>\n";
		echo "<br>";
		echo "<br>";
		echo "<hr width='100%'/>\n";
		echo "<br>";
		echo "<br>";

	   displayTimeDriftStats ($timeTracking);

	   echo "<br/><br/>\n";
	   displayResolvedDriftStats($timeTracking);

	   echo "<br/><br/>\n";
	   displayReopenedStats($timeTracking);


		echo "<br/><br/>\n";
		displayCheckWarnings($timeTracking);
	}
}
?>

</div>

<?php include 'footer.inc.php'; ?>
