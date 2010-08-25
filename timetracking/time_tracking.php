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

<h1>CRA</h1>


<script language="JavaScript">
  function submitUser(){
    // check fields
    foundError = 0;
    msgString = "Les champs suivants ont &eacute;t&eacute; oubli&eacute;s:\n\n"
        
    if (0 == document.forms["formUserAndPeriodSelect"].userid.value)  { msgString += "Nom\n"; ++foundError; }
                   
    if (0 == foundError) {
      document.forms["formUserAndPeriodSelect"].submit();
    } else {
      alert(msgString);    
    }    
  }
    
  function submitWeekid(selector){
    document.forms["form1"].weekid.value = selector.value;
    document.forms["form1"].action.value="updateWeekDisplay";
    document.forms["form1"].submit();
  }
    
  function addTrack(){
    // check fields
    foundError = 0;
    msgString = "Les champs suivants ont &eacute;t&eacute; oubli&eacute;s:\n\n"
        
    if (0 == document.forms["form1"].bugid.value)  { msgString += "Tache\n"; ++foundError; }
    if (0 == document.forms["form1"].job.value)    { msgString += "Poste\n";  ++foundError; }
    if (0 == document.forms["form1"].duree.value)  { msgString += "Dur&eacute;e\n";  ++foundError; }
                   
    if (0 == foundError) {
      document.forms["form1"].action.value="addTrack";
      document.forms["form1"].submit();
    } else {
      alert(msgString);    
    }    
  }

  function deleteTrack(id, description){
    confirmString = "D&eacute;sirez-vous vraiment supprimer cette ligne ?\n\n" + description;
    if (confirm(confirmString)) {
      document.forms["form1"].action.value="deleteTrack";
      document.forms["form1"].trackid.value=id;
      document.forms["form1"].submit();
    }
  }
</script>

<div id="content">

<?php 

include_once "../constants.php";
include_once "../tools.php";
include_once "../reports/issue.class.php";
include_once "../auth/user.class.php";
include_once "time_tracking.class.php";
include_once "time_tracking_tools.php";

require_once('calendar/classes/tc_calendar.php');

function setUserForm($originPage) {
  $session_user = new User($_SESSION['userid']);
  $teamList = $session_user->getLeadedTeamList();
   
  // separate list elements with ', '
  $formatedTeamString = "";
  foreach ($teamList as $tid) {
    if ($formatedTeamString != "") { $formatedTeamString .= ', ';}
    $formatedTeamString .= $tid;
  }
      
  // show only users from the teams that I lead.
  $query = "SELECT DISTINCT mantis_user_table.id, mantis_user_table.username, mantis_user_table.realname ".
    "FROM `mantis_user_table`, `codev_team_user_table` ".
    "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
    "AND codev_team_user_table.team_id IN ($formatedTeamString) ".
    "ORDER BY mantis_user_table.username";

  // create form
  echo "<form id='formUserAndPeriodSelect' name='formUserAndPeriodSelect' method='post' action='$originPage'>\n";

  echo "Nom :\n";
  echo "<select name='userid'>\n";
  echo "<option value='0'></option>\n";
   
  $result = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {
    if ($row->id == $_SESSION['userid']) {
      echo "<option selected value='".$row->id."'>".$row->username."</option>\n";
    } else {
      echo "<option value='".$row->id."'>".$row->username."</option>\n";
    }
  }
  echo "</select>\n";

  echo "<input type=button value='Envoyer' onClick='javascript: submitUser()'>\n";
   
  echo "<input type=hidden name=weekid  value=".date('W').">\n";
   
  echo "<input type=hidden name=currentForm value=setUserAndPeriodForm>\n";
  echo "<input type=hidden name=nextForm    value=addTrackForm>\n";

  echo "</form>\n";
}

// ================ MAIN =================
$year = date('Y');

$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");

$userid = isset($_POST[userid]) ? $_POST[userid] : $_SESSION['userid'];

$session_user = new User($_SESSION['userid']);
$teamList = $session_user->getLeadedTeamList();

// if first call to this page
if (!isset($_POST[nextForm])) {
  if (0 != count($teamList)) {
    // User is TeamLeader, let him choose the user he wants to manage
    setUserForm("time_tracking.php");
  } else {
    $_POST[nextForm] = "addTrackForm";
  }
}

if ($_POST[nextForm] == "addTrackForm") {
  $action = $_POST[action];
  $weekid = isset($_POST[weekid]) ? $_POST[weekid] : date('W');
   
  $defaultDate  = $formatedDate= date("Y-m-d", time());
  $defaultBugid = 0;
           
  $weekDates      = week_dates($weekid,$year);
  $startTimestamp = $weekDates[1];        
  $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[5]), date("d", $weekDates[5]), date("Y", $weekDates[5])); 
  $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp);

  if ("addTrack" == $action) {
    $formatedDate      = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : "";
    $timestamp = date2timestamp($formatedDate);
    $bugid     = $_POST[bugid];
    $job       = $_POST[job];
    $duration  = $_POST[duree];
      
    // save to DB
    $query = "INSERT INTO `codev_timetracking_table`  (`userid`, `bugid`, `jobid`, `date`, `duration`) VALUES ('$userid','$bugid','$job','$timestamp', '$duration');";
    mysql_query($query) or die("Query failed: $query");

    // decrease remaining (only if 'remaining' already has a value)
    $issue = new Issue ($bugid);
    if (NULL != $issue->remaining) {
      $remaining = $issue->remaining - $duration;
      if ($remaining < 0) { $remaining = 0; }
      $issue->setRemaining($remaining);
    }

    // pre-set form fields
    $defaultDate  = $formatedDate;
    $defaultBugid = $bugid;

  } else if ("deleteTrack" == $action) {
    $trackid  = $_POST[trackid];

    // increase remaining (only if 'remaining' already has a value)
    $query = "SELECT bugid, duration FROM `codev_timetracking_table` WHERE id = $trackid;";
    $result = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    { // REM: only one line in result, while should be optimized
      $bugid = $row->bugid;
      $duration = $row->duration;
    }
    $issue = new Issue ($bugid);
    if (NULL != $issue->remaining) {
      $remaining = $issue->remaining + $duration;
      $issue->setRemaining($remaining);
    }

    // delete track
    $query = "DELETE FROM `codev_timetracking_table` WHERE id = $trackid;";
    mysql_query($query) or die("Query failed: $query");
  } else if ("noAction" == $action) {
    echo "browserRefresh<br/>";
  } else {
    //echo "DEBUG: unknown action : $action<br/>";
  }

  // display Track Form
  addTrackForm($weekid, $userid, $defaultDate, $defaultBugid, "time_tracking.php");

  displayWeekDetails($weekid, $weekDates, $userid, $timeTracking);
   
   echo "<div class='center'>";
  displayCheckWarnings($userid);
   echo "</div>";
  displayTimetrackingTuples($userid);
}

?>

</div>

<?php include '../footer.inc.php'; ?>
