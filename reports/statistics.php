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

  
</script>


<div id="content">

<?php 
   
include_once "period_stats_report.class.php";
include_once "issue.class.php";
include_once "time_tracking.class.php";



function setTeamAndStartSelectionForm($originPage, $teamid, $teamList, $startYear) {
   
  // create form
  echo "<div align=center>\n";
  echo "<form id='form1' name='form1' method='post' action='$originPage'>\n";
  
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



function displaySubmittedResolved($periodStatsReport, $width, $height) {
	
   $submitted = $periodStatsReport->getStatus("submitted");
   $resolved  = $periodStatsReport->getStatus("delta_resolved");

   $graph_title="title=".("Submitted / Resolved Issues");
   $graph_width="width=$width";
   $graph_height="height=$height";


   $val1 = array_values($submitted);
   #$val1 = array(4, 2, 5, 3, 3, 6, 7, 6, 5, 4, 3, 2);
   $strVal1 = "leg1=Submitted&x1=".implode(':', $val1);

   $val2 = array_values($resolved);
   #$val2 = array(3, 8, 15, 6, 2, 1, 3, 2, 5, 7, 6, 6);
   $strVal2 = "leg2=Resolved&x2=".implode(':', $val2);

   $bottomLabel = array();
   foreach ($submitted as $date => $val) {
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
   echo "<th title='".T_("Nbre de fiches cr&eacute;&eacute;es SAUF SuiviOp, FDL")."'>".T_("Nb submissions")."</th>\n";
   echo "<th title='".T_("Nbre de fiches r&eacute;solues SAUF SuiviOp et non reouvertes'").">".T_("Nb Resolved")."</th>\n";
   echo "</tr>\n";
   foreach ($submitted as $date => $val) {
      echo "<tr>\n";
   	echo "<td class=\"right\">".date("F Y", $date)."</td>\n";
      echo "<td class=\"right\">".$val."</td>\n";
      echo "<td class=\"right\">".$resolved[$date]."</td>\n";
      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "</div>\n";
   echo "</div>\n";
   
   
}

function displayResolvedDriftGraph ($timeTrackingTable, $width, $height) {
   
   $start_day = 1; 
	$now = time();
   
	foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
	
         $driftStats_new = $timeTracking->getResolvedDriftStats();
         
         $val1[] = $driftStats_new["totalDriftETA"] ? $driftStats_new["totalDriftETA"] : 0;
         $val2[] = $driftStats_new["totalDrift"] ? $driftStats_new["totalDrift"] : 0;
         $bottomLabel[] = date("M y", $startTimestamp);
         
         #echo "DEBUG: ETA=".$driftStats_new['totalDriftETA']." Eff=".$driftStats_new['totalDrift']." date=".date('M y', $startTimestamp)."<br/>\n";
   }
   $graph_title="title=".("Drifts");
   $graph_width="width=$width";
   $graph_height="height=$height";
   
   $strVal1 = "leg1=ETA&x1=".implode(':', $val1);
   $strVal2 = "leg2=EffortEstim&x2=".implode(':', $val2);
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);
   
   echo "<div>\n";
   echo "<h2>".T_("Drifts")."</h2>\n";
   echo "<div class=\"float\">\n";
   echo "    <img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2'/>";
   echo "</div>\n";
   echo "</div>\n";
   
}

function displayProductivityRateGraph ($timeTrackingTable, $width, $height) {
   
   $start_day = 1; 
   $now = time();
   
   foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
   
         $driftStats_new = $timeTracking->getResolvedDriftStats();
         
         $val1[] = $timeTracking->getProductivityRate("ETA");
         $val2[] = $timeTracking->getProductivityRate("EffortEstim");
         $bottomLabel[] = date("M y", $startTimestamp);
         
         #echo "DEBUG: ETA=".$driftStats_new['totalDriftETA']." Eff=".$driftStats_new['totalDrift']." date=".date('M y', $startTimestamp)."<br/>\n";
   }
   $graph_title="title=".("Productivity Rate");
   $graph_width="width=$width";
   $graph_height="height=$height";
   
   $strVal1 = "leg1=Prod Rate ETA&x1=".implode(':', $val1);
   $strVal2 = "leg2=Prod Rate&x2=".implode(':', $val2);
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);
   
   echo "<div>\n";
   echo "<h2>".T_("Productivity Rate")."</h2>\n";
   echo "<div class=\"float\">\n";
   echo "    <img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&pointFormat=%.2f&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2'/>";
   echo "</div>\n";
   echo "</div>\n";
   
}



