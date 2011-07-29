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
   $_POST[page_name] = T_("Statistics"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

<script language="JavaScript">

  function submitTeamAndStartSelectionForm() {

       document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
       document.forms["form1"].year.value   = document.getElementById('yearSelector').value;
       
       document.forms["form1"].action.value = "displayStats";
       document.forms["form1"].submit();
  }

  function updateYearSelector() {
     document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
     document.forms["form1"].year.value   = document.getElementById('yearSelector').value;
     
     document.forms["form1"].action.value = "noAction";
     document.forms["form1"].submit();
  }
       
</script>


<div id="content">

<?php 
   
include_once "period_stats_report.class.php";
include_once "issue.class.php";
include_once "team.class.php";
include_once "time_tracking.class.php";



function setTeamAndStartSelectionForm($originPage, $teamid, $teamList, $startYear, $curYear) {
   
  // create form
  echo "<div align=center>\n";
  if (isset($_GET['debug'])) {
      echo "<form id='form1' name='form1' method='post' action='$originPage?debug'>\n";
  } else {
  	   echo "<form id='form1' name='form1' method='post' action='$originPage'>\n";
  }
  echo T_("Team").": <select id='teamidSelector' name='teamidSelector' onchange='updateYearSelector()'>\n";
  echo "<option value='0'></option>\n";
  foreach ($teamList as $tid => $tname) {
    if ($tid == $teamid) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";
  
  // -----------
  echo T_("Start Year").": <select id='yearSelector' name='yearSelector'>\n";
  for ($y = $startYear; $y <= date('Y'); $y++) {

    if ($y == $curYear) {
      echo "<option selected value='".$y."'>".$y."</option>\n";
    } else {
      echo "<option value='".$y."'>".$y."</option>\n";
    }
  }
  echo "</select>\n";
  
  
  echo "&nbsp;<input type=button value='".T_("Compute")."' onClick='submitTeamAndStartSelectionForm()'>\n";
  
  echo "<input type=hidden name=action  value=noAction>\n";
  echo "<input type=hidden name=teamid  value=0>\n";
  echo "<input type=hidden name=year    value=".date('Y').">\n";
  
  echo "</form>\n";
  echo "</div>\n";
}



function displaySubmittedResolved($timeTrackingTable, $width, $height) {
	
   $submittedList = array();
   $resolvedList  = array();
   foreach ($timeTrackingTable as $d => $tt) {
   	  $submittedList[$d] = count($tt->getSubmitted());     // returns bug_id !
   	  $resolvedList[$d]  = count($tt->getResolvedIssues()); // returns Issue instances !
   }
   
   
   $graph_title="title=".("Submitted / Resolved Issues");
   $graph_width="width=$width";
   $graph_height="height=$height";


   $val1 = array_values($submittedList);
   #$val1 = array(4, 2, 5, 3, 3, 6, 7, 6, 5, 4, 3, 2);
   $strVal1 = "leg1=Submitted&x1=".implode(':', $val1);

   $val2 = array_values($resolvedList);
   #$val2 = array(3, 8, 15, 6, 2, 1, 3, 2, 5, 7, 6, 6);
   $strVal2 = "leg2=Resolved&x2=".implode(':', $val2);

   $bottomLabel = array();
   foreach ($submittedList as $date => $val) {
      $bottomLabel[] = date("M y", $date);
   }
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);

   #$leftLabel = array(0, 10, 20, 30, 40);
   #$strLeftLabel = "leftLabel=".implode(':', $leftLabel);

   // ---------
   echo "<div>\n";
   echo "<h2>".T_("Submitted / Resolved Issues")."</h2>\n";
   
   echo "<div class=\"float\">\n";
   echo "    <img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2'/>";
   echo "</div>\n";
   
   echo "<div class=\"float\">\n";
   echo "<table>\n";
   echo "<caption title='".("Submitted / Resolved")."'</caption>";
   echo "<tr>\n";
   echo "<th>Date</th>\n";
   echo "<th title='".T_("Nb of submitted tasks EXCEPT SideTasks and FDL")."'>".T_("Nb submissions")."</th>\n";
   echo "<th title='".T_("Nb of resolved tasks EXCEPT SideTasks and reopened tasks")."'>".T_("Nb Resolved")."</th>\n";
   echo "</tr>\n";
   foreach ($submittedList as $date => $val) {
      echo "<tr>\n";
   	echo "<td class=\"right\">".date("F Y", $date)."</td>\n";
      echo "<td class=\"right\">".$val."</td>\n";
      echo "<td class=\"right\">".$resolvedList[$date]."</td>\n";
      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "</div>\n";
   echo "</div>\n";
   
   
}

function displayResolvedDriftGraph ($timeTrackingTable, $width, $height, $displayNoSupport = false) {
   
   $start_day = 1; 
	$now = time();
   
	foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
	
         // REM: the 'normal' drifts DO include support
		   $driftStats_new = $timeTracking->getResolvedDriftStats(true);
         if ($displayNoSupport) {
         	$driftStats_noSupport = $timeTracking->getResolvedDriftStats(false);
         }
         
         $val1[] = $driftStats_new["totalDriftETA"] ? $driftStats_new["totalDriftETA"] : 0;
         $val2[] = $driftStats_new["totalDrift"] ? $driftStats_new["totalDrift"] : 0;
         if ($displayNoSupport) {
            $val3[] = $driftStats_noSupport["totalDrift"] ? $driftStats_noSupport["totalDrift"] : 0;;
         }
         $bottomLabel[] = date("M y", $startTimestamp);
         
         #echo "DEBUG: ETA=".$driftStats_new['totalDriftETA']." Eff=".$driftStats_new['totalDrift']." date=".date('M y', $startTimestamp)."<br/>\n";
   }
   $graph_title="title=".("Drifts");
   $graph_width="width=$width";
   $graph_height="height=$height";
   
   $strVal1 = "leg1=ETA&x1=".implode(':', $val1);
   $strVal2 = "leg2=EffortEstim&x2=".implode(':', $val2);
   if ($displayNoSupport) {
      $strVal3 = "leg3=No Support&x3=".implode(':', $val3);
   }
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);
   
   echo "<div>\n";
   echo "<h2>".T_("Drifts")."</h2>\n";
   
   echo "<span class='help_font'>\n";
   echo T_("Drift").": ".T_("Overflow day quantity")."<br/>\n";
   echo "</span>\n";
   echo "<br/>\n";
   
   echo "<div class=\"float\">\n";
   if ($displayNoSupport) {
      echo "    <img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&pointFormat=%.1f&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2&$strVal3'/>";
   } else {
      echo "    <img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&pointFormat=%.1f&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2'/>";
   }
   echo "</div>\n";
   echo "<div class=\"float\">\n";
   echo "<table>\n";
   echo "<caption title='".("Drifts")."'</caption>";
   echo "<tr>\n";
   echo "<th>Date</th>\n";
   echo "<th title='".T_("")."'>".T_("ETA")."</th>\n";
   echo "<th title='"."BI + BS"."'>".T_("EffortEstim")."</th>\n";
   if ($displayNoSupport) {
      echo "<th title='"."BI + BS"."'>".T_("No Support")."</th>\n";
   }
   echo "</tr>\n";
   $i = 0;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      echo "<tr>\n";
      echo "<td class=\"right\">".date("F Y", $startTimestamp)."</td>\n";
      echo "<td class=\"right\">".$val1[$i]."</td>\n";
      echo "<td class=\"right\">".$val2[$i]."</td>\n";
      if ($displayNoSupport) {
         echo "<td class=\"right\">".$val3[$i]."</td>\n";
      }
      echo "</tr>\n";
      $i++;
   }
   echo "</table>\n";
   echo "</div>\n";
   echo "</div>\n";
   
}

