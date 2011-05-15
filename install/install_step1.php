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
   $_POST[page_name] = T_("Install - Step 1");
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
   echo "<ul>\n";
   echo "<li>Successfully installed Mantis</li>";
   echo "<li>MySQL 'codev' user created with access to Mantis DB</li>";
   echo "</ul>\n";
   echo "<h2>".T_("Actions")."</h2>\n";
   echo "<ul>\n";
   echo "<li>Create database configuration file for CoDev</li>";
   echo "<li>Create CoDev database tables</li>";
   echo "<li>Add CoDev specific custom fields to Mantis</li>";
   echo "<li>Create CommonSideTasks Project</li>";
   echo "</ul>\n";
   echo "";
}


function displayDatabaseForm($originPage, $db_mantis_host, $db_mantis_database, $db_mantis_user, $db_mantis_pass) {

   echo "<form id='databaseForm' name='databaseForm' method='post' action='$originPage' >\n";

   echo "<hr align='left' width='20%'/>\n";
   echo "<h2>".T_("Mantis Database Info")."</h2>\n";

   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Hostname")."</td>\n";
   echo "    <td><input size='50' type='text' name='db_mantis_host'  id='db_mantis_host' value='$db_mantis_host'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Database Name")."</td>\n";
   echo "    <td><input size='50' type='text' name='db_mantis_database'  id='db_mantis_database' value='$db_mantis_database'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("User")."</td>\n";
   echo "    <td><input size='50' type='text' name='db_mantis_user'  id='db_mantis_user' value='$db_mantis_user'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Password")."</td>\n";
   echo "    <td><input size='50' type='password' name='db_mantis_pass'  id='db_mantis_pass' value='$db_mantis_pass'></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";

   echo "  <br/>\n";
   echo "  <br/>\n";
   echo "<input type=button value='".T_("Proceed Step 1")."' onClick='javascript: setDatabaseInfo()'>\n";

   echo "<input type=hidden name=action      value=noAction>\n";

   echo "</form>";
}

// ================ MAIN =================


$originPage = "install_step1.php";
$sqlFile    = "./bugtracker_install.sql";

$db_mantis_host     = isset($_POST[db_mantis_host]) ?     $_POST[db_mantis_host]     : 'localhost';
$db_mantis_database = isset($_POST[db_mantis_database]) ? $_POST[db_mantis_database] : 'bugtracker';
$db_mantis_user     = isset($_POST[db_mantis_user]) ?     $_POST[db_mantis_user]     : 'codev';
$db_mantis_pass     = isset($_POST[db_mantis_pass]) ?     $_POST[db_mantis_pass]     : '';

$action      = $_POST[action];

displayStepInfo();

displayDatabaseForm($originPage, $db_mantis_host, $db_mantis_database, $db_mantis_user, $db_mantis_pass);


if ("setDatabaseInfo" == $action) {

   $install = new Install();

   $msg = $install->checkDBConnection($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);

   if ($msg) {
   	echo $msg;
   	exit;
   } else {

   	echo "DEBUG createMysqlConfigFile<br/>";
   	$install->createMysqlConfigFile($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);

   	echo "DEBUG execSQLscript<br/>";
   	$install->execSQLscript($sqlFile);

   	echo "DEBUG createCustomFields<br/>";
   	$install->createCustomFields();

   	echo "DEBUG createCommonSideTasksProject<br/>";
   	$stproj_id = $install->createCommonSideTasksProject(T_("SideTasks"), T_("CoDev commonSideTasks Project"));
   }

}

?>

