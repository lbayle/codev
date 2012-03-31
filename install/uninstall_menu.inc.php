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
include_once 'i18n.inc.php';
?>

<div id="menu">

<?php 
echo "<table class='menu'>\n";
echo "   <tr>\n";
echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/index.php' title=''>".T_("Home")."</a>\n";
echo "      </td>\n";

echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/install/install.php' title=''>".T_("Install")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/install/uninstall.php' title=''>".T_("Uninstall")."</a>\n";
echo "      </td>\n";

echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/doc/index.php' title='".T_("Documentation")."'>Doc</a>\n";
echo "      </td>\n";
echo "  </tr>\n";
echo "</table>";
?>
<br/>
<br/>
</div>
