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
   $_POST[page_name] = T_("Weekly CSV Report"); 
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

include_once "constants.php";
include_once "tools.php";
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
      $user = new User($row->user_id);
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
    $issue = new Issue($bugid);
    
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


// Connect DB
$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die(T_("Could not connect to database"));
mysql_select_db($db_mantis_database) or die("Could not select database");


$user = new User($userid);
$lTeamList = $user->getLeadedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $lTeamList + $managedTeamList;
$weekid = isset($_POST[weekid]) ? $_POST[weekid] : date('W');

$query = "SELECT name FROM `codev_team_table` WHERE id = $teamid";
$result = mysql_query($query) or die("Query failed: $query");
$teamName  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : $teamid;


if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
	echo T_("Sorry, you do NOT have access to this page.");
   echo "</div>";
	
} else {

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
		   echo "<b>- ".T_("Export Managed Issues")."...</b><br/>\n";
		   flush(); // envoyer tout l'affichage courant au navigateur 
		   
         $myFile = $path."\AOI-PIL-Mantis_".date("Ymd").".csv";
		   $filename = exportManagedIssuesToCSV($startTimestamp, $endTimestamp, $myFile);
	      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$myFile<br/>\n";
	      flush(); 
		   
	      // -----------------------------
	      echo "<br/>\n";
	      echo "<b>- ".T_("Export Week ").$weekid.T_(" Member Activity")."...</b><br/>\n";
	      flush(); // envoyer tout l'affichage courant au navigateur 
	      
	      
         $myFile = $codevReportsDir."\AOI-PIL-CRA_".$teamName."_".date("Y", $timeTracking->startTimestamp)."_W".sprintf('%02d',$weekid).".csv";
	      exportWeekActivityReportToCSV($teamid, $weekDates, $timeTracking, $myFile);
	      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$myFile<br/>\n";
	      flush(); 
	      
         // -----------------------------
         echo "<br/>\n";
         echo "<b>- ".T_("Export Week ").$weekid.T_(" Projects Activity")."...</b><br/>\n";
         flush(); 
         
         $myFile = $codevReportsDir."\AOI-PIL-Projects_".$teamName."_".date("Y", $timeTracking->startTimestamp)."_W".sprintf('%02d',$weekid).".csv";
         exportProjectActivityToCSV($timeTracking, $myFile);
         echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$myFile<br/>\n";
         flush(); 

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
