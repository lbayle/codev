<?php

include_once('../include/session.inc.php');

/*
  This file is part of CodevTT.

  CodevTT is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  CodevTT is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
 */

require('../path.inc.php');
require_once('../i18n/i18n.inc.php');

echo '<style type="text/css">
.help_font {
  font-style: italic;
  color: gray;
}
.error_font {
  font-style: italic;
  color: red;
}
.warn_font {
  font-style: italic;
  color: orange;
}
.success_font {
  font-style: italic;
  color: blue;
}
</style>';

function execQuery($query) {
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query<br/>" . SqlWrapper::getInstance()->sql_error() . "</span>";
      exit;
   }
   return $result;
}


/**
 * create a customField in Mantis (if not exist) & update codev_config_table
 *
 * ex: createCustomField("ExtRef", 0, "customField_ExtId");
 *
 * @param string $fieldName Mantis field name
 * @param int $fieldType Mantis field type
 * @param string $configId  codev_config_table.config_id
 * @param type $attributes
 * @param type $default_value
 * @param type $possible_values
 */
function createCustomField($fieldName, $fieldType, $configId, $attributes = NULL,
                           $default_value = '', $possible_values = '') {
   global $fieldList;

   if (NULL == $attributes) {
      $attributes = array();

      $attributes["access_level_r"] = 10;
      $attributes["access_level_rw"] = 25;
      $attributes["require_report"] = 1;
      $attributes["require_update"] = 1;
      $attributes["require_resolved"] = 0;
      $attributes["require_closed"] = 0;
      $attributes["display_report"] = 1;
      $attributes["display_update"] = 1;
      $attributes["display_resolved"] = 0;
      $attributes["display_closed"] = 0;

      echo "<span class='warn_font'>WARN: using default attributes for CustomField $fieldName</span><br/>";
   }

   $query = "SELECT id, name FROM `mantis_custom_field_table`";
   $result = execQuery($query);
   while ($row = mysql_fetch_object($result)) {
      $fieldList["$row->name"] = $row->id;
   }

   $fieldId = $fieldList[$fieldName];
   if (!$fieldId) {
      $query2 = "INSERT INTO `mantis_custom_field_table` " .
         "(`name`, `type` ,`access_level_r`," .
         "                 `access_level_rw` ,`require_report` ,`require_update` ,`display_report` ,`display_update` ,`require_resolved` ,`display_resolved` ,`display_closed` ,`require_closed` ";
      $query2 .= ", `possible_values`, `default_value`";

      $query2 .= ") VALUES ('$fieldName', '$fieldType', '" . $attributes["access_level_r"] . "', '" .
         $attributes["access_level_rw"] . "', '" .
         $attributes["require_report"] . "', '" .
         $attributes["require_update"] . "', '" .
         $attributes["display_report"] . "', '" .
         $attributes["display_update"] . "', '" .
         $attributes["require_resolved"] . "', '" .
         $attributes["display_resolved"] . "', '" .
         $attributes["display_closed"] . "', '" .
         $attributes["require_closed"] . "'";

      $query2 .= ", '$possible_values', '$default_value'";
      $query2 .= ");";

      #echo "DEBUG INSERT $fieldName --- query $query2 <br/>";

      $result2 = execQuery($query2);
      $fieldId = mysql_insert_id();

      #echo "custom field '$configId' created.<br/>";
   } else {
      echo "<span class='success_font'>INFO: custom field '$configId' already exists.</span><br/>";
   }

   // add to codev_config_table
   Config::getInstance()->setValue($configId, $fieldId, Config::configType_int);
}

// ======================================================
// update actions
// ======================================================

/**
 * update 0.99.18 to 0.99.19 (DB v9 to DB v10)
 */
function update_v9_to_v10() {

   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v9_v10.sql';
   if (!file_exists($sqlScriptFilename)) {
      echo "ERROR: SQL script not found:$sqlScriptFilename<br>";
      exit;
   }

   // the CodevTT_Type field must be created before the DB update
   echo "- Create CodevTT_Type customField<br>";
   $mType_list = 6;
   $access_viewer = 10;
   $access_reporter = 25;
   $attributes = array();
   $attributes["access_level_r"] = $access_viewer;
   $attributes["access_level_rw"] = $access_reporter;
   $attributes["require_report"] = 1;
   $attributes["display_report"] = 1;
   $attributes["require_update"] = 0;
   $attributes["display_update"] = 1;
   $attributes["require_resolved"] = 0;
   $attributes["display_resolved"] = 0;
   $attributes["require_closed"] = 0;
   $attributes["display_closed"] = 0;
   $defaultValue = NULL;
   $possible_values = 'Bug|Task';
   createCustomField("CodevTT_Type", $mType_list, "customField_type", $attributes, $defaultValue, $possible_values);

   // execute the SQL script
   //
   echo "- Execute SQL script:$sqlScriptFilename<br>";
   $retCode = Tools::execSQLscript2($sqlScriptFilename);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlScriptFilename</span><br/>";
      exit;
   }

   echo "<br>SUCCESS: Update 0.99.18 to 0.99.19 (DB v9 to DB v10)<br>";
   return TRUE;

}


