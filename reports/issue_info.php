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
   $_POST[page_name] = T_("Activity by task"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>


<script language="JavaScript">
  function submitForm() {
    document.forms["form1"].bugid.value = document.getElementById('bugidSelector').value;
    document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
    document.forms["form1"].action.value = "displayBug";
    document.forms["form1"].submit();
  }

  function setProjectid() {
     document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
     document.forms["form1"].action.value="setProjectid";
     document.forms["form1"].submit();
  }

  
</script>

<div id="content">

<?php

include_once "constants.php";
include_once "tools.php";
include_once "issue.class.php";
include_once "project.class.php";
include_once "time_track.class.php";
include_once "user.class.php";
include_once "jobs.class.php";
include_once "holidays.class.php";

// ---------------------------------------------------------------
function displayIssueSelectionForm($originPage, $user1, $defaultBugid, $defaultProjectid) {
   
   // Display form
   echo "<div style='text-align: center;'>";
   echo "<form name='form1' method='post' Action='$originPage'>\n";

   $project1 = new Project($defaultProjectid);
   
   // Project list
   echo "&nbsp;";
   echo "&nbsp;";

   // --- Project List
   // All projects from teams where I'm a Developper
   $devProjList = $user1->getProjectList(); 
   
   // All projects from Teams where I'm a Manager
   $managedProjList = $user1->getProjectList($user1->getManagedTeamList());
   
   $projList = $devProjList + $managedProjList;
   #echo "<table class='invisible'>\n";
   #echo "<tr>\n";
   #echo "<td>\n";
   
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
   #echo "</td>\n";
   
   #echo "&nbsp;";
   
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
   #echo "<td>\n";
   echo "<select id='bugidSelector' name='bugidSelector' style='width: 600px;' title='".T_("Task")."'>\n";
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
   #echo "</td>\n";
   
   #echo "<td>\n";
   echo "<input type=button value='".T_("Jump")."' onClick='javascript: submitForm()'>\n";
   #echo "</td>\n";
/*   
   if (0 != $defaultBugid) {
      #echo "<td>\n";
   	echo "&nbsp;";
   	echo "".mantisIssueURL($defaultBugid, NULL, TRUE);
      #echo "</td>\n";
   }
*/   
   #echo "</tr>\n";
   #echo "</table>\n";
   
   echo "<input type=hidden name=bugid  value=$defaultBugid>\n";
   echo "<input type=hidden name=projectid value=$defaultProjectid>\n";
   echo "<input type=hidden name=action       value=noAction>\n";
   echo "</form>\n";
   
   echo "</div>";
}


// ---------------------------------------------------------------
function displayIssueGeneralInfo($issue, $displaySupport=false ) {      
  
   global $ETA_balance;
	global $job_support;
	
  echo "<br/>\n";
  echo "<br/>\n";
  
  echo "<table>\n";
  echo "<tr>\n";
  echo "  <th>".T_("Duration")."</th>\n";
  echo "  <th title='".T_("BEFORE analysis")."'>".T_("ETA")."</th>\n";
  echo "  <th title='".T_("AFTER analysis")."'>".T_("EffortEstim <br/>(BI + BS)")."</th>\n";
  echo "  </tr>\n";
  
  echo "<tr>\n";
  echo "<td title='BI + BS'>".T_("Estimated")."</th>\n";
  echo "<td title='".$ETA_balance[$issue->eta]."'>".$issue->getEtaName()."</td>\n";
  echo "<td title='$issue->effortEstim + $issue->effortAdd'>".($issue->effortEstim + $issue->effortAdd)."</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<td>".T_("Elapsed")."</td>\n";
  echo "<td></td>\n";
  echo "<td title='".T_("Support NOT included")."'>".($issue->elapsed - $issue->getElapsed($job_support))."</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<td>".T_("Remaining")."</td>\n";
  echo "<td></td>\n";
  echo "<td>$issue->remaining</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<td>".T_("Drift")."</td>\n";
  $deriveETA = $issue->getDriftETA(false);
  $derive = $issue->getDrift(false);
  echo "<td style='background-color: ".$issue->getDriftColor($deriveETA)."'>".$deriveETA."</td>\n";
  echo "<td style='background-color: ".$issue->getDriftColor($derive)."'>".$derive."</td>\n";
  echo "</tr>\n";
   
  if ($displaySupport) {
      echo "<tr>\n";
      echo "<td>".T_("Drift +Support")."</td>\n";
      $deriveETA = $issue->getDriftETA();
      $derive = $issue->getDrift();
      echo "<td style='background-color: ".$issue->getDriftColor($deriveETA)."'>".$deriveETA."</td>\n";
      echo "<td style='background-color: ".$issue->getDriftColor($derive)."'>".$derive."</td>\n";
      echo "</tr>\n";
  }      
  echo "</table>\n";

  // TimeDrift
  echo "<br/>\n";
  echo "<br/>\n";
  
  echo "<table>\n";
  echo "<tr>\n";
  echo "  <th>".T_("Dates")."</th>\n";
  echo "  <th></th>\n";
  echo "</tr>\n";
  
  echo "<tr>\n";
  echo "  <td>".T_("DeliveryDate")."</td>\n";
  if (NULL != $issue->deliveryDate) {
      echo "  <td>".date("d M Y", $issue->deliveryDate)."</td>\n";
  } else {
      echo "  <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
  }
  echo "</tr>\n";
  
  echo "<tr>\n";
  echo "  <td>".T_("DeadLine")."</td>\n";
  if (NULL != $issue->deadLine) {
      echo "  <td>".date("d M Y", $issue->deadLine)."</td>\n";
  } else {
      echo "  <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
  }
  echo "</tr>\n";
  echo "<tr>\n";
  echo "  <td>".T_("Drift")."</td>\n";
  $timeDrift=$issue->getTimeDrift();
  if (!is_string($timeDrift)) {
      echo "  <td style='background-color: ".$issue->getDriftColor($timeDrift)."'>$timeDrift ".T_("days")."</td>\n";
  } else {
      echo "  <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
  }
  echo "</tr>\n";
  echo "</table>\n";
  
  
  
}

// ---------------------------------------------------------------
function displayJobDetails($issue) {
   
	$timeTracks = $issue->getTimeTracks();
   $durationByJob = array();
   $jobs = new Jobs();
   
   echo "<table>\n";
   echo "<tr>\n";
   echo "<th>".T_("Job")."</th>\n";
   echo "<th>".T_("Nb Days")."</th>\n";
   echo "<th></th>\n";
   echo "</tr>\n";

   foreach ($timeTracks as $tid => $tdate) {
      $tt = new TimeTrack($tid);
      $durationByJob[$tt->jobId] += $tt->duration;
      $totalDuration += $tt->duration;
   }
  
   #sort($durationByJob);
   foreach ($durationByJob as $jid => $duration) {
      echo "<tr>\n";
      echo "   <td style='background-color: ".$jobs->getJobColor($jid)."'>".$jobs->getJobName($jid)."</td>\n";
      echo "<td>$duration</td>\n";
      echo "<td>".number_format(($duration*100 / $totalDuration), 2)." %</td>\n";
      echo "</tr>\n";
   }
  echo "</table>\n";      
  
}
// ---------------------------------------------------------------
function displayMonth($month, $year, $issue) {

  $holidays = new Holidays();
  
  $jobs = new Jobs();
  
  // if no work done this month, do not display month
  $trackList = $issue->getTimeTracks();
  $found = 0;
  foreach ($trackList as $tid => $tdate) {
    if (($month == date('m', $tdate)) && 
        ($year  == date('Y', $tdate))) {
      $found += 1;
      break; 
    }
  }
  if (0 == $found) { return; }
   
  $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
  $monthFormated = date("F Y", $monthTimestamp); 
  $nbDaysInMonth = date("t", $monthTimestamp);

  echo "<div class='center'>\n";
  echo "<table width='70%'>\n";
  echo "<caption>$monthFormated</caption>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  for ($i = 1; $i <= $nbDaysInMonth; $i++) {
    if ($i < 10 ) {
      echo "<th>0$i</th>\n";
    }
    else {
      echo "<th>$i</th>\n";
    }
  }
  echo "</tr>\n";
   
  $userList = $issue->getInvolvedUsers();
  foreach ($userList as $uid => $username) {
    
    // build $durationByDate[] for this user	
    $userTimeTracks = $issue->getTimeTracks($uid);
    $durationByDate = array();
    $jobColorByDate = array();
    foreach ($userTimeTracks as $tid => $tdate) {
      $tt = new TimeTrack($tid);
    	$durationByDate[$tdate] += $tt->duration;
    	$jobColorByDate[$tdate] = $jobs->getJobColor($tt->jobId);
    }

   // ------
    echo "<tr>\n";
    echo "<td>$username</td>\n";
        
    for ($i = 1; $i <= $nbDaysInMonth; $i++) {
      $todayTimestamp = mktime(0, 0, 0, $month, $i, $year);
      $dayOfWeek = date("N", $todayTimestamp);
      
      if (NULL != $durationByDate[$todayTimestamp]) {
        echo "<td style='background-color: ".$jobColorByDate[$todayTimestamp]."; text-align: center;'>".$durationByDate[$todayTimestamp]."</td>\n";
      } else {
        // if weekend or holiday, display gray
        $h = $holidays->isHoliday($todayTimestamp);
        if (NULL != $h) {
            echo "<td style='background-color: $holidays->defaultColor;' title='$h->description'></td>\n";
        } else {
            echo "<td></td>\n";
        }
      }
    }
    echo "</tr>\n";
  }
  echo "</table>\n";
  echo "<br/><br/>\n";
}




