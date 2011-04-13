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
   $_POST[page_name] = T_("Planning"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>


<script language="JavaScript">

  function submitTeam(){

     foundError = 0;
     msgString = "Some fields are missing:" + "\n\n";
         
     if (0 == document.forms["teamSelectForm"].f_teamid.value)  { msgString += "Team\n"; ++foundError; }
                    
     if (0 != foundError) {
       alert(msgString);    
     }
     document.forms["teamSelectForm"].action.value = "displayPlanning";
     document.forms["teamSelectForm"].submit();
         
   }

  
</script>

<div id="content">

<?php

include_once "constants.php";
include_once "tools.php";
include_once "issue.class.php";
include_once "user.class.php";
include_once "scheduler.class.php";
include_once 'consistency_check.class.php'; 
include_once 'team.class.php'; 


// -----------------------------------------
function setTeamForm($originPage, $defaultSelection, $teamList) {
   
  // create form
  echo "<div align=center>\n";
  echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage'>\n";

  echo T_("Team")." :\n";
  echo "<select name='f_teamid'>\n";
  echo "<option value='0'></option>\n";

   foreach ($teamList as $tid => $tname) {
  
    if ($tid == $defaultSelection) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";

   echo "<input type=button value='".T_("Update")."' onClick='javascript: submitTeam()'>\n";
   
   echo "<input type=hidden name=action value=noAction>\n";
   
  echo "</form>\n";
  echo "</div>\n";
}



// -----------------------------------------
function displayUserSchedule($dayPixSize, $userName, $scheduledTaskList) {
	
   $totalPix = 0;
   $sepWidth = 1;
   $barHeight = 20;
   
   $image_color[true]  = "green";
   $image_color[false] = "red";
   
   foreach($scheduledTaskList as $key => $scheduledTask) {
   
      $taskPixSize = $scheduledTask->getPixSize($dayPixSize);
      $totalPix += $taskPixSize;
      
      if (NULL != $scheduledTask->deadLine) {
         $color = ($scheduledTask->isOnTime) ? "green" : "red"; 
      } else {
         $color = "grey";
      }
      $taskTitle = $scheduledTask->getDescription();
	   $formatedTitle = str_replace("'", " ", $taskTitle);
      $formatedTitle = str_replace("\"", " ", $formatedTitle);
	   
      echo "<a href='".getServerRootURL()."/reports/issue_info.php?bugid=$scheduledTask->bugId'><img title='$formatedTitle' src='".getServerRootURL()."/graphs/scheduledtask.png.php?height=$barHeight&width=$taskPixSize&text=$scheduledTask->bugId&color=".$color."' /></a>";

	   echo "<IMG WIDTH='$sepWidth' HEIGHT='$barHeight' SRC='../images/white.png'>";
	}
	#echo "DEBUG totalPix    = $totalPix<br/>\n";
	
}

// -----------------------------------------
/**
 * 
 * @param $dayPixSize
 * @param $scheduledTaskList
 */
function displayUserDeadLines($dayPixSize, $today, $scheduledTaskList) {
   
	$images[true]  = "../images/arrow_down_blue.PNG";
   $images[false] = "../images/arrow_down_red.PNG";
   $imageWidth = 10;
   $imageHeight = 7;
   
	$deadLines = array();
	$nbDaysToDeadLines = array();
	
	// remove duplicate deadLines & set color
	foreach($scheduledTaskList as $key => $scheduledTask) {
      if (NULL != $scheduledTask->deadLine) {
      	$isOnTime = $deadLines[$scheduledTask->deadLine];
      	 if ((NULL == $isOnTime) || (true == $isOnTime)) {
      	 	// if already exists and not on time, do not overwrite.
      	 	$deadLines[$scheduledTask->deadLine] = $scheduledTask->isOnTime;
      	 	$nbDaysToDeadLines[$scheduledTask->deadLine] = $scheduledTask->nbDaysToDeadLine;
            #echo "DEBUG ".date("d m Y", $scheduledTask->deadLine)." - ".date("d m Y", $today)." task $scheduledTask->bugId isOnTime=$scheduledTask->isOnTime nbDaysToDeadLine=$scheduledTask->nbDaysToDeadLine<br/>";
      	 }
      }
   }
   
   // sort deadLines by date ASC
   ksort($deadLines);

   // display deadLines
   $curPos=0;
   foreach($deadLines as $date => $isOnTime) {
      
   	$offset = $nbDaysToDeadLines[$date];
   	
      #echo "DEBUG deadline ".date("d/m/Y", $date)." offset = $offset isOnTime=$scheduledTask->isOnTime<br/>";
   	
      if ($offset >= 0) {
         $timeLineSize = ($offset * $dayPixSize) - ($imageWidth/2) - $curPos;
   
         echo "<IMG WIDTH='$timeLineSize' HEIGHT='$imageHeight' SRC='../images/time_line.jpg'>";
         echo "<IMG SRC='".$images[$isOnTime]."' ALT='Texte remplaçant l image' TITLE='".date("d/m/Y", $date)." (+$offset days)'>";

         $curPos += $timeLineSize + $imageWidth;
      }
   	
   }
	return $deadLines;
}



// -----------------------------------------
function displayScheduledTaskTable($scheduledTaskList) {
	
echo "<table>\n";
   echo "<tr>\n";
   echo "<th>bugId</th>\n";
   echo "<th>duration</th>\n";
   echo "<th>isOnTime</th>\n";
   echo "<th>deadLine</th>\n";
   echo "<th>key</th>\n";
   echo "</tr>\n";

   foreach($scheduledTaskList as $key => $scheduledTask) {

   echo "<tr>\n";
   echo "<td>$scheduledTask->bugId</td>\n";
   echo "<td>$scheduledTask->duration</td>\n";
   echo "<td>$scheduledTask->isOnTime</td>\n";
   if (NULL != $scheduledTask->deadLine) {
      echo "<td>".date("d-M-Y",$scheduledTask->deadLine)."</td>\n";
   } else {
      echo "<td></td>\n";
   }
   echo "<td>$key</td>\n";
   echo "</tr>\n";
}
echo "</table>\n";
	
}

// -----------------------------------------
function displayTeam($teamid, $today, $graphSize) {
	$scheduler = new Scheduler();

	$allTasksLists = array();
   $workloads = array();
	$teamMembers = Team::getMemberList($teamid);
	
	$nbDaysToDisplay = 0; 
	foreach ($teamMembers as $id => $name) {
	   $workload = 0;
	   $user = UserCache::getInstance()->getUser($id);
	   
	   if (!$user->isTeamDeveloper($teamid)) { continue; }
	   if (NULL != ($user->getDepartureDate()) && ($user->getDepartureDate() < $today)) { continue; }
	   
	   $scheduledTaskList = $scheduler->scheduleUser($user, $today);
	   
	   foreach($scheduledTaskList as $key => $scheduledTask) {
	      $workload += $scheduledTask->duration;
	   }
	   $nbDaysToDisplay = ($nbDaysToDisplay < $workload) ? $workload : $nbDaysToDisplay;
	   
	   $allTasksLists[$user->getName()] = $scheduledTaskList;
	   $workloads[$user->getName()]     = $workload;
	}
	
	$dayPixSize = (0 != $nbDaysToDisplay) ? ($graphSize / $nbDaysToDisplay) : 0;
	
	// display all team
	echo "<table class='invisible'>\n";
	foreach($allTasksLists as $userName => $scheduledTaskList) {
	
	   echo "<tr valign='center'>\n";
	   echo "<td title='".T_("workload")." = ".$workloads[$userName]." ".T_("days")."'>$userName</td>\n";
	   echo "<td>";
	   $deadLines = displayUserDeadLines($dayPixSize, $today, $scheduledTaskList);
      if (0 != count($deadLines)) { echo "<br/>"; } // 
	   displayUserSchedule($dayPixSize, $userName, $scheduledTaskList);
	   echo "</td>\n";
	   echo "</tr>\n";
	}
	echo "</table>\n";
	
}

// -----------------------------
function displayConsistencyErrors($teamid) {

   // get team's project list
   #$devTeamList = $sessionUser->getDevTeamList();
   #$leadedTeamList = $sessionUser->getLeadedTeamList();
   #$managedTeamList = $sessionUser->getManagedTeamList();
   #$teamList = $devTeamList + $leadedTeamList + $managedTeamList; 
   #$projectList = $sessionUser->getProjectList($teamList);

   $projectList = Team::getProjectList($teamid);
   
   $ccheck = new ConsistencyCheck($projectList);

   $cerrList = $ccheck->checkBadRAE();

   if (0 == count($cerrList)) {
      #echo "Pas d'erreur.<br/>\n";
   } else {
      echo "<hr/>\n";
      echo "<br/>\n";
      echo "<br/>\n";
   	
      echo "<div align='left'>\n";
      echo "<table class='invisible'>\n";
      foreach ($cerrList as $cerr) {
         $user = UserCache::getInstance()->getUser($cerr->userId);
         $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
      	echo "<tr>\n";
         echo "<td>".T_("ERROR on task ").mantisIssueURL($cerr->bugId, $issue->summary)."</td>";
         echo "<td>($user->name)</td>";
         echo "<td>: &nbsp;&nbsp;<span style='color:red'>".date("Y-m-d", $cerr->timestamp)."&nbsp;&nbsp;".$statusNames[$cerr->status]."&nbsp;&nbsp;$cerr->desc</span></td>\n";
         echo "</tr>\n";
      }
      echo "</table>\n";
      echo "</div>\n";
   }
   
}


// ================ MAIN =================

$graphSize = 800;

$teamid = 26; // codev

$today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));