/**
 * Display 'Adherence to deadlines' 
 * in percent of tasks delivered before the deadLine.
 *  
 * @param unknown_type $timeTrackingTable
 * @param unknown_type $width
 * @param unknown_type $height
 */
function displayTimeDriftGraph ($timeTrackingTable, $width, $height) {
   
   $start_day = 1; 
   $now = time();
   
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      
         // REM: the 'normal' drifts DO include support
         $timeDriftStats = $timeTracking->getTimeDriftStats();
         
         $nbTasks = $timeDriftStats["nbDriftsNeg"] + $timeDriftStats["nbDriftsPos"];
         $val1[] = (0 != $nbTasks) ? $timeDriftStats["nbDriftsNeg"] * 100 / $nbTasks : 100;

         $bottomLabel[] = date("M y", $startTimestamp);
         
         #echo "DEBUG: nbDriftsNeg=".$timeDriftStats['nbDriftsNeg']." nbDriftsPos=".$timeDriftStats['nbDriftsPos']." date=".date('M y', $startTimestamp)."<br/>\n";
         #echo "DEBUG: driftNeg=".$timeDriftStats['driftNeg']." driftPos=".$timeDriftStats['driftPos']." date=".date('M y', $startTimestamp)."<br/>\n";
   }
   $graph_title="title=".("Adherence to deadlines");
   $graph_width="width=$width";
   $graph_height="height=$height";
   
   $strVal1 = "leg1=% Tasks&x1=".implode(':', $val1);
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);
   
   echo "<div>\n";
   echo "<h2>".T_("Adherence to deadlines")."</h2>\n";
   
   echo "<span class='help_font'>\n";
   echo T_("% Tasks").": ".T_("Percentage of tasks delivered before the deadLine")."<br/>\n";
   echo "</span>\n";
   echo "<br/>\n";
   
   echo "<div class=\"float\">\n";
   echo "    <img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&pointFormat=%.1f&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2'/>";
   echo "</div>\n";
   echo "<div class=\"float\">\n";
   echo "<table>\n";
   echo "<caption title='".("Adherence to deadlines")."'</caption>";
   echo "<tr>\n";
   echo "<th>Date</th>\n";
   echo "<th title='".T_("% Tasks Delivered on time")."'>".T_("% Tasks")."</th>\n";
   echo "</tr>\n";
   $i = 0;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      echo "<tr>\n";
      echo "<td class=\"right\">".date("F Y", $startTimestamp)."</td>\n";
      echo "<td class=\"right\">".number_format($val1[$i], 1)."%</td>\n";
      echo "</tr>\n";
      $i++;
   }
   echo "</table>\n";
   echo "</div>\n";
   echo "</div>\n";
   
}

