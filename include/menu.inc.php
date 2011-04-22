<?php
   include_once "tools.php";
   include_once 'i18n.inc.php';
?>

<div id="menu">

<?php 
echo "<table class='menu'>\n";
echo "   <tr>\n";
echo "      <td class='menu'><a href='".getServerRootURL()."/index.php'>".T_("Home")."</a></td>\n";

echo "      <td><a href='http://".$_SERVER['HTTP_HOST']."/mantis' title='MantisBT'>Mantis</a></td>\n";

echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/timetracking/time_tracking.php' title=''>".T_("Time Tracking")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/timetracking/holidays_report.php' title='".T_("Holidays Reports")."'>".T_("Holidays")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/tools/check.php' title='".T_("Consistency Check")."'>".T_("Check")."</a>\n";
echo "      </td>\n";
echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/reports/issue_info.php' title='".T_("Task Information")."'>".T_("Task info")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/timetracking/team_activity_report.php' title='".T_("Team Weekly activities")."'>".T_("Weekly activities")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/productivity_report.php' title='".T_("Productivity Reports")."'>".T_("Productivity")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/statistics.php' title=''>".T_("Statistics")."</a>\n";
#echo "      |\n";
#echo "      <a href='".getServerRootURL()."/reports/mantis_reports.php' title=''>".T_("Mantis Reports")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/export_csv_weekly.php' title='".T_("Generate Excel Sheet")."'>".T_("Export to CSV")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/planning_report.php' title='".T_("Check DeadLines (~Gant diagram)")."'>".T_("Planning")."</a>\n";
echo "      </td>\n";
echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/doc/index.php' title='".T_("Documentation")."'>Doc</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/admin/index.php' title='".T_("CoDev Administration")."'>Admin</a>\n";
echo "      </td>\n";
echo "  </tr>\n";
echo "</table>";
?>
<br/>
<br/>
</div>