$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die("Could not connect database : ".mysql_error());
mysql_select_db($db_mantis_database) or die("Could not select database : ".mysql_error());

$action = $_POST[action];

// use the teamid set in the form, if not defined (first page call) use session teamid
if (isset($_POST[f_teamid])) {
   $teamid = $_POST[f_teamid];
   $_SESSION['teamid'] = $teamid;
} else {
   $teamid = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
}

$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

$dTeamList = $session_user->getDevTeamList();
$lTeamList = $session_user->getLeadedTeamList();
$managedTeamList = $session_user->getManagedTeamList();
$teamList = $dTeamList + $lTeamList + $managedTeamList;

//  if user is not Leader of $_SESSION[teamid], do not display current team page 
if (NULL == $teamList[$teamid]) { $teamid = 0;}

echo "<br/>";
echo "<br/>";
echo "<br/>";

if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
   echo T_("Sorry, you need to be member of a Team to access this page.");
   echo "</div>";

} else {
   setTeamForm("planning_report.php", $teamid, $teamList);
   
   if ("displayPlanning" == $action) {
   
      if (0 != $teamid) {
         echo "<br/>";
         echo "<br/>";
		   echo "<hr width='80%'/>\n";
		   echo "<br/>";
		   echo "<br/>";
		   echo "<br/>";
   
      	displayTeam($teamid, $today, $graphSize);
      	
         echo "<br/>\n";
         echo "<br/>\n";
         echo "<br/>\n";
         displayConsistencyErrors($teamid);
      	
      }
   }
   
}


echo "<br/>\n";
echo "<br/>\n";
?>

</div>

<?php include 'footer.inc.php'; ?>