function displayProductivityRateGraph ($timeTrackingTable, $width, $height, $displayNoSupport = false) {
   
   $start_day = 1; 
   $now = time();
   
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
   
         // REM: the 'normal' ProductivityRate DO include support
   	   $val1[] = $timeTracking->getProductivityRate("ETA");
         $val2[] = $timeTracking->getProductivityRate("EffortEstim");
         if ($displayNoSupport) {
            $val3[] = $timeTracking->getProductivityRateNoSupport("EffortEstim");
         }
         $bottomLabel[] = date("M y", $startTimestamp);
         
         #echo "DEBUG: ETA=".$driftStats_new['totalDriftETA']." Eff=".$driftStats_new['totalDrift']." date=".date('M y', $startTimestamp)."<br/>\n";
   }
   $graph_title="title=".("Productivity Rate");
   $graph_width="width=$width";
   $graph_height="height=$height";
   
   $strVal1 = "leg1=Prod Rate ETA&x1=".implode(':', $val1);
   $strVal2 = "leg2=Prod Rate&x2=".implode(':', $val2);
   if ($displayNoSupport) {
      $strVal3 = "leg3=No Support&x3=".implode(':', $val3);
   }
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);
   
   echo "<div>\n";
   echo "<h2>".T_("Productivity Rate")."</h2>\n";
   
   echo "<span class='help_font'>\n";
   echo T_("Productivity Rate").": ".T_("Ratio between the estimated time and the elapsed time")."<br/>\n";
      echo "</span>\n";
   echo "<br/>\n";
   echo "<div class=\"float\">\n";
  
   if ($displayNoSupport) { 
      echo "<img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&pointFormat=%.2f&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2&$strVal3'/>";
   } else {
      echo "<img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&pointFormat=%.2f&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2'/>";
   }
   echo "</div>\n";
   echo "<div class=\"float\">\n";
   echo "<table>\n";
   echo "<caption title='".T_("Productivity Rate")."'</caption>";
   echo "<tr>\n";
   echo "<th>Date</th>\n";
   echo "<th title='".T_("")."'>".T_("Prod Rate ETA")."</th>\n";
   echo "<th title='".T_("")."'>".T_("Prod Rate")."</th>\n";
   if ($displayNoSupport) {
      echo "<th title='".T_("")."'>".T_("No Support")."</th>\n";
   }
   echo "</tr>\n";
   $i = 0;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      echo "<tr>\n";
      echo "<td class=\"right\">".date("F Y", $startTimestamp)."</td>\n";
      echo "<td class=\"right\">".number_format($val1[$i], 2)."</td>\n";
      echo "<td class=\"right\">".number_format($val2[$i], 2)."</td>\n";
      if ($displayNoSupport) { 
         echo "<td class=\"right\">".number_format($val3[$i], 2)."</td>\n";
      }
      echo "</tr>\n";
      $i++;
   }
   echo "</table>\n";
   echo "</div>\n";
   echo "</div>\n";
   
}

