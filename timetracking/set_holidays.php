<?php if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
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
   $_POST[page_name] = T_("Add Holidays"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_holidays.inc.php'; ?>


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
  
   function addHolidays(){

      // TODO check date1 < date2

      // check fields
      foundError = 0;
      msgString = "Les champs suivants ont ete oublies:\n\n"

      if (0 == document.forms["form1"].bugid.value)     { msgString += "Fiche\n"; ++foundError; }
      if (0 == document.forms["form1"].job.value)       { msgString += "Poste\n";  ++foundError; }

      if (0 == foundError) {
        document.forms["form1"].action.value="addHolidays";
        document.forms["form1"].submit();
      } else {
        alert(msgString);
      }
    }
  
</script>

<div id="content">

<?php

#include_once "project.class.php";

include_once "time_tracking.class.php";
require_once('tc_calendar.php');


// --------------------------------------------------------------
function setUserForm($originPage) {
  global $accessLevel_dev;
  global $accessLevel_manager;

  $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
  $teamList = $session_user->getLeadedTeamList();

  // separate list elements with ', '
  $formatedTeamString = valuedListToSQLFormatedString($teamList);

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

  echo "<input type=hidden name=nextForm    value=addHolidaysForm>\n";

  echo "</form>\n";
  echo "</div>";
}

// --------------------------------------------------------------
function displayHolidaySelectionForm($user1, $defaultDate1, $defaultDate2, $defaultBugid, $defaultProjectid, $originPage) {

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
	
   // Display form
   echo "<div style='text-align: center;'>";
   echo "<form name='form1' method='post' Action='$originPage'>\n";

   echo T_("From").": ";
   $myCalendar1->writeScript();
   echo "&nbsp;";
   echo T_("To").": ";
   $myCalendar2->writeScript();
   $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);
   
   // Project list
   echo "&nbsp;";
   echo "&nbsp;";
   echo "&nbsp;";
   echo "&nbsp;";
   
   // --- SideTasks Project List
   $devProjList = $user1->getProjectList();
   $managedProjList = $user1->getProjectList($user1->getManagedTeamList());
   $projList = $devProjList + $managedProjList;

   foreach ($projList as $pid => $pname) {
      // we want only SideTasks projects
      $tmpPrj = ProjectCache::getInstance()->getProject($pid);
      if (!$tmpPrj->isSideTasksProject()) { unset($projList[$pid]); }
   }
   
   
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
   echo "<select name='bugid' style='width: 300px;' onchange='javascript: setBugId()' title='".T_("Task")."'>\n";
   echo "<option value='0'></option>\n";

   foreach ($issueList as $bugid) {
         $issue = IssueCache::getInstance()->getIssue($bugid);
      if ($issue->isVacation()) {
         if ($bugid == $defaultBugid) {
            echo "<option selected value='".$bugid."'>".$bugid." / $issue->tcId : $issue->summary</option>\n";
         } else {
            echo "<option value='".$bugid."'>".$bugid." / $issue->tcId : $issue->summary</option>\n";
         }
      }
   }
   echo "</select>\n";


   // --- Job list
   if (0 != $project1->id) {
      $jobList = $project1->getJobList();
   } else {
   	$jobList = array();
   	foreach ($projList as $pid => $pname) {
   		$tmpPrj = ProjectCache::getInstance()->getProject($pid);
   		$jobList += $tmpPrj->getJobList(); 
   	}
   }
   
   // do not display selector if only one Job
   if (1 == count($jobList)) {
   	reset($jobList);
   	echo "<input type=hidden name=job    value=".key($jobList).">\n";
   } else {
      echo "<select name='job' title='".T_("Job")."' style='width: 100px;' >\n";
      echo "<option value='0'></option>\n";
      foreach ($jobList as $jid => $jname)
      {
         echo "<option value='".$jid."'>$jname</option>\n";
      }
      echo "</select>";
   }
   
   // ---
   echo "&nbsp;&nbsp;";
   
   echo "<input type=button name='btAddHolidays' value='".T_("Add")."' onClick='javascript: addHolidays()'>\n";

   echo "<input type=hidden name=userid    value=$user1->id>\n";
   echo "<input type=hidden name=year      value=$curYear>\n";
   echo "<input type=hidden name=projectid value=$defaultProjectid>\n";
   echo "<input type=hidden name=trackid   value=unknown1>\n";

   echo "<input type=hidden name=action       value=noAction>\n";
   echo "<input type=hidden name=nextForm     value=addHolidaysForm>\n";
   echo "</form>\n";

   echo "</div>";
}




