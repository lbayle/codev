<?php
include_once('../include/session.inc.php');

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

$page_name = "CoDev Administration";
require_once 'header.inc.php';
require_once 'login.inc.php';
require_once 'menu.inc.php';
?>
<br/>
<?php include 'menu_admin.inc.php'; ?>

<?php
   global $codevVersion;
   echo "<div align=center>";
   echo "$codevVersion </br>\n";
   echo "<br/>\n";
   echo "<br/>\n";
   echo "Please visit &nbsp; <a href='http://codevtt.org' target='_blank' >http://codevtt.org</a> &nbsp; for more information.";
   echo "</div>";

   echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";
?>

<?php include 'footer.inc.php'; ?>
