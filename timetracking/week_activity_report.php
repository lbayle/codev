<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../login.php'\">login</a> to access this page.");
  exit;
} 
?>

<?php include '../header.inc.php'; ?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>

<h1>Activit&eacute; Hebdo</h1>


<script language="JavaScript">
  function submitForm() {
  // TODO: check teamid presence
    document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
    document.forms["form1"].weekid.value = document.getElementById('weekidSelector').value;
    document.forms["form1"].action.value = "updateWeekDisplay";
    document.forms["form1"].submit();
  }
</script>

<div id="content" class="center">

<?php 

include_once "../constants.php";
include_once "../tools.php";
include_once "../reports/issue.class.php";
include_once "../auth/user.class.php";
include_once "time_tracking.class.php";

function  displayWeekActivityReport($teamid, $weekid, $weekDates, $timeTracking) {
  #$user = new User($_SESSION['userid']);
        
  echo "<form id='form1' name='form1' method='post' action='week_activity_report.php'>\n";

  echo "Team: <select id='teamidSelector' name='teamidSelector'>\n";

  $query = "SELECT id, name FROM `codev_team_table` WHERE leader_id = ".$_SESSION['userid']." ORDER BY name";
  $result = mysql_query($query) or die("Query failed: $query");
   
  while($row = mysql_fetch_object($result))
  {
    // show only teams where logged user is teamLeader
    if ($row->id == $teamid) {
      echo "<option selected value='".$row->id."'>".$row->name."</option>\n";
    } else {
      echo "<option value='".$row->id."'>".$row->name."</option>\n";
    }
  }
  echo "</select>\n";

  echo "Week: <select id='weekidSelector' name='weekidSelector'>\n";
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

  echo "<input type=button value='Envoyer' onClick='javascript: submitForm()'>\n";

  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.realname ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $teamid ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.realname";   
   
  $result = mysql_query($query) or die("Query failed: $query");
   
  while($row = mysql_fetch_object($result))
  {
    echo "<div align='center'>\n";
    echo "<br/>";
    displayWeekDetails($weekid, $weekDates, $row->user_id, $timeTracking, $row->realname);
    echo "</div>";
  }
   
  echo "<input type=hidden name=teamid  value=1>\n";
  echo "<input type=hidden name=weekid  value=".date('W').">\n";
   
  echo "<input type=hidden name=action       value=noAction>\n";
  echo "<input type=hidden name=currentForm  value=weekActivityReport>\n";
  echo "<input type=hidden name=nextForm     value=weekActivityReport>\n";
  echo "</form>\n";
}

function displayWeekDetails($weekid, $weekDates, $userid, $timeTracking, $realname) {
  // PERIOD week
  //$thisWeekId=date("W");
        
  $weekTracks = $timeTracking->getWeekDetails($userid);
  echo "<table width='100%'>\n";
  //echo "<caption><b>Semaine $weekid</b> (du ".date("Y-m-d", $weekDates[1])." au ".date("Y-m-d", $weekDates[5]).")</caption>\n";
  echo "<caption>".$realname."</caption>\n";
  echo "<tr>\n";
  echo "<th width='50%'>Tache</th>\n";
  echo "<th width='7%'>Projet</th>\n";
  echo "<th width='10%'>Poste</th>\n";
  echo "<th width='10'>Lundi<br>".date("d F", $weekDates[1])."</th>\n";
  echo "<th width='10'>Mardi<br/>".date("d F", $weekDates[2])."</th>\n";
  echo "<th width='10'>Mercredi<br/>".date("d F", $weekDates[3])."</th>\n";
  echo "<th width='10'>Jeudi<br/>".date("d F", $weekDates[4])."</th>\n";
  echo "<th width='10'>Vendredi<br/>".date("d F", $weekDates[5])."</th>\n";
  echo "</tr>\n";
  foreach ($weekTracks as $bugid => $jobList) {
    $issue = new Issue($bugid);
    foreach ($jobList as $jobid => $dayList) {
                
      $query3  = "SELECT name FROM `codev_job_table` WHERE id=$jobid";
      $result3 = mysql_query($query3) or die("Query failed: $query3");
      $jobName = mysql_result($result3, 0);
                
      echo "<tr>\n";
      echo "<td>$bugid / ".$issue->tcId." : ".$issue->summary."</td>\n";
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

// ================ MAIN =================
$year = date('Y');
$defaultTeam = $_SESSION[teamid];

$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");

$action = $_POST[action];
$weekid = isset($_POST[weekid]) ? $_POST[weekid] : date('W');

$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;

$weekDates      = week_dates($weekid,$year);
   
$startTimestamp = $weekDates[1];        
$endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[5]), date("d", $weekDates[5]), date("Y", $weekDates[5])); 
$timeTracking   = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

displayWeekActivityReport($teamid, $weekid, $weekDates, $timeTracking);
   
//displayCheckWarnings($userid);
?>

</div>

<?php include '../footer.inc.php'; ?>