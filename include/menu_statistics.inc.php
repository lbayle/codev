<?php /*
    This file is part of CoDevTT.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDevTT is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>
<?php
   include_once '../path.inc.php';
   include_once 'i18n.inc.php';
   include_once "tools.php";
?>



<div id="menu">

<?php


echo "<table>\n";
echo "   <tr>\n";
echo "      <td><a href='".getServerRootURL()."/reports/productivity_report.php'>".T_("Period Statistics")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/statistics.php'>".T_("History")."</a>\n";
echo "      </td>\n";
echo "   </tr>\n";
echo "</table>\n";
?>
<br/>
<br/>
</div>
