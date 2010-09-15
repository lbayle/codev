<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
include_once "../tools.php";
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../'>login</a> to access this page.");
  
  exit;
} 
?>

<?php
   $_POST[page_name] = "Saisie des CRA"; 
   include '../header.inc.php'; 
?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>

<script language="JavaScript">
  function submitUser(){
    // check fields
    foundError = 0;
    msgString = "Les champs suivants ont ete oublies:\n\n"
        
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
    msgString = "Les champs suivants ont ete oublies:\n\n"
        
    //if (0 == document.forms["form1"].projectid.value) { msgString += "Projet\n"; ++foundError; }
    if (0 == document.forms["form1"].bugid.value)     { msgString += "Tache\n"; ++foundError; }
    if (0 == document.forms["form1"].job.value)       { msgString += "Poste\n";  ++foundError; }
    if (0 == document.forms["form1"].duree.value)     { msgString += "Duree\n";  ++foundError; }
                   
    if (0 == foundError) {
      document.forms["form1"].action.value="addTrack";
      document.forms["form1"].submit();
    } else {
      alert(msgString);    
    }    
  }

  function deleteTrack(trackid, description, bugid){
    confirmString = "D&eacute;sirez-vous vraiment supprimer cette ligne ?\n\n" + description;
    if (confirm(confirmString)) {
      document.forms["form1"].action.value="deleteTrack";
      document.forms["form1"].trackid.value=trackid;
      document.forms["form1"].bugid.value=bugid;
      document.forms["form1"].submit();
    }
  }

  function setProjectid() {
    document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
    document.forms["form1"].action.value="setProjectid";
    document.forms["form1"].submit();
  }

  
</script>

<div id="content">

<?php 

include_once "../constants.php";
include_once "../tools.php";
include_once "../reports/issue.class.php";
include_once "../reports/project.class.php";
include_once "../auth/user.class.php";
include_once "time_tracking.class.php";
include_once "time_tracking_tools.php";

require_once('calendar/classes/tc_calendar.php');

// --------------------------------------------------------------
function setUserForm($originPage) {
  $session_user = new User($_SESSION['userid']);
  $teamList = $session_user->getLeadedTeamList();
   
  // separate list elements with ', '
  $formatedTeamString = valuedListToSQLFormatedString($teamList);
  
  // show only users from the teams that I lead.
  $query = "SELECT DISTINCT mantis_user_table.id, mantis_user_table.username, mantis_user_table.realname ".
    "FROM `mantis_user_table`, `codev_team_user_table` ".
    "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
    "AND codev_team_user_table.team_id IN ($formatedTeamString) ".
    "ORDER BY mantis_user_table.username";

  // create form
  echo "<div align=center>";
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
  echo "</div>";
}

