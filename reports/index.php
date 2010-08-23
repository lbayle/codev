<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include '../header.inc.php'; ?>

<h1>Mantis reports</h1>

<?php include '../login.inc.php'; ?>

<?php include '../menu.inc.php'; ?>

<div id="content">

<?php

//
// MANTIS CoDev History Reports
//
include_once "../constants.php";
include_once "../tools.php";
include_once "issue.class.php";
include_once "period_stats_report.class.php";
include_once "issue_tracking.class.php";
include_once "issue_tracking_fdj.class.php";

// ================ MAIN ================
$bugList = array();

// Connect DB
$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");

// ----------- COMPUTE DURATIONS ----------
$issueTracking = new IssueTrackingFDJ();
$issueTracking->initialize();

// ----------- PERIOD STATS ----------
// TODO: get values from HTML fields
$start_year = date('Y');
$periodStatsReport = new PeriodStatsReport($start_year);
$periodStatsReport->computeReport();

// ---------- DISPLAY -------------
$periodStatsReport->displayHTMLReport();
$issueTracking->forseingTableDisplay();
$issueTracking->durationsTableDisplay();

// ---------- CSV -------------
$issueTracking->durationsTableToCSV("E:\\FDJ_Mantis_reports\\".date("Ymd", time())."_durations.csv");
$issueTracking->estimationsToCSV("E:\\FDJ_Mantis_reports\\".date("Ymd", time())."_estimations.csv");

// Fermeture de la connexion
mysql_close($link);
//exit;
?>

</div>

<?php include '../footer.inc.php'; ?>
