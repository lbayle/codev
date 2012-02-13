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

<?php include_once '../path.inc.php'; ?>

<?php
include_once 'i18n.inc.php';
if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}
?>

<?php
// ----------
// Getting the windows width and pass it to a php variable.
// Because php is server side, and javascript is client side, they cannot share data easily.

// 1. Load the page
// 2. Look for screen size in query string.
// 3. If not set, get screen size; reload the page
// 4. When the page reloads, you will have both the screen width and height in the query string, which can be retrieved using php's $_GET.

if(!isset($_GET['w'])) {
?>
<script type="text/javascript">

var myWidth = 0, myHeight = 0;
myWidth = window.innerWidth;
if (myWidth > 500) {
   myWidth = myWidth - 10;
}

myHeight = window.innerHeight;
var l = location;
if ("" == location.search) {
   document.location.href=location+"?w="+myWidth+"&h="+myHeight;
} else {
   document.location.href=location+"&w="+myWidth+"&h="+myHeight;
}

</script>
<?php
exit();
}
// ----------
?>


<?php
   $_POST['page_name'] = T_("Planning");
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

   function zoomIn(){

     document.forms["teamSelectForm"].action.value = "zoomIn";
     document.forms["teamSelectForm"].submit();
   }

   function zoomOut(){

     document.forms["teamSelectForm"].action.value = "zoomOut";
     document.forms["teamSelectForm"].submit();
   }

</script>

<div id="content">

<?php

include_once "issue.class.php";
include_once "user.class.php";
include_once "scheduler.class.php";
include_once 'consistency_check.class.php';
include_once 'team.class.php';


class DeadLine {
   public $date;
   public $nbDaysToDeadLine;
   public $isOnTime;    // true: ALL issues are on time
   public $issueList;
   public $isMonitored; // true: deadLine concerns only Monitored issues

   public function __construct($date, $nbDaysToDeadLine, $isOnTime, $isMonitored) {
      $this->date = $date;
      $this->nbDaysToDeadLine = $nbDaysToDeadLine;
      $this->isOnTime = $isOnTime;
      $this->isMonitored = $isMonitored;
      $this->issueList = array();
   }

   public function addIssue($bugId) {
      $this->issueList[] = $bugId;
   }

   public function setIsOnTime($isOnTime) {
      // if already exists and not on time, do not overwrite.
      if ((NULL == $isOnTime) || (true == $isOnTime)) {
        $this->isOnTime = $isOnTime;
      }
   }

   public function setIsMonitored($isMonitored) {

   	// non Monitored tasks have priority on deadLine status

      // if not a monitored task, do not overwrite.
      if (true == $this->isMonitored) {
      	$this->isMonitored = $isMonitored;
      }


   }

   public function toString() {
      $string = "".date("d/m/Y", $this->date)." (+$this->nbDaysToDeadLine days)  ".T_("Tasks").": ";

      $count = 0;
      foreach($this->issueList as $i) {
         $count++;
         $string .= "$i";
         if($count != count($this->issueList)) {
            $string .= ",";
         }
      }
      return $string;
   }

   /***
    * depending on $isOnTime, $isMonitored returns
    * the path to the arrow image to be displayed (blue, red, grey)
    */
   public function getImageURL() {
   	$image_isOnTime    = "../images/arrow_down_blue.png";
      $image_isNotOnTime = "../images/arrow_down_red.png";
   	$image_isMonitored = "../images/arrow_down_grey.png";

   	if (!$this->isOnTime)   { return $image_isNotOnTime; }

      if ($this->isMonitored) { return $image_isMonitored; }

   	return $image_isOnTime;
   }
}