/**
 * update 0.99.19 to 0.99.20 (DB v10 to DB v11)
 *
 * there is no .sql script to run, but db version is increased to 11 (add default issue_tooltip_fields)
 *
 */
function update_v10_to_v11() {

   // add default issue tooltips
   $customField_type = Config::getInstance()->getValue(Config::id_customField_type);
   $backlogField = Config::getInstance()->getValue(Config::id_customField_backlog);
   $fieldList = array('project_id', 'category_id', 'custom_'.$customField_type,
       'codevtt_elapsed', 'custom_'.$backlogField, 'codevtt_drift');
   $serialized = serialize($fieldList);
   Config::setValue('issue_tooltip_fields', $serialized, Config::configType_string, 'fields to be displayed in issue tooltip');

   $query = "UPDATE `codev_config_table` SET `value`='11' WHERE `config_id`='database_version';";
   $result = execQuery($query);

}

/**
 * update 0.99.21 to 0.99.22 (DB v11 to DB v12)
 *
 */
function update_v11_to_v12() {

   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v11_v12.sql';
   if (!file_exists($sqlScriptFilename)) {
      echo "ERROR: SQL script not found:$sqlScriptFilename<br>";
      exit;
   }

   // execute the SQL script
   echo "- Execute SQL script:$sqlScriptFilename<br>";
   $retCode = Tools::execSQLscript2($sqlScriptFilename);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlScriptFilename</span><br/>";
      exit;
   }

   // --- create new categories for ExternalTasksProject
   $extTasksProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
   // create leave category
   $query = "INSERT INTO `mantis_category_table`  (`project_id`, `user_id`, `name`, `status`) ".
            "VALUES ('$extTasksProjId','0','Leave', '0');";
   $result = execQuery($query);
   $catLeaveId = SqlWrapper::getInstance()->sql_insert_id();

   // create otherInternal category
   $query = "INSERT INTO `mantis_category_table`  (`project_id`, `user_id`, `name`, `status`) ".
            "VALUES ('$extTasksProjId','0','Other activity', '0');";
   $result = execQuery($query);
   $catOtherInternalId = SqlWrapper::getInstance()->sql_insert_id();

   // update codev_config_table
   Config::getInstance()->setValue(Config::id_externalTasksCat_leave, $catLeaveId, Config::configType_int);
   Config::getInstance()->setValue(Config::id_externalTasksCat_otherInternal, $catOtherInternalId, Config::configType_int);

   // update existing issues
   $leaveTaskId = Config::getInstance()->getValue(Config::id_externalTask_leave);
   $query = "UPDATE `mantis_bug_table` SET `category_id`='$catLeaveId' WHERE `id`='$leaveTaskId';";
   $result = execQuery($query);
   $query = "UPDATE `mantis_bug_table` SET `category_id`='$catOtherInternalId' ".
           "WHERE `project_id`='$extTasksProjId' ".
           "AND `id` <> '$leaveTaskId';";
   $result = execQuery($query);

   #echo "<br>SUCCESS: Update 0.99.21 to 0.99.22 (DB v11 to DB v12)<br>";
   return TRUE;
}

/**
 * update 0.99.24 to 0.99.25 (DB v12 to DB v13)
 * update mantis plugin 0.3 -> 0.4
 *
 */
