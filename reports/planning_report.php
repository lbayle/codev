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
    document.forms["form1"].bugid.value = document.getElementById('bugidSelector').value;
    document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
    document.forms["form1"].action.value = "displayBug";
    document.forms["form1"].submit();
  }


  
</script>

<div id="content">

<?php

include_once "constants.php";
include_once "tools.php";
include_once "issue.class.php";
include_once "user.class.php";
include_once "scheduler.class.php";


function displayUserSchedule($dayPixSize, $userName, $scheduledTaskList) {
	
   $totalPix = 0;

   $images[true]  = "../images/schedTask_green.bmp";
   $images[false] = "../images/schedTask_red.bmp";


#echo "".$userName."&nbsp; &nbsp;";
foreach($scheduledTaskList as $key => $scheduledTask) {
   
   $taskPixSize = $scheduledTask->getPixSize($dayPixSize);
   $totalPix += $taskPixSize;
   
   echo "<IMG WIDTH='$taskPixSize' HEIGHT='20' SRC='".$images[$scheduledTask->isOnTime]."' ALT='Texte remplaçant l image' TITLE=' task ".$scheduledTask->bugId.": ".$scheduledTask->duration."j'>";
   echo "<IMG WIDTH='$sepWidth' HEIGHT='20' SRC='../images/schedTask_white.bmp'>";
}
#echo "DEBUG totalPix    = $totalPix<br/>\n";
	
}

// ------------------------------
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


echo "<br/>";
echo "<br/>";
echo "<br/>";


// ================ MAIN =================

$graphSize = 800;
$sepWidth = 1;

$teamid = 26; // codev

$today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));


$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die("Could not connect database : ".mysql_error());
mysql_select_db($db_mantis_database) or die("Could not select database : ".mysql_error());


#$user = new User(9); // afebvre
#$user = new User(7); // lob

$scheduler = new Scheduler();




echo "<br/>\n";
echo "<br/>\n";


// ----------------------


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
   echo "<tr>\n";
   echo "<td>$userName</td>\n";
   echo "<td>";
   displayUserSchedule($dayPixSize, $userName, $scheduledTaskList);
   echo "</td>\n";
   echo "</tr>\n";
}
echo "</table>\n";



?>

</div>

<?php include 'footer.inc.php'; ?>