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
   $_POST[page_name] = T_("Project Management"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>


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

include_once "time_tracking.class.php";
require_once('tc_calendar.php');

// -----------------------------------------------
function displayTeamAndWeekSelectionForm($leadedTeamList, $teamid, $weekid, $curYear) {

  echo "<div align='center'>\n";
  echo "<form id='form1' name='form1' method='post' action='proj_management_report.php'>\n";

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



// ---------------------------------------------------------------
function exportManagedIssuesToCSV($path="", $startTimestamp, $endTimestamp) {
   
   global $status_resolved;
   global $status_delivered;
   global $status_closed;

   $sepChar=';';
   
   $myFile = $path."\AOI-PIL-Mantis_".date("Ymd").".csv";
   $fh = fopen($myFile, 'w');
  
   // write header
   // WARNING i18n: translations with HTML chars (&eacute;) include ';' which is the CSV separation char !
   $stringData = T_("Project").$sepChar.
                 T_("m_id").$sepChar.   
                 T_("tc_id").$sepChar.
                 T_("Summary").$sepChar.
                 T_("Status").$sepChar.
                 T_("Submitted").$sepChar.
                 T_("Start date").$sepChar.
                 T_("Dead line").$sepChar.
                 T_("Release").$sepChar.
                 T_("Priority").$sepChar.
                 "Category".$sepChar.
                 T_("Resolution").$sepChar.
                 T_("ETA").$sepChar.
                 T_("BI").$sepChar.
                 T_("BS").$sepChar.
                 "Elapsed".$sepChar.
                 T_("RAE").$sepChar.
                 T_("Delivery Date").$sepChar.
                 T_("Delivery Sheet").$sepChar.
                 T_("Assigned to").$sepChar.
                 "\n";
   fwrite($fh, $stringData);
   
   // for all issues with status !=  {resolved, closed}
   
      $query = "SELECT DISTINCT id FROM `mantis_bug_table` WHERE status NOT IN ($status_resolved,$status_delivered,$status_closed) ORDER BY id DESC";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result)) {
            $issue = new Issue($row->id);
            $user = new User($issue->handlerId);

            $deadLine = "";
            if (NULL != $issue->deadLine) {
             $deadLine = date("d/m/Y", $issue->deadLine);
            }
            $deliveryDate = "";
            if (NULL != $issue->deliveryDate) {
             $deliveryDate = date("d/m/Y", $issue->deliveryDate);
            }
            
            // remove sepChar from summary text
            $formatedSummary = str_replace("$sepChar", " ", $issue->summary);
            
            $startDate="";
            if (NULL != ($d = $issue->getStartTimestamp())) {
               $startDate = date("d/m/Y", $d);
            }
            
			   // write data
			   $stringData = $issue->getProjectName().$sepChar.
			                 $issue->bugId.$sepChar.   
			                 $issue->getTC().$sepChar.
                          $formatedSummary.$sepChar.
                          $issue->getCurrentStatusName().$sepChar.
                          date("d/m/Y", $issue->dateSubmission).$sepChar.
                          $startDate.$sepChar.
                          $deadLine.$sepChar.
                          $issue->release.$sepChar.
			                 $issue->getPriorityName().$sepChar.
			                 $issue->getCategoryName().$sepChar.
			                 $issue->getResolutionName().$sepChar.
			                 $issue->getEtaName().$sepChar.
			                 $issue->effortEstim.$sepChar.
			                 $issue->effortAdd.$sepChar.
                          $issue->elapsed.$sepChar.
			                 $issue->remaining.$sepChar.
			                 $deliveryDate.$sepChar.
                          $issue->deliveryId.$sepChar.
                          $user->getShortname().
                          "\n";
			   fwrite($fh, $stringData);
            
      }

  // Add resolved issues modified into the period
  $query = "SELECT DISTINCT id FROM `mantis_bug_table` WHERE status IN ($status_resolved,$status_delivered,$status_closed) AND last_updated > $startTimestamp AND last_updated < $endTimestamp ORDER BY id DESC";
  $result = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result)) {
    $issue = new Issue($row->id);
    $user = new User($issue->handlerId);

    $deliveryDate = "";
    if (NULL != $issue->deliveryDate) {
      $deliveryDate = date("d/m/Y", $issue->deliveryDate);
    }
    
    // remove sepChar from summary text
    $formatedSummary = str_replace("$sepChar", " ", $issue->summary);
    
    $startDate="";
    if (NULL != ($d = $issue->getStartTimestamp())) {
      $startDate = date("d/m/Y", $d);
    }
    
    // write data
    $stringData = $issue->getProjectName().$sepChar.
        $issue->bugId.$sepChar.   
        $issue->getTC().$sepChar.
        $formatedSummary.$sepChar.
        $issue->getCurrentStatusName().$sepChar.
        date("d/m/Y", $issue->dateSubmission).$sepChar.
        $startDate.$sepChar.
        $deadLine.$sepChar.
        $issue->release.$sepChar.
        $issue->getPriorityName().$sepChar.
        $issue->getCategoryName().$sepChar.
		  $issue->getResolutionName().$sepChar.
		  $issue->getEtaName().$sepChar.
		  $issue->effortEstim.$sepChar.
		  $issue->effortAdd.$sepChar.
        $issue->elapsed.$sepChar.
		  $issue->remaining.$sepChar.
		  $deliveryDate.$sepChar.
        $issue->deliveryId.$sepChar.
		  $user->getShortname().
		  "\n";
    fwrite($fh, $stringData);
  }

  fclose($fh);
  return $myFile;
}

