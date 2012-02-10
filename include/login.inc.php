<?php /*
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
*/ ?>
<?php

include_once "constants.php";
include_once 'i18n.inc.php';
include_once "tools.php";

//
// MAIN
//
echo "<div id='login'>\n";
if (isset($_SESSION['userid'])) {
  echo T_("Logged in as ").$_SESSION['username']." (".$_SESSION['realname'].") <span class='floatr'><a href='".getServerRootURL()."/logout.php' title='logout'>".T_("log out")."</a></span>\n";
} else {
  echo "<a href='".getServerRootURL()."/'>".T_("log in")."</a> <span class='floatr'><a href='".getServerRootURL()."'>".T_("log in")."</a></span>\n";
}
echo "</div>";
   
?>
