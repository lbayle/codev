<?php
require('../include/session.inc.php');

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

require('../path.inc.php');

require('include/super_header.inc.php');

include_once('i18n/i18n.inc.php');

include_once('install/install.class.php');

include_once('include/internal_config.inc.php');

require_once('tools.php');

if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}

$page_name = T_("Uninstall");
require_once('include/header.inc.php');

require_once('include/login.inc.php');
require_once('install/uninstall_menu.inc.php');

?>

<script type="text/javascript">
   function uninstall() {
      document.forms["form1"].action.value="uninstall";
      document.forms["form1"].is_modified.value= "true";
      document.forms["form1"].submit();
   }
</script>

<div id="content">

<?php

function displayForm($originPage, $is_modified, $isBackup, $filename) {
   echo "<form id='form1' name='form1' method='post' action='$originPage' >\n";

   echo "<h2>".T_("Do you want to remove CodevTT from your Mantis server ?")."</h2>\n";
   echo "<span class='help_font'>".T_("This step will clean Mantis DB.")."</span><br/>\n";
   echo "  <br/>\n";
   echo "  <br/>\n";

   $isChecked = $isBackup ? "CHECKED" : "";
   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_backup' id='cb_backup' /></td>\n";
   echo "    <td width='70'>Backup data</td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td></td>";
   Config::setQuiet(true);
   $codevReportsDir = InternalConfig::$codevReportsDir;
   Config::setQuiet(false);
   echo "    <td><span class='help_font'>".T_("Backup file will ve saved in CodevTT reports directory").". ( $codevReportsDir )</span></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td></td>";
   echo "    <td>".T_("Filename").": <input name='backup_filename' id='backup_filename' type='text' value='$filename' size='50'>";
   echo "  </tr>\n";
   echo "</table>\n";

   echo "  <br/>\n";
   echo "  <br/>\n";
   echo "<div  style='text-align: center;'>\n";
   echo "<input type=button style='font-size:150%' value='".T_("Uninstall")." !' onclick='uninstall()'>\n";
   echo "</div>\n";

   echo "<input type=hidden name=action      value=noAction />\n";
   echo "<input type=hidden name=is_modified value=$is_modified />\n";
   echo "</form>";
}

/**
 * backup Mantis DB (including CodevTT tables, if exists)
 * @param string $filename
 * @return bool
 */
function backupDB($filename) {
   echo "dumping MantisDB to $filename ...</br>";
   return SqlWrapper::getInstance()->sql_dump($filename);
}