function displayEfficiencyGraph ($timeTrackingTable, $width, $height) {
   
   $start_day = 1; 
   $now = time();
   
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
   
         $driftStats_new = $timeTracking->getResolvedDriftStats();
         
         $val1[] = $timeTracking->getEfficiencyRate();
         $val2[] = $timeTracking->getSystemDisponibilityRate();
         $bottomLabel[] = date("M y", $startTimestamp);
   }
   $graph_title="title=".("Efficiency");
   $graph_width="width=$width";
   $graph_height="height=$height";
   
   $strVal1 = "leg1=% Efficiency&x1=".implode(':', $val1);
   $strVal2 = "leg2=% Sys Disp&x2=".implode(':', $val2);
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);
   
   echo "<div>\n";
   echo "<h2>".T_("Efficiency and System Disponibility")."</h2>\n";
   echo "<span class='help_font'>\n";
   echo T_("Efficiency Rate").": ".T_("Exclude side tasks to get the percent of time spent working on the projects")."<br/>\n";
   echo "</span>\n";
   echo "<br/>\n";
   echo "<div class=\"float\">\n";
   echo "    <img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&pointFormat=%.2f&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2'/>";
   echo "</div>\n";
   echo "<div class=\"float\">\n";
   echo "<table>\n";
   echo "<caption title='".T_("Sys Disp.")."'</caption>";
   echo "<tr>\n";
   echo "<th>Date</th>\n";
   echo "<th title='".T_("")."'>".T_("Efficiency")."</th>\n";
   echo "<th title='".T_("")."'>".T_("Sys Disp")."</th>\n";
   echo "</tr>\n";
   $i = 0;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      echo "<tr>\n";
      echo "<td class=\"right\">".date("F Y", $startTimestamp)."</td>\n";
      echo "<td class=\"right\">".number_format($val1[$i], 2)."%</td>\n";
      echo "<td class=\"right\">".number_format($val2[$i], 3)."%</td>\n";
      echo "</tr>\n";
      $i++;
   }
   echo "</table>\n";
   echo "</div>\n";
   echo "</div>\n";
   
}



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
         
         if ($startTimestamp > $now) { break; }
         
         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);
         $timeTrackingTable[$startTimestamp] = $timeTracking;
         
         #echo "DEBUG: ETA=".$driftStats_new['totalDriftETA']." Eff=".$driftStats_new['totalDrift']." date=".date('M y', $startTimestamp)."<br/>\n";
         $day   = 1;
      }
      $start_month = 1;
   }
	return $timeTrackingTable;
}

/**
 * 
 * @param $timeTrackingTable
 * @param $width
 * @param $height
 */
function displayReopenedRateGraph ($timeTrackingTable, $width, $height) {

   $start_day = 1; 
   $now = time();
   
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      
         $nbReopened   = count($timeTracking->getReopened());
         $reopenedRate = $timeTracking->getReopenedRate() * 100; // x100 to get a percentage
         
         $val1[] = $reopenedRate;
         $val2[] = $nbReopened;

         $bottomLabel[] = date("M y", $startTimestamp);
         
         #echo "DEBUG: nbDriftsNeg=".$timeDriftStats['nbDriftsNeg']." nbDriftsPos=".$timeDriftStats['nbDriftsPos']." date=".date('M y', $startTimestamp)."<br/>\n";
         #echo "DEBUG: driftNeg=".$timeDriftStats['driftNeg']." driftPos=".$timeDriftStats['driftPos']." date=".date('M y', $startTimestamp)."<br/>\n";
   }
   $graph_title="title=".("Reopened Rate");
   $graph_width="width=$width";
   $graph_height="height=$height";
   
   $strVal1 = "leg1=% Reopened&x1=".implode(':', $val1);
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);
   
   echo "<div>\n";
   echo "<h2>".T_("Reopened Rate")."</h2>\n";
   
   echo "<span class='help_font'>\n";
   echo T_("% Reopened").": ".T_("Percentage of submitted tasks having been reopened in the period")."<br/>\n";
   echo "</span>\n";
   echo "<br/>\n";
   
   echo "<div class=\"float\">\n";
   echo "    <img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&pointFormat=%.1f&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1'/>";
   echo "</div>\n";
   echo "<div class=\"float\">\n";
   echo "<table>\n";
   echo "<caption title='".("Reopened Rate")."'</caption>";
   echo "<tr>\n";
   echo "<th>Date</th>\n";
   echo "<th title='".T_("Reopened Rate")."'>".T_("% Reopened")."</th>\n";
   echo "<th title='".T_("Nb Reopened")."'>".T_("Nb Reopened")."</th>\n";
   echo "</tr>\n";
   $i = 0;
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
      echo "<tr>\n";
      echo "<td class=\"right\">".date("F Y", $startTimestamp)."</td>\n";
      echo "<td class=\"right\">".round($val1[$i], 1)."%</td>\n";
      echo "<td class=\"right\">".$val2[$i]."</td>\n";
      echo "</tr>\n";
      $i++;
   }
   echo "</table>\n";
   echo "</div>\n";
   echo "</div>\n";
  

}


