<?php 
if (!isset($_SESSION)) { 
	$tokens = explode('/', $_SERVER['PHP_SELF'], 3);
	$sname = str_replace('.', '_', $tokens[1]);
	session_name($sname); 
	session_start(); 
	header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); 
} 

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
if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}

$page_name = T_("Uninstall");
include 'header.inc.php';

include 'login.inc.php';
include 'uninstall_menu.inc.php';
?>

<script language="JavaScript">

function uninstall() {
	document.forms["form1"].action.value="uninstall";
   document.forms["form1"].is_modified.value= "true";
	document.forms["form1"].submit();
}

</script>

<div id="content">



<?php
include_once 'user.class.php';
include_once 'install.class.php';


// ------------------------------------------------
function displayForm($originPage, $is_modified,
                     $isBackup,
                     $filename) {

	echo "<form id='form1' name='form1' method='post' action='$originPage' >\n";

	// ------
	echo "<h2>".T_("Do you want to remove CodevTT from your Mantis server ?")."</h2>\n";
	echo "<span class='help_font'>".T_("This step will clean Mantis DB.")."</span><br/>\n";
   echo "  <br/>\n";
   echo "  <br/>\n";
   echo "  <br/>\n";

   // ---
   $isChecked = $isBackup ? "CHECKED" : "";
   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_backup' id='cb_backup'></input></td>\n";
   echo "    <td width='70'>Backup data</td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td></td>";
   Config::setQuiet(true);
   $codevReportsDir = Config::getInstance()->getValue(Config::id_codevReportsDir);
   Config::setQuiet(false);
   echo "    <td><span class='help_font'>".T_("Backup file will ve saved in CodevTT reports directory").". ( $codevReportsDir )</span></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td></td>";
   echo "    <td>".T_("Filename").": <input name='backup_filename' id='backup_filename' type='text' value='$filename' size='50'>";
   echo "  </tr>\n";
   echo "</table>\n";

   // ----------
   echo "  <br/>\n";
	echo "  <br/>\n";
	echo "<div  style='text-align: center;'>\n";
	echo "<input type=button style='font-size:150%' value='".T_("Uninstall")." !' onClick='javascript: uninstall()'>\n";
	echo "</div>\n";

  // ------
	echo "<input type=hidden name=action      value=noAction>\n";
	echo "<input type=hidden name=is_modified value=$is_modified>\n";

	echo "</form>";
}

/**
 *
 * backup Mantis DB (including CodevTT tables, if exists)
 *
 * @param $filename
 */
function backupDB($filename) {

	global $db_mantis_host;
	global $db_mantis_user;
	global $db_mantis_pass;
	global $db_mantis_database;

	$command = "mysqldump --host=$db_mantis_host --user=$db_mantis_user --password=$db_mantis_pass  $db_mantis_database > $filename";

	echo "dumping MantisDB to $filename ...</br>";
	#$status = system($command, $retCode);
	$status = exec($command, $output, $retCode);
	if (0 != $retCode) {
	   echo "BACKUP FAILED (err $retCode) $status</br>";
	}
	return $retCode;
}

function displayProjectsToRemove() {

	echo "Please MANUALY delete the following projects:</br>";

	$prjList = array();

	// find externalTasks project
	$extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);
	$project = ProjectCache::getInstance()->getProject($extproj_id);
	$prjList[$project->id] = $project->name;

	// find sideTasks projects
	$query = "SELECT mantis_project_table.id, mantis_project_table.name ".
	               "FROM `codev_sidetasks_category_table`, `mantis_project_table` ".
	               "WHERE mantis_project_table.id = codev_sidetasks_category_table.project_id ".
	               "ORDER BY mantis_project_table.name DESC";

	$result = mysql_query($query) or die("Query failed: $query");
	while($row = mysql_fetch_object($result)) {
	   $prjList[$row->id] = $row->name;
	}

   echo "<ul>\n";
	foreach ($prjList as $id => $name) {
		echo "<li title='$id'>$name</li>";
	}
	echo "</ul>\n";


}

