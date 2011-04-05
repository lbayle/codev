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
  function submitForm() {
    document.forms["form1"].action.value = "displayBug";
    document.forms["form1"].submit();
  }

  function submitTeam(){
     // check fields
     foundError = 0;
     msgString = "Some fields are missing:" + "\n\n";
         
     if (0 == document.forms["teamSelectForm"].f_teamid.value)  { msgString += "Team\n"; ++foundError; }
                    
     if (0 == foundError) {
       document.forms["teamSelectForm"].submit();
     } else {
       alert(msgString);    
     }    
   }

  
</script>

<div id="content">

<?php

include_once "constants.php";
include_once "tools.php";
include_once "issue.class.php";
include_once "user.class.php";
include_once "scheduler.class.php";


// -----------------------------------------
function setTeamForm($originPage, $defaultSelection, $teamList) {
   
  // create form
  echo "<div align=center>\n";
  echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage' onchange='javascript: submitTeam()'>\n";

  echo "Team :\n";
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

  echo "<input type=hidden name=currentForm value=teamSelectForm>\n";
  echo "<input type=hidden name=nextForm    value=editTeamForm>\n";

  echo "</form>\n";
  echo "</div>\n";
}



// -----------------------------------------
function displayUserSchedule($dayPixSize, $userName, $scheduledTaskList) {
	
   $totalPix = 0;
   $sepWidth = 1;
   
   $images[true]  = "../images/schedTask_green.bmp";
   $images[false] = "../images/schedTask_red.bmp";


#echo "".$userName."&nbsp; &nbsp;";
foreach($scheduledTaskList as $key => $scheduledTask) {
   
   $taskPixSize = $scheduledTask->getPixSize($dayPixSize);
   $totalPix += $taskPixSize;
   
   #echo "DEBUG scheduledTask $scheduledTask->bugId size = $taskPixSize<br/>";
   $taskTitle= "task ".$scheduledTask->bugId.": ".$scheduledTask->duration." days";
   if (NULL != $scheduledTask->deadLine) {
   	$taskTitle .= " deadLine = ".date("d/m/Y", $scheduledTask->deadLine);
   }
   
   echo "<IMG WIDTH='$taskPixSize' HEIGHT='20' SRC='".$images[$scheduledTask->isOnTime]."' TITLE='$taskTitle'>";
   echo "<IMG WIDTH='$sepWidth' HEIGHT='20' SRC='../images/schedTask_white.bmp'>";
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
   $imageHeight = 5;
   
	$deadLines = array();
	$nbDaysToDeadLines = array();
	
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

   $curPos=0;
   foreach($deadLines as $date => $isOnTime) {
      
   	$offset = $nbDaysToDeadLines[$date];
   	#$offset = ($date - $today) / 86400 ; // in days since today
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

	$allLists = array();
	$teamMembers = Team::getMemberList($teamid);
	
	$nbDaysToDisplay = 0; 
	foreach ($teamMembers as $id => $name) {
	   $workload = 0;
	   $user = new User($id);
	   
	   if (!$user->isTeamDeveloper($teamid)) { continue; }
	   
	   $scheduledTaskList = $scheduler->scheduleUser($user, $today);
	   
	   foreach($scheduledTaskList as $key => $scheduledTask) {
	      $workload += $scheduledTask->duration;
	   }
	   $nbDaysToDisplay = ($nbDaysToDisplay < $workload) ? $workload : $nbDaysToDisplay;
	   
	   $allLists[$user->getName()] = $scheduledTaskList;
	}
	
	$dayPixSize = $graphSize / $nbDaysToDisplay;
	
	// display all team
	echo "<table class='invisible'>\n";
	foreach($allLists as $userName => $scheduledTaskList) {
	
	   echo "<tr valign='center'>\n";
	   echo "<td>$userName</td>\n";
	   echo "<td>";
	   $deadLines = displayUserDeadLines($dayPixSize, $today, $scheduledTaskList);
      if (0 != count($deadLines)) { echo "<br/>"; } // 
	   displayUserSchedule($dayPixSize, $userName, $scheduledTaskList);
	   echo "</td>\n";
	   echo "</tr>\n";
	}
	echo "</table>\n";
	
}


// ================ MAIN =================

$graphSize = 800;

$teamid = 26; // codev

$today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));


$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die("Could not connect database : ".mysql_error());
mysql_select_db($db_mantis_database) or die("Could not select database : ".mysql_error());


// use the teamid set in the form, if not defined (first page call) use session teamid
if (isset($_POST[f_teamid])) {
   $teamid = $_POST[f_teamid];
   $_SESSION['teamid'] = $teamid;
} else {
   $teamid = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
}

$session_user = new User($_SESSION['userid']);

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
   
   echo "<br/>";
   echo "<br/>";
   echo "<hr width='80%'/>\n";
   echo "<br/>";
   echo "<br/>";
   echo "<br/>";
   
   if (0 != $teamid) {
      displayTeam($teamid, $today, $graphSize);
   }
}

echo "<br/>\n";
echo "<br/>\n";
?>

</div>

<?php include 'footer.inc.php'; ?>