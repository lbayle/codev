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

<?php 

include_once 'consistency_check.class.php'; 


$ccheck = new ConsistencyCheck($_SESSION['userid']);

//$cerrList = ;
$cerrList1 = $ccheck->checkBI();
$cerrList2 = $ccheck->checkRAE();
$cerrList3 = $ccheck->checkResolved();
$cerrList = array_merge($cerrList1, $cerrList2, $cerrList3);

   echo "<div align='left'>\n";
   echo "<table>\n";
   echo "<caption>Errors:</caption>\n";   
   echo "<tr>\n";
   echo "<th>User</th>\n";
   echo "<th>Issue</th>\n";
   echo "<th>Error Description</th>\n";
   echo "</tr>\n";
   foreach ($cerrList as $cerr) {
         
       echo "<tr>\n";
       echo "<td>$cerr->userId</td>\n";
       echo "<td>".mantisIssueURL($cerr->bugId)."</td>\n";
       echo "<td>$cerr->desc</td>\n";
       echo "</tr>\n";
   }
   
   
   
   
   
?>
