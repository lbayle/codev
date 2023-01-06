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
   $sql = AdodbWrapper::getInstance();
   $result = $sql->sql_query($query);
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
   $sql = AdodbWrapper::getInstance();

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

   $query = "SELECT id, name FROM {custom_field}";
   $result = $sql->sql_query($query);
   while ($row = $sql->fetchObject($result)) {
      $fieldList["$row->name"] = $row->id;
   }

   $fieldId = $fieldList[$fieldName];
   if (!$fieldId) {
      $query2 = "INSERT INTO {custom_field} " .
         "(name, type ,access_level_r," .
         "                 access_level_rw ,require_report ,require_update ,display_report ,display_update ,require_resolved ,display_resolved ,display_closed ,require_closed ";
      $query2 .= ", possible_values, default_value";

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

      $result2 = $sql->sql_query($query2);
      $fieldId = $sql->getInsertId();

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

   $query = "UPDATE codev_config_table SET value='11' WHERE config_id='database_version';";
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
   $query = "INSERT INTO {category}  (project_id, user_id, name, status) ".
            "VALUES ('$extTasksProjId','0','Leave', '0');";
   $result = execQuery($query);
   $catLeaveId = AdodbWrapper::getInstance()->getInsertId();

   // create otherInternal category
   $query = "INSERT INTO {category}  (project_id, user_id, name, status) ".
            "VALUES ('$extTasksProjId','0','Other activity', '0');";
   $result = execQuery($query);
   $catOtherInternalId = AdodbWrapper::getInstance()->getInsertId();

   // update codev_config_table
   Config::getInstance()->setValue(Config::id_externalTasksCat_leave, $catLeaveId, Config::configType_int);
   Config::getInstance()->setValue(Config::id_externalTasksCat_otherInternal, $catOtherInternalId, Config::configType_int);

   // update existing issues
   $leaveTaskId = Config::getInstance()->getValue(Config::id_externalTask_leave);
   $query = "UPDATE {bug} SET category_id='$catLeaveId' WHERE id='$leaveTaskId';";
   $result = execQuery($query);
   $query = "UPDATE {bug} SET category_id='$catOtherInternalId' ".
           "WHERE project_id='$extTasksProjId' ".
           " AND id <> '$leaveTaskId';";
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
         $query = "DELETE FROM {config} WHERE config_id = 'main_menu_custom_options'";
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

/**
 * update 1.2.0 to 1.2.1 (DB v16 to DB v17)
 *
 * - clasmap.ser (new plugin: UserTeamList)
 * - DB projectTypes
 */
function update_v16_to_v17() {

      $sql = AdodbWrapper::getInstance();


   // update pluginManager (new plugin: UserTeamList)
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

   echo "- Update Jobs / Projects<br>";

   // remove default-jobs assignations for workingProjects (not normal case, prepare for query3)
   $query = "DELETE FROM  codev_project_job_table ".
           "WHERE project_id IN (SELECT project_id FROM codev_team_project_table WHERE type= ".Project::type_workingProject.") ".
           " AND job_id IN (SELECT job.id FROM codev_job_table job WHERE type = ".Job::type_commonJob.");";
   execQuery($query);

   // find deprecated workingProjects
   $query0 = "SELECT mpt.id, mpt.name FROM codev_team_project_table ctpt ".
             "JOIN {project} mpt ON mpt.id = ctpt.project_id ".
             "WHERE ctpt.type = ".Project::type_workingProject.';';
   $result0 = execQuery($query0);
   while($row = $sql->fetchObject($result0)) {
      // add default-jobs to workingProjects (convert to noCommonJobProject)
      $query3 = "INSERT INTO codev_project_job_table(project_id, job_id) ".
                "SELECT ".$row->id.", job.id FROM codev_job_table job ".
                "WHERE type = ".Job::type_commonJob.';';
      echo "&nbsp;&nbsp;&nbsp;Project $row->id ($row->name) updated<br>";
      execQuery($query3);
   }

   // convert workingProject (deprecated)  to noCommonJobProject
   $query4 = "UPDATE codev_team_project_table SET type= ".Project::type_regularProject." WHERE type= ".Project::type_workingProject.';';
   execQuery($query4);

   // remove duplicates
   $query6 = "DELETE t1 FROM codev_project_job_table AS t1, codev_project_job_table AS t2 ".
           "WHERE t1.id > t2.id ".
           " AND t1.project_id = t2.project_id ".
           " AND t1.job_id = t2.job_id;";
   execQuery($query6);

   $query5 = "UPDATE codev_config_table SET value='17' WHERE config_id='database_version';";
   execQuery($query5);

}

/**
 * update 1.2.2 to 1.3.0 (DB v17 to DB v18)
 *
 */
function update_v17_to_v18() {


   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v17_v18.sql';
   if (!file_exists($sqlScriptFilename)) {
      echo "<span class='error_font'>SQL script not found:$sqlScriptFilename</span><br/>";
      exit;
   }

   // CodevTT plugins must be updated (0.7.1 -> 0.7.2)
   echo "- Update Mantis plugin: CodevTT (0.7.2)<br>";
   if (checkMantisPluginDir()) {
      $errStr = installMantisPlugin('CodevTT', true);
      if (NULL !== $errStr) {
         echo "<span class='error_font'>Please update 'CodevTT' mantis-plugin manualy</span><br/>";
         echo "<script type=\"text/javascript\">console.error(\"$errStr\");</script>";
      }
   }

   // write new config file (add: db_table_prefix, db_table_suffix)
   //if (!update_config_file()) {
   //   // ask for manual update
   //   echo "<span class='error_font'>Could not update config.ini</span><br/>";
   //   exit;
   //}

   // execute the SQL script
   echo "- Execute SQL script: $sqlScriptFilename<br>";
   $retCode = Tools::execSQLscript2($sqlScriptFilename);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlScriptFilename</span><br/>";
      exit;
   }
}