// ---------------------------------------------
// format: nom;prenom;trigramme;date de debut;date de fin;nb jours
// format date: "jj/mm/aa"
function exportHolidaystoCSV($month, $year, $teamid, $teamName, $path="") {

  $sepChar=';';
  
  $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
  $nbDaysInMonth = date("t", $monthTimestamp);
  $startT = mktime(0, 0, 0, $month, 1, $year);
  $endT   = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);
   
   // create filename & open file
   $myFile = $path."\AOI-PIL-Holidays_".$teamName."_".date("Ym", $monthTimestamp).".csv";
   $fh = fopen($myFile, 'w');

  // USER
  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username, mantis_user_table.realname ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $teamid ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";

  
  $result = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {
      $user1 = new User($row->user_id);
      
      // if user was working on the project within the timestamp
      if (($user1->isTeamDeveloper($teamid, $startT, $endT)) ||
          ($user1->isTeamManager($teamid, $startT, $endT))) {
      	
         $daysOf = $user1->getDaysOfInPeriod($startT, $endT);
          
           // concatenate days 
         $startBlockTimestamp = 0;
         $endBlockTimestamp = 0;
         $blockSize = 0;
         
         for ($i = 1; $i <= $nbDaysInMonth; $i++) {        
            if (NULL != $daysOf[$i]) {
               
               $evtTimestamp = mktime(0, 0, 0, $month, $i, $year);
               
               if (1 == $daysOf[$i]) {
                  // do not write, concatenate evt to block
                  if (0 == $startBlockTimestamp) {$startBlockTimestamp = $evtTimestamp; }
                  $blockSize += 1;
                  $endBlockTimestamp = $evtTimestamp;
                  
               } else {
                  // write previous block if exist
                  if (0 != $blockSize) {
                     $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                             date("d/m/y", $startBlockTimestamp).$sepChar.
                             date("d/m/y", $endBlockTimestamp).$sepChar.
                             $blockSize."\n";   
                     fwrite($fh, $stringData);
                     $startBlockTimestamp = 0;
                     $endBlockTimestamp = 0;
                     $blockSize = 0;
                  }
                  
                  // write current line ( < 1)
                  $evtDate      = date("d/m/y", $evtTimestamp); 
                  $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                             $evtDate.$sepChar.
                             $evtDate.$sepChar.
                             $daysOf[$i]."\n";   
                  fwrite($fh, $stringData);
               }              
               
               
            } else {
                  // write previous block if exist
               if (0 != $blockSize) {
                  $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                             date("d/m/y", $startBlockTimestamp).$sepChar.
                             date("d/m/y", $endBlockTimestamp).$sepChar.
                             $blockSize."\n";   
                  fwrite($fh, $stringData);
                  $startBlockTimestamp = 0;
                  $endBlockTimestamp = 0;
                  $blockSize = 0;
               }
               
            }
          }
          if (0 != $blockSize) {
                 $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                             date("d/m/y", $startBlockTimestamp).$sepChar.
                             date("d/m/y", $endBlockTimestamp).$sepChar.
                             $blockSize."\n";   
                  fwrite($fh, $stringData);
                  $startBlockTimestamp = 0;
                  $endBlockTimestamp = 0;
                  $blockSize = 0;
          }
      }    
  }
  fclose($fh);
  return $myFile;
}






// ---------------------------------------------
function exportWeekActivityReportToCSV($teamid, $weekid, $weekDates, $timeTracking, $teamName, $path="") {

  $sepChar=';';

  // create filename & open file
  $myFile = $path."\AOI-PIL-CRA_".$teamName."_".date("Y", $timeTracking->startTimestamp)."_W".sprintf('%02d',$weekid).".csv";
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
      	
         exportWeekDetailsToCSV($weekid, $weekDates, $row->user_id, $timeTracking, $user->getShortname(),$fh);
      }
   
  }
  fclose($fh);
  return $myFile;
}