// -----------------------------------------
function setTeamForm($originPage, $defaultSelection, $teamList) {

  // create form
  echo "<div align=center>\n";
  if (isset($_GET['w'])) {
      echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage?w=".$_GET['w']."&h=".$_GET['h']."'>\n";
  } else {
      echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage'>\n";
  }
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
   echo "&nbsp;&nbsp;&nbsp;&nbsp;\n";
   echo "<a title='".T_("zoom in")."' href=\"javascript: zoomIn()\" ><img border='0' src='../images/zoom_in.png'></a>\n";
   echo "<a title='".T_("zoom out")."' href=\"javascript: zoomOut()\" ><img border='0' src='../images/zoom_out.png'></a>\n";

   echo "<input type=hidden name=action value=noAction>\n";

  echo "</form>\n";
  echo "</div>\n";
}



// -----------------------------------------
function displayUserSchedule($dayPixSize, $userName, $scheduledTaskList, $teamid) {

   $totalPix = 0;
   $sepWidth = 1;
   $barHeight = 20;
   $deadLineTriggerWidth = 10;
   
   $projList = Team::getProjectList($teamid);

   echo "<IMG WIDTH='".($deadLineTriggerWidth/2)."' HEIGHT='$barHeight' SRC='../images/white.png'>";

   foreach($scheduledTaskList as $key => $scheduledTask) {

      $taskPixSize = $scheduledTask->getPixSize($dayPixSize);
      $totalPix += $taskPixSize;

      // set color
      if (NULL != $scheduledTask->deadLine) {
         $color = ($scheduledTask->isOnTime) ? "green" : "red";

         if (!$scheduledTask->isOnTime) {
            $color = "red";
         } else {
            $color = ($scheduledTask->isMonitored) ? "grey" : "green";
         }
      } else {
         $color = ($scheduledTask->isMonitored) ? "grey" : "blue";
      }

	  // hide tasks not in team projects
      $strike="";
	  $issue = IssueCache::getInstance()->getIssue($scheduledTask->bugId);
	  if ( NULL == $projList[$issue->projectId] ) {
  	     $strike="&strike";
	  }
	  
      $taskTitle = $scheduledTask->getDescription();
	   $formatedTitle = str_replace("'", " ", $taskTitle);
      $formatedTitle = str_replace("\"", " ", $formatedTitle);

      $drawnTaskPixSize = $taskPixSize - $sepWidth;
      echo "<a href='".getServerRootURL()."/reports/issue_info.php?bugid=$scheduledTask->bugId'><img title='$formatedTitle' src='".getServerRootURL()."/graphs/scheduledtask.png.php?height=$barHeight&width=$drawnTaskPixSize&text=$scheduledTask->bugId&color=".$color.$strike."' /></a>";

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

   $deadLineTriggerWidth = 10;
   $imageHeight = 7;
   $barHeight = $imageHeight;

	$deadLines = array();
	$nbDaysToDeadLines = array();

   // remove duplicate deadLines & set color
	foreach($scheduledTaskList as $key => $scheduledTask) {
      if (NULL != $scheduledTask->deadLine) {

		  if (!isset($deadLines["$scheduledTask->deadLine"]) ||
		      (NULL == $deadLines["$scheduledTask->deadLine"])) {
   		 $dline = new DeadLine($scheduledTask->deadLine,
   		                       $scheduledTask->nbDaysToDeadLine,
   		                       $scheduledTask->isOnTime,
   		                       $scheduledTask->isMonitored);
   		 $dline->addIssue($scheduledTask->bugId);
   		 $deadLines["$scheduledTask->deadLine"] = $dline;
   	  } else {
   		 $dline = $deadLines["$scheduledTask->deadLine"];
   		 $dline->setIsOnTime($scheduledTask->isOnTime);
          $dline->addIssue($scheduledTask->bugId);
          $dline->setIsMonitored($scheduledTask->isMonitored);
   	  }
      }
   }

   // well if no deadLines, ...
   if (0 == count($deadLines)) { return $deadLines; }

   // sort deadLines by date ASC
   ksort($deadLines);

   // because the 'size' of the arrow, the first scheduledTask has been shifted
   // we need to check if the $nbDays of the first deadLine = 0
   reset($deadLines);
   $dline = $deadLines[key($deadLines)];

   if (0 != $dline->nbDaysToDeadLine) {
            // align
            echo "<IMG WIDTH='".($deadLineTriggerWidth/2)."' HEIGHT='$barHeight' SRC='../images/white.png'>";
   }


   // display deadLines
   $curPos=0;
   foreach($deadLines as $date => $dline) {

      $offset = $dline->nbDaysToDeadLine;

      if ($offset >= 0) {
         if (0 != $offset) {
            // draw timeLine
            $timeLineSize = ($offset * $dayPixSize) - ($deadLineTriggerWidth/2) - $curPos;
            echo "<IMG WIDTH='$timeLineSize' HEIGHT='$imageHeight' SRC='../images/time_line.jpg'>";

            $curPos += $timeLineSize + $deadLineTriggerWidth;
         } else {
            $curPos += $deadLineTriggerWidth/2;
         }

         // drawArrow
         echo "<IMG SRC='".$dline->getImageURL()."' ALT='Text replacing image' TITLE='".$dline->toString()."'>";
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

	$deadLineTriggerWidth = 10;
    $barHeight = 1;

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

	   $scheduledTaskList = $scheduler->scheduleUser($user, $today, true);

	   foreach($scheduledTaskList as $key => $scheduledTask) {
	      $workload += $scheduledTask->duration;
	   }
	   $nbDaysToDisplay = ($nbDaysToDisplay < $workload) ? $workload : $nbDaysToDisplay;

	   $allTasksLists[$user->getName()] = $scheduledTaskList;
	   $workloads[$user->getName()]     = $workload;
	}

	$dayPixSize = (0 != $nbDaysToDisplay) ? ($graphSize / $nbDaysToDisplay) : 0;
	$dayPixSize = round($dayPixSize);
   #echo "DEBUG dayPixSize    = $dayPixSize<br/>\n";

	// display all team
	echo "<table class='invisible'>\n";

   echo "<tr>\n";
   echo "  <td ></td>\n";
   echo "  <td >\n";
   echo "<IMG WIDTH='".($deadLineTriggerWidth/2)."' HEIGHT='$barHeight' SRC='../images/white.png'>";
   for ($i = 0; $i < $nbDaysToDisplay; $i++) {
      echo "<IMG HEIGHT='7' WIDTH='1' SRC='../images/timeline_stop.jpg'>";
   	echo "<IMG WIDTH='".($dayPixSize-1)."' HEIGHT='7' SRC='../images/time_line.jpg'>";
   }
   echo "<IMG HEIGHT='7' WIDTH='1' SRC='../images/timeline_stop.jpg'>";
   echo "</td >\n";
   echo "</tr>\n";

	foreach($allTasksLists as $userName => $scheduledTaskList) {

	   echo "<tr valign='center'>\n";
	   echo "<td title='".T_("workload")." = ".$workloads[$userName]." ".T_("days")."'>$userName</td>\n";
	   echo "<td>";
	   $deadLines = displayUserDeadLines($dayPixSize, $today, $scheduledTaskList);
      if (0 != count($deadLines)) { echo "<br/>"; } //
	   displayUserSchedule($dayPixSize, $userName, $scheduledTaskList, $teamid);
	   echo "</td>\n";
	   echo "</tr>\n";
	}
	echo "</table>\n";

	return $dayPixSize;
}

// -----------------------------------------
function displayLegend($dayPixSize) {

   $barHeight = 14;
   $barWidtht = 14;

   $colorTypes = array(
     "green" => T_("onTime"),
     "red"   => T_("NOT onTime"),
     "blue"  => T_("no deadLine"),
     "grey"  => T_("monitored"),
   );

   echo "<div class='center'>\n";
   echo "<table class='invisible'  width='700'>\n";
   echo "<tr>\n";

   foreach ($colorTypes as $color => $type) {
      echo "<td >\n";
      echo "<img src='".getServerRootURL()."/graphs/scheduledtask.png.php?height=$barHeight&width=$barWidtht&color=".$color."' />";
      echo "&nbsp;&nbsp;$type";
      echo "</td>\n";
   }
   echo "  <td >\n";
   echo "<IMG HEIGHT='7' WIDTH='1' SRC='../images/timeline_stop.jpg'>";
   echo "<IMG WIDTH='".($dayPixSize-1)."' HEIGHT='7' SRC='../images/time_line.jpg'>";
   echo "<IMG HEIGHT='7' WIDTH='1' SRC='../images/timeline_stop.jpg'>";
   echo "&nbsp;&nbsp;1 ".T_("day")."\n";
   echo "  </td >\n";
   echo "</tr>\n";
   echo "</table>\n";
   echo "</div>\n";

}


// -----------------------------
function displayConsistencyErrors($teamid) {

   global $statusNames;

   $projectList = Team::getProjectList($teamid);
   $ccheck = new ConsistencyCheck($projectList);

   $cerrList = $ccheck->checkBadRemaining();

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
         if ($user->isTeamMember($teamid)) {
            $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
      	   echo "<tr>\n";
            echo "<td>".T_("ERROR on task ").mantisIssueURL($cerr->bugId, $issue->summary)."</td>";
            echo "<td>(".$user->getName().")</td>";
            echo "<td>: &nbsp;&nbsp;<span style='color:red'>".date("Y-m-d", $cerr->timestamp)."&nbsp;&nbsp;".$statusNames["$cerr->status"]."&nbsp;&nbsp;$cerr->desc</span></td>\n";
            echo "</tr>\n";
         }
      }
      echo "</table>\n";
      echo "</div>\n";
   }

}