function createTimeTeackingList($start_day, $start_month, $start_year, $teamid) {

   $now = time();
   $timeTrackingTable = array();
   
   for ($y = $start_year; $y <= date('Y'); $y++) {
      
      for ($month=$start_month; $month<13; $month++) {
         
         $startTimestamp = mktime(0, 0, 1, $month, $start_day, $y);
         $endTimestamp   = mktime(0, 0, 1, ($month + 1), $start_day, $y);
   
         if ($startTimestamp > $now) { break; }
         
         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);
         $timeTrackingTable[$startTimestamp] = $timeTracking;
         
         #echo "DEBUG: ETA=".$driftStats_new['totalDriftETA']." Eff=".$driftStats_new['totalDrift']." date=".date('M y', $startTimestamp)."<br/>\n";
      }
      $start_month = 1;
      $start_day   = 1;
   }
	return $timeTrackingTable;
}

# ======================================
# ================ MAIN ================

$default_year = date('Y') -1; // TODO CoDev install date !
$start_month = 6;             // TODO CoDev install date !
$start_day = 1;               // TODO CoDev install date !

$start_year = isset($_POST[year]) ? $_POST[year] : $default_year;


$userid = $_SESSION['userid'];
$action = $_POST[action];

$defaultTeam = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;

// Connect DB
$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die(T_("Could not connect to database"));
mysql_select_db($db_mantis_database) or die("Could not select database");


$session_user = new User($userid);
$mTeamList = $session_user->getTeamList();
$lTeamList = $session_user->getLeadedTeamList();
$oTeamList = $session_user->getObservedTeamList();
$managedTeamList = $session_user->getManagedTeamList();
$teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList; 


if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
   echo T_("Sorry, you do NOT have access to this page.");
   echo "</div>";
   
} else {

   // ----- selection Form
   setTeamAndStartSelectionForm("statistics.php", $teamid, $teamList, $default_year);

   
   if ("displayStats" == $action) {
   
      if (0 != $teamid) {

      	$timeTrackingTable = createTimeTeackingList($start_day, $start_month, $start_year, $teamid);
      	
      	
         echo "<div align='left'>\n";
         echo "<ul>\n";
         echo "   <li><a href='#tagSubmittedResolved'>".T_("Submitted / Resolved Issues")."</a></li>\n";
         echo "   <li><a href='#tagResolvedDrift'>".T_("Drifts")."</a></li>\n";
         echo "   <li><a href='#tagProductivityRate'>".T_("Productivity Rate")."</a></li>\n";
         echo "</ul><br/>\n";
         echo "</div>\n";
      
      
      	
         // ---- Submitted / Resolved
         echo "<br/>\n";
         echo "<hr/>\n";
         echo "<br/>\n";
         echo "<a name='tagSubmittedResolved'></a>\n";
         $periodStatsReport = new PeriodStatsReport($start_year, $start_month, $teamid);
         $periodStatsReport->computeReport();
         displaySubmittedResolved($periodStatsReport, 1000, 300);

         echo "<div class=\"spacer\"> </div>\n";

         echo "<br/>\n";
         echo "<br/>\n";
         echo "<br/>\n";

         // --------- Drifts
         echo "<br/>\n";
         echo "<hr/>\n";
         echo "<br/>\n";
         echo "<a name='tagResolvedDrift'></a>\n";
         displayResolvedDriftGraph ($timeTrackingTable, 1000, 300);

         echo "<div class=\"spacer\"> </div>\n";
         
         
         // --------- ProductivityRate
         echo "<br/>\n";
         echo "<hr/>\n";
         echo "<br/>\n";
         echo "<a name='tagProductivityRate'></a>\n";
         displayProductivityRateGraph ($timeTrackingTable, 1000, 300);

         echo "<div class=\"spacer\"> </div>\n";
      }
   }
}
?>

</div>

<?php include 'footer.inc.php'; ?>

