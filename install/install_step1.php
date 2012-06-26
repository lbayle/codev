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

$page_name = T_("Install - Step 1");
require_once 'install_header.inc.php';

require_once 'install_menu.inc.php';
?>

<script language="JavaScript">

function setDatabaseInfo(){
   // check fields
   foundError = 0;
   msgString = "The following fields are missing:\n\n";

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
include_once 'user.class.php';

function displayStepInfo() {
   echo "<h2>".T_("Prerequisites")."</h2>\n";
   echo "<ul>\n";
   echo "<li>Successfully installed Mantis</li>";
   echo "<li>user 'apache' has write access to CodevTT directory</li>";
   echo "<li>MySQL 'codev' user created with access to Mantis DB</li>";
   echo "</ul>\n";
   echo "<h2>".T_("Actions")."</h2>\n";
   echo "<ul>\n";
   echo "<li>Create database configuration file for CodevTT</li>";
   echo "<li>Create CodevTT database tables</li>";
   echo "<li>Add CodevTT specific custom fields to Mantis</li>";
   echo "<li>Create ExternalTasks Project</li>";
   echo "<li>Create CodevTT Admin team</li>";
   echo "</ul>\n";
   echo "";
}


function displayDatabaseForm($originPage, $db_mantis_host, $db_mantis_database, $db_mantis_user, $db_mantis_pass) {

   echo "<form id='databaseForm' name='databaseForm' method='post' action='$originPage' >\n";

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
   echo "<div  style='text-align: center;'>\n";
   echo "<input type=button style='font-size:150%' value='".T_("Proceed Step 1")."' onClick='javascript: setDatabaseInfo()'>\n";
   echo "</div>\n";

   echo "<input type=hidden name=action      value=noAction>\n";

   echo "</form>";
}

// ================ MAIN =================


$originPage = "install_step1.php";
$sqlFile_tables        = "./codevtt_tables.sql";
$sqlFile_procedures    = "./codevtt_procedures.sql";

$db_mantis_host     = isset($_POST['db_mantis_host']) ?     $_POST['db_mantis_host']     : 'localhost';
$db_mantis_database = isset($_POST['db_mantis_database']) ? $_POST['db_mantis_database'] : 'bugtracker';
$db_mantis_user     = isset($_POST['db_mantis_user']) ?     $_POST['db_mantis_user']     : 'codevtt';
$db_mantis_pass     = isset($_POST['db_mantis_pass']) ?     $_POST['db_mantis_pass']     : '';

$action      = isset($_POST['action']) ? $_POST['action'] : '';

#displayStepInfo();
#echo "<hr align='left' width='20%'/>\n";

displayDatabaseForm($originPage, $db_mantis_host, $db_mantis_database, $db_mantis_user, $db_mantis_pass);


if ("setDatabaseInfo" == $action) {

   $install = new Install();

   $errStr = $install->checkDBConnection($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
   	exit;
   }

   // check if I can create CodevTT tables & procedures
   $errStr = $install->checkDBprivileges($db_mantis_database);
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
   	exit;
   }


   echo "DEBUG 1/3 createMysqlConfigFile<br/>";
   $errStr = $install->createMysqlConfigFile($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
      exit;
   }

   // TODO check user access (create_table, create_procedure, alter, insert,delete, ...)


   echo "DEBUG 2/3 execSQLscript - create Tables<br/>";
   $retCode = execSQLscript2($sqlFile_tables);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlFile_tables</span><br/>";
      exit;
   }

   echo "DEBUG 3/3 execSQLscript2 - create Procedures<br/>";
   $retCode = execSQLscript2($sqlFile_procedures);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlFile_procedures</span><br/>";
      exit;
   }

   // everything went fine, goto step2
   echo ("<script> parent.location.replace('install_step2.php'); </script>");


}

?>

