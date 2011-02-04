<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
include_once 'i18n.inc.php';
include_once "../tools.php";
if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  
  exit;
} 
?>

<?php
   $_POST[page_name] = T_("Consistency Check"); 
   include '../header.inc.php'; 
?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>

<div id="content">
<?php 

include_once 'consistency_check.class.php'; 
include_once 'user.class.php'; 


// ================ MAIN =================

$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die(T_("Could not connect to DB"));
mysql_select_db($db_mantis_database) or die("Could not select database");

$userid = $_SESSION['userid'];
$sessionUser = new User($userid);

// get projects i'm involved in (dev, Leader, Manager)
$devTeamList = $sessionUser->getDevTeamList();
$leadedTeamList = $sessionUser->getLeadedTeamList();
$managedTeamList = $sessionUser->getManagedTeamList();
$teamList = $devTeamList + $leadedTeamList + $managedTeamList; 
$projectList = $sessionUser->getProjectList($teamList);

$ccheck = new ConsistencyCheck($projectList);

$cerrList = $ccheck->check();

   if (0 == count($cerrList)) {
   	echo "Pas d'erreur.</br>\n";
   } else {

	   echo "<div align='left'>\n";
	   echo "<table>\n";
	   echo "<caption>".count($cerrList).T_(" Error(s) in Mantis Tasks")."</caption>\n";   
	   echo "<tr>\n";
	   echo "<th>".T_("User")."</th>\n";
	   echo "<th>".T_("Task")."</th>\n";
	   echo "<th title='".T_("last modification date")."'>Date</th>\n";
	   echo "<th>".T_("Status")."</th>\n";
      echo "<th>".T_("Level")."</th>\n";
	   echo "<th>".T_("Error Description")."</th>\n";
	   echo "</tr>\n";
	   foreach ($cerrList as $cerr) {
	         
	   	 $user = new User($cerr->userId);
          $issue = new Issue($cerr->bugId);
	   	 echo "<tr>\n";
	       echo "<td>".$user->getName()."</td>\n";
	       echo "<td>".mantisIssueURL($cerr->bugId, $issue->summary)."</td>\n";
	       echo "<td>".date("Y-m-d", $cerr->timestamp)."</td>\n";
	       echo "<td>".$statusNames[$cerr->status]."</td>\n";
          echo "<td>$cerr->severity</td>\n";
	       echo "<td>$cerr->desc</td>\n";
	       echo "</tr>\n";
	   }
      echo "</table>\n";
      echo "</div>\n";
   }
   
   
   
   
?>
</div>

<?php include '../footer.inc.php'; ?>