/**
 * NOTE: function adapted from from mantis/core/custom_field_api.php
 *
 * Delete the field definition and all associated values and project associations
 * return true on success, false on failure
 * @param int $p_field_id custom field id
 * @return bool
*/
function custom_field_destroy( $p_field_id ) {

	# delete all values
	$query = "DELETE FROM `mantis_custom_field_string_table` WHERE field_id= $p_field_id;";
	mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

	# delete all project associations
	$query = "DELETE FROM `mantis_custom_field_project_table` WHERE field_id= $p_field_id;";
	mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

	# delete the definition
	$query = "DELETE FROM `mantis_custom_field_table` WHERE id= $p_field_id;";
	mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

	#custom_field_clear_cache( $p_field_id );

	#echo "DEBUG: customField $p_field_id removed</br>";
	return true;
}

function removeCustomFields() {
	$tcCustomField           = Config::getInstance()->getValue(Config::id_customField_ExtId);
	$mgrEffortEstim         = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);
	$estimEffortCustomField  = Config::getInstance()->getValue(Config::id_customField_effortEstim);
	$addEffortCustomField    = Config::getInstance()->getValue(Config::id_customField_addEffort);
	$remainingCustomField    = Config::getInstance()->getValue(Config::id_customField_remaining);
	$deadLineCustomField     = Config::getInstance()->getValue(Config::id_customField_deadLine);
	$deliveryDateCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);
	#$deliveryIdCustomField   = Config::getInstance()->getValue(Config::id_customField_deliveryId);

	custom_field_destroy($tcCustomField);
	custom_field_destroy($mgrEffortEstim);
	custom_field_destroy($estimEffortCustomField);
	custom_field_destroy($addEffortCustomField);
	custom_field_destroy($remainingCustomField);
	custom_field_destroy($deadLineCustomField);
	custom_field_destroy($deliveryDateCustomField);
	#custom_field_destroy($deliveryIdCustomField);

}


// ================ MAIN =================


// Admins only
global $admin_teamid;
$session_user = new User($_SESSION['userid']);
if (!$session_user->isTeamMember($admin_teamid)) {
	echo T_("Sorry, you need to be in the admin-team to access this page.");
	exit;
}

$originPage = "uninstall.php";
$action      = isset($_POST['action']) ? $_POST['action'] : '';
$is_modified = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";

$filename           = isset($_POST['backup_filename']) ? $_POST['backup_filename'] : "codevtt_backup_".date("Ymj").".sql";

// --- init
// 'is_modified' is used because it's not possible to make a difference
// between an unchecked checkBox and an unset checkbox variable
if ("false" == $is_modified) {

	$isBackup = true;
	#$isPart2 = true;
	#$isPart3 = true;
	#$isPart4 = true;

} else {
	$isBackup   = $_POST['cb_backup'];
	#$isPart2   = $_POST['cb_part2'];
	#$isPart3   = $_POST['cb_part3'];
	#$isPart4   = $_POST['cb_part4'];
}



// --- actions
if ("uninstall" == $action) {

	if (true == $isBackup) {
		echo "---- Backup<br/>";
	   $codevReportsDir = Config::getInstance()->getValue(Config::id_codevReportsDir);

	   $retCode = backupDB($codevReportsDir.DIRECTORY_SEPARATOR.$filename);
	   if (0 != $retCode) {
	   	echo "Uninstall aborted !";
	   	exit;
	   }
	   echo "</br>";
	}

	echo "1/4 ---- Remove CodevTT from Mantis menu</br>";
	echo "TODO</br>";

	echo "2/4 ---- Remove CodevTT specific projects</br>";
   displayProjectsToRemove();

   echo "3/4 ---- Remove CodevTT customFields</br>";
   removeCustomFields();

   echo "4/4 ---- Remove CodevTT tables from MantisDB</br>";
   execSQLscript("uninstall.sql");

   echo "5/5 ---- Remove CodevTT config files</br>";
   Install::deleteConfigFiles();

} else {

	// ----- DISPLAY PAGE

	$error = Install::checkMysqlAccess();
	if (TRUE == strstr($error, T_("ERROR"))) {
	 	echo "<span class='error_font'>$error</span><br/>";
		exit;
	}

	displayForm($originPage, $is_modified, $isBackup, $filename);
}

?>

</div>