function update_v12_to_v13() {

   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v12_v13.sql';
   if (!file_exists($sqlScriptFilename)) {
      echo "ERROR: SQL script not found:$sqlScriptFilename<br>";
      exit;
   }
   // execute the SQL script
   echo "- Execute SQL script:$sqlScriptFilename<br>";
   $retCode = Tools::execSQLscript2($sqlScriptFilename);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlScriptFilename</span><br/>";
      exit;
   }

   // update mantis plugin 0.3 -> 0.4
   try {
      $mantisPluginDir = Constants::$mantisPath . DIRECTORY_SEPARATOR . 'plugins';
      $srcDir = Constants::$codevRootDir . DIRECTORY_SEPARATOR . 'mantis_plugin' . DIRECTORY_SEPARATOR . 'CodevTT';
      $destDir = $mantisPluginDir . DIRECTORY_SEPARATOR . 'CodevTT';

      if (!is_writable($mantisPluginDir)) {
         echo "<br><span class='warn_font'>WARN: <b>'" . $mantisPluginDir . "'</b> directory is <b>NOT writable</b>: Please update the mantis plugin manualy.</span><br>";
         return false;
      }
      // remove previous installed CodevTT plugin
       if (is_writable($destDir)) {
          Tools::deleteDir($destDir);
       } else {
         echo "<br><span class='warn_font'>WARN: <b>'" . $destDir . "'</b> directory is <b>NOT writable</b>: Please update the mantis plugin manualy.</span><br>";
         return false;
      }

       // copy CodevTT plugin
       if (is_dir($srcDir)) {
          $result = Tools::recurse_copy($srcDir, $destDir);
         if (!$result) {
            echo "<br><span class='warn_font'>mantis plugin installation failed: CodevTT plugin must be updated manualy.</span><br>";
            return false;
         }
       } else {
          echo "<br><span class='warn_font'>plugin directory '" . $srcDir . "' NOT found: Please update the mantis plugin manualy.</span><br>";
          return false;
       }

   } catch (Exception $e) {
      echo "<span class='warn_font'>mantis plugin installation failed: " . $e->getMessage() . "</span><br>";
      echo "<span class='warn_font'>mantis plugin must be installed manualy.</span><br>";
      return false;
   }

   #echo "<br>SUCCESS: Update 0.99.24 to 0.99.25 (DB v12 to DB v13)<br>";
   return TRUE;
}

/**
 * update 1.0.3 to 1.0.4 (DB v13 to DB v14)
 *
 */
function update_v13_to_v14() {


   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v13_v14.sql';
   if (!file_exists($sqlScriptFilename)) {
      echo "<span class='error_font'>SQL script not found:$sqlScriptFilename</span><br/>";
      exit;
   }
   // execute the SQL script
   echo "- Execute SQL script: $sqlScriptFilename<br>";
   $retCode = Tools::execSQLscript2($sqlScriptFilename);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlScriptFilename</span><br/>";
      exit;
   }
}

/**
 * update 1.0.x to 1.1.0 (DB v14 to DB v15)
 *
 * - clasmap.ser
 * - config.ini
 * - remove from mantis menu
 * - mantis-plugins if mantis v1.3
 * - DB
 */
function update_v14_to_v15() {

   echo "- Update classmap.ser<br>";
   try {
      Tools::createClassMap();
   } catch (Exception $e) {
      echo "<span class='error_font'>Could not create classmap: ".$e->getMessage()."</span><br/>";
      exit;
   }

   echo "- Add [mantis] 'status_enum_workflow' to config.ini<br>";
   // reload mantis config files
   $path_config_defaults_inc = Constants::$mantisPath.DIRECTORY_SEPARATOR."config_defaults_inc.php";
   $path_core_constant_inc = Constants::$mantisPath.DIRECTORY_SEPARATOR."core".DIRECTORY_SEPARATOR."constant_inc.php";

   $path_mantis_config = Constants::$mantisPath;
   if (is_dir(Constants::$mantisPath.DIRECTORY_SEPARATOR.'config')) {
      $path_mantis_config .= DIRECTORY_SEPARATOR.'config'; // mantis v1.3 or higher
   }
   $path_mantis_config_inc=$path_mantis_config.DIRECTORY_SEPARATOR.'config_inc.php';
   $path_custom_constants = $path_mantis_config.DIRECTORY_SEPARATOR.'custom_constants_inc.php';

   global $g_status_enum_workflow;
   include_once($path_core_constant_inc);
   include_once($path_custom_constants);
   include_once($path_config_defaults_inc);
   include_once($path_mantis_config_inc);

   // set status_enum_workflow
   Constants::$status_enum_workflow = $g_status_enum_workflow;
   if (!is_array(Constants::$status_enum_workflow)) {
      echo "<span class='error_font'>Could not retrieve status_enum_workflow from Mantis config files</span><br/>";
      exit;
   }

   // write new config file
   if (!update_config_file()) {
      // ask for manual update
      echo "<span class='error_font'>Could not update config.ini</span><br/>";
      exit;
   }

   // if Mantis 1.3, plugins must be updated
   if (!Tools::isMantisV1_2()) {
         echo "- Remove 'CodevTT' from Mantis main menu (CodevTT v1.0.x is incompatible with Mantis v1.3.x)<br>";
         $query = "DELETE FROM `mantis_config_table` WHERE config_id = 'main_menu_custom_options'";
         $result = execQuery($query);

         echo "- Install Mantis plugin: CodevTT (for mantis v1.3.x)<br>";
      if (checkMantisPluginDir()) {
         $errStr = installMantisPlugin('CodevTT', true);
         if (NULL !== $errStr) {
            echo "<span class='error_font'>Please update 'CodevTT' mantis-plugin manualy</span><br/>";
            echo "<script type=\"text/javascript\">console.error(\"$errStr\");</script>";
         }
         echo "- Install Mantis plugin: FilterBugList (for mantis v1.3.x)<br>";
         $errStr = installMantisPlugin('FilterBugList', true);
         if (NULL !== $errStr) {
            echo "<span class='error_font'>Please update 'FilterBugList' mantis-plugin manualy</span><br/>";
            echo "<script type=\"text/javascript\">console.error(\"$errStr\");</script>";
         }
      }
   }

   // execute the SQL script
   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v14_v15.sql';
   if (!file_exists($sqlScriptFilename)) {
      echo "<span class='error_font'>SQL script not found:$sqlScriptFilename</span><br/>";
      exit;
   }
   echo "- Execute SQL script: $sqlScriptFilename<br>";
   $retCode = Tools::execSQLscript2($sqlScriptFilename);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlScriptFilename</span><br/>";
      exit;
   }

}

