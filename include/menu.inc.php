<?php
   include_once "tools.php";
   include_once 'i18n.inc.php';
?>

<div id="menu">

<?php 
echo "
<table class='menu'>
   <tr>
      <td class='menu'><a href='".getServerRootURL()."/index.php'>".T_("Home")."</a></td>

      <td><a href='http://".$_SERVER['HTTP_HOST']."/mantis' title='MantisBT'>Mantis</a></td>

      <td>
      <a href='".getServerRootURL()."/timetracking/time_tracking.php' title=''>".T_("Time Tracking")."</a>
      |
      <a href='".getServerRootURL()."/timetracking/holidays_report.php' title='".T_("Holidays Reports")."'>".T_("Holidays")."</a>
      |
      <a href='".getServerRootURL()."/tools/check.php' title='".T_("Consistency Check")."'>".T_("Check")."</a>
      </td>
      <td>
      <a href='".getServerRootURL()."/reports/issue_info.php' title='".T_("Task Information")."'>".T_("Task info")."</a>
      |
      <a href='".getServerRootURL()."/timetracking/week_activity_report.php' title='".T_("Weekly activities")."'>".T_("Weekly activities")."</a>
      |
      <a href='".getServerRootURL()."/reports/productivity_report.php' title='".T_("Statistics")."'>".T_("Productivity Reports")."</a>
      |
      <a href='".getServerRootURL()."/reports/statistics.php' title=''>".T_("Statistics")."</a>
      |
      <a href='".getServerRootURL()."/reports/mantis_reports.php' title=''>".T_("Mantis Reports")."</a>
      |
      <a href='".getServerRootURL()."/reports/proj_management_report.php' title='".T_("Project Management Reports")."'>".T_("Export to CSV")."</a>
      </td>
      <td>
      <a href='".getServerRootURL()."/doc/index.php' title='".T_("Documentation")."'>Doc</a>
      |
      <a href='".getServerRootURL()."/admin/index.php' title='".T_("CoDev Administration")."'>Admin</a>
      </td>
   </tr>
</table>";
?>
<br/>
<br/>
</div>