/**
 * update 1.3.0 to 1.4.0 (DB v18 to DB v19)
 *
 */
function update_v18_to_v19() {


   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v18_v19.sql';
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

   // Promote new BlogWall plugin by removing users settings on Homepage dashboard
   $query = "DELETE FROM  codev_config_table WHERE config_id LIKE 'dashboard_homepage%'";
   execQuery($query);


}

/**
 * update 1.4.0 to 1.5.0
 *
 */
function update_v19_to_v20() {

   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v19_v20.sql';
   $sql = AdodbWrapper::getInstance();

   // --- Create customField "CodevTT_DailyPrice" in Mantis
   // Note: this cannod be done in the .sql file because we need to know mantis table prefix & postfix...

   $access_manager = 70;
   $default_value = 0;
   $fieldType = 2; // float
   $customFieldName = 'CodevTT_DailyPrice';

   $attributes = array();
   $attributes["access_level_r"] = $access_manager;
   $attributes["access_level_rw"] = $access_manager;
   $attributes["require_report"] = 1;
   $attributes["require_update"] = 1;
   $attributes["require_resolved"] = 0;
   $attributes["require_closed"] = 0;
   $attributes["display_report"] = 1;
   $attributes["display_update"] = 1;
   $attributes["display_resolved"] = 0;
   $attributes["display_closed"] = 0;

   $query2 = "INSERT INTO {custom_field} " .
         "(name, type ,access_level_r," .
         " access_level_rw , require_report ,require_update ,display_report ,display_update, ".
         " require_resolved ,display_resolved ,display_closed ,require_closed, ".
         " default_value)";

   $query2 .= " VALUES (".$sql->db_param(). ", " .$sql->db_param(). ", " . $sql->db_param(). ", " .
                    $sql->db_param(). ", " .$sql->db_param(). ", " . $sql->db_param(). ", " .
                    $sql->db_param(). ", " .$sql->db_param(). ", " . $sql->db_param(). ", " .
                    $sql->db_param(). ", " .$sql->db_param(). ", " . $sql->db_param(). ", ".
                    $sql->db_param(). ")";

   $q_params2[]=$customFieldName;
   $q_params2[]=$fieldType; // float
   $q_params2[]=$attributes["access_level_r"];
   $q_params2[]=$attributes["access_level_rw"];
   $q_params2[]=$attributes["require_report"];
   $q_params2[]=$attributes["require_update"];
   $q_params2[]=$attributes["display_report"];
   $q_params2[]=$attributes["display_update"];
   $q_params2[]=$attributes["require_resolved"];
   $q_params2[]=$attributes["display_resolved"];
   $q_params2[]=$attributes["display_closed"];
   $q_params2[]=$attributes["require_closed"];
//   $q_params2[]=$possible_values;
   $q_params2[]=$default_value;

   try {
      echo "- Create Manris customField: $customFieldName<br>";
      $sql->sql_query($query2, $q_params2);
   } catch (Exception $ex) {
      echo "<span class='error_font'>Could not create Mantis customField: $customFieldName</span><br/>";
      exit;
   }

   // CodevTT plugins must be updated (0.7.2 -> 0.7.3)
   echo "- Update Mantis plugin: CodevTT (0.7.3)<br>";
   if (checkMantisPluginDir()) {
      $errStr = installMantisPlugin('CodevTT', true);
      if (NULL !== $errStr) {
         echo "<span class='error_font'>Please update 'CodevTT' mantis-plugin manualy</span><br/>";
         echo "<script type=\"text/javascript\">console.error(\"$errStr\");</script>";
      }
   }

   // add new plugins, and update domains for some others
   echo "- Activate new CodevTT plugins: 'Administration tools', 'Ongoing tasks', 'Selling Price for the Period'<br>";
   $pm = PluginManager::getInstance();
   $pm->discoverNewPlugins();

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
 * update 1.5.0 to 1.6.0 (DB v20 to DB v21)
 *
 */
function update_v20_to_v21() {

   $sql = AdodbWrapper::getInstance();

   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v20_v21.sql';
   if (!file_exists($sqlScriptFilename)) {
      echo "<span class='error_font'>SQL script not found:$sqlScriptFilename</span><br/>";
      exit;
   }

   // this step is intentionaly manual, we want the admin to be aware about this change !
   if (file_exists(Constants::$log4php_file_old)) {
      echo "<span class='warn_font'>Please move log4php file to: ".Constants::$codevRootDir."/config/log4php.xml</span><br/>";
   }
   // execute the SQL script
   echo "- Execute SQL script: $sqlScriptFilename<br>";
   $retCode = Tools::execSQLscript2($sqlScriptFilename);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlScriptFilename</span><br/>";
      exit;
   }

   // replace global project_job asso with a team specific asso
   $query0 = "SELECT project_id, job_id FROM codev_project_job_table WHERE team_id = 0";
   $result0 = execQuery($query0);
   while($row = $sql->fetchObject($result0)) {
      // for each team having this project
      echo "--- Found association prj=$row->project_id, job=$row->job_id, team=0<br/>";
      $query1 = "SELECT team_id FROM codev_team_project_table where project_id = $row->project_id";
      $result1 = execQuery($query1);
      while($row1 = $sql->fetchObject($result1)) {
         echo "   Create association prj=$row->project_id, job=$row->job_id, team=$row1->team_id<br/>";
         Jobs::addJobProjectAssociation($row->project_id, $row->job_id, $row1->team_id);
      }
      echo "   Delete association prj=$row->project_id, job=$row->job_id, team=0<br/>";
      Jobs::removeJobProjectAssociation($row->project_id, $row->job_id, 0);
   }

}

