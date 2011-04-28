<?php  if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>

<?php
include_once 'path.inc.php';
include_once 'i18n.inc.php';
include 'header.inc.php'; 



unset($_SESSION['userid']);
unset($_SESSION['username']);
unset($_SESSION['realname']);
session_destroy();
          
echo ("<script> parent.location.replace('../codev'); </script>");
       
?>
     
<?php include 'footer.inc.php'; ?>