// --------------------------------------------------------------
function addTrackForm($weekid, $userid, $defaultDate, $defaultBugid, $defaultProjectid, $originPage) {
   
   list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate);

   $myCalendar = new tc_calendar("date1", true, false);
   $myCalendar->setIcon("calendar/images/iconCalendar.gif");
   $myCalendar->setDate($defaultDay, $defaultMonth, $defaultYear);
   $myCalendar->setPath("calendar/");
   $myCalendar->setYearInterval(2010, 2015);
   $myCalendar->dateAllow('2010-01-01', '2015-12-31');
   $myCalendar->setDateFormat('Y-m-d');
   $myCalendar->startMonday(true);

   // Display form
   echo "<div style='text-align: center;'>";
   echo "<form name='form1' method='post' Action='$originPage'>\n";

   #echo "Date: \n"; 
   $myCalendar->writeScript();

   $user1    = new User($userid);
   $project1 = new Project($defaultProjectid);
   
   // Project list
   echo "&nbsp;";
   echo "&nbsp;";
   #echo "Project: \n";
   echo "<select id='projectidSelector' name='projectidSelector' onchange='javascript: setProjectid()' title='Projet'>\n";
   echo "<option value='0'>(tous)</option>\n";
   
   $projList = $user1->getProjectList();
   foreach ($projList as $pid => $pname)
   {
      if ($pid == $defaultProjectid) {
         echo "<option selected value='".$pid."'>$pname</option>\n";
      } else {
         echo "<option value='".$pid."'>$pname</option>\n";
               }
   }
   echo "</select>\n";

   echo "&nbsp;";
   
   // --- Task list
   if (0 != $project1->id) {
      $issueList = $project1->getIssueList();
   } else {
   	 // no project specified: show all tasks
       $issueList = array();
	    $formatedProjList = valuedListToSQLFormatedString($projList);
	    
       $query  = "SELECT id ".
                 "FROM `mantis_bug_table` ".
                 "WHERE project_id IN ($formatedProjList) ".
                 "ORDER BY id DESC";
       $result = mysql_query($query) or die("Query failed: $query");
         if (0 != mysql_num_rows($result)) {
            while($row = mysql_fetch_object($result))
            {
               $issueList[] = $row->id;
            }
       }
   }
   echo "<select name='bugid' style='width: 600px;' title='Tache'>\n";
   echo "<option value='0'></option>\n";

   foreach ($issueList as $bugid) {
         $issue = new Issue ($bugid);
      if ($bugid == $defaultBugid) {
         echo "<option selected value='".$bugid."'>".$bugid." / $issue->tcId : $issue->summary</option>\n";
      } else {
         echo "<option value='".$bugid."'>".$bugid." / $issue->tcId : $issue->summary</option>\n";
      }
   }
   echo "</select>\n";
   
   
   // --- Job list
   if (0 != $project1->id) {
      $jobList = $project1->getJobList();
   } else {
      $query  = "SELECT id, name FROM `codev_job_table` ";
   	$result = mysql_query($query) or die("Query failed: $query");
         if (0 != mysql_num_rows($result)) {
            while($row = mysql_fetch_object($result))
            {
               $jobList[$row->id] = $row->name;
            }
       }
   }
   echo "<select name='job' title='Poste' style='width: 100px;' >\n";
   if (1 != count($jobList)) {
      echo "<option value='0'></option>\n";
   }
   foreach ($jobList as $jid => $jname)
   {
      echo "<option value='".$jid."'>$jname</option>\n";
   }
   echo "</select>\n";

   // --- Duration list
   echo " <select name='duree' title='Dur&eacute;e (en jours)'>\n";
   echo "<option value='0'></option>\n";
   echo "<option value='1'>1</option>\n";
   echo "<option value='0.9'>0.9</option>\n";
   echo "<option value='0.8'>0.8</option>\n";
   echo "<option value='0.75'>0.75</option>\n";
   echo "<option value='0.7'>0.7</option>\n";
   echo "<option value='0.6'>0.6</option>\n";
   echo "<option value='0.5'>0.5 (4h)</option>\n";
   echo "<option value='0.4'>0.4 (3h)</option>\n";
   echo "<option value='0.3'>0.3 (2h 30)</option>\n";
   echo "<option value='0.25'>0.25 (2h)</option>\n";
   echo "<option value='0.2'>0.2 (1h 30)</option>\n";
   echo "<option value='0.1'>0.1 (1h)</option>\n";
   echo "</select>\n";

   echo "<input type=button name='btAddTrack' value='Ajouter' onClick='javascript: addTrack()'>\n";

   echo "<input type=hidden name=userid    value=$userid>\n";
   echo "<input type=hidden name=weekid    value=$weekid>\n";
   echo "<input type=hidden name=projectid value=$defaultProjectid>\n";
   echo "<input type=hidden name=trackid   value=unknown1>\n";

   echo "<input type=hidden name=action       value=noAction>\n";
   echo "<input type=hidden name=currentForm  value=addTrackForm>\n";
   echo "<input type=hidden name=nextForm     value=addTrackForm>\n";
   echo "</form>\n";
   
   echo "</div>";
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
  $defaultProjectid=0;
           
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
    $defaultProjectid  = $_POST[projectid];
        
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

  } elseif ("deleteTrack" == $action) {
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
    
    // pre-set form fields
    $defaultBugid     = $_POST[bugid];
    
  } elseif ("setProjectid" == $action) {

  	 // pre-set form fields
  	 $defaultProjectid  = $_POST[projectid];
  	 $formatedDate      = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : "";
  	 $defaultDate = $formatedDate;
  	 
  }elseif ("noAction" == $action) {
    echo "browserRefresh<br/>";
  } else {
    //echo "DEBUG: unknown action : $action<br/>";
  }

  // Display user name
  $query = "SELECT realname FROM `mantis_user_table` WHERE id = $userid";
  $result = mysql_query($query) or die("Query failed: $query");
  $userName    = mysql_result($result, 0);
  echo "<h2 style='text-align: center;'>$userName</h2>\n";
  
  // display Track Form
  echo "<br/>";
  addTrackForm($weekid, $userid, $defaultDate, $defaultBugid, $defaultProjectid, "time_tracking.php");
  echo "<br/>";
   
  displayWeekDetails($weekid, $weekDates, $userid, $timeTracking);
   
  echo "<div class='center'>";
  displayCheckWarnings($userid);
  echo "</div>";
  displayTimetrackingTuples($userid);
}

?>

</div>

<?php include '../footer.inc.php'; ?>
