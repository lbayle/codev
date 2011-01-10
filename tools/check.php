<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
include_once "../tools.php";
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../'>login</a> to access this page.");
  
  exit;
} 
?>

<?php
   $_POST[page_name] = "Consistency Check"; 
   include '../header.inc.php'; 
?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>

<div id="content">
<?php 

include_once 'consistency_check.class.php'; 
include_once '../auth/user.class.php'; 


// ================ MAIN =================

$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");

$userid = $_SESSION['userid'];


$ccheck = new ConsistencyCheck();

$cerrList = $ccheck->check();

   if (0 == count($cerrList)) {
   	echo "Pas d'erreur.</br>\n";
   } else {

	   echo "<div align='left'>\n";
	   echo "<table>\n";
	   echo "<caption>".count($cerrList)." Erreur(s) dans les fiches Mantis</caption>\n";   
	   echo "<tr>\n";
	   echo "<th>User</th>\n";
	   echo "<th>Issue</th>\n";
	   echo "<th title='last modification date'>Date</th>\n";
	   echo "<th>Status</th>\n";
	   echo "<th>Error Description</th>\n";
	   echo "</tr>\n";
	   foreach ($cerrList as $cerr) {
	         
	   	$user = new User($cerr->userId);
	       echo "<tr>\n";
	       echo "<td>".$user->getName()."</td>\n";
	       echo "<td>".mantisIssueURL($cerr->bugId)."</td>\n";
	       echo "<td>".date("Y-m-d", $cerr->timestamp)."</td>\n";
	       echo "<td>".$statusNames[$cerr->status]."</td>\n";
	       echo "<td>$cerr->desc</td>\n";
	       echo "</tr>\n";
	   }
      echo "</table>\n";
      echo "</div>\n";
   }
   
   
   
   
?>
</div>

<?php include '../footer.inc.php'; ?>