// ---------------------------------------------
function exportWeekDetailsToCSV($weekid, $weekDates, $userid, $timeTracking, $realname, $fh) {
  
  $sepChar=';';
	
  $weekTracks = $timeTracking->getWeekDetails($userid);
  foreach ($weekTracks as $bugid => $jobList) {
    $issue = new Issue($bugid);
    foreach ($jobList as $jobid => $dayList) {
                
      $query3  = "SELECT name FROM `codev_job_table` WHERE id=$jobid";
      $result3 = mysql_query($query3) or die("Query failed: $query3");
      $jobName = mysql_result($result3, 0);

      
      $stringData = $bugid.$sepChar.   
                    $jobName.$sepChar.
                    $issue->summary.$sepChar.
                    $realname.$sepChar;
      for ($i = 1; $i <= 4; $i++) {
        $stringData .= $dayList[$i].$sepChar;
      }
      $stringData .= $dayList[5]."\n";
      fwrite($fh, $stringData);
    }
  }
}


// ------------------------------------------------
function exportProjectActivityToCSV($timeTracking, $weekid, $teamName, $path="") {

  $sepChar=';';
	
  $myFile = $path."\AOI-PIL-Projects_".$teamName."_".date("Y", $timeTracking->startTimestamp)."_W".sprintf('%02d',$weekid).".csv";
  $fh = fopen($myFile, 'w');
  
  // $projectTracks[projectid][bugid][jobid] = duration
  $projectTracks = $timeTracking->getProjectTracks();   
   
  foreach ($projectTracks as $projectId => $bugList) {
   
     // write table header
     $project = new Project($projectId);
     $stringData = $project->name."\n";

     $stringData .=T_("Task").$sepChar;
     $stringData .=T_("RAE").$sepChar;
     $jobList = $project->getJobList();
     foreach($jobList as $jobId => $jobName) {
        $stringData .= $jobName.$sepChar;
     }
     $stringData .="\n";
      
     // write table content (by bugid)
     foreach ($bugList as $bugid => $jobs) {
         $issue = new Issue($bugid);
      
         $stringData .= "$bugid / ".$issue->tcId." : ".$issue->summary.$sepChar;
         $stringData .= $issue->remaining.$sepChar;
         foreach($jobList as $jobId => $jobName) {
            $stringData .= $jobs[$jobId].$sepChar;
         }
         $stringData .="\n";
     }
     $stringData .="\n";
     fwrite($fh, $stringData);
  }
  fclose($fh);
  return $myFile;
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
		   
		   $filename = exportManagedIssuesToCSV($codevReportsDir, $startTimestamp, $endTimestamp);
	      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$filename<br/>\n";
	      flush(); 
		   
	      // -----------------------------
	      echo "<br/>\n";
	      echo "<b>- ".T_("Export Week ").$weekid.T_(" Member Activity")."...</b><br/>\n";
	      flush(); // envoyer tout l'affichage courant au navigateur 
	      
	      
	      $filename = exportWeekActivityReportToCSV($teamid, $weekid, $weekDates, $timeTracking, $teamName, $codevReportsDir);
	      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$filename<br/>\n";
	      flush(); 
	      
         // -----------------------------
         echo "<br/>\n";
         echo "<b>- ".T_("Export Week ").$weekid.T_(" Projects Activity")."...</b><br/>\n";
         flush(); 
         
         $filename = exportProjectActivityToCSV($timeTracking, $weekid, $teamName, $codevReportsDir);
         echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$filename<br/>\n";
         flush(); 

         // -----------------------------
	      echo "<br/>\n";
	      echo "<b>- ".T_("Export Holidays ").$year."...</b><br/>\n";
		   flush(); // envoyer tout l'affichage courant au navigateur
		   
		   // reduce scope to enhance speed
		   $startMonth = 1;
		   /*
		   if ($year == date('Y')) {
		       $startMonth= date("m");
		       if ($startMonth > 1) { $startMonth -= 1; } // previous month may be usefull
		   }
		   */ 
		   for ($i = $startMonth; $i <= 12; $i++) {
		      $filename = exportHolidaystoCSV($i, $year, $teamid, $teamName, $codevReportsDir);
	         echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$filename<br/>\n";
	         //echo "<a href='$filename'>$filename</a><br/>\n"; 
	         flush(); 
		   }
		   
		   
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
