<?php
/*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

   include_once "tools.php";
   include_once "constants.php";
   include_once 'i18n.inc.php';
?>

<div class="menu">

<?php
global $mantisURL;


echo "<table>\n";
echo "   <tr>\n";
echo "      <td><a href='".getServerRootURL()."/index.php'>".T_("Home")."</a></td>\n";

echo "      <td><a href='".$mantisURL."' title='MantisBT'>Mantis</a></td>\n";

echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/timetracking/time_tracking.php' title=''>".T_("Time Tracking")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/timetracking/holidays_report.php' title='".T_("Holidays Reports")."'>".T_("Holidays")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/planning_report.php' title='".T_("Check DeadLines")."'>".T_("Planning")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/gantt_report.php' title='".T_("Gantt Chart")."'>".T_("Gantt")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/issue_info.php' title='".T_("Task Information")."'>".T_("Task info")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/project_info.php' title='".T_("Project Information")."'>".T_("Project info")."</a>\n";
echo "      </td>\n";
echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/timetracking/team_activity_report.php' title='".T_("Team Weekly activities")."'>".T_("Weekly activities")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/forecasting_report.php' title=''>".T_("Forecasting")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/productivity_report.php' title=''>".T_("Statistics")."</a>\n";
#echo "      |\n";
#echo "      <a href='".getServerRootURL()."/reports/mantis_reports.php' title=''>".T_("Mantis Reports")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/export_csv_weekly.php' title='".T_("Generate Excel Sheet")."'>".T_("Export to CSV")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/tools/check.php' title='".T_("Consistency Check")."'>".T_("Check")."</a>\n";
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
</div>

<?php
if (!isset($_SESSION['userid'])) {
    echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
    exit;
}
?>