// =========== MAIN ==========
$originPage = "set_holidays.php";

$userid = isset($_POST[userid]) ? $_POST[userid] : $_SESSION['userid'];
$managed_user = UserCache::getInstance()->getUser($userid);

$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
$teamList = $session_user->getLeadedTeamList();


// dates
if (isset($_REQUEST["date1"])) {
   $date1          = $_REQUEST["date1"];
   $startTimestamp = date2timestamp($date1);
} else {
   $date1          = date("Y-m-d");
   $endTimestamp   = date2timestamp($date1);
}
if (isset($_REQUEST["date2"])) {
   $date2          = $_REQUEST["date2"];
   $endTimestamp   = date2timestamp($date2);
} else {
   $date2          = date("Y-m-d");
   $endTimestamp   = date2timestamp($date2);
}



// if first call to this page
if (!isset($_POST[nextForm])) {
  if (0 != count($teamList)) {
    // User is TeamLeader, let him choose the user he wants to manage
    setUserForm($originPage);
  } else {
   // developper & manager can add timeTracks
   $mTeamList = $session_user->getTeamList();
   $managedTeamList = $session_user->getManagedTeamList();
   $teamList = $mTeamList + $managedTeamList;

   if (0 != count($teamList)) {
      $_POST[nextForm] = "addHolidaysForm";
   } else {
      echo "<div id='content'' class='center'>";
      echo (T_("Sorry, you need to be member of a Team to access this page."));
      echo "</div>";
   }
  }
}


if ($_POST[nextForm] == "addHolidaysForm") {
	
   $action = $_POST[action];
   $defaultDate  = $formatedDate= date("Y-m-d", time());

   $defaultBugid = isset($_POST[bugid]) ? $_POST[bugid] : 0;
   $defaultProjectid  = isset($_POST[projectid]) ? $_POST[projectid] : Config::getInstance()->getValue("defaultSideTaskProject");

   
   if ("addHolidays" == $action) {
   	
   	// TODO add tracks !
   	
    $bugid     = $_POST[bugid];
    $job       = $_POST[job];
    
    $holydays = new Holidays();
    
    // save to DB
    $timestamp = $startTimestamp;
    while ($timestamp <= $endTimestamp) {
      
      // check if not a fixed holiday
      if (!$holydays->isHoliday($timestamp)) {
      	
      	// TODO check existing timetracks on $timestamp and adjust duration
         $duration  = 1;
      	
      	echo "TAMERE  ".date("Y-m-d", $timestamp)." duration $duration job $job<br/>";
    	   #TimeTrack::create($managed_user->id, $bugid, $job, $timestamp, $duration);
      }

    	$timestamp = strtotime("+1 day",$timestamp);;
    }
    
    
    
   
   } elseif ("setProjectid" == $action) {

    // pre-set form fields
    $defaultProjectid  = $_POST[projectid];

   } elseif ("setBugId" == $action) {

    // --- pre-set form fields
    // find ProjectId to update categories
    $defaultBugid     = $_POST[bugid];
    $issue = IssueCache::getInstance()->getIssue($defaultBugid);
    $defaultProjectid  = $issue->projectId;

   }

   // --- display Add Form
   
   $userName = $managed_user->getRealname();
   echo "<h2 style='text-align: center;'>$userName</h2>\n";

   // display Track Form
   echo "<br/>";
   displayHolidaySelectionForm($managed_user, $date1, $date2, $defaultBugid, $defaultProjectid, $originPage);
   echo "<br/>";

   
	
}

















?>
</div>

<?php include 'footer.inc.php'; ?>