/**
 * update 1.1.0 to 1.2.0 (DB v15 to DB v16)
 *
 * - clasmap.ser
 * - mantis-plugins 0.7.0
 * - DB
 */
function update_v15_to_v16() {

   try {
      echo "- Update classmap.ser<br>";
      Tools::createClassMap();

      echo "- Discover new plugins (Must be enabled manualy from the Admin/PluginManager page)<br>";
      $pm = PluginManager::getInstance();
      $pm->discoverNewPlugins();
   } catch (Exception $e) {
      echo "<span class='error_font'>Could not create classmap: ".$e->getMessage()."</span><br/>";
      exit;
   }

   // CodevTT plugins must be updated (0.6.3 -> 0.7.0)
   echo "- Update Mantis plugin: CodevTT (0.7.0)<br>";
   if (checkMantisPluginDir()) {
      $errStr = installMantisPlugin('CodevTT', true);
      if (NULL !== $errStr) {
         echo "<span class='error_font'>Please update 'CodevTT' mantis-plugin manualy</span><br/>";
         echo "<script type=\"text/javascript\">console.error(\"$errStr\");</script>";
      }
      echo "- Install Mantis plugin: FilterBugList<br>";
      $errStr = installMantisPlugin('FilterBugList', true);
      if (NULL !== $errStr) {
         echo "<span class='error_font'>Please update 'FilterBugList' mantis-plugin manualy</span><br/>";
         echo "<script type=\"text/javascript\">console.error(\"$errStr\");</script>";
      }
   }

   // execute the SQL script
   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v15_v16.sql';
   if (!file_exists($sqlScriptFilename)) {
      echo "<span class='error_font'>SQL script not found:$sqlScriptFilename</span><br/>";
      exit;
   }
   echo "- Execute SQL script: $sqlScriptFilename<br>";
   $retCode = Tools::execSQLscript2($sqlScriptFilename);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlScriptFilename</span><br/>";
      exit;
   }
}

// ======================================================
// toolbox
// ======================================================


/**
 * Some new variables may have been added, this rewrites the config.ini
 * file with new default values.
 *
 */
function update_config_file() {

   // check if config.ini is writable
   if (!is_writable(Constants::$config_file)) {
      echo "<span class='warn_font'>File not writable : ".Constants::$config_file.'</span><br/>';
      return false;
   }

   // backup config.ini to config.ini.old_YYYYMMDDHHmmss
   if (is_writable(Constants::$codevRootDir)) {
      if (FALSE == copy (Constants::$config_file,
                         Constants::$config_file.'.old_'.date('YmdHis'))) {
         echo "<span class='warn_font'>Could not backup config.ini file</span><br/>";
      }
   } else {
      echo "<span class='warn_font'>Could not backup config.ini file (directory not writable)</span><br/>";
   }

   // write new config.ini file
   return Constants::writeConfigFile();
}

function checkMantisPluginDir() {
   $mantisPluginDir = Constants::$mantisPath . DIRECTORY_SEPARATOR . 'plugins';

   if (!is_writable($mantisPluginDir)) {
      echo '<br>';
      echo "<span class='warn_font'>WARN: <b>'" . $mantisPluginDir . "'</b> directory is <b>NOT writable</b>: Please give write access to user '<b>".exec('whoami')."</b>' if you want the Mantis plugin to be installed.</span><br>";
      echo '<br>';
      return false;
   }
   return true;
}