function displayProjectsToRemove() {
   echo "Please MANUALY delete the following projects:</br>";

   $prjList = array();

   // find externalTasks project
   $extproj_id = InternalConfig::$externalTasksProject;
   $project = ProjectCache::getInstance()->getProject($extproj_id);
   $prjList[$project->id] = $project->name;
   
   // find sideTasks projects
   $sideTaskProj_id = Project::type_sideTaskProject;
   $query = "SELECT project.id, project.name ".
            "FROM `mantis_project_table` as project ".
            "JOIN `codev_team_project_table` as team_project ON project.id = team_project.project_id ".
            "WHERE team_project.type = $sideTaskProj_id ".
            "ORDER BY project.name DESC;";

   $result = SqlWrapper::getInstance()->sql_query($query) or die("Query failed: $query");
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
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
 * @return bool
*/
function removeCustomFields() {
   $fieldIds = array(
      InternalConfig::$tcCustomField,
      InternalConfig::$mgrEffortEstimCustomField,
      InternalConfig::$estimEffortCustomField,
      InternalConfig::$addEffortCustomField,
      InternalConfig::$backlogCustomField,
      InternalConfig::$deadLineCustomField,
      InternalConfig::$deliveryDateCustomField
   );
   
   # delete all values
   $query = "DELETE FROM `mantis_custom_field_string_table` WHERE field_id IN (".implode(', ', $fieldIds).");";
   SqlWrapper::getInstance()->sql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".SqlWrapper::getInstance()->sql_error()."</span>");

   # delete all project associations
   $query = "DELETE FROM `mantis_custom_field_project_table` WHERE field_id IN (".implode(', ', $fieldIds).");";
   SqlWrapper::getInstance()->sql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".SqlWrapper::getInstance()->sql_error()."</span>");

   # delete the definition
   $query = "DELETE FROM `mantis_custom_field_table` WHERE id IN (".implode(', ', $fieldIds).");";
   SqlWrapper::getInstance()->sql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".SqlWrapper::getInstance()->sql_error()."</span>");
   
   #custom_field_clear_cache( $p_field_id );
   
   #echo "DEBUG: customField $p_field_id removed</br>";
   return true;
}

/**
 * remove CodevTT Config Files
 */
function saveConfigFiles() {
   global $logger;
   
   $codevReportsDir = InternalConfig::$codevReportsDir;
   if (file_exists(Install::FILENAME_CONSTANTS)) {
      $filename = ereg_replace(".*/", "", Install::FILENAME_CONSTANTS);
      $retCode = copy(Install::FILENAME_CONSTANTS, $codevReportsDir.DIRECTORY_SEPARATOR.$filename);
      if (!$retCode) {
         $logger->error("ERROR: Could not save file: " . Install::FILENAME_CONSTANTS);
         return FALSE;
      }
   }
   if (file_exists(Install::FILENAME_MYSQL_CONFIG)) {
      $filename = ereg_replace(".*/", "", Install::FILENAME_MYSQL_CONFIG);
      $retCode = copy(Install::FILENAME_MYSQL_CONFIG, $codevReportsDir.DIRECTORY_SEPARATOR.$filename);
      if (!$retCode) {
         $logger->error("ERROR: Could not save file: " . Install::FILENAME_MYSQL_CONFIG);
         return FALSE;
      }
   }
   
   return TRUE;
}

/**
 * remove CodevTT Config Files
 */
function deleteConfigFiles() {
   global $logger;
   
   if (file_exists(Install::FILENAME_CONSTANTS)) {
      $retCode = unlink(Install::FILENAME_CONSTANTS);
      if (!$retCode) {
         $logger->error("ERROR: Could not delete file: " . Install::FILENAME_CONSTANTS);
         return FALSE;
      }
   }
   if (file_exists(Install::FILENAME_MYSQL_CONFIG)) {
      $retCode = unlink(Install::FILENAME_MYSQL_CONFIG);
      if (!$retCode) {
         $logger->error("ERROR: Could not delete file: " . Install::FILENAME_MYSQL_CONFIG);
         return FALSE;
      }
   }
   
   return TRUE;
}

// ================ MAIN =================
// Admins only
$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
if ($session_user->isTeamMember(InternalConfig::$admin_teamid)) {
   $originPage = "uninstall.php";
   $is_modified = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";

   // init
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

   $action = Tools::getSecurePOSTStringValue('action', '');
   $filename = Tools::getSecurePOSTStringValue('backup_filename', "codevtt_backup_".date("Ymj").".sql");

   // actions
   if ("uninstall" == $action) {

      if ($isBackup) {
         echo "Backup : <br />";

         if (backupDB($filename) && saveConfigFiles()) {
            echo "Backup successfully done<br />";
         } else {
            echo "Uninstall aborted !";
            exit;
         }
         echo "<br />";
      }
   
      echo "1/5 Remove CodevTT from Mantis menu : ";
      echo "TODO<br />";
      echo "<br />";

      echo "2/5 Remove CodevTT specific projects</br>";
      displayProjectsToRemove();

      echo "3/5 Remove CodevTT customFields : ";
      removeCustomFields();
      echo "done<br />";
      echo "<br />";

      echo "4/5 Remove CodevTT tables from MantisDB : ";
      Tools::execSQLscript("uninstall.sql");
      echo "done<br />";
      echo "<br />";

      echo "5/5 Remove CodevTT config files : ";
      if(deleteConfigFiles()) {
         echo "done";
      } else {
         echo "<br />ERROR: Could not delete files";
      }
      echo "<br />";

   } else {
      // DISPLAY PAGE
      displayForm($originPage, $is_modified, $isBackup, $filename);
   }
} else {
   echo T_("Sorry, you need to be in the admin-team to access this page.");
}

?>

   
</div>
