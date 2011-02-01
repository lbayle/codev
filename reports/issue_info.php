<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../'\">login</a> to access this page.");
  exit;
}
?>

<?php
   include_once 'i18n.inc.php';
   $_POST[page_name] = "Activit&eacute; par t&acirc;che"; 
   include '../header.inc.php'; 
?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>


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

include_once "../constants.php";
include_once "../tools.php";
include_once "issue.class.php";
include_once "project.class.php";
include_once "time_track.class.php";
include_once "user.class.php";
include_once "jobs.class.php";

// ---------------------------------------------------------------
function displayIssueSelectionForm($user1, $defaultBugid, $defaultProjectid) {
   
   // Display form
   echo "<div style='text-align: center;'>";
   echo "<form name='form1' method='post' Action='issue_info.php'>\n";

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
   
   echo "<select id='projectidSelector' name='projectidSelector' onchange='javascript: setProjectid()' title='".T_("Project")."'>\n";
   echo "<option value='0'>(tous)</option>\n";
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
   echo "<select id='bugidSelector' name='bugidSelector' style='width: 600px;' title='Tache'>\n";
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
   
   echo "<input type=button value='Envoyer' onClick='javascript: submitForm()'>\n";
   
   echo "<input type=hidden name=bugid  value=$defaultBugid>\n";
   echo "<input type=hidden name=projectid value=$defaultProjectid>\n";
   echo "<input type=hidden name=action       value=noAction>\n";
   echo "</form>\n";
   
   echo "</div>";
}


// ---------------------------------------------------------------
function displayIssueGeneralInfo($issue) {      
  echo "<table>\n";

  echo "<tr>\n";
  echo "<th>Status</th>\n";
  echo "<td>".$issue->getCurrentStatusName()."</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<th title='BI + BS'>Estimated</th>\n";
  echo "<td title='$issue->effortEstim + $issue->effortAdd'>".($issue->effortEstim + $issue->effortAdd)."</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<th>Elapsed</th>\n";
  echo "<td>".$issue->elapsed."</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<th>Remaining</th>\n";
  echo "<td>$issue->remaining</td>\n";
  echo "</tr>\n";
   
  echo "<tr>\n";
  echo "<th>Drift</th>\n";
  $derive = $issue->getDrift();
  echo "<td style='background-color: ".$issue->getDriftColor($derive)."'>".$derive."</td>\n";
  echo "</tr>\n";
   
  echo "</table>\n";      
}

// ---------------------------------------------------------------
function displayJobDetails($issue) {
   
	global $job_colors;
	
	$timeTracks = $issue->getTimeTracks();
   $durationByJob = array();
   $jobs = new Jobs();
   
   echo "<table>\n";
   echo "<tr>\n";
   echo "<th>Job</th>\n";
   echo "<th>Nb Days</th>\n";
   echo "<th>% Total</th>\n";
   echo "</tr>\n";

   foreach ($timeTracks as $tid => $tdate) {
      $tt = new TimeTrack($tid);
      $durationByJob[$tt->jobId] += $tt->duration;
      $totalDuration += $tt->duration;
   }
  
   #sort($durationByJob);
   foreach ($durationByJob as $jid => $duration) {
      echo "<tr>\n";
      echo "   <td style='background-color: ".$job_colors[$jid]."'>".$jobs->getJobName($jid)."</td>\n";
      echo "<td>$duration</td>\n";
      echo "<td>".number_format(($duration*100 / $totalDuration), 2)." %</td>\n";
      echo "</tr>\n";
   }
  echo "</table>\n";      
  
}
// ---------------------------------------------------------------
function displayMonth($month, $year, $issue) {
  global $globalHolidaysList;
  global $job_study;
  global $job_analyse;
  global $job_dev;  
  global $job_test;
  global $job_none;
  global $job_colors;
  
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
    	$jobColorByDate[$tdate] = $job_colors[$tt->jobId];
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
        if (($dayOfWeek > 5) || 
            (in_array(date("Y-m-d", $todayTimestamp), $globalHolidaysList))) { 
          echo "<td style='background-color: #d8d8d8;'></td>\n";
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


$user = new User($session_userid);

$dTeamList = $user->getDevTeamList();
$lTeamList = $user->getLeadedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $dTeamList + $lTeamList + $managedTeamList;

if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
	echo ("Sorry, you need to be member of a Team to access this page.");
   echo "</div>";

} else {

	displayIssueSelectionForm($user, $bug_id, $defaultProjectid);
	
	if ("displayBug" == $action) {
	  $issue = new Issue ($bug_id);
	        
	  echo "<br/><br/>\n";
	  echo "<br/>";
	  displayIssueGeneralInfo($issue);
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

<?php include '../footer.inc.php'; ?>