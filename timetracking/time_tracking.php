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

<?php
include_once '../path.inc.php';
include_once 'i18n.inc.php';

if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}

$_POST['page_name'] = T_("Time Tracking");
include 'header.inc.php';

include_once 'tools.php';
include 'login.inc.php';
include 'menu.inc.php';

// ----
include_once "issue.class.php";
include_once "project.class.php";
include_once "user.class.php";
include_once "time_tracking.class.php";
include_once "time_tracking_tools.php";
require_once('tc_calendar.php');
?>

<style>
   fieldset { padding:0; border:0; }
   validateTips { border: 1px solid transparent; padding: 0.3em; }
</style>

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

  function submitWeekid() {
    document.forms["form1"].weekid.value = document.getElementById('weekidSelector').value;
    document.forms["form1"].year.value = document.getElementById('yearSelector').value;
    document.forms["form1"].action.value="updateWeekDisplay";
    document.forms["form1"].submit();
  }

  function previousWeek() {
     weekid = document.getElementById('weekidSelector').value;
     year   = document.getElementById('yearSelector').value;

     if (1 != weekid) {
       document.forms["form1"].weekid.value = --weekid;
       document.forms["form1"].year.value = year;
     }

     document.forms["form1"].action.value="updateWeekDisplay";
     document.forms["form1"].submit();
   }

  function nextWeek() {
     weekid = document.getElementById('weekidSelector').value;
     year   = document.getElementById('yearSelector').value;

     if (weekid <= 52) {
       document.forms["form1"].weekid.value = ++weekid;
       document.forms["form1"].year.value = year;
     } else {
        document.forms["form1"].weekid.value = 1;
        document.forms["form1"].year.value = ++year;
     }

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

  function setBugId() {
     // if projectId not set: do it, to update categories
     if (0 == document.getElementById('projectidSelector').value) {
        document.forms["form1"].action.value="setBugId";
        document.forms["form1"].submit();
     }
   }

   
   // ------ JQUERY ------
	$(function() {

		var bugid = $( "#bugid" ),
		    remaining = $( "#remaining" ),
			 allFields = $( [] ).add( remaining ),
			 tips = $( "#validateTips" );

		function updateTips( t ) {
			tips
				.text( t )
				.addClass( "ui-state-highlight" );
			setTimeout(function() {
				tips.removeClass( "ui-state-highlight", 1500 );
			}, 500 );
		}

		function checkRegexp( o, regexp, n ) {
			if ( !( regexp.test( o.val() ) ) ) {
				o.addClass( "ui-state-error" );
				updateTips( n );
				return false;
			} else {
				return true;
			}
		}

		$( "#update_remaining_dialog_form" ).dialog({
			autoOpen: false,
			height: 200,
			width: 500,
			modal: true,
			open: function() { 
               // Select input field contents 
               $( "#remaining" ).select(); 
			},
			buttons: {
				"Update": function() {
					var bValid = true;
					allFields.removeClass( "ui-state-error" );
					bValid = bValid && checkRegexp( remaining, /^[0-9]+(\.[0-9]5?)?$/i, "format: '1','0.3' or '1.55'" );

					if ( bValid ) {
						// here, use AJAX to call php func and update remaining on bugid
						//$( this ).dialog( "close" );
						$( "#action" ).val("updateRemainingAction");
						$('#formUpdateRemaining').submit();
					}
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			},
			close: function() {
				allFields.val( "" ).removeClass( "ui-state-error" );
			}
		});
	});


</script>

<div id="content">


<div id="update_remaining_dialog_form" title="Task XXX - Update Remaining" style='display: none'>
	<p id="validateTips">Set new value</p>
	<form id='formUpdateRemaining' name='formUpdateRemaining' method='post' Action='time_tracking.php' >
	   <fieldset>
		   <label for="remaining">Remaining: </label>
		   <input type='text'  id='remaining' name='remaining' size='3' class='text' />
	   </fieldset>
	  <input type='hidden' id='userid'   name='userid'   value='0' >
      <input type='hidden' id='bugid'    name='bugid'    value='0' >
      <input type='hidden' id='action'   name='action'   value='noAction' >
      <input type='hidden' id='nextForm' name='nextForm' value='addTrackForm' >
	</form>
</div>


<?php

// --------------------------------------------------------------
function setUserForm($originPage) {
  $accessLevel_dev     = Team::accessLevel_dev;
  $accessLevel_manager = Team::accessLevel_manager;

  $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
  $teamList = $session_user->getLeadedTeamList();

  // separate list elements with ', '
  $formatedTeamString = implode( ', ', array_keys($teamList));

  // show only users from the teams that I lead.
  $query = "SELECT DISTINCT mantis_user_table.id, mantis_user_table.username, mantis_user_table.realname ".
    "FROM `mantis_user_table`, `codev_team_user_table` ".
    "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
    "AND   codev_team_user_table.team_id IN ($formatedTeamString) ".
    "AND   codev_team_user_table.access_level IN ($accessLevel_dev, $accessLevel_manager) ".
    "ORDER BY mantis_user_table.username";

  // create form
  echo "<div align=center>";
  echo "<form id='formUserAndPeriodSelect' name='formUserAndPeriodSelect' method='post' action='$originPage'>\n";

  echo T_("Name")." :\n";
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

  echo "<input type=button value='".T_("Jump")."' onClick='javascript: submitUser()'>\n";

  echo "<input type=hidden name=weekid  value=".date('W').">\n";
  echo "<input type=hidden name=year    value=".date('Y').">\n";

  echo "<input type=hidden name=currentForm value=setUserAndPeriodForm>\n";
  echo "<input type=hidden name=nextForm    value=addTrackForm>\n";

  echo "</form>\n";
  echo "</div>";
}

// --------------------------------------------------------------
function addTrackForm($weekid, $curYear, $user1, $defaultDate, $defaultBugid, $defaultProjectid, $originPage) {

   list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate);

   $myCalendar = new tc_calendar("date1", true, false);
   $myCalendar->setIcon("../calendar/images/iconCalendar.gif");
   $myCalendar->setDate($defaultDay, $defaultMonth, $defaultYear);
   $myCalendar->setPath("../calendar/");
   $myCalendar->setYearInterval(2010, 2015);
   $myCalendar->dateAllow('2010-01-01', '2015-12-31');
   $myCalendar->setDateFormat('Y-m-d');
   $myCalendar->startMonday(true);

   // Display form
   echo "<div style='text-align: center;'>";
   echo "<form name='form1' method='post' Action='$originPage'>\n";

   #echo "Date: \n";
   $myCalendar->writeScript();

   $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);

   // Project list
   echo "&nbsp;";
   echo "&nbsp;";

   // --- Project List
   // All projects from teams where I'm a Developper
   $devProjList = $user1->getProjectList();

   // SideTasksProjects from Teams where I'm a Manager
   $managedProjList = $user1->getProjectList($user1->getManagedTeamList());
   foreach ($managedProjList as $pid => $pname) {
   	// we want only SideTasks and NoStatsProject of projects that I manage
   	$tmpPrj = ProjectCache::getInstance()->getProject($pid);
      if (!$tmpPrj->isSideTasksProject() && !$tmpPrj->isNoStatsProject()) { unset($managedProjList[$pid]); }
   }

   $projList = $devProjList + $managedProjList;

   echo "<select id='projectidSelector' name='projectidSelector' onchange='javascript: setProjectid()' title='".T_("Project")."'>\n";
   echo "<option value='0'>".T_("(all)")."</option>\n";
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
	    $formatedProjList = implode( ', ', array_keys($projList));

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
   echo "<select name='bugid' style='width: 600px;' onchange='javascript: setBugId()' title='".T_("Task")."'>\n";
   if (1 != count($issueList)) {
      echo "<option value='0'></option>\n";
   }

   foreach ($issueList as $bugid) {
         $issue = IssueCache::getInstance()->getIssue($bugid);
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
   echo "<select name='job' title='".T_("Job")."' style='width: 100px;' >\n";
   if (1 != count($jobList)) {
      echo "<option value='0'></option>\n";
   }
   foreach ($jobList as $jid => $jname)
   {
      echo "<option value='".$jid."'>$jname</option>\n";
   }
   echo "</select>\n";

   // --- Duration list
   echo " <select name='duree' title='".T_("Duration (in days)")."'>\n";
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
   echo "<option value='0.05'>0.05 (30min)</option>\n";
   echo "</select>\n";

   echo "<input type=button name='btAddTrack' value='".T_("Add")."' onClick='javascript: addTrack()'>\n";

   echo "<input type=hidden name=userid    value=$user1->id>\n";
   echo "<input type=hidden name=year      value=$curYear>\n";
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

$job_support = Config::getInstance()->getValue(Config::id_jobSupport);

//$year = date('Y');
$year = isset($_POST['year']) ? $_POST['year'] : date('Y');

$userid = isset($_POST['userid']) ? $_POST['userid'] : $_SESSION['userid'];
$managed_user = UserCache::getInstance()->getUser($userid);

$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
$teamList = $session_user->getLeadedTeamList();

// updateRemaining data
$bugid  = isset($_POST['bugid']) ? $_POST['bugid'] : '';
$remaining  = isset($_POST['remaining']) ? $_POST['remaining'] : '';

$action = isset($_POST["action"]) ? $_POST["action"] : '';
$weekid = isset($_POST['weekid']) ? $_POST['weekid'] : date('W');

// if first call to this page
if (!isset($_POST['nextForm'])) {
  if (0 != count($teamList)) {
    // User is TeamLeader, let him choose the user he wants to manage
    setUserForm("time_tracking.php");
  } else {
  	// developper & manager can add timeTracks
   $mTeamList = $session_user->getTeamList();
   $managedTeamList = $session_user->getManagedTeamList();
   $teamList = $mTeamList + $managedTeamList;

   if (0 != count($teamList)) {
  	   $_POST['nextForm'] = "addTrackForm";
   } else {
      echo "<div id='content'' class='center'>";
   	echo (T_("Sorry, you need to be member of a Team to access this page."));
      echo "</div>";
   }
  }
}

if ($_POST['nextForm'] == "addTrackForm") {
  
  $defaultDate  = $formatedDate= date("Y-m-d", time());
  $defaultBugid = 0;
  $defaultProjectid=0;

  $weekDates      = week_dates($weekid,$year);
  $startTimestamp = $weekDates[1];
  $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
  $timeTracking   = new TimeTracking($startTimestamp, $endTimestamp);

  if ("addTrack" == $action) {
    $formatedDate      = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : "";
    $timestamp = date2timestamp($formatedDate);
    $bugid     = $_POST['bugid'];
    $job       = $_POST['job'];
    $duration  = $_POST['duree'];
    $defaultProjectid  = $_POST['projectid'];

    // save to DB
    TimeTrack::create($managed_user->id, $bugid, $job, $timestamp, $duration);


    // do NOT decrease remaining if job is job_support !
    if ($job != $job_support) {
      // decrease remaining (only if 'remaining' already has a value)
      $issue = IssueCache::getInstance()->getIssue($bugid);
      if (NULL != $issue->remaining) {
         $remaining = $issue->remaining - $duration;
         if ($remaining < 0) { $remaining = 0; }
         $issue->setRemaining($remaining);
      }
    }

    // pre-set form fields
    $defaultDate  = $formatedDate;
    $defaultBugid = $bugid;

  } elseif ("deleteTrack" == $action) {
    $trackid  = $_POST['trackid'];

    // increase remaining (only if 'remaining' already has a value)
    $query = "SELECT bugid, jobid, duration FROM `codev_timetracking_table` WHERE id = $trackid;";
    $result = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    { // REM: only one line in result, while should be optimized
      $bugid = $row->bugid;
      $duration = $row->duration;
      $job = $row->jobid;
    }

    $issue = IssueCache::getInstance()->getIssue($bugid);
    // do NOT decrease remaining if job is job_support !
    if ($job != $job_support) {
      if (NULL != $issue->remaining) {
         $remaining = $issue->remaining + $duration;
         $issue->setRemaining($remaining);
      }
    }

    // delete track
    $query = "DELETE FROM `codev_timetracking_table` WHERE id = $trackid;";
    mysql_query($query) or die("Query failed: $query");

    // pre-set form fields
    $defaultBugid     = $_POST['bugid'];
    $defaultProjectid  = $issue->projectId;

  } elseif ("setProjectid" == $action) {

  	 // pre-set form fields
  	 $defaultProjectid  = $_POST['projectid'];
  	 $formatedDate      = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : "";
  	 $defaultDate = $formatedDate;

  } elseif ("setBugId" == $action) {

    // --- pre-set form fields
  	 // find ProjectId to update categories
    $defaultBugid     = $_POST['bugid'];
  	 $issue = IssueCache::getInstance()->getIssue($defaultBugid);
    $defaultProjectid  = $issue->projectId;
    $formatedDate      = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : "";
    $defaultDate = $formatedDate;

  }elseif ("updateRemainingAction" == $action) {
	$issue = IssueCache::getInstance()->getIssue($bugid);
	if (NULL != $issue->remaining) {
		$issue->setRemaining($remaining);
	}
	
  }elseif ("noAction" == $action) {
    echo "browserRefresh<br/>";
  } else {
    //echo "DEBUG: unknown action : $action<br/>";
  }

  // Display user name

  $userName = $managed_user->getRealname();
  echo "<h2 style='text-align: center;'>$userName</h2>\n";

  // display Track Form
  echo "<br/>";
  addTrackForm($weekid, $year, $managed_user, $defaultDate, $defaultBugid, $defaultProjectid, "time_tracking.php");
  echo "<br/>";

  displayWeekDetails($weekid, $weekDates, $managed_user->id, $timeTracking, $year);

  echo "<div class='center'>";
  displayCheckWarnings($userid);
  echo "</div>";

  echo "<br/>";
  echo "<br/>";
  displayTimetrackingTuples($userid, $startTimestamp);
}

?>

</div>

<?php include 'footer.inc.php'; ?>