/**
 * copy plugin in mantis plugins directory
 * info: same functyion exists in install_step3.php
 * @return NULL or error string
 */
function installMantisPlugin($pluginName, $isReplace=true) {
   try {

      $mantisPluginDir = Constants::$mantisPath . DIRECTORY_SEPARATOR . 'plugins';

      // load core/constant_inc.php and check for 'MANTIS_VERSION'
      $mantisVersion = Tools::getMantisVersion(Constants::$mantisPath);
      if (NULL != $mantisVersion) {
         if (version_compare($mantisVersion, '1.2', 'ge') && version_compare($mantisVersion, '1.3', 'lt')) {
            $mantis_plugin_version = 'mantis_1_2';

         } else if (version_compare($mantisVersion, '1.3', 'ge') && version_compare($mantisVersion, '2.0', 'lt')) {
            $mantis_plugin_version = 'mantis_1_3';

         } else if (version_compare($mantisVersion, '2.0', 'ge')) {
            $mantis_plugin_version = 'mantis_2_0';
         } else {
            return "ERROR unsupported mantis version : $mantisVersion";
         }
      } else {
         return "ERROR could not guess mantis version !";
      }
      $srcDir = Constants::$codevRootDir . DIRECTORY_SEPARATOR . 'mantis_plugin' . DIRECTORY_SEPARATOR . $mantis_plugin_version . DIRECTORY_SEPARATOR . $pluginName;
      $destDir = $mantisPluginDir . DIRECTORY_SEPARATOR . $pluginName;

      if (!is_writable($mantisPluginDir)) {
         return "ERROR Path to mantis plugins directory '" . $mantisPluginDir . "' is NOT writable: $pluginName plugin must be installed manualy.";
      }
      if (!is_dir($srcDir)) {
         return "ERROR mantis plugin directory '" . $srcDir . "' NOT found !";
      }

      // do not replace if already installed
      if (!$isReplace && is_dir($destDir)) {
         echo "<script type=\"text/javascript\">console.info(\"INFO Mantis $pluginName plugin is already installed\");</script>";
         return NULL;
      }

      // remove previous installed plugin
      if (is_writable($destDir)) {
         Tools::deleteDir($destDir);
      }

      // copy plugin
      if (is_dir($srcDir)) {
         $result = Tools::recurse_copy($srcDir, $destDir);
      } else {
         return "ERROR: plugin directory '" . $srcDir . "' NOT found: $pluginName plugin must be installed manualy";
      }

      if (!$result) {
         return "ERROR: mantis plugin installation failed: $pluginName plugin must be installed manualy";
      }

      // activate plugin
      $query = "INSERT INTO mantis_plugin_table (basename, enabled, protected, priority)".
              " SELECT * FROM (SELECT '$pluginName', '1', '0', '3') AS tmp".
              " WHERE NOT EXISTS (".
              " SELECT basename FROM mantis_plugin_table WHERE basename = '$pluginName') LIMIT 1;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return "WARNING: mantis $pluginName plugin must be activated manualy";
      }

   } catch (Exception $e) {
      echo "<script type=\"text/javascript\">console.error(\"ERROR mantis plugin installation failed: " . $e->getMessage()."\");</script>";
      return "ERROR: mantis $pluginName plugin installation failed: " . $e->getMessage();
   }
   return NULL;
}

// =========== MAIN ==========
$logger = Logger::getLogger("versionUpdater");

/*
 * 1) check administration rights
 * 2) check DB version
 * 3) execute PHP & DB actions
 *
 *
 */

 // check DB version
$query = "SELECT * from `codev_config_table` WHERE `config_id` = 'database_version' ";
$result = execQuery($query);
$row = SqlWrapper::getInstance()->sql_fetch_object($result);
$currentDatabaseVersion=$row->value;

echo "Current  database_version = $currentDatabaseVersion<br>";
echo "Expected database_version = ".Config::databaseVersion."<br><br>";

if ($currentDatabaseVersion < Config::databaseVersion) {
   echo 'An update to version '.Config::codevVersion.' needs to be done.<br><br>';
   flush();

   try {
      for ($i = $currentDatabaseVersion; $i < Config::databaseVersion; $i++) {
         $callback = "update_v".($i)."_to_v".($i+1);
         echo "=== $callback<br>";

         $function =  new ReflectionFunction($callback);
         $function->invoke();
         echo "<br>";
         flush();
      }
   } catch (Exception $e) {
      echo "ERROR: ".$e->getMessage()."<br>";
      exit;
   }
   echo "<br><br>UPDATE DONE.<br>";

}


