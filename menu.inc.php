<?php include_once "tools.php"; ?>

<div id="menu">

<ul>
<?php echo "<li><a href='".getServerRootURL()."/home.php' title='Acceuil'>CoDev</a></li>" ?>
</ul>

<br/>



<ul>
<?php echo "<li><a href='http://".$_SERVER['HTTP_HOST']."/mantis.php' title='MantisBT'>Mantis</a></li>" ?>
<?php echo "<li><a href='".getServerRootURL()."/doc/AOI-DOC-Cycle_dev-v2.1.png' title='Cycle de vie'>Cycle flow</a></li>" ?>
<?php echo "<li><a href='".getServerRootURL()."/doc/mantis_userguide.html' title='Aide'>User Guide</a></li>" ?>
</ul>

<br/>

<ul>
<?php echo "<li><a href='".getServerRootURL()."/timetracking/time_tracking.php' title=''>Time Tracking</a></li>" ?>
</ul>

<br/>

<ul>
<?php echo "<li><a href='".getServerRootURL()."/reports/' title=''>Mantis Reports</a></li>" ?>
<?php echo "<li><a href='".getServerRootURL()."/timetracking/time_tracking_report.php' title='Indicateurs de production'>Productivity indicators</a></li>" ?>
<?php echo "<li><a href='".getServerRootURL()."/timetracking/week_activity_report.php' title='Activit&eacute; hebdo'>Weekly activities</a></li>" ?>
<?php echo "<li><a href='".getServerRootURL()."/reports/issue_info.php' title='Activit&eacute; par t&acirc;che'>Task tracking</a></li>" ?>
<?php echo "<li><a href='".getServerRootURL()."/timetracking/holidays_report.php' title='Vacances'>Holidays Reports</a></li>" ?>
</ul>

<hr/>

</div>
