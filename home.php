<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include 'header.inc.php'; ?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

<h1>Bienvenu sur le serveur CoDev</h1>


<div id="content" class="center">

<div id="mantis">
<?php echo "<a href='http://".$_SERVER['HTTP_HOST']."/mantis.php'><img src='images/Mantis_Main.jpg' alt='Mantis' width='200' height='120' /></a>" ?>
<a href="./doc/AOI-DOC-Cycle_dev-v2.1.png"><img src="images/Mantis_Cycle_de_vie.jpg" alt="Cycle de vie" width="200" height="120" /></a>
<a href="./doc/mantis_userguide.html"><img src="images/Mantis_User_guide.jpg" alt="User Guide" width="200" height="120" /></a>
</div>

<div id="timetracking">
<a href="./timetracking/time_tracking.php"><img src="images/Time_Tracking.jpg" alt="Time Tracking" width="200" height="120" /></a>
</div>

<div id="reports">
<a href="./reports/"><img src="images/codev_mantis_reports.jpg" alt="Mantis Reports" width="200" height="120" /></a>
<a href="./timetracking/time_tracking_report.php"><img src="images/codev_time_tracking_report.jpg" alt="Indicateurs de production" width="200" height="120" /></a>
<a href="./timetracking/week_activity_report.php"><img src="images/codev_week_activity_report.jpg" alt="Activit&eacute; Hebdo" width="200" height="120" /></a>
<a href="./reports/issue_info.php"><img src="images/codev_issue_info.jpg" alt="Activit&eacute; Tache" width="200" height="120" /></a>
<a href="./timetracking/holidays_report.php"><img src="images/codev_holiday_report.jpg" alt="Holidays Reports" width="200" height="120" /></a>
</div>

</div>

<?php include 'footer.inc.php'; ?>