/**
 * update 1.6.0 to 1.7.0 (DB v21 to DB v22)
 *
 */
function update_v21_to_v22() {

   $sql = AdodbWrapper::getInstance();

   $sqlScriptFilename = Constants::$codevRootDir.'/install/codevtt_update_v21_v22.sql';
   if (!file_exists($sqlScriptFilename)) {
      echo "<span class='error_font'>SQL script not found:$sqlScriptFilename</span><br/>";
      exit;
   }

   // this step is intentionaly manual, we want the admin to be aware about this change !
   if (file_exists(Constants::$config_file_old)) {
      echo "<span class='warn_font'>Please move config file to: ".Constants::$codevRootDir."/config/config.ini</span><br/>";
   }

   // execute the SQL script
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
      $sql = AdodbWrapper::getInstance();

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
      $query = "INSERT INTO {plugin} (basename, enabled, protected, priority)".
              " SELECT * FROM (SELECT '$pluginName', '1', '0', '3') AS tmp".
              " WHERE NOT EXISTS (".
              " SELECT basename FROM {plugin} WHERE basename = '$pluginName') LIMIT 1;";
      $result = $sql->sql_query($query);
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

if (Tools::isConnectedUser()){

   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   $sql = AdodbWrapper::getInstance();

   if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {

      // check DB version
     $query = "SELECT * from codev_config_table WHERE config_id = 'database_version' ";
     $result = execQuery($query);
     $row = $sql->fetchObject($result);
     $currentDatabaseVersion=$row->value;

     echo "Current  database_version = $currentDatabaseVersion<br>";
     echo "Expected database_version = ".Config::databaseVersion."<br><br>";

     if ($currentDatabaseVersion < Config::databaseVersion) {
        echo 'An update to CodevTT '.Config::codevVersion.' needs to be done.<br><br>';
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
            echo "<br><br>UPDATE DONE.<br>";
         } catch (Exception $e) {
            echo "ERROR: ".$e->getMessage()."<br>";
            exit;
         }
      }
   } else {
      echo "Sorry, you need to be in the admin-team to access this page.<br>";
   }
} else {
   echo "Sorry, you need to be in the admin-team to access this page.<br>";
}