# ======================================
# ================ MAIN ================


$userid = $_SESSION['userid'];
$action = $_POST[action];

$defaultTeam = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;

// if 'support' is set in the URL, display graphs for 'with/without Support'
$displayNoSupport  = isset($_GET['support']) ? true : false;
$originPage = isset($_GET['support']) ? "statistics.php?support" : "statistics.php"; 


$session_user = UserCache::getInstance()->getUser($userid);
$mTeamList = $session_user->getTeamList();
$lTeamList = $session_user->getLeadedTeamList();
$oTeamList = $session_user->getObservedTeamList();
$managedTeamList = $session_user->getManagedTeamList();
$teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList; 

$team = new Team($teamid);
$default_year = date("Y", $team->date);
$start_year  = isset($_POST[year]) ? $_POST[year] : $default_year;
$start_month = ($start_year == $default_year) ? date("m", $team->date) : 1;
$start_day   = ($start_year == $default_year) ? date("d", $team->date) : 1;



if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
   echo T_("Sorry, you do NOT have access to this page.");
   echo "</div>";
   
} else {

   // ----- selection Form
   setTeamAndStartSelectionForm($originPage, $teamid, $teamList, $default_year, $start_year);

   
   if ("displayStats" == $action) {
   
      if (0 != $teamid) {

      	$timeTrackingTable = createTimeTrackingList($start_day, $start_month, $start_year, $teamid);
      	
      	
         echo "<div align='left'>\n";
         echo "<ul>\n";
         echo "   <li><a href='#tagSubmittedResolved'>".T_("Submitted / Resolved Issues")."</a></li>\n";
         echo "   <li><a href='#tagTimeDrift'>".T_("Adherence to deadlines")."</a></li>\n";
         echo "   <li><a href='#tagResolvedDrift'>".T_("Drifts")."</a></li>\n";
         echo "   <li><a href='#tagEfficiencyRate'>".T_("Efficiency - System Disponibility")."</a></li>\n";
#         echo "   <li><a href='#tagProductivityRate'>".T_("Productivity Rate")."</a></li>\n";
         echo "</ul><br/>\n";
         echo "</div>\n";
      
         // ---- Submitted / Resolved
         echo "<br/>\n";
         echo "<hr/>\n";
         echo "<br/>\n";
         echo "<a name='tagSubmittedResolved'></a>\n";
         displaySubmittedResolved($timeTrackingTable, 800, 300);
         
         flush();
         
         echo "<div class=\"spacer\"> </div>\n";

         echo "<br/>\n";
         echo "<br/>\n";
         echo "<br/>\n";

         // --------- Drifts
         echo "<br/>\n";
         echo "<hr/>\n";
         echo "<br/>\n";
         echo "<a name='tagTimeDrift'></a>\n";
         displayTimeDriftGraph ($timeTrackingTable, 800, 300);         
         flush();
         
         echo "<div class=\"spacer\"> </div>\n";
         
         echo "<br/>\n";
         echo "<hr/>\n";
         echo "<br/>\n";
         echo "<a name='tagResolvedDrift'></a>\n";
         displayResolvedDriftGraph ($timeTrackingTable, 800, 300, $displayNoSupport);
         flush();
         
         echo "<div class=\"spacer\"> </div>\n";
         
         
         // --------- EfficiencyRate
         echo "<br/>\n";
         echo "<hr/>\n";
         echo "<br/>\n";
         echo "<a name='tagEfficiencyRate'></a>\n";
         displayEfficiencyGraph ($timeTrackingTable, 800, 300);
         flush();
         
         echo "<div class=\"spacer\"> </div>\n";

         // --------- ReopenedRate
         echo "<br/>\n";
         echo "<hr/>\n";
         echo "<br/>\n";
         echo "<a name='tagProductivityRate'></a>\n";
         displayReopenedRateGraph ($timeTrackingTable, 800, 300);
         flush();
         
         echo "<div class=\"spacer\"> </div>\n";
         
         
         // --------- ProductivityRate
/*
         echo "<br/>\n";
         echo "<hr/>\n";
         echo "<br/>\n";
         echo "<a name='tagProductivityRate'></a>\n";
         displayProductivityRateGraph ($timeTrackingTable, 800, 300, $displayNoSupport);

         echo "<div class=\"spacer\"> </div>\n";
*/         
         
      }
   }
}
?>

</div>

<?php include 'footer.inc.php'; ?>

