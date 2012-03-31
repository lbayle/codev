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

include_once '../path.inc.php';
include_once 'i18n.inc.php';
include_once "tools.php";
?>

<div id="menu">

<?php 

echo "<table>\n";
echo "   <tr>\n";
echo "      <td><a href='".getServerRootURL()."/admin/create_team.php'>".T_("Create Team")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/admin/edit_team.php'>".T_("Edit Team")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/admin/edit_jobs.php'>".T_("Edit Jobs")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/admin/edit_holidays.php'>".T_("Edit Holidays")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/admin/prepare_project.php'>".T_("Prepare Projects")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/tools/workflow.php'>".T_("Clone Projects")."</a>\n";
echo "      </td>\n";
echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/admin/logs.php'>".T_("logs")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/doc/codev_adminguide.html'>".T_("Admin Guide")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/install/uninstall.php' title=''>".T_("Uninstall")."</a>\n";
echo "      </td>\n";
echo "   </tr>\n";
echo "</table>\n";
?>      
<br/>
<br/>
</div>
