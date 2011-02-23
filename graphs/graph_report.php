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
   $_POST[page_name] = T_("Statistics"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

<div id="content">

<?php 
   
include_once "period_stats_report.class.php";
include_once "issue.class.php";

$start_year = date('Y') -1;

$defaultTeam = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;


// Connect DB
$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die(T_("Could not connect to database"));
mysql_select_db($db_mantis_database) or die("Could not select database");


// ---------
$periodStatsReport = new PeriodStatsReport($start_year, $teamid);
$periodStatsReport->computeReport();

$submitted = $periodStatsReport->getStatus("submitted");
$resolved  = $periodStatsReport->getStatus("delta_resolved");


// ---------

$graph_title=T_("Submitted / Resolved");

$val1 = array_values($submitted); # array(4, 2, 5, 3, 3, 6, 7, 6, 5, 4, 3, 2);
$strVal1 = "leg1=Submitted&x1=".implode(':', $val1);

$val2 = array_values($resolved); # array(3, 8, 7, 6, 2, 1, 3, 2, 5, 7, 6, 6);
$strVal2 = "leg2=Resolved&x2=".implode(':', $val2);

$y = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
$strY = "y=".implode(':', $y);


echo "<img src='two_lines.php?title=$graph_title&$strY&$strVal1&$strVal2'/>";

?>

</div>

<?php include 'footer.inc.php'; ?>

