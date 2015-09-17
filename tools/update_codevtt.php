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




function execQuery($query) {
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query<br/>" . mysql_error() . "</span>";
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

/**
 * update 0.99.18 to 0.99.19 (DB v9 to DB v10)
 */
function update_v9_to_v10() {

   $sqlScriptFilename = '../install/codevtt_update_v9_v10.sql';
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

   $sqlScriptFilename = '../install/codevtt_update_v11_v12.sql';
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

   $sqlScriptFilename = '../install/codevtt_update_v12_v13.sql';
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


   $sqlScriptFilename = '../install/codevtt_update_v13_v14.sql';
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
 */
function update_v14_to_v15() {

   echo "- Update classmap.ser<br>";
   try {
      Tools::createClassMap();
   } catch (Exception $e) {
      echo "<span class='error_font'>Could not create classmap: ".$e->getMessage()."</span><br/>";
      exit;
   }

   $sqlScriptFilename = '../install/codevtt_update_v14_v15.sql';
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

?>
