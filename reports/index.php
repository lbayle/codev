<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../'\">login</a> to access this page.");
  exit;
} 
?>

<?php
   $_POST[page_name] = "Suivi des fiches Mantis"; 
   include '../header.inc.php'; 
?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>

<script language="JavaScript">
  function submitTeam() {
    // check fields
    foundError = 0;
    msgString = "Les champs suivants ont &eacute;t&eacute; oubli&eacute;s:\n\n"
        
    if (0 == document.forms["teamSelectForm"].teamid.value)  { msgString += "Team\n"; ++foundError; }
                   
    if (0 == foundError) {
      document.forms["teamSelectForm"].submit();
    } else {
      alert(msgString);    
    }    
  }
</script>

<div id="content">

<?php

//
// MANTIS CoDev History Reports
//
include_once "../constants.php";
include_once "../tools.php";
include_once "../auth/user.class.php";
include_once "issue.class.php";
include_once "period_stats_report.class.php";
include_once "issue_tracking.class.php";
include_once "issue_tracking_fdj.class.php";


function setTeamForm($originPage, $defaultSelection, $teamList) {
   
  // create form
  echo "<div align=center>\n";
  echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage' onchange='javascript: submitTeam()'>\n";

  echo "Team :\n";
  echo "<select name='teamid'>\n";
  echo "<option value='0'></option>\n";

   foreach ($teamList as $tid => $tname) {
  
    if ($tid == $defaultSelection) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";

  echo "<input type=hidden name=currentForm value=teamSelectForm>\n";
  echo "<input type=hidden name=nextForm    value=editTeamForm>\n";

  echo "</form>\n";
  echo "</div>\n";
}


// ================ MAIN ================
// TODO: get values from HTML fields
$start_year = date('Y');


$defaultTeam = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;


// Connect DB
$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");


$session_user = new User($_SESSION['userid']);
$teamList = $session_user->getMemberAndLeadedTeamList();


$bugList = array();


// ---------- DISPLAY -------------

setTeamForm("index.php", $teamid, $teamList);

echo "<br/>";
echo "<br/>";

if (0 != $teamid) {

	$periodStatsReport = new PeriodStatsReport($start_year, $teamid);
	$periodStatsReport->computeReport();
	$periodStatsReport->displayHTMLReport();
	
   echo "<br/>";
	echo "<br/>";
	echo "<br/>";
	$issueTracking = new IssueTrackingFDJ($teamid);
	$issueTracking->initialize();
	$issueTracking->forseingTableDisplay();
	
   echo "<br/>";
	echo "<br/>";
	echo "<br/>";
	$issueTracking->durationsTableDisplay();
	
}
// Fermeture de la connexion
mysql_close($link);
//exit;
?>

</div>

<?php include '../footer.inc.php'; ?>