// ================ MAIN =================
$year = date('Y');

// if 'support' is set in the URL, display graphs for 'with/without Support'
$displaySupport  = isset($_GET['support']) ? true : false;
$originPage = isset($_GET['support']) ? "issue_info.php?support" : "issue_info.php"; 


$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die("Could not connect database : ".mysql_error());
mysql_select_db($db_mantis_database) or die("Could not select database : ".mysql_error());

$action           = $_POST[action];
$session_userid   = isset($_POST[userid]) ? $_POST[userid] : $_SESSION['userid'];
$bug_id           = isset($_POST[bugid])  ? $_POST[bugid] : 0;
$defaultProjectid = isset($_POST[projectid]) ? $_POST[projectid] : 0;


// if bugid is set in the URL, display directly
 if (isset($_GET['bugid'])) {
 	$bug_id = $_GET['bugid'];
 	$action = "displayBug";
 }


$user = UserCache::getInstance()->getUser($session_userid);

$dTeamList = $user->getDevTeamList();
$lTeamList = $user->getLeadedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $dTeamList + $lTeamList + $managedTeamList;

if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
	echo T_("Sorry, you need to be member of a Team to access this page.");
   echo "</div>";

} else {

	displayIssueSelectionForm($originPage, $user, $bug_id, $defaultProjectid);
	
	if ("displayBug" == $action) {
	  $issue = new Issue ($bug_id);
	        
	  echo "<br/><br/>\n";
     echo "<div id='content'' class='center'>";
	  echo "<hr width='80%'/>\n";
     echo "<br/>";
     echo "<h2>$issue->summary</h2>\n";  
     echo "".mantisIssueURL($issue->bugId)." / <span title='".T_("TC issue")."'>$issue->tcId</span><br/>\n";  
     echo "<br/>";
     echo "<b>".$issue->getCurrentStatusName()."</b>\n";
     echo "</div>";
     echo "<br/>";
     echo "<br/>";
     echo "<br/>";
     echo "<br/>";
     displayIssueGeneralInfo($issue, $displaySupport);
	  echo "<br/><br/>\n";
	  
	  displayJobDetails($issue);
     echo "<br/><br/>\n";
     
	  for ($y = date('Y', $issue->dateSubmission); $y <= $year; $y++) {
         for ($m = 1; $m <= 12; $m++) {
            displayMonth($m, $y, $issue);
         }
	  }
	
	  echo "<br/><br/>\n";
	} elseif ("setProjectid" == $action) {

    // pre-set form fields
    $defaultProjectid  = $_POST[projectid];
	} 
	
}
?>

</div>

<?php include 'footer.inc.php'; ?>