// ================ MAIN =================


$pageWidth = $_GET['w'];
$pageHeigh = $_GET['h'];
#echo "DEBUG pageWidth $pageWidth<br/>";

$graphSize = ("undefined" != $pageWidth) ? $pageWidth -150 : 800;

$teamid = 26; // codev

$today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));


#$action = isset($_POST['action']) ? $_POST['action'] : '';

if (isset($_POST['action'])) {
	$action = $_POST['action'];
} else if (isset($_GET['display'])) {
	$action = "displayPlanning";
} else {
	$action = '';
}


// use the teamid set in the form, if not defined (first page call) use session teamid
if (isset($_POST['f_teamid'])) {
   $teamid = $_POST['f_teamid'];
   $_SESSION['teamid'] = $teamid;
} else {
   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
}

$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

$dTeamList = $session_user->getDevTeamList();
$lTeamList = $session_user->getLeadedTeamList();
$managedTeamList = $session_user->getManagedTeamList();
$teamList = $dTeamList + $lTeamList + $managedTeamList;

//  if user is not Leader of $_SESSION[teamid], do not display current team page
if (!isset($teamList["$teamid"]) || (NULL == $teamList["$teamid"])) { $teamid = 0;}

if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
   echo T_("Sorry, you need to be member of a Team to access this page.");
   echo "</div>";

} else {
   setTeamForm("planning_report.php", $teamid, $teamList);

   if ("displayPlanning" == $action) {

      if (0 != $teamid) {
         echo "<br/>";
		   echo "<hr width='80%'/>\n";
		   echo "<br/>";
		   echo "<br/>";
		   echo "<br/>";

      	$dayPixSize = displayTeam($teamid, $today, $graphSize);
         echo "<br/>\n";
         echo "<br/>\n";
         echo "<br/>\n";
         echo "<br/>\n";
         displayLegend($dayPixSize);
         echo "<br/>\n";
         displayConsistencyErrors($teamid);

      }
   } elseif ("zoomIn" == $action) {

      $pageWidth = floor($pageWidth + ($pageWidth/2)); # +50%

      // set $_GET['w']
      echo ("<script> parent.location.replace('./planning_report.php?display&w=$pageWidth'); </script>");
   } elseif ("zoomOut" == $action) {

      $pageWidth = floor($pageWidth - ($pageWidth/3)); # -33%
      
      if ($pageWidth < 300) { $pageWidth = 300; }

      // set $_GET['w']
      echo ("<script> parent.location.replace('./planning_report.php?display&w=$pageWidth'); </script>");
   }

}


echo "<br/>\n";
echo "<br/>\n";
?>

</div>

<?php include 'footer.inc.php'; ?>