<?php if (!isset($_SESSION)) { session_start(); } ?>
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

<?php include_once '../path.inc.php'; ?>
<?php include_once 'i18n.inc.php'; ?>

<?php
   $_POST[page_name] = T_("Install - Step 3");
   include 'install_header.inc.php';

   include 'install_menu.inc.php';
?>

<script language="JavaScript">

function setDatabaseInfo(){
   // check fields
   foundError = 0;
   msgString = "The following fields are missing:\n\n"

   if (0 == document.forms["databaseForm"].db_mantis_host.value)     { msgString += "Hostname\n"; ++foundError; }
   if (0 == document.forms["databaseForm"].db_mantis_database.value)     { msgString += "Database\n"; ++foundError; }
   if (0 == document.forms["databaseForm"].db_mantis_user.value)     { msgString += "User\n"; ++foundError; }
   //if (0 == document.forms["databaseForm"].db_mantis_password.value)     { msgString += Password"\n"; ++foundError; }

   if (0 == foundError) {
     document.forms["databaseForm"].action.value="setDatabaseInfo";
     document.forms["databaseForm"].submit();
   } else {
     alert(msgString);
   }
 }

</script>

<div id="content">


<?php

include_once 'install.class.php';

function displayStepInfo() {
   echo "<h2>".T_("Prerequisites")."</h2>\n";
   echo "Step 1,2 finished";
   echo "<br/>";
   echo "<h2>".T_("Actions")."</h2>\n";
   echo "<ul>\n";
   echo "<li>Set path for .CSV reports (Excel)</li>";
   echo "<li>Create default tasks</li>";
   echo "<li>Create default jobs</li>";
   echo "<li>Add custom fields to existing projects</li>";
   echo "</ul>\n";
   echo "";
}


function displayForm($originPage,
            $g_eta_enum_string, $eta_balance_string,
            $g_status_enum_string, $s_priority_enum_string, $s_resolution_enum_string) {

   echo "<form id='databaseForm' name='databaseForm' method='post' action='$originPage' >\n";
   echo "<hr align='left' width='20%'/>\n";
   echo "<h2>".T_("Confirm Mantis customizations")."</h2>\n";


   echo "  <br/>\n";
   echo "  <br/>\n";
   echo "<input type=button value='".T_("Proceed Step 3")."' onClick='javascript: setDatabaseInfo()'>\n";

   echo "<input type=hidden name=action      value=noAction>\n";

   echo "</form>";
}

// ================ MAIN =================


$originPage = "install_step3.php";

$codevReportsDir      = "\\\\172.24.209.4\Share\FDJ\Codev_Reports";


$action      = $_POST[action];

displayStepInfo();
/*
displayForm($originPage,
            $g_eta_enum_string, $eta_balance_string,
            $g_status_enum_string, $s_priority_enum_string, $s_resolution_enum_string);


if ("setDatabaseInfo" == $action) {

   $install = new Install();




}
*/
?>

